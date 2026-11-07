<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RedXShipmentClient
{
    /**
     * @return array{ok: bool, message: string, tracking_number: ?string, tracking_url: ?string, raw: mixed}
     */
    public function create(Order $order, CourierApi $credentials): array
    {
        $token = CourierApi::normalizeBearerToken($credentials->token ?: $credentials->api_key);

        if (blank($token)) {
            return $this->failure('RedX access token is required.');
        }

        $phone = $this->normalizePhone((string) $order->customer_phone);

        if ($phone === null) {
            return $this->failure('Customer phone must be an 11-digit Bangladeshi number.');
        }

        $area = $this->resolveDeliveryArea($order, $credentials, $token);

        if ($area === null) {
            return $this->failure('Could not resolve a RedX delivery area. Set a default area ID in Courier API settings or fill the order district.');
        }

        $order->loadMissing('items');
        $codAmount = $this->codAmount($order);
        $parcelWeight = 500;

        $parcelDetails = $credentials->sendsProductNames()
            ? $order->items->map(fn ($item) => [
                'name' => (string) $item->product_name,
                'category' => 'General',
                'value' => (int) round((float) $item->price),
            ])->values()->all()
            : [];

        $meta = is_array($credentials->meta) ? $credentials->meta : [];
        $pickupStoreId = $meta['pickup_store_id'] ?? null;

        $payload = [
            'customer_name' => mb_substr(trim((string) $order->customer_name), 0, 100),
            'customer_phone' => $phone,
            'delivery_area' => $area['name'],
            'delivery_area_id' => $area['id'],
            'customer_address' => trim((string) $order->customer_address),
            'merchant_invoice_id' => (string) $order->invoice_no,
            'cash_collection_amount' => (string) $codAmount,
            'parcel_weight' => (string) $parcelWeight,
            'value' => (string) max(1, (int) round($codAmount > 0 ? $codAmount : (float) $order->total)),
            'instruction' => (string) ($credentials->shippingNoteFor($order) ?? ''),
        ];

        if ($parcelDetails !== []) {
            $payload['parcel_details_json'] = $parcelDetails;
        }

        if (is_numeric($pickupStoreId) && (int) $pickupStoreId > 0) {
            $payload['pickup_store_id'] = (int) $pickupStoreId;
        }

        try {
            $baseUrl = CourierApi::normalizeBaseUrl(
                $credentials->base_url,
                CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_REDX],
            ) ?? CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_REDX];

            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'API-ACCESS-TOKEN' => 'Bearer '.$token,
                ])
                ->connectTimeout(5)
                ->timeout(25)
                ->post($baseUrl.'/parcel', $payload);

            $data = $response->json();
            $trackingId = is_array($data)
                ? (string) ($data['tracking_id'] ?? data_get($data, 'parcel.tracking_id') ?? '')
                : '';

            if (! $response->successful() || $trackingId === '') {
                $message = is_array($data)
                    ? (string) ($data['message'] ?? $data['error'] ?? 'RedX create parcel failed.')
                    : 'RedX create parcel failed.';

                Log::warning('RedX create parcel soft-failed', [
                    'order_id' => $order->id,
                    'http_status' => $response->status(),
                    'message' => $message,
                ]);

                return $this->failure($message, $data);
            }

            return [
                'ok' => true,
                'message' => 'Sent to RedX.',
                'tracking_number' => $trackingId,
                'tracking_url' => null,
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            Log::warning('RedX create parcel exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Could not reach RedX: '.$e->getMessage());
        }
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function resolveDeliveryArea(Order $order, CourierApi $credentials, string $token): ?array
    {
        $meta = is_array($credentials->meta) ? $credentials->meta : [];
        $defaultId = $meta['default_area_id'] ?? null;
        $defaultName = $meta['default_area_name'] ?? null;

        if (is_numeric($defaultId) && (int) $defaultId > 0) {
            return [
                'id' => (int) $defaultId,
                'name' => filled($defaultName) ? (string) $defaultName : 'Default',
            ];
        }

        $district = trim((string) ($order->delivery_district ?: $order->delivery_upazila ?: ''));

        if ($district === '') {
            return null;
        }

        $cacheKey = 'redx.area.'.md5(strtolower($district).'|'.$credentials->id);

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['id'], $cached['name'])) {
            return $cached;
        }

        try {
            $baseUrl = CourierApi::normalizeBaseUrl(
                $credentials->base_url,
                CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_REDX],
            ) ?? CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_REDX];

            $response = Http::acceptJson()
                ->withHeaders([
                    'API-ACCESS-TOKEN' => 'Bearer '.$token,
                ])
                ->connectTimeout(5)
                ->timeout(20)
                ->get($baseUrl.'/areas', [
                    'district_name' => $district,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            $areas = data_get($json, 'areas', data_get($json, 'data', []));

            if (! is_array($areas) || $areas === []) {
                return null;
            }

            $first = collect($areas)->first(fn ($area) => is_array($area) && filled($area['id'] ?? $area['area_id'] ?? null));

            if (! is_array($first)) {
                return null;
            }

            $resolved = [
                'id' => (int) ($first['id'] ?? $first['area_id']),
                'name' => (string) ($first['name'] ?? $first['area_name'] ?? $district),
            ];

            Cache::put($cacheKey, $resolved, now()->addMinutes(30));

            return $resolved;
        } catch (Throwable $e) {
            Log::warning('RedX area lookup soft-failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     balance: float|null,
     *     details: list<array{label: string, value: string}>,
     *     raw: mixed
     * }
     */
    public function accountSnapshot(CourierApi $credentials): array
    {
        $token = CourierApi::normalizeBearerToken($credentials->token ?: $credentials->api_key);

        if (blank($token)) {
            return [
                'ok' => false,
                'message' => 'RedX access token is required.',
                'balance' => null,
                'details' => [],
                'raw' => null,
            ];
        }

        try {
            $baseUrl = CourierApi::normalizeBaseUrl(
                $credentials->base_url,
                CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_REDX],
            ) ?? CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_REDX];

            $response = Http::acceptJson()
                ->withHeaders([
                    'API-ACCESS-TOKEN' => 'Bearer '.$token,
                ])
                ->connectTimeout(5)
                ->timeout(20)
                ->get($baseUrl.'/pickup/stores');

            $json = $response->json();
            $stores = data_get($json, 'pickup_stores', data_get($json, 'data', data_get($json, 'stores', [])));

            if (! $response->successful()) {
                $message = is_array($json)
                    ? (string) ($json['message'] ?? $json['error'] ?? 'Could not load RedX pickup stores.')
                    : 'Could not load RedX pickup stores.';

                return [
                    'ok' => false,
                    'message' => $message,
                    'balance' => null,
                    'details' => [],
                    'raw' => $json,
                ];
            }

            $storeRows = collect(is_array($stores) ? $stores : [])
                ->filter(fn ($store): bool => is_array($store))
                ->values();

            $primary = $storeRows->first();
            $primaryName = is_array($primary)
                ? (string) ($primary['name'] ?? $primary['store_name'] ?? 'Pickup store')
                : '—';
            $primaryId = is_array($primary)
                ? (string) ($primary['id'] ?? $primary['pickup_store_id'] ?? '—')
                : '—';

            return [
                'ok' => true,
                'message' => 'RedX merchant connection verified.',
                'balance' => null,
                'details' => [
                    ['label' => 'Pickup stores', 'value' => (string) $storeRows->count()],
                    ['label' => 'Primary store', 'value' => $primaryName],
                    ['label' => 'Store ID', 'value' => $primaryId],
                    ['label' => 'Balance API', 'value' => 'Not provided by RedX — use merchant panel'],
                ],
                'raw' => $json,
            ];
        } catch (Throwable $e) {
            Log::warning('RedX account snapshot soft-failed', [
                'courier_api_id' => $credentials->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Could not reach RedX: '.$e->getMessage(),
                'balance' => null,
                'details' => [],
                'raw' => null,
            ];
        }
    }

    private function codAmount(Order $order): float
    {
        if (strtolower((string) $order->payment_status) === 'paid') {
            return 0.0;
        }

        return max(0, round((float) $order->total, 2));
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        return strlen($digits) === 11 ? $digits : null;
    }

    /**
     * @return array{ok: bool, message: string, tracking_number: ?string, tracking_url: ?string, raw: mixed}
     */
    private function failure(string $message, mixed $raw = null): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'tracking_number' => null,
            'tracking_url' => null,
            'raw' => $raw,
        ];
    }
}
