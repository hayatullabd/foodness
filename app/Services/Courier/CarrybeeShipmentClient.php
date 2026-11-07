<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CarrybeeShipmentClient
{
    public const DEFAULT_BASE_URL = 'https://developers.carrybee.com';

    /**
     * Book one parcel on Carrybee.
     * Endpoint: POST /api/v2/orders
     *
     * @return array{ok: bool, message: string, tracking_number: ?string, tracking_url: ?string, raw: mixed}
     */
    public function create(Order $order, CourierApi $credentials): array
    {
        if (! $this->hasCredentials($credentials)) {
            return $this->failure('Carrybee Client-ID, Client-Secret, and Client-Context are required.');
        }

        $phone = $this->normalizePhone((string) $order->customer_phone);

        if ($phone === null) {
            return $this->failure('Customer phone must be an 11-digit Bangladeshi mobile number (01XXXXXXXXX).');
        }

        $address = $this->recipientAddress($order);

        if (mb_strlen($address) < 10) {
            return $this->failure('Customer address must be at least 10 characters for Carrybee.');
        }

        $storeId = $this->resolveStoreId($credentials);

        if ($storeId === null) {
            return $this->failure('Carrybee store ID is missing. Add it under Delivery Methods, or create a pickup store in Carrybee.');
        }

        $order->loadMissing('items');

        $meta = is_array($credentials->meta) ? $credentials->meta : [];
        $weight = (int) ($meta['item_weight'] ?? 500);
        $weight = max(1, min(25000, $weight));
        $deliveryType = (int) ($meta['delivery_type'] ?? 1);
        $productType = (int) ($meta['product_type'] ?? 1);

        $payload = [
            'store_id' => $storeId,
            'merchant_order_id' => mb_substr(trim((string) $order->invoice_no), 0, 49),
            'delivery_type' => in_array($deliveryType, [1, 2], true) ? $deliveryType : 1,
            'product_type' => in_array($productType, [1, 2, 3], true) ? $productType : 1,
            'recipient_phone' => $phone,
            'recipient_name' => mb_substr(trim((string) $order->customer_name), 0, 99),
            'recipient_address' => mb_substr($address, 0, 200),
            'item_weight' => $weight,
            'item_quantity' => max(1, min(200, (int) $order->items->sum('qty'))),
            'collectable_amount' => $this->codAmount($order),
        ];

        $description = $credentials->sendsProductNames()
            ? $this->itemSummary($order)
            : 'Order items';

        if ($description !== '') {
            $payload['product_description'] = mb_substr($description, 0, 255);
        }

        $note = $credentials->shippingNoteFor($order);

        if (filled($note)) {
            $payload['special_instruction'] = mb_substr(trim((string) $note), 0, 255);
        }

        $location = $this->resolveAddress($credentials, $payload['recipient_address']);

        if ($location !== null) {
            $payload['city_id'] = $location['city_id'];
            $payload['zone_id'] = $location['zone_id'];
        }

        try {
            $response = $this->request($credentials)
                ->post($this->baseUrl($credentials).'/api/v2/orders', $payload);

            $data = $response->json();
            $consignmentId = is_array($data)
                ? (string) data_get($data, 'data.order.consignment_id', '')
                : '';

            if (! $response->successful() || $consignmentId === '' || (is_array($data) && ($data['error'] ?? false) === true)) {
                $message = $this->extractErrorMessage($response, is_array($data) ? $data : null);

                Log::warning('Carrybee create order soft-failed', [
                    'order_id' => $order->id,
                    'http_status' => $response->status(),
                    'message' => $message,
                ]);

                return $this->failure($message, $data);
            }

            return [
                'ok' => true,
                'message' => 'Sent to Carrybee.',
                'tracking_number' => $consignmentId,
                'tracking_url' => null,
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            Log::warning('Carrybee create order exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Could not reach Carrybee: '.$e->getMessage());
        }
    }

    /**
     * Endpoint: GET /api/v2/orders/{consignment_id}/details
     */
    public function transferStatus(string $consignmentId, CourierApi $credentials): ?string
    {
        $consignmentId = trim($consignmentId);

        if ($consignmentId === '' || ! $this->hasCredentials($credentials)) {
            return null;
        }

        try {
            $response = $this->request($credentials)
                ->get($this->baseUrl($credentials).'/api/v2/orders/'.rawurlencode($consignmentId).'/details');

            $data = $response->json();

            if (! $response->successful() || ! is_array($data) || ($data['error'] ?? false) === true) {
                return null;
            }

            $status = data_get($data, 'data.transfer_status');

            return is_string($status) && trim($status) !== '' ? trim($status) : null;
        } catch (Throwable $e) {
            Log::warning('Carrybee status check soft-failed', [
                'consignment_id' => $consignmentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{ok: bool, message: string, balance: float|null, details: list<array{label: string, value: string}>, raw: mixed}
     */
    public function accountSnapshot(CourierApi $credentials): array
    {
        if (! $this->hasCredentials($credentials)) {
            return [
                'ok' => false,
                'message' => 'Carrybee Client-ID, Client-Secret, and Client-Context are required.',
                'balance' => null,
                'details' => [],
                'raw' => null,
            ];
        }

        try {
            $response = $this->request($credentials)
                ->get($this->baseUrl($credentials).'/api/v2/stores');

            $data = $response->json();
            $stores = is_array($data) ? data_get($data, 'data.stores', []) : [];

            if (! $response->successful() || ! is_array($data) || ($data['error'] ?? false) === true || ! is_array($stores)) {
                return [
                    'ok' => false,
                    'message' => $this->extractErrorMessage($response, is_array($data) ? $data : null),
                    'balance' => null,
                    'details' => [],
                    'raw' => $data,
                ];
            }

            $active = collect($stores)->filter(fn ($store) => is_array($store) && ($store['is_active'] ?? false))->count();
            $default = collect($stores)->first(fn ($store) => is_array($store) && ($store['is_default_pickup_store'] ?? false));
            $defaultName = is_array($default) ? (string) ($default['name'] ?? '') : '';

            return [
                'ok' => true,
                'message' => 'Carrybee credentials accepted.',
                'balance' => null,
                'details' => [
                    ['label' => 'Environment', 'value' => $credentials->carrybeeEnvironment()],
                    ['label' => 'Stores', 'value' => (string) count($stores)],
                    ['label' => 'Active stores', 'value' => (string) $active],
                    ['label' => 'Default pickup', 'value' => $defaultName !== '' ? $defaultName : 'Not set'],
                ],
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Could not reach Carrybee: '.$e->getMessage(),
                'balance' => null,
                'details' => [],
                'raw' => null,
            ];
        }
    }

    public function baseUrl(CourierApi $credentials): string
    {
        return CourierApi::normalizeBaseUrl(
            $credentials->base_url,
            self::DEFAULT_BASE_URL,
        ) ?? self::DEFAULT_BASE_URL;
    }

    private function hasCredentials(CourierApi $credentials): bool
    {
        return filled($credentials->api_key)
            && filled($credentials->secret_key)
            && filled($credentials->username);
    }

    private function request(CourierApi $credentials): \Illuminate\Http\Client\PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'Client-ID' => trim((string) $credentials->api_key),
                'Client-Secret' => trim((string) $credentials->secret_key),
                'Client-Context' => trim((string) $credentials->username),
            ])
            ->connectTimeout(8)
            ->timeout(25);
    }

    private function resolveStoreId(CourierApi $credentials): ?string
    {
        $stored = trim((string) $credentials->metaValue('store_id', ''));

        if ($stored !== '') {
            return $stored;
        }

        try {
            $response = $this->request($credentials)
                ->get($this->baseUrl($credentials).'/api/v2/stores');

            $stores = $response->json('data.stores');

            if (! $response->successful() || ! is_array($stores)) {
                return null;
            }

            $pick = collect($stores)->first(function ($store): bool {
                return is_array($store)
                    && filled($store['id'] ?? null)
                    && ($store['is_default_pickup_store'] ?? false)
                    && ($store['is_active'] ?? true);
            });

            if (! is_array($pick)) {
                $pick = collect($stores)->first(fn ($store) => is_array($store) && filled($store['id'] ?? null) && ($store['is_active'] ?? true));
            }

            return is_array($pick) ? (string) $pick['id'] : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{city_id: int, zone_id: int}|null
     */
    private function resolveAddress(CourierApi $credentials, string $address): ?array
    {
        if (mb_strlen($address) < 10) {
            return null;
        }

        try {
            $response = $this->request($credentials)
                ->post($this->baseUrl($credentials).'/api/v2/address-details', [
                    'query' => mb_substr($address, 0, 200),
                ]);

            $data = $response->json();
            $cityId = is_array($data) ? data_get($data, 'data.city_id') : null;
            $zoneId = is_array($data) ? data_get($data, 'data.zone_id') : null;

            if (! $response->successful() || ! is_numeric($cityId) || ! is_numeric($zoneId)) {
                return null;
            }

            return [
                'city_id' => (int) $cityId,
                'zone_id' => (int) $zoneId,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function recipientAddress(Order $order): string
    {
        $parts = array_filter([
            trim((string) $order->customer_address),
            trim((string) $order->delivery_upazila),
            trim((string) $order->delivery_district),
            trim((string) $order->delivery_division),
        ], fn (string $part): bool => $part !== '');

        $address = implode(', ', $parts);
        $collapsed = preg_replace('/\s+/', ' ', $address) ?? $address;

        return trim($collapsed);
    }

    private function codAmount(Order $order): int
    {
        if (in_array(strtolower((string) $order->payment_status), ['paid', 'completed'], true)) {
            return 0;
        }

        return max(0, min(100000, (int) round((float) $order->total)));
    }

    private function itemSummary(Order $order): string
    {
        $parts = $order->items
            ->map(fn ($item) => trim((string) $item->product_name).' x'.(int) $item->qty)
            ->filter()
            ->take(5)
            ->values()
            ->all();

        return $parts === [] ? 'Order items' : implode(', ', $parts);
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

        if (strlen($digits) !== 11 || ! str_starts_with($digits, '01')) {
            return null;
        }

        return $digits;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function extractErrorMessage(Response $response, ?array $data): string
    {
        if (is_array($data) && filled($data['message'] ?? null)) {
            $message = (string) $data['message'];
            $causes = $data['causes'] ?? null;

            if (is_array($causes) && $causes !== []) {
                $fields = implode(', ', array_keys($causes));

                if ($fields !== '') {
                    $message .= ' ('.$fields.')';
                }
            }

            return $message;
        }

        return 'Carrybee request failed (HTTP '.$response->status().').';
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
