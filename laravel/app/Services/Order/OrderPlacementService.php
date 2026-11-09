<?php

namespace App\Services\Order;

use App\Exceptions\InsufficientStockException;
use App\Jobs\CheckOrderFraud;
use App\Jobs\SendOrderNotificationJob;
use App\Models\FraudSetting;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingCharge;
use App\Models\User;
use App\Services\BdCourierFraudService;
use App\Services\BulkSmsBdService;
use App\Services\CheckoutSecurityService;
use App\Services\FacebookCapiService;
use App\Services\OrderRestrictionService;
use App\Services\StoreEmailService;
use App\Services\TrackingService;
use App\Support\BackgroundQueueRunner;
use App\Support\Cart;
use App\Support\ClientIp;
use App\Support\IncompleteOrderTracker;
use App\Support\TrafficAttribution;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderPlacementService
{
    public function __construct(
        protected BulkSmsBdService $sms,
        protected BdCourierFraudService $fraudService,
        protected FacebookCapiService $facebookCapi,
        protected ?TrackingService $trackingService = null,
        protected ?CheckoutSecurityService $securityService = null,
    ) {
        $this->trackingService = $trackingService ?? app(TrackingService::class);
        $this->securityService = $securityService ?? app(CheckoutSecurityService::class);
    }

    /**
     * Atomically place an order with pessimistic locking and price integrity verification.
     *
     * @param array{
     *     customer_name: string,
     *     customer_phone: string,
     *     customer_address: string,
     *     shipping_charge_id?: int|null,
     *     payment_method: string,
     *     notes?: string|null,
     *     landing_page_id?: int|null,
     *     user_id?: int|null,
     * } $data
     *
     * @throws InsufficientStockException|ValidationException
     */
    public function placeOrder(
        Request $request,
        Cart $cart,
        IncompleteOrderTracker $tracker,
        array $data,
    ): Order {
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            throw new InsufficientStockException('Your shopping bag is empty.');
        }

        // Sanitize customer data
        $data = $this->securityService->sanitizeCustomerData($data);

        /** @var OrderRestrictionService $orderRestrictions */
        $orderRestrictions = app(OrderRestrictionService::class);
        $restrictionError = $orderRestrictions->validateCheckout(
            $request,
            $lines,
            [
                'phone' => $data['customer_phone'] ?? '',
                'name' => $data['customer_name'] ?? '',
                'address' => $data['customer_address'] ?? '',
            ],
            Auth::id(),
        );

        if ($restrictionError !== null) {
            throw new ValidationException(
                validator: validator([], []),
                response: redirect()->route('checkout.create')->withInput()->with('error', $restrictionError),
            );
        }

        $phone = $data['customer_phone'] ?? '';
        $lockKey = 'order_placement_'.($phone !== '' ? $phone : md5((string) $request->ip()));
        $atomicLock = Cache::lock($lockKey, 15);

        return $atomicLock->block(10, function () use ($request, $data, $lines, $cart, $tracker): Order {
            return DB::transaction(function () use ($request, $data, $lines, $cart, $tracker): Order {
                // 1. Deterministic row locking: Sort product and variant IDs to prevent database deadlocks
                $productIds = collect($lines)->pluck('product.id')->unique()->sort()->values()->all();
                $variantIds = collect($lines)->pluck('variant.id')->filter()->unique()->sort()->values()->all();

                /** @var Collection<int, Product> $lockedProducts */
                $lockedProducts = Product::query()->whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');
                /** @var Collection<int, ProductVariant> $lockedVariants */
                $lockedVariants = ! empty($variantIds)
                    ? ProductVariant::query()->whereIn('id', $variantIds)->lockForUpdate()->get()->keyBy('id')
                    : collect();

                // 2. Verify stock availability on locked records
                foreach ($lines as $line) {
                    /** @var Product $product */
                    $product = $line['product'];
                    /** @var ProductVariant|null $variant */
                    $variant = $line['variant'] ?? null;
                    $qty = (int) $line['qty'];

                    if ($variant) {
                        $lockedVariant = $lockedVariants->get($variant->id);
                        $isUnlimited = (bool) ($lockedVariant?->is_unlimited_stock ?? $variant->is_unlimited_stock ?? false);
                        if (! $isUnlimited && (! $lockedVariant || (int) $lockedVariant->stock < $qty)) {
                            $itemName = $product->name.' ('.($variant->label() ?? 'Variant').')';
                            throw new InsufficientStockException("{$itemName} does not have enough stock available.");
                        }
                    } else {
                        $lockedProduct = $lockedProducts->get($product->id);
                        $isUnlimited = (bool) ($lockedProduct?->is_unlimited_stock ?? $product->is_unlimited_stock ?? false);
                        if (! $isUnlimited && (! $lockedProduct || (int) $lockedProduct->stock < $qty)) {
                            throw new InsufficientStockException("{$product->name} does not have enough stock available.");
                        }
                    }
                }

                // 3. Strict server-side item price calculation (Anti-Price Tampering)
                $landingPage = ! empty($data['landing_page_id'])
                    ? LandingPage::query()->with('landingPageProducts')->find((int) $data['landing_page_id'])
                    : null;

                $calculatedSubtotal = '0.00';
                $resolvedLineItems = [];

                foreach ($lines as $line) {
                    /** @var Product $product */
                    $product = $line['product'];
                    /** @var ProductVariant|null $variant */
                    $variant = $line['variant'] ?? null;
                    $qty = (int) $line['qty'];

                    // Strict server-side price resolution from locked records
                    $lockedProd = $lockedProducts->get($product->id);
                    $lockedVar = $variant ? $lockedVariants->get($variant->id) : null;

                    if ($lockedVar) {
                        $unitPrice = (float) $lockedVar->price > 0 ? (string) $lockedVar->price : (string) ($lockedProd?->price ?? $product->price);
                    } else {
                        $unitPrice = (string) ($lockedProd?->price ?? $product->price);
                    }

                    if ($landingPage) {
                        $lpp = $landingPage->landingPageProducts->firstWhere('product_id', $product->id);
                        if ($lpp && (float) $lpp->custom_price > 0 && (! $lockedVar || (float) $lockedVar->price <= 0)) {
                            $unitPrice = (string) $lpp->custom_price;
                        } elseif ($landingPage->custom_price && (! $lockedVar || (float) $lockedVar->price <= 0)) {
                            $unitPrice = (string) $landingPage->custom_price;
                        }
                    }

                    $lineTotal = bcmul($unitPrice, (string) $qty, 2);
                    $calculatedSubtotal = bcadd($calculatedSubtotal, $lineTotal, 2);

                    $resolvedLineItems[] = [
                        'product' => $lockedProd ?? $product,
                        'variant' => $lockedVar ?? $variant,
                        'unit_price' => $unitPrice,
                        'qty' => $qty,
                        'variation_name' => $line['variation_name'] ?? ($variant ? $variant->label() : null),
                        'attributes' => $line['attributes'] ?? null,
                        'color' => $line['color'] ?? null,
                        'size' => $line['size'] ?? null,
                    ];
                }

                $shippingAmount = $this->resolveShippingAmount($cart, $data['shipping_charge_id'] ?? null);
                $discount = '0.00';
                $total = bcsub(bcadd($calculatedSubtotal, $shippingAmount, 2), $discount, 2);
                $paymentMethod = $data['payment_method'];

                // 4. Resolve pending order status
                $pendingStatus = OrderStatus::query()->where('slug', 'pending')->first();
                $attribution = app(TrafficAttribution::class)->attributesForPersistence();
                $clientIp = ClientIp::resolve($request);
                $attribution['user_agent'] = $request->userAgent() ?: ($attribution['user_agent'] ?? null);
                $attributionJson = is_array($attribution['attribution_json'] ?? null) ? $attribution['attribution_json'] : [];
                $attributionJson['visitor'] = ClientIp::locationSnapshot($request, $clientIp);
                $attribution['attribution_json'] = $attributionJson;

                // 5. Create Order
                $candidateUserId = $data['user_id'] ?? (Auth::guard('web')->user()?->id);
                $userId = ($candidateUserId !== null && User::query()->whereKey($candidateUserId)->exists())
                    ? (int) $candidateUserId
                    : null;

                $order = Order::query()->create([
                    'landing_page_id' => $data['landing_page_id'] ?? null,
                    'invoice_no' => $this->generateInvoiceNumber(),
                    'user_id' => $userId,
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'customer_address' => $data['customer_address'],
                    'subtotal' => $calculatedSubtotal,
                    'shipping_charge' => $shippingAmount,
                    'discount' => $discount,
                    'total' => $total,
                    'status' => 'pending',
                    'order_status_id' => $pendingStatus?->id,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                    'custom_fields' => $data['custom_fields'] ?? null,
                    'device_id' => ($did = $request->cookie('foodness_did') ?: $request->header('X-Device-Id') ?: $request->input('device_id')) ? substr((string) $did, 0, 64) : null,
                    'source' => ! empty($data['landing_page_id']) ? 'landing_page' : 'direct',
                    ...$attribution,
                    'ip_address' => $clientIp,
                ]);

                // 6. Create OrderItems and Decrement Stock on locked records
                foreach ($resolvedLineItems as $resolved) {
                    /** @var Product $product */
                    $product = $resolved['product'];
                    /** @var ProductVariant|null $variant */
                    $variant = $resolved['variant'];
                    $qty = $resolved['qty'];

                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'variant_id' => $variant?->id,
                        'variation_name' => $resolved['variation_name'],
                        'attributes' => $resolved['attributes'],
                        'color' => $resolved['color'],
                        'size' => $resolved['size'],
                        'source' => 'storefront',
                        'price' => $resolved['unit_price'],
                        'qty' => $qty,
                    ]);

                    $isProductUnlimited = (bool) $product->is_unlimited_stock;
                    $isVariantUnlimited = $variant ? (bool) $variant->is_unlimited_stock : false;

                    if (! $isProductUnlimited) {
                        $product->decrement('stock', $qty);
                    }
                    if ($variant && ! $isVariantUnlimited) {
                        $variant->decrement('stock', $qty);
                    }
                }

                $cart->clear();
                $tracker->clearForSession();

                // 7. Track guest order in session
                $guestOrderIds = collect(session()->get('guest_order_ids', []))
                    ->push($order->id)
                    ->unique()
                    ->values()
                    ->all();
                session()->put('guest_order_ids', $guestOrderIds);

                // 8. Safe Post-Order Processing
                $this->handlePostOrderActions($order, $request);

                return $order;
            });
        });
    }

    /**
     * Resolve the shipping cost based on cart conditions and selected shipping option.
     */
    public function resolveShippingAmount(Cart $cart, ?int $shippingChargeId): string
    {
        $customDeliveryCharge = $cart->customDeliveryCharge();

        if ($shippingChargeId) {
            $areaCustom = $cart->deliveryChargeForArea($shippingChargeId);
            if ($areaCustom !== null) {
                return $areaCustom;
            }

            $shipping = ShippingCharge::query()
                ->active()
                ->findOrFail($shippingChargeId);

            return (string) $shipping->amount;
        }

        if ($customDeliveryCharge !== null) {
            return $customDeliveryCharge;
        }

        $shipping = ShippingCharge::query()
            ->active()
            ->orderBy('amount')
            ->firstOrFail();

        return (string) $shipping->amount;
    }

    /**
     * Execute post-order side effects safely so failure of any 3rd-party API never breaks the user flow.
     */
    public function handlePostOrderActions(Order $order, Request $request): void
    {
        // 1. Courier Fraud Verification
        if (FraudSetting::current()->isConfigured()) {
            if (config('queue.default') !== 'sync') {
                CheckOrderFraud::dispatch($order);
            } else {
                try {
                    $this->fraudService->applyToOrder($order, softFail: true);
                } catch (Throwable $e) {
                    Log::warning('Fraud check failed softly during order placement.', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // 2. SMS & Email Notifications (Non-blocking: queued when queue is active, or processed safely)
        if (config('queue.default') !== 'sync') {
            SendOrderNotificationJob::dispatch($order);
        } else {
            // SMS Notification
            try {
                $this->sms->notifyOrderPlaced($order);
            } catch (Throwable $e) {
                Log::warning('SMS notification failed softly during order placement.', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Email Notification
            try {
                app(StoreEmailService::class)->notifyOrderPlaced($order);
            } catch (Throwable $e) {
                Log::warning('Email notification failed softly during order placement.', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 4. Hybrid Server-Side Tracking (Meta CAPI, TikTok API, GA4)
        if ($order->payment_method !== 'sslcommerz') {
            try {
                $this->trackingService->trackPurchase($order, $request, flashToSession: false);
            } catch (Throwable $e) {
                Log::warning('Hybrid tracking purchase event failed softly.', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Trigger background worker so all queued jobs (emails, fraud check, tracking) process without blocking response
        BackgroundQueueRunner::trigger();
    }

    /**
     * Generate collision-resistant unique sequential invoice number.
     */
    public function generateInvoiceNumber(): string
    {
        return Order::generateInvoiceNumber();
    }
}
