<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RedXCourierStatusProvider extends AbstractCourierStatusProvider
{
    public function providerKey(): string
    {
        return 'redx';
    }

    protected function fetchRemoteStatus(Order $order, CourierApi $credentials): ?string
    {
        if (blank($credentials->token) && blank($credentials->api_key)) {
            return null;
        }

        $trackingId = trim((string) $order->tracking_number);

        if ($trackingId === '') {
            return null;
        }

        try {
            $baseUrl = rtrim($credentials->base_url ?: 'https://openapi.redx.com.bd', '/');
            $token = $credentials->token ?: $credentials->api_key;

            $response = Http::withHeaders([
                'API-ACCESS-TOKEN' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->connectTimeout(5)->timeout(15)->get($baseUrl.'/v1/parcel/info/'.$trackingId);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (! is_array($data)) {
                return null;
            }

            $status = $data['parcel']['status'] ?? $data['status'] ?? null;

            return is_string($status) ? $status : null;
        } catch (Throwable $e) {
            Log::warning('RedX status check soft-failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
