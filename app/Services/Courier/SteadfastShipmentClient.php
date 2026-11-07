<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SteadfastShipmentClient
{
    /**
     * Live Steadfast merchant API host from the API guide (Packzy portal).
     * Note: portal.steadfast.com.bd does not resolve in DNS — do not use it.
     */
    public const DEFAULT_BASE_URL = 'https://portal.packzy.com/api/v1';

    /**
     * Book one parcel on Steadfast.
     * Endpoint: POST /create_order
     *
     * @return array{ok: bool, message: string, tracking_number: ?string, tracking_url: ?string, raw: mixed}
     */
    public function create(Order $order, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return $this->failure('Steadfast API key and secret key are required. Add them under Delivery Methods from the Steadfast API guide.');
        }

        $phone = $this->normalizePhone((string) $order->customer_phone);

        if ($phone === null) {
            return $this->failure('Customer phone must be an 11-digit Bangladeshi mobile number (01XXXXXXXXX).');
        }

        $invoice = $this->normalizeInvoice((string) $order->invoice_no);

        if ($invoice === null) {
            return $this->failure('Invoice must contain only letters, numbers, hyphens, or underscores (max 100).');
        }

        $order->loadMissing('items');

        $payload = [
            'invoice' => $invoice,
            'recipient_name' => $this->sanitizeSteadfastText((string) $order->customer_name, 100),
            'recipient_phone' => $phone,
            'recipient_address' => $this->sanitizeSteadfastText((string) $order->customer_address, 490),
            'cod_amount' => $this->codAmount($order),
            'delivery_type' => 0,
            'total_lot' => max(1, (int) $order->items->sum('qty')),
            'item_description' => $this->sanitizeSteadfastText(
                $credentials->sendsProductNames() ? $this->itemSummary($order) : 'Order items',
                255,
            ),
        ];

        $note = $credentials->shippingNoteFor($order);

        if (filled($note)) {
            $payload['note'] = $this->sanitizeSteadfastText((string) $note, 480);
        }

        try {
            $endpoint = $this->createOrderEndpoint((string) ($credentials->base_url ?? ''));

            $response = $this->postCreateOrder($endpoint, $apiKey, $secretKey, $payload);

            // Packzy → steadfast redirects drop custom auth headers; retry once on the resolved host.
            if (in_array($response->status(), [301, 302, 307, 308], true)) {
                $location = $response->header('Location');

                if (filled($location)) {
                    $response = $this->postCreateOrder($location, $apiKey, $secretKey, $payload);
                }
            }

            $data = $response->json();
            $parsed = $this->parseCreateResponse(is_array($data) ? $data : null);

            if (! $response->successful() || blank($parsed['tracking_number'])) {
                $message = $this->extractErrorMessage($response, is_array($data) ? $data : null);

                Log::warning('Steadfast create order soft-failed', [
                    'order_id' => $order->id,
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'message' => $message,
                    'body' => is_array($data) ? $data : $response->body(),
                ]);

                return $this->failure($message, $data);
            }

            $trackingUrl = filled($parsed['tracking_link'])
                ? $parsed['tracking_link']
                : (filled($parsed['tracking_code']) ? 'https://steadfast.com.bd/t/'.$parsed['tracking_code'] : null);

            return [
                'ok' => true,
                'message' => 'Sent to Steadfast.',
                'tracking_number' => $parsed['tracking_number'],
                'tracking_url' => $trackingUrl,
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            Log::warning('Steadfast create order exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Could not reach Steadfast: '.$e->getMessage());
        }
    }

    /**
     * Book up to 500 parcels in one request.
     * Endpoint: POST /create_order/bulk-order
     *
     * @param  Collection<int, Order>|list<Order>  $orders
     * @return array{ok: bool, message: string, raw: mixed}
     */
    public function createBulk(Collection|array $orders, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return ['ok' => false, 'message' => 'Steadfast credentials required.', 'raw' => null];
        }

        $items = [];

        foreach ($orders as $order) {
            $phone = $this->normalizePhone((string) $order->customer_phone);
            $invoice = $this->normalizeInvoice((string) $order->invoice_no);

            if ($phone === null || $invoice === null) {
                continue;
            }

            $order->loadMissing('items');

            $row = [
                'invoice' => $invoice,
                'recipient_name' => $this->sanitizeSteadfastText((string) $order->customer_name, 100),
                'recipient_phone' => $phone,
                'recipient_address' => $this->sanitizeSteadfastText((string) $order->customer_address, 490),
                'cod_amount' => $this->codAmount($order),
                'total_lot' => max(1, (int) $order->items->sum('qty')),
                'item_description' => $this->sanitizeSteadfastText($this->itemSummary($order), 255),
            ];

            $note = $credentials->shippingNoteFor($order);

            if (filled($note)) {
                $row['note'] = $this->sanitizeSteadfastText((string) $note, 480);
            }

            $items[] = $row;
        }

        if ($items === []) {
            return ['ok' => false, 'message' => 'No valid orders to book.', 'raw' => null];
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                ])
                ->connectTimeout(10)
                ->timeout(60)
                ->post($baseUrl.'/create_order/bulk-order', ['data' => $items]);

            $data = $response->json();

            return [
                'ok' => $response->successful(),
                'message' => $response->successful() ? 'Bulk order created.' : 'Bulk order booking failed.',
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'raw' => null];
        }
    }

    /**
     * Service check endpoint (no credentials needed).
     * Endpoint: GET /ping
     *
     * @return array{ok: bool, status: int, message: string, raw: mixed}
     */
    public function ping(?string $storedUrl = null): array
    {
        try {
            $baseUrl = self::resolveBaseUrl($storedUrl);
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->get($baseUrl.'/ping');

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Steadfast API reachable.' : 'Steadfast ping failed (HTTP '.$response->status().').',
                'raw' => $response->json() ?? $response->body(),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'message' => 'Could not connect to Steadfast: '.$e->getMessage(),
                'raw' => null,
            ];
        }
    }

    /**
     * Where a parcel is, with return status if coming back.
     * Endpoint: GET /status_with_return_status_by_cid/{consignment_id}
     *
     * @return array{ok: bool, delivery_status: ?string, raw: mixed}
     */
    public function statusWithReturnByCid(int|string $consignmentId, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return ['ok' => false, 'delivery_status' => null, 'raw' => null];
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->withoutRedirecting()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                    'Content-Type' => 'application/json',
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->get($baseUrl.'/status_with_return_status_by_cid/'.rawurlencode((string) $consignmentId));

            $data = $response->json();
            $status = is_array($data) ? ($data['delivery_status'] ?? $data['status'] ?? null) : null;

            return [
                'ok' => $response->successful(),
                'delivery_status' => is_string($status) ? $status : null,
                'raw' => $data,
            ];
        } catch (Throwable) {
            return ['ok' => false, 'delivery_status' => null, 'raw' => null];
        }
    }

    /**
     * Where a parcel is, by consignment id.
     * Endpoint: GET /status_by_cid/{consignment_id}
     *
     * @return array{ok: bool, delivery_status: ?string, raw: mixed}
     */
    public function statusByCid(int|string $consignmentId, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return ['ok' => false, 'delivery_status' => null, 'raw' => null];
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->withoutRedirecting()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                    'Content-Type' => 'application/json',
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->get($baseUrl.'/status_by_cid/'.rawurlencode((string) $consignmentId));

            $data = $response->json();
            $status = is_array($data) ? ($data['delivery_status'] ?? $data['status'] ?? null) : null;

            return [
                'ok' => $response->successful(),
                'delivery_status' => is_string($status) ? $status : null,
                'raw' => $data,
            ];
        } catch (Throwable) {
            return ['ok' => false, 'delivery_status' => null, 'raw' => null];
        }
    }

    /**
     * Where a parcel is, by tracking code.
     * Endpoint: GET /status_by_trackingcode/{tracking_code}
     *
     * @return array{ok: bool, delivery_status: ?string, raw: mixed}
     */
    public function statusByTrackingCode(string $trackingCode, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return ['ok' => false, 'delivery_status' => null, 'raw' => null];
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->withoutRedirecting()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                    'Content-Type' => 'application/json',
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->get($baseUrl.'/status_by_trackingcode/'.rawurlencode($trackingCode));

            $data = $response->json();
            $status = is_array($data) ? ($data['delivery_status'] ?? $data['status'] ?? null) : null;

            return [
                'ok' => $response->successful(),
                'delivery_status' => is_string($status) ? $status : null,
                'raw' => $data,
            ];
        } catch (Throwable) {
            return ['ok' => false, 'delivery_status' => null, 'raw' => null];
        }
    }

    /**
     * Where a parcel is, by invoice number.
     * Endpoint: GET /status_by_invoice/{invoice}
     *
     * @return array{ok: bool, delivery_status: ?string, raw: mixed}
     */
    public function statusByInvoice(string $invoice, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return ['ok' => false, 'delivery_status' => null, 'raw' => null];
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->withoutRedirecting()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                    'Content-Type' => 'application/json',
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->get($baseUrl.'/status_by_invoice/'.rawurlencode($invoice));

            $data = $response->json();
            $status = is_array($data) ? ($data['delivery_status'] ?? $data['status'] ?? null) : null;

            return [
                'ok' => $response->successful(),
                'delivery_status' => is_string($status) ? $status : null,
                'raw' => $data,
            ];
        } catch (Throwable) {
            return ['ok' => false, 'delivery_status' => null, 'raw' => null];
        }
    }

    /**
     * Fetch every step a parcel has been through.
     * Endpoint: GET /trackings_by_invoice/{invoice}
     *
     * @return array{ok: bool, message: string, tracking: list<array<string, mixed>>, raw: mixed}
     */
    public function trackingsByInvoice(string $invoice, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return ['ok' => false, 'message' => 'Steadfast API credentials required.', 'tracking' => [], 'raw' => null];
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->get($baseUrl.'/trackings_by_invoice/'.rawurlencode(trim($invoice)));

            $data = $response->json();

            if (! $response->successful() || ! is_array($data)) {
                return ['ok' => false, 'message' => 'Could not fetch Steadfast tracking history.', 'tracking' => [], 'raw' => $data];
            }

            $trackingList = is_array($data['tracking'] ?? null) ? $data['tracking'] : [];

            return [
                'ok' => true,
                'message' => 'Tracking history loaded.',
                'tracking' => $trackingList,
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'tracking' => [], 'raw' => null];
        }
    }

    /**
     * Ask for a parcel to be brought back before delivery.
     * Endpoint: POST /create_return_request
     *
     * @return array{ok: bool, message: string, raw: mixed}
     */
    public function createReturnRequest(string $invoiceOrCid, string $reason, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return ['ok' => false, 'message' => 'Steadfast API credentials required.', 'raw' => null];
        }

        $payload = [
            'reason' => $this->sanitizeSteadfastText($reason, 500),
        ];

        if (ctype_digit(trim($invoiceOrCid))) {
            $payload['consignment_id'] = (int) trim($invoiceOrCid);
        } else {
            $payload['invoice'] = trim($invoiceOrCid);
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                ])
                ->connectTimeout(5)
                ->timeout(20)
                ->post($baseUrl.'/create_return_request', $payload);

            $data = $response->json();

            if (in_array($response->status(), [200, 201], true)) {
                return [
                    'ok' => true,
                    'message' => 'Return request created successfully.',
                    'raw' => $data,
                ];
            }

            $message = is_array($data) ? ($data['message'] ?? $data['error'] ?? 'Return request failed.') : 'Return request failed.';

            return [
                'ok' => false,
                'message' => is_string($message) ? $message : 'Return request failed.',
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'raw' => null];
        }
    }

    /**
     * Ask for a rider to collect from one of your saved pickup addresses.
     * Endpoint: POST /create_pickup_request
     *
     * @param  array{address_id: int, police_station_id: int, address: string, contact_number: string, note?: string, estim_qty?: int}  $data
     * @return array{ok: bool, message: string, raw: mixed}
     */
    public function createPickupRequest(array $data, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return ['ok' => false, 'message' => 'Steadfast API credentials required.', 'raw' => null];
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                ])
                ->connectTimeout(5)
                ->timeout(20)
                ->post($baseUrl.'/create_pickup_request', $data);

            $body = $response->json();

            return [
                'ok' => in_array($response->status(), [200, 201], true),
                'message' => is_array($body) ? (string) ($body['message'] ?? 'Pickup request submitted.') : 'Pickup request submitted.',
                'raw' => $body,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'raw' => null];
        }
    }

    /**
     * Fetch payouts made to you.
     * Endpoint: GET /payments
     *
     * @return array{ok: bool, payments: list<array<string, mixed>>, raw: mixed}
     */
    public function getPayments(CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return ['ok' => false, 'payments' => [], 'raw' => null];
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                ])
                ->connectTimeout(5)
                ->timeout(20)
                ->get($baseUrl.'/payments');

            $data = $response->json();
            $payments = is_array($data) && is_array($data['payments'] ?? null) ? $data['payments'] : [];

            return [
                'ok' => $response->successful(),
                'payments' => $payments,
                'raw' => $data,
            ];
        } catch (Throwable) {
            return ['ok' => false, 'payments' => [], 'raw' => null];
        }
    }

    /**
     * Check customer delivery record via Steadfast fraud check endpoint.
     * Endpoint: GET /fraud_check/score/{phone}
     *
     * @return array{
     *     ok: bool,
     *     message: string,
     *     delivery_ratio: int|null,
     *     cancellation_ratio: int|null,
     *     volume_band: string|null,
     *     volume_range: string|null,
     *     delivered_count: int|null,
     *     cancelled_count: int|null,
     *     total_reports: int|null,
     *     fraud_categories: array<string, int>,
     *     raw: mixed
     * }
     */
    public function checkCustomerHistory(string $phone, CourierApi $credentials): array
    {
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return [
                'ok' => false,
                'message' => 'Steadfast API credentials required.',
                'delivery_ratio' => null,
                'cancellation_ratio' => null,
                'volume_band' => null,
                'volume_range' => null,
                'delivered_count' => null,
                'cancelled_count' => null,
                'total_reports' => null,
                'fraud_categories' => [],
                'raw' => null,
            ];
        }

        $phone = $this->normalizePhone($phone);

        if ($phone === null) {
            return [
                'ok' => false,
                'message' => 'Invalid phone number format.',
                'delivery_ratio' => null,
                'cancellation_ratio' => null,
                'volume_band' => null,
                'volume_range' => null,
                'delivered_count' => null,
                'cancelled_count' => null,
                'total_reports' => null,
                'fraud_categories' => [],
                'raw' => null,
            ];
        }

        try {
            $baseUrl = self::resolveBaseUrl($credentials->base_url);
            $response = Http::acceptJson()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->get($baseUrl.'/fraud_check/score/'.$phone);

            $data = $response->json();

            if (! $response->successful() || ! is_array($data)) {
                return [
                    'ok' => false,
                    'message' => 'Steadfast customer check failed.',
                    'delivery_ratio' => null,
                    'cancellation_ratio' => null,
                    'volume_band' => null,
                    'volume_range' => null,
                    'delivered_count' => null,
                    'cancelled_count' => null,
                    'total_reports' => null,
                    'fraud_categories' => [],
                    'raw' => $data,
                ];
            }

            return [
                'ok' => true,
                'message' => 'Steadfast customer record loaded.',
                'delivery_ratio' => isset($data['delivery_ratio']) ? (int) $data['delivery_ratio'] : null,
                'cancellation_ratio' => isset($data['cancellation_ratio']) ? (int) $data['cancellation_ratio'] : null,
                'volume_band' => $data['volume_band'] ?? null,
                'volume_range' => $data['volume_range'] ?? null,
                'delivered_count' => isset($data['delivered_count']) ? (int) $data['delivered_count'] : null,
                'cancelled_count' => isset($data['cancelled_count']) ? (int) $data['cancelled_count'] : null,
                'total_reports' => isset($data['total_reports']) ? (int) $data['total_reports'] : null,
                'fraud_categories' => is_array($data['fraud_categories'] ?? null) ? $data['fraud_categories'] : [],
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'delivery_ratio' => null,
                'cancellation_ratio' => null,
                'volume_band' => null,
                'volume_range' => null,
                'delivered_count' => null,
                'cancelled_count' => null,
                'total_reports' => null,
                'fraud_categories' => [],
                'raw' => null,
            ];
        }
    }

    /**
     * Sanitizes text according to Steadfast documentation:
     * Replaces each of { } ; < > $ with a space, collapses spaces, and truncates.
     */
    public function sanitizeSteadfastText(string $text, int $maxLength): string
    {
        $sanitized = str_replace(['{', '}', ';', '<', '>', '$'], ' ', $text);
        $collapsed = preg_replace('/\s+/', ' ', $sanitized) ?? $sanitized;

        return mb_substr(trim($collapsed), 0, $maxLength);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postCreateOrder(string $endpoint, string $apiKey, string $secretKey, array $payload): Response
    {
        return Http::acceptJson()
            ->asJson()
            ->withoutRedirecting()
            ->withHeaders([
                'Api-Key' => $apiKey,
                'Secret-Key' => $secretKey,
                'Content-Type' => 'application/json',
            ])
            ->connectTimeout(5)
            ->timeout(30)
            ->post($endpoint, $payload);
    }

    public static function resolveBaseUrl(?string $storedUrl): string
    {
        $base = CourierApi::normalizeBaseUrl(
            $storedUrl,
            self::DEFAULT_BASE_URL,
        ) ?? self::DEFAULT_BASE_URL;

        $base = preg_replace('#/create_order(?:/.*)?$#i', '', $base) ?: $base;
        $base = rtrim($base, '/');

        // Some docs still mention portal.steadfast.com.bd, but that host has no DNS.
        if (str_contains(strtolower($base), 'portal.steadfast.com.bd')) {
            return self::DEFAULT_BASE_URL;
        }

        return $base;
    }

    private function createOrderEndpoint(string $storedUrl): string
    {
        $base = self::resolveBaseUrl($storedUrl);

        if (stripos($base, 'create_order') !== false) {
            return $base;
        }

        return $base.'/create_order';
    }

    /**
     * @param  array<string, mixed>|null  $res
     * @return array{tracking_number: ?string, tracking_code: ?string, tracking_link: ?string}
     */
    private function parseCreateResponse(?array $res): array
    {
        $out = ['tracking_number' => null, 'tracking_code' => null, 'tracking_link' => null];

        if (! is_array($res)) {
            return $out;
        }

        $consignment = $res['consignment'] ?? null;

        if (is_array($consignment) && filled($consignment['consignment_id'] ?? null)) {
            $out['tracking_number'] = (string) $consignment['consignment_id'];
        } elseif (filled($res['consignment_id'] ?? null)) {
            $out['tracking_number'] = (string) $res['consignment_id'];
        } elseif (is_array($res['data'] ?? null) && filled($res['data']['consignment_id'] ?? null)) {
            $out['tracking_number'] = (string) $res['data']['consignment_id'];
        }

        if (is_array($consignment) && filled($consignment['tracking_code'] ?? null)) {
            $out['tracking_code'] = (string) $consignment['tracking_code'];
        } elseif (filled($res['tracking_code'] ?? null)) {
            $out['tracking_code'] = (string) $res['tracking_code'];
        } elseif (is_array($res['data'] ?? null) && filled($res['data']['tracking_code'] ?? null)) {
            $out['tracking_code'] = (string) $res['data']['tracking_code'];
        }

        if (is_array($consignment) && filled($consignment['tracking_link'] ?? null)) {
            $out['tracking_link'] = (string) $consignment['tracking_link'];
        } elseif (filled($res['tracking_link'] ?? null)) {
            $out['tracking_link'] = (string) $res['tracking_link'];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function extractErrorMessage(Response $response, ?array $data): string
    {
        $status = $response->status();

        if ($status === 401 || $status === 403) {
            return 'Steadfast rejected the API credentials (HTTP '.$status.'). Open Delivery Methods and paste Api-Key + Secret-Key from https://steadfast.com.bd/user/api/guide. Base URL must be https://portal.packzy.com/api/v1.';
        }

        if (is_array($data)) {
            if (filled($data['message'] ?? null)) {
                return (string) $data['message'];
            }

            if (filled($data['error'] ?? null)) {
                return is_string($data['error']) ? $data['error'] : 'Steadfast create order failed.';
            }

            if (is_array($data['errors'] ?? null)) {
                $parts = collect($data['errors'])
                    ->flatten()
                    ->filter(fn ($value) => filled($value))
                    ->map(fn ($value) => (string) $value)
                    ->values()
                    ->all();

                if ($parts !== []) {
                    return implode(' ', $parts);
                }
            }
        }

        $body = trim($response->body());

        if ($body !== '' && strlen($body) < 240) {
            return 'Steadfast create order failed (HTTP '.$status.'): '.$body;
        }

        return 'Steadfast create order failed (HTTP '.$status.').';
    }

    private function codAmount(Order $order): int
    {
        if (strtolower((string) $order->payment_status) === 'paid') {
            return 0;
        }

        return max(0, (int) round((float) $order->total));
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

    private function normalizeInvoice(string $invoice): ?string
    {
        $invoice = trim($invoice);

        if ($invoice === '') {
            return null;
        }

        // Steadfast allows letters, digits, hyphen, underscore only.
        $invoice = preg_replace('/[^A-Za-z0-9_-]+/', '-', $invoice) ?? '';
        $invoice = trim($invoice, '-_');
        $invoice = mb_substr($invoice, 0, 100);

        return $invoice !== '' ? $invoice : null;
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
        $apiKey = trim((string) $credentials->api_key);
        $secretKey = trim((string) $credentials->secret_key);

        if ($apiKey === '' || $secretKey === '') {
            return [
                'ok' => false,
                'message' => 'Steadfast API key and secret key are required.',
                'balance' => null,
                'details' => [],
                'raw' => null,
            ];
        }

        try {
            $endpoint = self::resolveBaseUrl((string) ($credentials->base_url ?? '')).'/get_balance';

            $response = Http::acceptJson()
                ->withoutRedirecting()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                ])
                ->connectTimeout(5)
                ->timeout(20)
                ->get($endpoint);

            $data = $response->json();
            $balance = $this->extractBalance(is_array($data) ? $data : null);

            if (! $response->successful() || $balance === null) {
                $message = is_array($data)
                    ? (string) ($data['message'] ?? $data['error'] ?? 'Could not load Steadfast balance.')
                    : 'Could not load Steadfast balance.';

                return [
                    'ok' => false,
                    'message' => $message,
                    'balance' => null,
                    'details' => [],
                    'raw' => $data,
                ];
            }

            return [
                'ok' => true,
                'message' => 'Live balance loaded from Steadfast.',
                'balance' => $balance,
                'details' => [
                    ['label' => 'Current balance', 'value' => number_format($balance, 2).' BDT'],
                    ['label' => 'API host', 'value' => parse_url($endpoint, PHP_URL_HOST) ?: 'portal.packzy.com'],
                ],
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            Log::warning('Steadfast balance check soft-failed', [
                'courier_api_id' => $credentials->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Could not reach Steadfast: '.$e->getMessage(),
                'balance' => null,
                'details' => [],
                'raw' => null,
            ];
        }
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function extractBalance(?array $data): ?float
    {
        if ($data === null) {
            return null;
        }

        $candidates = [
            $data['current_balance'] ?? null,
            data_get($data, 'data.current_balance'),
            $data['balance'] ?? null,
            data_get($data, 'data.balance'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return round((float) $candidate, 2);
            }
        }

        return null;
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
