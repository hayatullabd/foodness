<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PathaoCourierStatusProvider extends AbstractCourierStatusProvider
{
    public function providerKey(): string
    {
        return 'pathao';
    }

    protected function fetchRemoteStatus(Order $order, CourierApi $credentials): ?string
    {
        if (blank($credentials->token) && (blank($credentials->api_key) || blank($credentials->secret_key))) {
            return null;
        }

        $consignmentId = $order->tracking_number;

        if (blank($consignmentId)) {
            return null;
        }

        try {
            $baseUrl = rtrim($credentials->base_url ?: 'https://api-hermes.pathao.com', '/');
            $baseUrl = preg_replace('#/aladdin/?$#', '', $baseUrl) ?: $baseUrl;
            $token = $credentials->token ?: $credentials->api_key;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->connectTimeout(5)->timeout(15)->get($baseUrl.'/aladdin/api/v1/orders/'.$consignmentId.'/info');

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (! is_array($data)) {
                return null;
            }

            $status = $data['data']['order_status_slug']
                ?? $data['data']['order_status']
                ?? null;

            return is_string($status) ? $status : null;
        } catch (Throwable $e) {
            Log::warning('Pathao status check soft-failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
