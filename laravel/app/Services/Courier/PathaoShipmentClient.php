<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PathaoShipmentClient
{
    public function __construct(private PathaoTokenService $tokens) {}

    /**
     * @return array{ok: bool, message: string, tracking_number: ?string, tracking_url: ?string, raw: mixed}
     */
    public function create(Order $order, CourierApi $credentials): array
    {
        $token = $this->ensureAccessToken($credentials);

        if ($token === null) {
            return $this->failure('Pathao access token is missing. Generate a token under Courier API settings.');
        }

        $storeId = $this->resolveStoreId($credentials, $token);

        if ($storeId === null) {
            return $this->failure('Could not resolve a Pathao store. Save a store ID in Courier API settings or ensure stores exist.');
        }

        $phone = $this->normalizePhone((string) $order->customer_phone);

        if ($phone === null) {
            return $this->failure('Customer phone must be an 11-digit Bangladeshi number.');
        }

        $order->loadMissing('items');
        $quantity = max(1, (int) $order->items->sum('qty'));

        $payload = [
            'store_id' => $storeId,
            'merchant_order_id' => (string) $order->invoice_no,
            'recipient_name' => mb_substr(trim((string) $order->customer_name), 0, 100),
            'recipient_phone' => $phone,
            'recipient_address' => mb_substr(trim((string) $order->customer_address), 0, 220),
            'delivery_type' => 48,
            'item_type' => 2,
            'item_quantity' => $quantity,
            'item_weight' => 0.5,
            'amount_to_collect' => (int) round($this->codAmount($order)),
            'item_description' => $credentials->sendsProductNames()
                ? $this->itemSummary($order)
                : 'Order items',
            'special_instruction' => $credentials->shippingNoteFor($order),
        ];

        try {
            $baseUrl = $this->normalizeBaseUrl($credentials->base_url);

            $response = Http::acceptJson()
                ->asJson()
                ->withToken($token)
                ->connectTimeout(5)
                ->timeout(25)
                ->post($baseUrl.'/aladdin/api/v1/orders', array_filter(
                    $payload,
                    fn ($value) => $value !== null && $value !== '',
                ));

            $data = $response->json();
            $consignmentId = $this->extractConsignmentId(is_array($data) ? $data : null);

            if (! $response->successful() || blank($consignmentId)) {
                $message = is_array($data)
                    ? (string) ($data['message'] ?? $data['error'] ?? 'Pathao create order failed.')
                    : 'Pathao create order failed.';

                Log::warning('Pathao create order soft-failed', [
                    'order_id' => $order->id,
                    'http_status' => $response->status(),
                    'message' => $message,
                ]);

                return $this->failure($message, $data);
            }

            return [
                'ok' => true,
                'message' => 'Sent to Pathao.',
                'tracking_number' => (string) $consignmentId,
                'tracking_url' => null,
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            Log::warning('Pathao create order exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Could not reach Pathao: '.$e->getMessage());
        }
    }

    private function ensureAccessToken(CourierApi $credentials): ?string
    {
        $token = CourierApi::normalizeBearerToken($credentials->token);

        if (filled($token)) {
            return $token;
        }

        $result = $this->tokens->generateFor($credentials);

        if (! $result['ok'] || blank($result['access_token'])) {
            return null;
        }

        return (string) $result['access_token'];
    }

    private function resolveStoreId(CourierApi $credentials, string $token): ?int
    {
        $meta = is_array($credentials->meta) ? $credentials->meta : [];
        $configured = $meta['store_id'] ?? null;

        if (is_numeric($configured) && (int) $configured > 0) {
            return (int) $configured;
        }

        $cacheKey = 'pathao.store_id.'.$credentials->id;

        $cached = Cache::get($cacheKey);
        if (is_numeric($cached) && (int) $cached > 0) {
            return (int) $cached;
        }

        try {
            $baseUrl = $this->normalizeBaseUrl($credentials->base_url);

            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(5)
                ->timeout(20)
                ->get($baseUrl.'/aladdin/api/v1/stores');

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            $stores = data_get($json, 'data.data', data_get($json, 'data', []));

            if (! is_array($stores) || $stores === []) {
                return null;
            }

            $first = collect($stores)->first(function ($store): bool {
                if (! is_array($store)) {
                    return false;
                }

                $id = $store['store_id'] ?? $store['id'] ?? null;

                return is_numeric($id) && (int) $id > 0;
            });

            if (! is_array($first)) {
                return null;
            }

            $storeId = (int) ($first['store_id'] ?? $first['id']);

            Cache::put($cacheKey, $storeId, now()->addMinutes(30));

            $meta['store_id'] = $storeId;
            $credentials->meta = $meta;
            $credentials->save();

            return $storeId;
        } catch (Throwable $e) {
            Log::warning('Pathao store lookup soft-failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function extractConsignmentId(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        $candidates = [
            data_get($data, 'data.consignment_id'),
            data_get($data, 'consignment.consignment_id'),
            data_get($data, 'consignment_id'),
        ];

        foreach ($candidates as $candidate) {
            if (filled($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
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
        $token = $this->ensureAccessToken($credentials);

        if ($token === null) {
            return [
                'ok' => false,
                'message' => 'Pathao access token is missing. Generate a token under Delivery Methods.',
                'balance' => null,
                'details' => [],
                'raw' => null,
            ];
        }

        try {
            $baseUrl = $this->normalizeBaseUrl($credentials->base_url);

            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(5)
                ->timeout(20)
                ->get($baseUrl.'/aladdin/api/v1/stores');

            $json = $response->json();
            $stores = data_get($json, 'data.data', data_get($json, 'data', []));

            if (! $response->successful() || ! is_array($stores)) {
                $message = is_array($json)
                    ? (string) ($json['message'] ?? $json['error'] ?? 'Could not load Pathao stores.')
                    : 'Could not load Pathao stores.';

                return [
                    'ok' => false,
                    'message' => $message,
                    'balance' => null,
                    'details' => [],
                    'raw' => $json,
                ];
            }

            $storeRows = collect($stores)
                ->filter(fn ($store): bool => is_array($store))
                ->values();

            $primary = $storeRows->first();
            $primaryName = is_array($primary)
                ? (string) ($primary['store_name'] ?? $primary['name'] ?? 'Store')
                : '—';
            $primaryId = is_array($primary)
                ? (string) ($primary['store_id'] ?? $primary['id'] ?? '—')
                : '—';

            return [
                'ok' => true,
                'message' => 'Pathao merchant connection verified.',
                'balance' => null,
                'details' => [
                    ['label' => 'Stores linked', 'value' => (string) $storeRows->count()],
                    ['label' => 'Primary store', 'value' => $primaryName],
                    ['label' => 'Store ID', 'value' => $primaryId],
                    ['label' => 'Balance API', 'value' => 'Not provided by Pathao — use merchant panel'],
                ],
                'raw' => $json,
            ];
        } catch (Throwable $e) {
            Log::warning('Pathao account snapshot soft-failed', [
                'courier_api_id' => $credentials->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Could not reach Pathao: '.$e->getMessage(),
                'balance' => null,
                'details' => [],
                'raw' => null,
            ];
        }
    }

    private function normalizeBaseUrl(?string $url): string
    {
        $base = CourierApi::normalizeBaseUrl(
            $url,
            CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_PATHAO],
        ) ?? CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_PATHAO];

        return preg_replace('#/aladdin/?$#i', '', $base) ?: $base;
    }

    private function codAmount(Order $order): float
    {
        if (strtolower((string) $order->payment_status) === 'paid') {
            return 0.0;
        }

        return max(0, round((float) $order->total, 2));
    }

    private function itemSummary(Order $order): string
    {
        $parts = $order->items
            ->map(fn ($item) => trim((string) $item->product_name).' x'.(int) $item->qty)
            ->filter()
            ->take(5)
            ->values()
            ->all();

        $summary = $parts === [] ? 'Order items' : implode(', ', $parts);

        return mb_substr($summary, 0, 200);
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
