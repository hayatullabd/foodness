<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SteadfastCourierStatusProvider extends AbstractCourierStatusProvider
{
    public function providerKey(): string
    {
        return 'steadfast';
    }

    protected function fetchRemoteStatus(Order $order, CourierApi $credentials): ?string
    {
        if (blank($credentials->api_key) || blank($credentials->secret_key)) {
            return null;
        }

        $tracking = trim((string) $order->tracking_number);

        if ($tracking === '') {
            return null;
        }

        try {
            $baseUrl = SteadfastShipmentClient::resolveBaseUrl($credentials->base_url);
            $headers = [
                'Api-Key' => trim((string) $credentials->api_key),
                'Secret-Key' => trim((string) $credentials->secret_key),
                'Content-Type' => 'application/json',
            ];

            // 1. If tracking is numeric consignment_id, prefer status_with_return_status_by_cid as documented
            if (ctype_digit($tracking)) {
                $response = Http::acceptJson()
                    ->withoutRedirecting()
                    ->withHeaders($headers)
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->get($baseUrl.'/status_with_return_status_by_cid/'.rawurlencode($tracking));

                if ($response->successful()) {
                    $data = $response->json();
                    $status = is_array($data) ? ($data['delivery_status'] ?? $data['status'] ?? null) : null;

                    if (is_string($status) && $status !== '') {
                        return $status;
                    }
                }

                // Fallback to regular status_by_cid
                $response = Http::acceptJson()
                    ->withoutRedirecting()
                    ->withHeaders($headers)
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->get($baseUrl.'/status_by_cid/'.rawurlencode($tracking));

                if ($response->successful()) {
                    $data = $response->json();
                    $status = is_array($data) ? ($data['delivery_status'] ?? $data['status'] ?? null) : null;

                    if (is_string($status) && $status !== '') {
                        return $status;
                    }
                }
            } else {
                // Alphanumeric tracking code
                $response = Http::acceptJson()
                    ->withoutRedirecting()
                    ->withHeaders($headers)
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->get($baseUrl.'/status_by_trackingcode/'.rawurlencode($tracking));

                if ($response->successful()) {
                    $data = $response->json();
                    $status = is_array($data) ? ($data['delivery_status'] ?? $data['status'] ?? null) : null;

                    if (is_string($status) && $status !== '') {
                        return $status;
                    }
                }
            }

            // Fallback by invoice number if available
            if (filled($order->invoice_no)) {
                $response = Http::acceptJson()
                    ->withoutRedirecting()
                    ->withHeaders($headers)
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->get($baseUrl.'/status_by_invoice/'.rawurlencode($order->invoice_no));

                if ($response->successful()) {
                    $data = $response->json();
                    $status = is_array($data) ? ($data['delivery_status'] ?? $data['status'] ?? null) : null;

                    if (is_string($status) && $status !== '') {
                        return $status;
                    }
                }
            }

            return null;
        } catch (Throwable $e) {
            Log::warning('Steadfast status check soft-failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
