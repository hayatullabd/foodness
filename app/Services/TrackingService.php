<?php

namespace App\Services;

use App\Jobs\SendServerSideTrackingJob;
use App\Models\ApiIntegration;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\BackgroundQueueRunner;
use App\Support\Cart;
use App\Support\StoreSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TrackingService
{
    public const SESSION_TRACKED_EVENT = 'tracked_event';

    protected ?array $resolvedConfig = null;

    /**
     * Resolve unified configuration supporting both Admin Settings (StoreSettings)
     * and ApiIntegration models seamlessly.
     *
     * @return array{
     *     gtm: array{enabled: bool, container_id: ?string, custom_server_url: ?string},
     *     meta: array{pixel_enabled: bool, capi_enabled: bool, pixel_id: ?string, access_token: ?string, test_event_code: ?string},
     *     tiktok: array{pixel_enabled: bool, api_enabled: bool, pixel_id: ?string, access_token: ?string, test_event_code: ?string},
     *     ga4: array{api_enabled: bool, measurement_id: ?string, api_secret: ?string, ads_id: ?string},
     *     is_hybrid: bool,
     * }
     */
    public function getConfig(): array
    {
        if ($this->resolvedConfig !== null) {
            return $this->resolvedConfig;
        }
        // 1. Resolve Google / GTM & GA4 Settings
        $googleIntegration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GOOGLE_TRACKING);
        $gtmContainerId = $this->sanitizeGtmId(
            (string) (StoreSettings::get('gtm_container_id') ?: $googleIntegration->credential('gtm_container_id', ''))
        );
        $gtmCustomServerUrl = $this->sanitizeUrl(
            (string) (StoreSettings::get('custom_gtm_server_url') ?: $googleIntegration->credential('custom_gtm_server_url', ''))
        );
        $ga4MeasurementId = $this->sanitizeId(
            (string) (StoreSettings::get('ga4_measurement_id') ?: $googleIntegration->credential('ga4_measurement_id', ''))
        );
        $ga4ApiSecret = trim((string) (StoreSettings::get('ga4_api_secret') ?: $googleIntegration->credential('ga4_api_secret', '')));
        $googleAdsId = $this->sanitizeId((string) $googleIntegration->credential('google_ads_id', ''));

        $gtmEnabled = ($googleIntegration->is_active || (bool) StoreSettings::get('gtm_enabled', false)) && filled($gtmContainerId);
        $ga4ApiEnabled = ($googleIntegration->is_active || (bool) StoreSettings::get('ga4_api_enabled', false)) && filled($ga4MeasurementId) && filled($ga4ApiSecret);

        // 2. Resolve Facebook / Meta Pixel & CAPI Settings
        $fbPixelIntegration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_FACEBOOK_PIXEL);
        $fbCapiIntegration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_FACEBOOK_CAPI);

        $fbPixelId = $this->sanitizeId(
            (string) (StoreSettings::get('fb_pixel_id')
                ?: ($fbCapiIntegration->credential('pixel_id') ?: $fbPixelIntegration->credential('pixel_id', '')))
        );
        $fbAccessToken = trim((string) (StoreSettings::get('fb_access_token') ?: $fbCapiIntegration->credential('access_token', '')));
        $fbTestEventCode = trim((string) (StoreSettings::get('fb_test_event_code') ?: $fbCapiIntegration->credential('test_event_code', '')));

        $fbPixelEnabled = ($fbPixelIntegration->is_active || (bool) StoreSettings::get('fb_pixel_enabled', false)) && filled($fbPixelId);
        $fbCapiEnabled = ($fbCapiIntegration->is_active || (bool) StoreSettings::get('fb_capi_enabled', false)) && filled($fbPixelId) && filled($fbAccessToken);

        // 3. Resolve TikTok Pixel & Events API Settings
        $tiktokIntegration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_TIKTOK_PIXEL);
        $tiktokPixelId = $this->sanitizeId(
            (string) (StoreSettings::get('tiktok_pixel_id') ?: $tiktokIntegration->credential('pixel_id', ''))
        );
        $tiktokAccessToken = trim((string) (StoreSettings::get('tiktok_access_token') ?: $tiktokIntegration->credential('access_token', '')));
        $tiktokTestEventCode = trim((string) (StoreSettings::get('tiktok_test_event_code') ?: $tiktokIntegration->credential('test_event_code', '')));

        $tiktokPixelEnabled = ($tiktokIntegration->is_active || (bool) StoreSettings::get('tiktok_pixel_enabled', false)) && filled($tiktokPixelId);
        $tiktokApiEnabled = ($tiktokIntegration->is_active || (bool) StoreSettings::get('tiktok_api_enabled', false)) && filled($tiktokPixelId) && filled($tiktokAccessToken);

        $hasClientSide = $gtmEnabled || $fbPixelEnabled || $tiktokPixelEnabled;
        $hasServerSide = $fbCapiEnabled || $tiktokApiEnabled || $ga4ApiEnabled;

        return $this->resolvedConfig = [
            'gtm' => [
                'enabled' => $gtmEnabled,
                'container_id' => $gtmContainerId,
                'custom_server_url' => $gtmCustomServerUrl,
            ],
            'meta' => [
                'pixel_enabled' => $fbPixelEnabled,
                'capi_enabled' => $fbCapiEnabled,
                'pixel_id' => $fbPixelId,
                'access_token' => $fbAccessToken ?: null,
                'test_event_code' => $fbTestEventCode ?: null,
            ],
            'tiktok' => [
                'pixel_enabled' => $tiktokPixelEnabled,
                'api_enabled' => $tiktokApiEnabled,
                'pixel_id' => $tiktokPixelId,
                'access_token' => $tiktokAccessToken ?: null,
                'test_event_code' => $tiktokTestEventCode ?: null,
            ],
            'ga4' => [
                'api_enabled' => $ga4ApiEnabled,
                'measurement_id' => $ga4MeasurementId,
                'api_secret' => $ga4ApiSecret ?: null,
                'ads_id' => $googleAdsId,
            ],
            'is_hybrid' => $hasClientSide && $hasServerSide,
        ];
    }

    /**
     * Check if any server-side API integration is active.
     */
    public function isServerSideActive(): bool
    {
        $config = $this->getConfig();

        return ! empty($config['meta']['capi_enabled'])
            || ! empty($config['tiktok']['api_enabled'])
            || ! empty($config['ga4']['api_enabled']);
    }

    /**
     * Normalize Bangladeshi and international phone numbers.
     * Converts Bengali digits (০-৯) to 0-9 and prefixes 880 for standard 11-digit numbers.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        // 1. Convert Bengali numerals to Western digits
        $bengaliDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $westernDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $converted = str_replace($bengaliDigits, $westernDigits, (string) $phone);

        // 2. Remove all non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $converted);

        if (blank($digits)) {
            return null;
        }

        // 3. Prepend Bangladesh country code '88' if starts with '01' (11 digits: 01XXXXXXXXX -> 8801XXXXXXXXX)
        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            $digits = '88'.$digits;
        } elseif (str_starts_with($digits, '1') && strlen($digits) === 10) {
            $digits = '880'.$digits;
        }

        return $digits;
    }

    /**
     * SHA-256 hash a normalized string (trimmed, lowercased in UTF-8).
     */
    public function hashString(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = mb_strtolower(trim((string) $value), 'UTF-8');

        return filled($normalized) ? hash('sha256', $normalized) : null;
    }

    /**
     * Build normalized and hashed customer data for High Event Match Quality (EMQ).
     *
     * @param array{
     *     name?: ?string,
     *     phone?: ?string,
     *     email?: ?string,
     *     address?: ?string,
     *     city?: ?string,
     *     district?: ?string,
     *     external_id?: ?string,
     * }|null $customer
     * @return array<string, mixed>
     */
    public function buildUserData(?array $customer = null, ?Request $request = null): array
    {
        $context = $this->resolveTrackingContext($request);

        $name = trim((string) ($customer['name'] ?? ''));
        $firstName = null;
        $lastName = null;

        if (filled($name)) {
            $parts = preg_split('/\s+/', $name);
            $firstName = $parts[0] ?? $name;
            if (count($parts) > 1) {
                $lastName = implode(' ', array_slice($parts, 1));
            }
        }

        $rawPhone = $customer['phone'] ?? null;
        $normalizedPhone = $this->normalizePhone($rawPhone);
        $rawEmail = $customer['email'] ?? null;
        $city = $customer['city'] ?? $customer['district'] ?? null;
        $externalId = $customer['external_id'] ?? (string) (Auth::id() ?? '');

        return array_filter([
            // Meta CAPI array-wrapped sha256 hashes
            'ph' => $normalizedPhone ? [$this->hashString($normalizedPhone)] : null,
            'em' => filled($rawEmail) ? [$this->hashString($rawEmail)] : null,
            'fn' => filled($firstName) ? [$this->hashString($firstName)] : null,
            'ln' => filled($lastName) ? [$this->hashString($lastName)] : null,
            'ct' => filled($city) ? [$this->hashString($city)] : null,
            'country' => [$this->hashString('bd')],
            'external_id' => filled($externalId) ? [$this->hashString((string) $externalId)] : null,

            // Browser cookies & connection context (unhashed)
            'client_ip_address' => $context['client_ip'] ?? $request?->ip(),
            'client_user_agent' => $context['user_agent'] ?? $request?->userAgent(),
            'fbp' => $context['fbp'] ?? null,
            'fbc' => $context['fbc'] ?? null,
            'ttp' => $context['ttp'] ?? null,
            'ttclid' => $context['ttclid'] ?? null,
            'ga' => $context['ga'] ?? null,
            'ga_client_id' => $context['ga_client_id'] ?? null,
        ]);
    }

    /**
     * Track 'ViewContent' / 'view_item' event.
     *
     * @return array<string, mixed>
     */
    public function trackViewContent(Product $product, ?Request $request = null): array
    {
        $eventId = 'view_'.Str::uuid()->toString();
        $sourceUrl = $request?->fullUrl() ?? route('product.show', $product);

        $price = (float) $product->price;
        $categoryName = $product->category?->name ?? 'General';

        $items = [
            [
                'item_id' => (string) $product->id,
                'item_name' => $product->name,
                'item_category' => $categoryName,
                'price' => $price,
                'quantity' => 1,
            ],
        ];

        $userData = $this->buildUserData([
            'email' => Auth::user()?->email,
            'phone' => Auth::user()?->phone,
            'name' => Auth::user()?->name,
            'external_id' => Auth::id() ? (string) Auth::id() : null,
        ], $request);

        $payload = [
            'event_name' => 'ViewContent',
            'event_id' => $eventId,
            'event_time' => now()->timestamp,
            'event_source_url' => $sourceUrl,
            'user_data' => $userData,
            'custom_data' => [
                'currency' => 'BDT',
                'value' => $price,
                'content_name' => $product->name,
                'content_category' => $categoryName,
                'content_type' => 'product',
                'contents' => $items,
                'content_ids' => [(string) $product->id],
                'num_items' => 1,
            ],
            'datalayer' => [
                'event' => 'view_item',
                'event_id' => $eventId,
                'ecommerce' => [
                    'currency' => 'BDT',
                    'value' => $price,
                    'items' => $items,
                ],
            ],
        ];

        $this->dispatchServerSideIfActive($payload);

        return $payload;
    }

    /**
     * Track 'AddToCart' / 'add_to_cart' event.
     *
     * @return array<string, mixed>
     */
    public function trackAddToCart(
        Product $product,
        ?ProductVariant $variant = null,
        int $qty = 1,
        ?Request $request = null,
        bool $flashToSession = true
    ): array {
        $eventId = 'atc_'.Str::uuid()->toString();
        $sourceUrl = $request?->fullUrl() ?? url('/cart');

        $unitPrice = (float) ($variant ? $variant->effectivePrice() : $product->price);
        $totalVal = round($unitPrice * max(1, $qty), 2);
        $categoryName = $product->category?->name ?? 'General';

        $items = [
            [
                'item_id' => (string) $product->id,
                'item_name' => $product->name.($variant ? ' - '.$variant->label() : ''),
                'item_category' => $categoryName,
                'price' => $unitPrice,
                'quantity' => max(1, $qty),
            ],
        ];

        $userData = $this->buildUserData([
            'email' => Auth::user()?->email,
            'phone' => Auth::user()?->phone,
            'name' => Auth::user()?->name,
            'external_id' => Auth::id() ? (string) Auth::id() : null,
        ], $request);

        $payload = [
            'event_name' => 'AddToCart',
            'event_id' => $eventId,
            'event_time' => now()->timestamp,
            'event_source_url' => $sourceUrl,
            'user_data' => $userData,
            'custom_data' => [
                'currency' => 'BDT',
                'value' => $totalVal,
                'content_type' => 'product',
                'contents' => $items,
                'content_ids' => [(string) $product->id],
                'num_items' => max(1, $qty),
            ],
            'datalayer' => [
                'event' => 'add_to_cart',
                'event_id' => $eventId,
                'ecommerce' => [
                    'currency' => 'BDT',
                    'value' => $totalVal,
                    'items' => $items,
                ],
            ],
        ];

        $this->dispatchServerSideIfActive($payload);

        if ($flashToSession) {
            session()->flash(self::SESSION_TRACKED_EVENT, $payload);
            // Backward-compatible flash for existing store/cart.blade.php
            session()->flash('tracked_add_to_cart', [
                'id' => (string) $product->id,
                'name' => $product->name,
                'category' => $categoryName,
                'price' => $unitPrice,
                'quantity' => max(1, $qty),
            ]);
        }

        return $payload;
    }

    /**
     * Track 'InitiateCheckout' / 'begin_checkout' event.
     *
     * @return array<string, mixed>
     */
    public function trackInitiateCheckout(Cart $cart, ?Request $request = null): array
    {
        $eventId = 'ic_'.Str::uuid()->toString();
        $sourceUrl = $request?->fullUrl() ?? route('checkout.create');

        $lines = $cart->lines();
        $items = [];
        $contentIds = [];
        $totalQuantity = 0;

        foreach ($lines as $line) {
            /** @var Product $prod */
            $prod = $line['product'];
            $var = $line['variant'] ?? null;
            $qty = (int) ($line['qty'] ?? 1);
            $price = (float) ($line['unit_price'] ?? $prod->price);

            $items[] = [
                'item_id' => (string) $prod->id,
                'item_name' => $prod->name.($var ? ' - '.$var->label() : ''),
                'item_category' => $prod->category?->name ?? 'General',
                'price' => $price,
                'quantity' => $qty,
            ];
            $contentIds[] = (string) $prod->id;
            $totalQuantity += $qty;
        }

        $subtotal = (float) $cart->subtotal();

        $userData = $this->buildUserData([
            'email' => Auth::user()?->email,
            'phone' => Auth::user()?->phone,
            'name' => Auth::user()?->name,
            'external_id' => Auth::id() ? (string) Auth::id() : null,
        ], $request);

        $payload = [
            'event_name' => 'InitiateCheckout',
            'event_id' => $eventId,
            'event_time' => now()->timestamp,
            'event_source_url' => $sourceUrl,
            'user_data' => $userData,
            'custom_data' => [
                'currency' => 'BDT',
                'value' => $subtotal,
                'content_type' => 'product',
                'contents' => $items,
                'content_ids' => array_values(array_unique($contentIds)),
                'num_items' => $totalQuantity,
            ],
            'datalayer' => [
                'event' => 'begin_checkout',
                'event_id' => $eventId,
                'ecommerce' => [
                    'currency' => 'BDT',
                    'value' => $subtotal,
                    'items' => $items,
                ],
            ],
        ];

        $this->dispatchServerSideIfActive($payload);

        return $payload;
    }

    /**
     * Track 'Purchase' / 'purchase' event with reliable deduplication.
     * Uses 'order_{$order->id}' as the shared deduplication event_id across
     * Client-Side GTM dataLayer, Meta Pixel, TikTok Pixel, and Server-Side APIs.
     *
     * @return array<string, mixed>
     */
    public function trackPurchase(Order $order, ?Request $request = null, bool $flashToSession = true): array
    {
        $order->loadMissing(['items', 'user']);

        // Canonical deduplication ID shared between Client and Server
        $eventId = 'order_'.$order->id;
        $sourceUrl = route('checkout.success', $order);

        $items = [];
        $contentIds = [];
        $totalQuantity = 0;

        foreach ($order->items as $item) {
            $qty = (int) $item->qty;
            $price = (float) $item->price;

            $items[] = [
                'item_id' => (string) $item->product_id,
                'item_name' => $item->product_name,
                'price' => $price,
                'quantity' => $qty,
            ];
            $contentIds[] = (string) $item->product_id;
            $totalQuantity += $qty;
        }

        $total = (float) $order->total;
        $shipping = (float) $order->shipping_charge;

        // Customer details for Event Match Quality (EMQ)
        $userData = $this->buildUserData([
            'name' => $order->customer_name,
            'phone' => $order->customer_phone,
            'email' => $order->user?->email,
            'address' => $order->customer_address,
            'city' => $order->delivery_district ?? $order->delivery_division,
            'external_id' => (string) ($order->user_id ?: $order->customer_phone),
        ], $request);

        // Fallback to order-captured IP and click IDs if request context lacks them
        if (empty($userData['client_ip_address']) && filled($order->ip_address)) {
            $userData['client_ip_address'] = $order->ip_address;
        }
        if (empty($userData['client_user_agent']) && filled($order->user_agent)) {
            $userData['client_user_agent'] = $order->user_agent;
        }
        if (empty($userData['fbc']) && filled($order->fbclid)) {
            $userData['fbc'] = sprintf('fb.1.%d.%s', (int) round(microtime(true) * 1000), $order->fbclid);
        }
        if (empty($userData['ttclid']) && filled($order->ttclid)) {
            $userData['ttclid'] = $order->ttclid;
        }

        $payload = [
            'event_name' => 'Purchase',
            'event_id' => $eventId,
            'event_time' => $order->created_at ? $order->created_at->timestamp : now()->timestamp,
            'event_source_url' => $sourceUrl,
            'user_data' => $userData,
            'custom_data' => [
                'currency' => 'BDT',
                'value' => $total,
                'order_id' => $order->invoice_no,
                'shipping' => $shipping,
                'tax' => 0.0,
                'content_type' => 'product',
                'contents' => $items,
                'content_ids' => array_values(array_unique($contentIds)),
                'num_items' => $totalQuantity,
            ],
            'datalayer' => [
                'event' => 'purchase',
                'event_id' => $eventId,
                'ecommerce' => [
                    'transaction_id' => $order->invoice_no,
                    'value' => $total,
                    'shipping' => $shipping,
                    'tax' => 0.0,
                    'currency' => 'BDT',
                    'items' => $items,
                ],
            ],
        ];

        // Non-blocking Server-Side Dispatch
        $this->dispatchServerSideIfActive($payload);

        // Flash event to session for automatic consumption on thank-you redirect page
        if ($flashToSession) {
            session()->flash(self::SESSION_TRACKED_EVENT, $payload);
        }

        return $payload;
    }

    /**
     * Dispatch server-side tracking job asynchronously after the HTTP response has been sent.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function dispatchServerSideIfActive(array $payload): void
    {
        $config = $this->getConfig();

        if ($this->isServerSideActive()) {
            if (config('queue.default') !== 'sync') {
                SendServerSideTrackingJob::dispatch($payload, [
                    'meta' => $config['meta'],
                    'tiktok' => $config['tiktok'],
                    'ga4' => $config['ga4'],
                ]);

                BackgroundQueueRunner::trigger();
            } else {
                SendServerSideTrackingJob::dispatchAfterResponse($payload, [
                    'meta' => $config['meta'],
                    'tiktok' => $config['tiktok'],
                    'ga4' => $config['ga4'],
                ]);
            }
        }
    }

    /**
     * Resolve tracking context from request attributes or session.
     *
     * @return array<string, mixed>
     */
    public function resolveTrackingContext(?Request $request = null): array
    {
        $req = $request ?? request();

        $fromAttributes = $req?->attributes?->get('tracking_context');
        if (is_array($fromAttributes) && $fromAttributes !== []) {
            return $fromAttributes;
        }

        $fromSession = session()->get('tracking_cookies');
        if (is_array($fromSession) && $fromSession !== []) {
            return $fromSession;
        }

        return [
            'client_ip' => $req?->ip(),
            'user_agent' => $req?->userAgent(),
            'source_url' => $req?->fullUrl(),
            'fbp' => $req?->cookie('_fbp') ?? $req?->cookies->get('_fbp'),
            'fbc' => $req?->cookie('_fbc') ?? $req?->cookies->get('_fbc'),
            'ttp' => $req?->cookie('_ttp') ?? $req?->cookies->get('_ttp'),
            'ttclid' => $req?->cookie('ttclid') ?? $req?->cookies->get('ttclid'),
            'ga' => $req?->cookie('_ga') ?? $req?->cookies->get('_ga'),
        ];
    }

    public function sanitizeId(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        $safe = preg_replace('/[^A-Za-z0-9_\-]/', '', $trimmed);

        return filled($safe) ? $safe : null;
    }

    public function sanitizeGtmId(string $value): ?string
    {
        $trimmed = strtoupper(trim($value));

        if ($trimmed === '') {
            return null;
        }

        if (! preg_match('/^GTM-[A-Z0-9]+$/', $trimmed)) {
            $digits = preg_replace('/[^A-Z0-9]/', '', $trimmed);

            if (filled($digits) && ! str_starts_with($digits, 'GTM')) {
                $trimmed = 'GTM-'.$digits;
            } else {
                $trimmed = $digits ?? '';
            }
        }

        if (! preg_match('/^GTM-[A-Z0-9]+$/', $trimmed)) {
            return null;
        }

        return $trimmed;
    }

    public function sanitizeUrl(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (! str_starts_with($trimmed, 'http://') && ! str_starts_with($trimmed, 'https://')) {
            $trimmed = 'https://'.$trimmed;
        }

        return filter_var($trimmed, FILTER_VALIDATE_URL) ? rtrim($trimmed, '/') : null;
    }
}
