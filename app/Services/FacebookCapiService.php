<?php

namespace App\Services;

use App\Models\ApiIntegration;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FacebookCapiService
{
    public function isEnabled(): bool
    {
        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_FACEBOOK_CAPI);

        return $integration->is_active
            && filled($integration->credential('pixel_id'))
            && filled($integration->credential('access_token'));
    }

    /**
     * Send a Purchase event to Meta Conversions API.
     */
    public function sendPurchase(Order $order, ?Request $request = null): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_FACEBOOK_CAPI);
        $pixelId = trim((string) $integration->credential('pixel_id'));
        $accessToken = trim((string) $integration->credential('access_token'));
        $testEventCode = trim((string) $integration->credential('test_event_code'));

        $order->loadMissing('items');

        $contents = [];
        foreach ($order->items as $item) {
            $contents[] = [
                'id' => (string) $item->product_id,
                'quantity' => (int) $item->qty,
                'item_price' => (float) $item->price,
            ];
        }

        $userData = array_filter([
            'client_ip_address' => $order->ip_address ?: $request?->ip(),
            'client_user_agent' => $request?->userAgent(),
            'ph' => $this->hashPhone($order->customer_phone),
            'fn' => $this->hashName($order->customer_name),
        ]);

        $eventData = [
            'event_name' => 'Purchase',
            'event_time' => $order->created_at ? $order->created_at->timestamp : now()->timestamp,
            'event_id' => 'order_'.$order->id,
            'event_source_url' => route('checkout.success', $order),
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => [
                'currency' => 'BDT',
                'value' => (float) $order->total,
                'order_id' => $order->invoice_no,
                'content_type' => 'product',
                'contents' => $contents,
                'num_items' => (int) $order->items->sum('qty'),
            ],
        ];

        $payload = [
            'data' => [$eventData],
        ];

        if (filled($testEventCode)) {
            $payload['test_event_code'] = $testEventCode;
        }

        try {
            $url = sprintf('https://graph.facebook.com/v19.0/%s/events', urlencode($pixelId));
            $response = Http::timeout(5)
                ->withToken($accessToken)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::warning('Facebook CAPI purchase event failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Facebook CAPI request exception: '.$e->getMessage(), [
                'order_id' => $order->id,
            ]);

            return false;
        }
    }

    /**
     * Normalize and hash phone number (E.164 without leading plus, or local digits).
     *
     * @return array<int, string>|null
     */
    public function hashPhone(?string $phone): ?array
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (blank($digits)) {
            return null;
        }

        // Prepend Bangladesh country code if omitted (01XXXXXXXXX -> 8801XXXXXXXXX)
        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            $digits = '88'.$digits;
        }

        return [hash('sha256', $digits)];
    }

    /**
     * Normalize and hash name.
     *
     * @return array<int, string>|null
     */
    public function hashName(?string $name): ?array
    {
        if (blank($name)) {
            return null;
        }

        $clean = mb_strtolower(trim($name), 'UTF-8');

        // Extract first name
        $parts = preg_split('/\s+/', $clean);
        $firstName = $parts[0] ?? $clean;

        if (blank($firstName)) {
            return null;
        }

        return [hash('sha256', $firstName)];
    }
}
