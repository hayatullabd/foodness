<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingCharge;
use App\Services\BulkSmsBdService;
use App\Services\CheckoutSecurityService;
use App\Services\FraudOrderGate;
use App\Services\Order\OrderPlacementService;
use App\Services\OrderRestrictionService;
use App\Services\PaymentMethodService;
use App\Services\SslCommerzService;
use App\Support\Cart;
use App\Support\IncompleteOrderTracker;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $landingPage = LandingPage::query()
            ->with([
                'product.category',
                'product.images',
                'product.variants.product',
                'product.variants.color',
                'product.variants.size',
                'products.category',
                'products.images',
                'products.variants.product',
                'products.variants.color',
                'products.variants.size',
                'landingPageProducts.product.category',
                'landingPageProducts.product.images',
                'landingPageProducts.product.variants.product',
                'landingPageProducts.product.variants.color',
                'landingPageProducts.product.variants.size',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        // If inactive and not an authenticated admin, return 404
        if (! $landingPage->is_active && ! Auth::guard('admin')->check()) {
            abort(404);
        }

        // Count views per session to avoid duplicate count on refresh
        $viewedKey = 'viewed_landing_'.$landingPage->id;
        if (! Session::has($viewedKey)) {
            $landingPage->increment('views_count');
            Session::put($viewedKey, true);
        }

        $product = $landingPage->defaultProduct() ?? $landingPage->product;
        $shippingOptions = $this->resolveShippingOptions($landingPage);
        $paymentMethodsService = app(PaymentMethodService::class);
        $availableGateways = $paymentMethodsService->availableGateways();
        $sslCommerzEnabled = $paymentMethodsService->isSslCommerzEnabled();
        $codEnabled = $paymentMethodsService->isCodEnabled();
        $manualPaymentEnabled = $paymentMethodsService->isManualPaymentEnabled();

        $assignedProducts = $landingPage->landingPageProducts->isNotEmpty()
            ? $landingPage->landingPageProducts
            : collect();

        // Render embedded checkout form
        $checkoutHtml = view('landing.partials.checkout-form', [
            'landingPage' => $landingPage,
            'product' => $product,
            'assignedProducts' => $assignedProducts,
            'shippingOptions' => $shippingOptions,
            'effectivePrice' => $landingPage->effectivePrice(null, $product?->id),
            'user' => Auth::guard('web')->user(),
            'availableGateways' => $availableGateways,
            'codEnabled' => $codEnabled,
            'sslCommerzEnabled' => $sslCommerzEnabled,
            'manualPaymentEnabled' => $manualPaymentEnabled,
            'manualPaymentData' => [],
        ])->render();

        // Replace dynamic template tags
        $parsedHtml = (string) ($landingPage->custom_html ?? '');
        $primaryImagePath = $product?->primaryImagePath();
        $primaryImageUrl = $primaryImagePath ? asset('storage/'.$primaryImagePath) : asset('images/placeholder.png');
        $formattedPrice = Money::formatBn($landingPage->effectivePrice(null, $product?->id));

        $replacements = [
            '{{product_title}}' => e($product?->name ?? ''),
            '{{product_price}}' => $formattedPrice,
            '{{product_image_url}}' => $primaryImageUrl,
            '{{product_image}}' => '<img src="'.$primaryImageUrl.'" alt="'.e($product?->name ?? '').'" class="w-full h-auto object-cover">',
        ];

        $parsedHtml = strtr($parsedHtml, $replacements);

        // Inject checkout form
        if (str_contains($parsedHtml, '[checkout_form]')) {
            $finalHtml = str_replace('[checkout_form]', $checkoutHtml, $parsedHtml);
        } else {
            $finalHtml = $parsedHtml."\n".$checkoutHtml;
        }

        return view('landing.show', [
            'landingPage' => $landingPage,
            'product' => $product,
            'finalHtml' => $finalHtml,
        ]);
    }

    public function checkout(
        Request $request,
        string $slug,
        Cart $cart,
        IncompleteOrderTracker $tracker,
        OrderPlacementService $orderPlacement,
        FraudOrderGate $fraudOrderGate,
        OrderRestrictionService $orderRestrictions,
    ): JsonResponse|RedirectResponse {
        $landingPage = LandingPage::query()
            ->with([
                'product.images',
                'product.variants',
                'products.images',
                'products.variants',
                'landingPageProducts.product',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $security = app(CheckoutSecurityService::class);

        // Anti-bot Honeypot check
        if (! $security->verifyHoneypot($request)) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'অর্ডার অনুরোধটি গৃহীত হয়নি।'], 422);
            }

            return back()->withInput()->with('error', 'অর্ডার অনুরোধটি গৃহীত হয়নি।');
        }

        // Anti-bot Submission Timing check (< 2 seconds)
        if (! $security->verifySubmissionTiming($request)) {
            $speedMsg = 'অর্ডারটি খুব দ্রুত সাবমিট হয়েছে। অনুগ্রহ করে কয়েক সেকেন্ড অপেক্ষা করে আবার চেষ্টা করুন।';
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $speedMsg], 422);
            }

            return back()->withInput()->with('error', $speedMsg);
        }

        $paymentMethodsService = app(PaymentMethodService::class);
        $allowedMethods = $paymentMethodsService->allowedMethods();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^(?:\+?88|88)?01[3-9]\d{8}$/'],
            'customer_address' => ['required', 'string', 'max:500'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:50'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'selected_products' => ['nullable', 'array'],
            'quantities' => ['nullable', 'array'],
            'variants' => ['nullable', 'array'],
            'shipping_option' => ['required', 'string'],
            'payment_method' => ['nullable', 'string', Rule::in($allowedMethods)],
            'notes' => ['nullable', 'string', 'max:500'],
            'custom_fields' => ['nullable', 'array'],
        ], [
            'customer_name.required' => 'আপনার নাম লিখুন।',
            'customer_phone.required' => 'সঠিক মোবাইল নম্বর লিখুন।',
            'customer_phone.regex' => '১১ ডিজিটের সঠিক মোবাইল নম্বর লিখুন (যেমন: 017XXXXXXXX)।',
            'customer_address.required' => 'আপনার সম্পূর্ণ ঠিকানা লিখুন।',
            'shipping_option.required' => 'ডেলিভারি এলাকা নির্বাচন করুন।',
        ]);

        // Input Sanitization (strip_tags)
        $validated = $security->sanitizeCustomerData($validated);

        // Duplicate order cooldown check (3-5 minutes)
        $cooldownMsg = $security->checkDuplicateOrderCooldown($validated['customer_phone'], $request->ip());
        if ($cooldownMsg !== null) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $cooldownMsg], 422);
            }

            return back()->withInput()->with('error', $cooldownMsg);
        }

        // Fraud check
        $fraudGateMessage = $fraudOrderGate->blockingMessage($validated['customer_phone']);
        if ($fraudGateMessage !== null) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $fraudGateMessage], 422);
            }

            return back()->withInput()->with('error', $fraudGateMessage);
        }

        // OTP verification check if enabled in store
        $smsService = app(BulkSmsBdService::class);
        if ($smsService->isTriggerEnabled(BulkSmsBdService::TRIGGER_ORDER_OTP)) {
            $normPhone = preg_replace('/\D+/', '', $validated['customer_phone']);
            $verifiedTime = session('verified_order_phones.'.$normPhone);
            if (! $verifiedTime || (now()->timestamp - $verifiedTime) > 1800) {
                $otpMsg = 'মোবাইল নম্বরটি ওটিপি (OTP) দিয়ে ভেরিফাই করুন।';
                if ($request->wantsJson()) {
                    return response()->json(['ok' => false, 'message' => $otpMsg, 'require_otp' => true], 422);
                }

                return back()->withInput()->with('error', $otpMsg);
            }
        }

        // Clear cart and add landing products
        $cart->clear();

        if ($landingPage->isMultipleChoice()) {
            $itemsToAdd = $validated['items'] ?? [];
            if (empty($itemsToAdd) && $request->has('selected_products')) {
                $sel = (array) $request->input('selected_products', []);
                $quants = (array) $request->input('quantities', []);
                $vars = (array) $request->input('variants', []);
                foreach ($sel as $pId) {
                    $itemsToAdd[] = [
                        'product_id' => (int) $pId,
                        'quantity' => (int) ($quants[$pId] ?? 1),
                        'variant_id' => ! empty($vars[$pId]) ? (int) $vars[$pId] : null,
                    ];
                }
            }

            foreach ($itemsToAdd as $item) {
                $pid = (int) $item['product_id'];
                $qty = (int) ($item['quantity'] ?? 1);
                if ($qty <= 0) {
                    continue;
                }

                $p = Product::query()->findOrFail($pid);
                $v = ! empty($item['variant_id'])
                    ? ProductVariant::query()->where('product_id', $pid)->find($item['variant_id'])
                    : null;

                $lpp = $landingPage->landingPageProducts->firstWhere('product_id', $pid);
                $customPrice = $lpp?->custom_price !== null && (float) $lpp->custom_price > 0
                    ? (string) $lpp->custom_price
                    : null;

                if ($v && (float) $v->price > 0) {
                    $customPrice = (string) $v->price;
                }

                $cart->add($p, $qty, $v, $customPrice);
            }
        } else {
            $selectedPid = ! empty($validated['product_id'])
                ? (int) $validated['product_id']
                : ($landingPage->defaultProduct()?->id ?? $landingPage->product_id);

            $p = Product::query()->findOrFail($selectedPid);
            $v = ! empty($validated['variant_id'])
                ? ProductVariant::query()->where('product_id', $p->id)->find($validated['variant_id'])
                : null;

            $qty = (int) ($validated['quantity'] ?? 1);
            $lpp = $landingPage->landingPageProducts->firstWhere('product_id', $p->id);
            $customPrice = $lpp?->custom_price !== null && (float) $lpp->custom_price > 0
                ? (string) $lpp->custom_price
                : ($landingPage->custom_price ? (string) $landingPage->custom_price : null);

            if ($v && (float) $v->price > 0) {
                $customPrice = (string) $v->price;
            }

            $cart->add($p, $qty, $v, $customPrice);
        }

        if ($cart->isEmpty()) {
            $msg = 'কমপক্ষে একটি প্রোডাক্ট সিলেক্ট করতে হবে।';
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }

            return back()->withInput()->with('error', $msg);
        }

        // Security & Anti-Fraud / Order Restriction validation
        $lines = $cart->lines();
        $restrictionMessage = $orderRestrictions->validateCheckout(
            $request,
            $lines,
            [
                'phone' => $validated['customer_phone'],
                'name' => $validated['customer_name'],
                'address' => $validated['customer_address'],
            ],
            Auth::guard('web')->user()?->id,
        );

        if ($restrictionMessage !== null) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $restrictionMessage], 422);
            }

            return back()->withInput()->with('error', $restrictionMessage);
        }

        // Server-Side Shipping & Price Integrity Verification
        $authorizedShipping = collect($this->resolveShippingOptions($landingPage));
        $matchedShipping = $authorizedShipping->firstWhere('key', $validated['shipping_option']);

        if (! $matchedShipping) {
            $shipErrMsg = 'নির্বাচিত ডেলিভারি অপশনটি সঠিক নয়।';
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $shipErrMsg], 422);
            }

            return back()->withInput()->with('error', $shipErrMsg);
        }

        // Resolve shipping charge with variant-level delivery settings check
        $shippingChargeId = null;
        $shippingParts = explode(':', $validated['shipping_option']);
        $shippingType = $shippingParts[0] ?? '';
        $shippingVal = $shippingParts[1] ?? '0';

        // Check if any selected variant overrides delivery
        $activeVariants = collect($lines)->map(fn ($line) => $line['variant'])->filter();
        $variantOverride = null;
        foreach ($activeVariants as $actVar) {
            $cfg = $landingPage->variantDeliveryConfig($actVar);
            if ($cfg['type'] !== 'default') {
                $variantOverride = $cfg;
                if ($cfg['type'] === 'free') {
                    break;
                }
            }
        }

        if ($variantOverride !== null) {
            if ($variantOverride['type'] === 'free') {
                $cart->setOverrideDeliveryCharge('0.00');
            } elseif ($variantOverride['type'] === 'custom') {
                $chargeName = $matchedShipping['name'] ?? '';
                $isInside = str_contains(mb_strtolower($chargeName), 'inside')
                    || str_contains($chargeName, 'ভেতরে')
                    || str_contains(mb_strtolower($chargeName), 'dhaka');
                $customRate = $isInside
                    ? ($variantOverride['inside'] ?? 70.0)
                    : ($variantOverride['outside'] ?? 130.0);
                $cart->setOverrideDeliveryCharge(number_format((float) $customRate, 2, '.', ''));
            }
        } elseif ($shippingType === 'charge_id') {
            $shippingChargeId = (int) $shippingVal;
            $activeCharge = ShippingCharge::query()->active()->find($shippingChargeId);
            if (! $activeCharge) {
                $shipErrMsg = 'নির্বাচিত ডেলিভারি চার্জটি সক্রিয় নয়।';
                if ($request->wantsJson()) {
                    return response()->json(['ok' => false, 'message' => $shipErrMsg], 422);
                }

                return back()->withInput()->with('error', $shipErrMsg);
            }
        } elseif ($shippingType === 'free') {
            $cart->setOverrideDeliveryCharge('0.00');
        } elseif ($shippingType === 'custom') {
            // Strictly set the amount from the server-side authorized option, never trusting client input
            $cart->setOverrideDeliveryCharge(number_format((float) $matchedShipping['amount'], 2, '.', ''));
        }

        $sslCommerz = app(SslCommerzService::class);
        $paymentMethod = $validated['payment_method'] ?? ($allowedMethods[0] ?? 'cod');
        if (! in_array($paymentMethod, $allowedMethods, true)) {
            $paymentMethod = $allowedMethods[0] ?? 'cod';
        }

        try {
            $order = $orderPlacement->placeOrder($request, $cart, $tracker, [
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'shipping_charge_id' => $shippingChargeId,
                'payment_method' => $paymentMethod,
                'notes' => $validated['notes'] ?? null,
                'custom_fields' => $validated['custom_fields'] ?? null,
                'landing_page_id' => $landingPage->id,
            ]);

            // Increment orders count for this landing page
            $landingPage->increment('orders_count');

            if ($paymentMethod === 'sslcommerz') {
                $session = $sslCommerz->initiatePayment($order->load('items'));
                if ($session['ok'] && filled($session['gateway_url'])) {
                    if ($request->wantsJson()) {
                        return response()->json([
                            'ok' => true,
                            'redirect' => $session['gateway_url'],
                        ]);
                    }

                    return redirect()->away($session['gateway_url']);
                }
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'redirect' => route('checkout.success', $order),
                ]);
            }

            return redirect()->route('checkout.success', $order);
        } catch (InsufficientStockException $e) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            $msg = $e->validator && $e->validator->errors()->isNotEmpty()
                ? $e->validator->errors()->first()
                : $e->getMessage();

            if ($e->response && method_exists($e->response, 'getSession') && $e->response->getSession()) {
                $sessionError = $e->response->getSession()->get('error');
                if ($sessionError) {
                    $msg = $sessionError;
                }
            }

            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }

            return back()->withInput()->with('error', $msg);
        } catch (\Throwable $e) {
            Log::error('Landing page order placement failed: '.$e->getMessage(), [
                'exception' => $e,
                'landing_page_id' => $landingPage->id,
            ]);

            $errMsg = $e->getMessage() ?: 'অর্ডার প্রক্রিয়াকরণে সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।';
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $errMsg], 500);
            }

            return back()->withInput()->with('error', $errMsg);
        }
    }

    /**
     * @return array<int, array{key: string, name: string, amount: float, formatted_amount: string, is_default: bool}>
     */
    private function resolveShippingOptions(LandingPage $landingPage): array
    {
        if ($landingPage->delivery_charge_type === 'free') {
            return [
                [
                    'key' => 'free:0',
                    'name' => 'সারা বাংলাদেশ (ফ্রি ডেলিভারি)',
                    'amount' => 0.0,
                    'formatted_amount' => 'ফ্রি',
                    'is_default' => true,
                ],
            ];
        }

        if ($landingPage->delivery_charge_type === 'custom') {
            $inside = (float) ($landingPage->custom_delivery_inside ?? 70);
            $outside = (float) ($landingPage->custom_delivery_outside ?? 130);

            return [
                [
                    'key' => 'custom:'.$inside,
                    'name' => 'ঢাকার ভেতরে ডেলিভারি',
                    'amount' => $inside,
                    'formatted_amount' => $inside == 0 ? 'ফ্রি' : Money::formatBn($inside),
                    'is_default' => true,
                ],
                [
                    'key' => 'custom:'.$outside,
                    'name' => 'ঢাকার বাইরে ডেলিভারি',
                    'amount' => $outside,
                    'formatted_amount' => $outside == 0 ? 'ফ্রি' : Money::formatBn($outside),
                    'is_default' => false,
                ],
            ];
        }

        // Default store shipping charges
        $charges = ShippingCharge::query()->active()->orderBy('amount')->get();

        if ($charges->isEmpty()) {
            return [
                [
                    'key' => 'custom:70',
                    'name' => 'ঢাকার ভেতরে ডেলিভারি',
                    'amount' => 70.0,
                    'formatted_amount' => Money::formatBn(70),
                    'is_default' => true,
                ],
                [
                    'key' => 'custom:130',
                    'name' => 'ঢাকার বাইরে ডেলিভারি',
                    'amount' => 130.0,
                    'formatted_amount' => Money::formatBn(130),
                    'is_default' => false,
                ],
            ];
        }

        return $charges->map(function (ShippingCharge $charge, int $index): array {
            return [
                'key' => 'charge_id:'.$charge->id,
                'name' => $charge->name,
                'amount' => (float) $charge->amount,
                'formatted_amount' => (float) $charge->amount == 0 ? 'ফ্রি' : Money::formatBn($charge->amount),
                'is_default' => $index === 0,
            ];
        })->all();
    }

    public function progress(
        Request $request,
        string $slug,
        Cart $cart,
        IncompleteOrderTracker $tracker,
    ): JsonResponse {
        $landingPage = LandingPage::query()
            ->with(['landingPageProducts.product', 'products', 'product'])
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'product_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer'],
            'items' => ['nullable', 'array'],
        ]);

        $security = app(CheckoutSecurityService::class);
        $validated = $security->sanitizeCustomerData($validated);

        $cart->clear();

        if ($landingPage->isMultipleChoice()) {
            $itemsToAdd = $validated['items'] ?? [];
            if (empty($itemsToAdd) && $request->has('selected_products')) {
                $sel = (array) $request->input('selected_products', []);
                $quants = (array) $request->input('quantities', []);
                $vars = (array) $request->input('variants', []);
                foreach ($sel as $pId) {
                    $itemsToAdd[] = [
                        'product_id' => (int) $pId,
                        'quantity' => (int) ($quants[$pId] ?? 1),
                        'variant_id' => ! empty($vars[$pId]) ? (int) $vars[$pId] : null,
                    ];
                }
            }

            foreach ($itemsToAdd as $item) {
                $pid = (int) ($item['product_id'] ?? 0);
                $qty = (int) ($item['quantity'] ?? 1);
                if ($pid <= 0 || $qty <= 0) {
                    continue;
                }

                $p = Product::query()->find($pid);
                if (! $p) {
                    continue;
                }

                $v = ! empty($item['variant_id'])
                    ? ProductVariant::query()->where('product_id', $pid)->find($item['variant_id'])
                    : null;

                $lpp = $landingPage->landingPageProducts->firstWhere('product_id', $pid);
                $customPrice = $lpp?->custom_price !== null && (float) $lpp->custom_price > 0
                    ? (string) $lpp->custom_price
                    : null;

                if ($v && (float) $v->price > 0) {
                    $customPrice = (string) $v->price;
                }

                $cart->add($p, $qty, $v, $customPrice);
            }
        } else {
            $selectedPid = ! empty($validated['product_id'])
                ? (int) $validated['product_id']
                : ($landingPage->defaultProduct()?->id ?? $landingPage->product_id);

            $p = Product::query()->find($selectedPid) ?? $landingPage->product;
            if ($p) {
                $v = ! empty($validated['variant_id'])
                    ? ProductVariant::query()->where('product_id', $p->id)->find($validated['variant_id'])
                    : null;

                $qty = (int) ($validated['quantity'] ?? 1);
                $lpp = $landingPage->landingPageProducts->firstWhere('product_id', $p->id);
                $customPrice = $lpp?->custom_price !== null && (float) $lpp->custom_price > 0
                    ? (string) $lpp->custom_price
                    : ($landingPage->custom_price ? (string) $landingPage->custom_price : null);

                if ($v && (float) $v->price > 0) {
                    $customPrice = (string) $v->price;
                }

                $cart->add($p, $qty, $v, $customPrice);
            }
        }

        $incomplete = $tracker->capture($cart, [
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_phone' => $validated['customer_phone'],
            'customer_address' => $validated['customer_address'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $incomplete?->id,
        ]);
    }
}
