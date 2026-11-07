<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShippingCharge;
use App\Services\BulkSmsBdService;
use App\Services\CheckoutSecurityService;
use App\Services\FraudOrderGate;
use App\Services\Order\OrderPlacementService;
use App\Services\OrderRestrictionService;
use App\Services\PaymentMethodService;
use App\Services\SslCommerzService;
use App\Services\TrackingService;
use App\Support\Cart;
use App\Support\IncompleteOrderTracker;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(
        Cart $cart,
        IncompleteOrderTracker $tracker,
        PaymentMethodService $paymentMethods,
        TrackingService $tracking,
    ): View|RedirectResponse {
        $cart->adoptBuyNowIfActive();

        if ($cart->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your bag is empty.');
        }

        $user = Auth::user();

        if ($user?->phone && IncompleteOrderTracker::isValidPhone($user->phone)) {
            $tracker->capture($cart, [
                'customer_name' => $user->name,
                'customer_phone' => $user->phone,
                'customer_address' => $user->address,
            ]);
        }

        $trackedEvent = $tracking->trackInitiateCheckout($cart, request());

        $shippingCharges = ShippingCharge::query()
            ->active()
            ->orderBy('amount')
            ->get();

        $gateways = $paymentMethods->availableGateways();

        return view('store.checkout', [
            'lines' => $cart->lines(),
            'subtotal' => $cart->subtotal(),
            'shippingCharges' => $shippingCharges,
            'cart' => $cart,
            'customDeliveryCharge' => $cart->customDeliveryCharge(),
            'availableGateways' => $gateways,
            'sslCommerzEnabled' => $paymentMethods->isSslCommerzEnabled(),
            'customer' => [
                'name' => old('customer_name', $user?->name),
                'phone' => old('customer_phone', $user?->phone),
                'address' => old('customer_address', $user?->address),
            ],
            'trackedEvent' => $trackedEvent,
        ]);
    }

    public function progress(Request $request, Cart $cart, IncompleteOrderTracker $tracker, CheckoutSecurityService $security): JsonResponse
    {
        $cart->adoptBuyNowIfActive();

        if ($cart->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Cart is empty.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_address' => ['nullable', 'string', 'max:500'],
        ]);

        $validated = $security->sanitizeCustomerData($validated);
        $incomplete = $tracker->capture($cart, $validated);

        return response()->json([
            'ok' => true,
            'id' => $incomplete?->id,
        ]);
    }

    public function store(
        Request $request,
        Cart $cart,
        IncompleteOrderTracker $tracker,
        PaymentMethodService $paymentMethods,
        SslCommerzService $sslCommerz,
        OrderRestrictionService $orderRestrictions,
        FraudOrderGate $fraudOrderGate,
        OrderPlacementService $orderPlacement,
        CheckoutSecurityService $security,
    ): RedirectResponse {
        $cart->adoptBuyNowIfActive();

        if ($cart->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your bag is empty.');
        }

        // Anti-bot Honeypot check
        if (! $security->verifyHoneypot($request)) {
            return redirect()
                ->route('checkout.create')
                ->withInput()
                ->with('error', 'অর্ডার অনুরোধটি গৃহীত হয়নি।');
        }

        // Anti-bot Submission Timing check
        if (! $security->verifySubmissionTiming($request)) {
            return redirect()
                ->route('checkout.create')
                ->withInput()
                ->with('error', 'অর্ডারটি খুব দ্রুত সাবমিট হয়েছে। অনুগ্রহ করে কয়েক সেকেন্ড অপেক্ষা করে আবার চেষ্টা করুন।');
        }

        $allowedMethods = $paymentMethods->allowedMethods();

        $customDeliveryCharge = $cart->customDeliveryCharge();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:'.CheckoutSecurityService::BD_PHONE_REGEX],
            'customer_address' => ['required', 'string', 'max:500'],
            'shipping_charge_id' => [
                $customDeliveryCharge !== null ? 'nullable' : 'required',
                'integer',
                Rule::exists('shipping_charges', 'id')->where('is_active', true),
            ],
            'payment_method' => ['required', 'string', Rule::in($allowedMethods)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'customer_phone.regex' => '১১ ডিজিটের সঠিক মোবাইল নম্বর লিখুন (যেমন: 017XXXXXXXX)।',
        ]);

        // Input sanitization (strip_tags)
        $validated = $security->sanitizeCustomerData($validated);

        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your bag is empty.');
        }

        // Duplicate order cooldown check (3-5 minutes)
        $shippingChargeId = ! empty($validated['shipping_charge_id']) ? (int) $validated['shipping_charge_id'] : null;
        $approxShipping = $orderPlacement->resolveShippingAmount($cart, $shippingChargeId);
        $approxTotal = bcadd($cart->subtotal(), $approxShipping, 2);

        $cooldownMsg = $security->checkDuplicateOrderCooldown($validated['customer_phone'], $request->ip(), $approxTotal);
        if ($cooldownMsg !== null) {
            return redirect()
                ->route('checkout.create')
                ->withInput()
                ->with('error', $cooldownMsg);
        }

        $restrictionMessage = $orderRestrictions->validateCheckout(
            $request,
            $lines,
            [
                'phone' => $validated['customer_phone'],
                'name' => $validated['customer_name'],
                'address' => $validated['customer_address'],
            ],
            Auth::id(),
        );

        if ($restrictionMessage !== null) {
            return redirect()
                ->route('checkout.create')
                ->withInput()
                ->with('error', $restrictionMessage);
        }

        $fraudGateMessage = $fraudOrderGate->blockingMessage($validated['customer_phone']);

        if ($fraudGateMessage !== null) {
            return redirect()
                ->route('checkout.create')
                ->withInput()
                ->with('error', $fraudGateMessage);
        }

        // Order Placement OTP Check
        $smsService = app(BulkSmsBdService::class);
        if ($smsService->isTriggerEnabled(BulkSmsBdService::TRIGGER_ORDER_OTP)) {
            $normPhone = preg_replace('/\D+/', '', $validated['customer_phone']);
            $verifiedTime = session('verified_order_phones.'.$normPhone);
            if (! $verifiedTime || (now()->timestamp - $verifiedTime) > 1800) {
                return redirect()
                    ->route('checkout.create')
                    ->withInput()
                    ->with('error', 'মোবাইল নম্বরটি ওটিপি (OTP) দিয়ে ভেরিফাই করুন।');
            }
        }

        try {
            $order = $orderPlacement->placeOrder($request, $cart, $tracker, [
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'shipping_charge_id' => ! empty($validated['shipping_charge_id']) ? (int) $validated['shipping_charge_id'] : null,
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (InsufficientStockException $e) {
            return redirect()
                ->route('cart.index')
                ->with('error', $e->getMessage());
        }

        if ($validated['payment_method'] === 'sslcommerz') {
            $session = $sslCommerz->initiatePayment($order->load('items'));

            if ($session['ok'] && filled($session['gateway_url'])) {
                return redirect()->away($session['gateway_url']);
            }

            return redirect()
                ->route('checkout.success', $order)
                ->with('error', $session['message'] ?: 'Could not start SSLCommerz payment. Your order is saved as unpaid.');
        }

        $successMsg = match ($validated['payment_method']) {
            'manual' => 'Order placed successfully. Please complete your manual payment as instructed.',
            default => 'Order placed successfully. Pay cash on delivery.',
        };

        return redirect()
            ->route('checkout.success', $order)
            ->with('success', $successMsg);
    }

    public function modalDataPayload(Cart $cart, PaymentMethodService $paymentMethods, ?TrackingService $tracking = null): array
    {
        $cart->adoptBuyNowIfActive();

        $shippingCharges = ShippingCharge::query()
            ->active()
            ->orderBy('amount')
            ->get();

        $user = Auth::user();

        // Server-Side + Client Payload Dispatch for InitiateCheckout
        $trackingService = $tracking ?? app(TrackingService::class);
        $trackedEvent = ! $cart->isEmpty() ? $trackingService->trackInitiateCheckout($cart, request()) : null;

        $lines = $cart->lines()->map(function (array $line): array {
            $product = $line['product'];
            $variant = $line['variant'] ?? null;
            $imagePath = $variant?->primaryImagePath() ?: $product->primaryImagePath();
            $imageUrl = $imagePath ? asset('storage/'.$imagePath) : null;

            return [
                'item_key' => $line['item_key'],
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => $product->name,
                'variation_name' => $line['variation_name'] ?? null,
                'color' => $line['color'] ?? null,
                'size' => $line['size'] ?? null,
                'qty' => $line['qty'],
                'unit_price' => $line['unit_price'],
                'price_formatted' => Money::formatBn($line['unit_price']),
                'line_total' => $line['line_total'],
                'line_total_formatted' => Money::formatBn($line['line_total']),
                'image_url' => $imageUrl,
            ];
        })->values();

        $shippingData = $shippingCharges->map(function ($charge) use ($cart) {
            $areaOverride = $cart->deliveryChargeForArea($charge->id);
            $effectiveAmount = $areaOverride !== null ? $areaOverride : ($cart->customDeliveryCharge() !== null ? $cart->customDeliveryCharge() : $charge->amount);

            return [
                'id' => $charge->id,
                'name' => $charge->name,
                'amount' => (float) $effectiveAmount,
                'amount_formatted' => (float) $effectiveAmount == 0 ? 'ফ্রি ডেলিভারি' : Money::formatBn($effectiveAmount),
            ];
        });

        return [
            'is_empty' => $cart->isEmpty(),
            'lines' => $lines,
            'subtotal' => (float) $cart->subtotal(),
            'subtotal_formatted' => Money::formatBn($cart->subtotal()),
            'shipping_charges' => $shippingData,
            'gateways' => $paymentMethods->availableGateways(),
            'customer' => [
                'name' => $user?->name ?? '',
                'phone' => $user?->phone ?? '',
                'address' => $user?->address ?? '',
            ],
            'tracked_event' => $trackedEvent,
            'is_buy_now' => $cart->isBuyNow(),
        ];
    }

    public function modalData(Cart $cart, PaymentMethodService $paymentMethods, TrackingService $tracking): JsonResponse
    {
        return response()->json($this->modalDataPayload($cart, $paymentMethods, $tracking));
    }

    public function storeModal(
        Request $request,
        Cart $cart,
        IncompleteOrderTracker $tracker,
        PaymentMethodService $paymentMethods,
        SslCommerzService $sslCommerz,
        OrderRestrictionService $orderRestrictions,
        FraudOrderGate $fraudOrderGate,
        OrderPlacementService $orderPlacement,
        CheckoutSecurityService $security,
    ): JsonResponse {
        $cart->adoptBuyNowIfActive();

        if ($cart->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => $cart->isBuyNow() ? 'Buy now checkout expired.' : 'Your bag is empty.',
            ], 422);
        }

        // Anti-bot Honeypot check
        if (! $security->verifyHoneypot($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'অর্ডার অনুরোধটি গৃহীত হয়নি।',
            ], 422);
        }

        // Anti-bot Submission Timing check
        if (! $security->verifySubmissionTiming($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'অর্ডারটি খুব দ্রুত সাবমিট হয়েছে। অনুগ্রহ করে কয়েক সেকেন্ড অপেক্ষা করে আবার চেষ্টা করুন।',
            ], 422);
        }

        $allowedMethods = $paymentMethods->allowedMethods();

        $customDeliveryCharge = $cart->customDeliveryCharge();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:'.CheckoutSecurityService::BD_PHONE_REGEX],
            'customer_address' => ['required', 'string', 'max:500'],
            'shipping_charge_id' => [
                $customDeliveryCharge !== null ? 'nullable' : 'required',
                'integer',
                Rule::exists('shipping_charges', 'id')->where('is_active', true),
            ],
            'payment_method' => ['required', 'string', Rule::in($allowedMethods)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'customer_phone.regex' => '১১ ডিজিটের সঠিক মোবাইল নম্বর লিখুন (যেমন: 017XXXXXXXX)।',
        ]);

        // Input sanitization (strip_tags)
        $validated = $security->sanitizeCustomerData($validated);

        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Your bag is empty.',
            ], 422);
        }

        // Duplicate order cooldown check (3-5 minutes)
        $shippingChargeId = ! empty($validated['shipping_charge_id']) ? (int) $validated['shipping_charge_id'] : null;
        $approxShipping = $orderPlacement->resolveShippingAmount($cart, $shippingChargeId);
        $approxTotal = bcadd($cart->subtotal(), $approxShipping, 2);

        $cooldownMsg = $security->checkDuplicateOrderCooldown($validated['customer_phone'], $request->ip(), $approxTotal);
        if ($cooldownMsg !== null) {
            return response()->json([
                'ok' => false,
                'message' => $cooldownMsg,
            ], 422);
        }

        $restrictionMessage = $orderRestrictions->validateCheckout(
            $request,
            $lines,
            [
                'phone' => $validated['customer_phone'],
                'name' => $validated['customer_name'],
                'address' => $validated['customer_address'],
            ],
            Auth::id(),
        );

        if ($restrictionMessage !== null) {
            return response()->json([
                'ok' => false,
                'message' => $restrictionMessage,
            ], 422);
        }

        $fraudGateMessage = $fraudOrderGate->blockingMessage($validated['customer_phone']);

        if ($fraudGateMessage !== null) {
            return response()->json([
                'ok' => false,
                'message' => $fraudGateMessage,
            ], 422);
        }

        // Order Placement OTP Check for Modal
        $smsService = app(BulkSmsBdService::class);
        if ($smsService->isTriggerEnabled(BulkSmsBdService::TRIGGER_ORDER_OTP)) {
            $normPhone = preg_replace('/\D+/', '', $validated['customer_phone']);
            $verifiedTime = session('verified_order_phones.'.$normPhone);
            if (! $verifiedTime || (now()->timestamp - $verifiedTime) > 1800) {
                return response()->json([
                    'ok' => false,
                    'otp_required' => true,
                    'message' => 'অর্ডার নিশ্চিত করতে অনুগ্রহ করে মোবাইল নম্বরটি OTP দিয়ে ভেরিফাই করুন।',
                ], 422);
            }
        }

        try {
            $order = $orderPlacement->placeOrder($request, $cart, $tracker, [
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'shipping_charge_id' => ! empty($validated['shipping_charge_id']) ? (int) $validated['shipping_charge_id'] : null,
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($validated['payment_method'] === 'sslcommerz') {
            $session = $sslCommerz->initiatePayment($order->load('items'));

            if ($session['ok'] && filled($session['gateway_url'])) {
                return response()->json([
                    'ok' => true,
                    'redirect_url' => $session['gateway_url'],
                    'order_id' => $order->id,
                    'invoice_no' => $order->invoice_no,
                ]);
            }

            return response()->json([
                'ok' => true,
                'order_id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'payment_warning' => $session['message'] ?: 'Could not start SSLCommerz payment. Order saved as unpaid.',
            ]);
        }

        $order->load('items');

        $successMsg = match ($validated['payment_method']) {
            'manual' => 'Order placed successfully. Please complete your manual payment as instructed.',
            default => 'Order placed successfully. Pay cash on delivery.',
        };

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'invoice_no' => $order->invoice_no,
            'total' => (float) $order->total,
            'total_formatted' => Money::formatBn($order->total),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'payment_method' => $order->payment_method,
            'items_count' => $order->items->count(),
            'event_id' => 'order_'.$order->id,
            'tracked_event' => [
                'event_name' => 'Purchase',
                'event_id' => 'order_'.$order->id,
                'custom_data' => [
                    'currency' => 'BDT',
                    'value' => (float) $order->total,
                    'order_id' => $order->invoice_no,
                    'contents' => $order->items->map(fn ($it) => [
                        'id' => (string) $it->product_id,
                        'name' => $it->product_name,
                        'price' => (float) $it->price,
                        'quantity' => (int) $it->qty,
                    ])->all(),
                ],
                'datalayer' => [
                    'event' => 'purchase',
                    'event_id' => 'order_'.$order->id,
                    'ecommerce' => [
                        'transaction_id' => $order->invoice_no,
                        'value' => (float) $order->total,
                        'shipping' => (float) $order->shipping_charge,
                        'tax' => 0.0,
                        'currency' => 'BDT',
                        'items' => $order->items->map(fn ($it) => [
                            'item_id' => (string) $it->product_id,
                            'item_name' => $it->product_name,
                            'price' => (float) $it->price,
                            'quantity' => (int) $it->qty,
                        ])->all(),
                    ],
                ],
            ],
            'items' => $order->items->map(fn ($it) => [
                'name' => $it->product_name,
                'variant_label' => $it->variant_label,
                'qty' => $it->qty,
                'total_formatted' => Money::format($it->total),
            ]),
            'message' => $successMsg,
        ]);
    }

    public function success(Request $request, Order $order): View
    {
        $isOwner = Auth::check() && (int) $order->user_id === (int) Auth::id();
        $isGuestOrder = in_array($order->id, session()->get('guest_order_ids', []), true);

        abort_unless($isOwner || $isGuestOrder || $request->hasValidSignature(), 403);

        $order->load('items');

        return view('store.checkout-success', [
            'order' => $order,
        ]);
    }
}
