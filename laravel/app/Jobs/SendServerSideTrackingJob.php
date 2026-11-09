<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendServerSideTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 10;

    /**
     * Create a new job instance.
     *
     * @param  array{
     *     event_name: string,
     *     event_id: string,
     *     event_time: int,
     *     event_source_url: ?string,
     *     user_data: array<string, mixed>,
     *     custom_data: array<string, mixed>,
     * }  $eventPayload
     * @param  array{
     *     meta: array{capi_enabled: bool, pixel_id: ?string, access_token: ?string, test_event_code: ?string},
     *     tiktok: array{api_enabled: bool, pixel_id: ?string, access_token: ?string, test_event_code: ?string},
     *     ga4: array{api_enabled: bool, measurement_id: ?string, api_secret: ?string},
     * }  $serverConfig
     */
    public function __construct(
        public array $eventPayload,
        public array $serverConfig,
    ) {}

    /**
     * Execute the job concurrently via Http::pool().
     */
    public function handle(): void
    {
        try {
            $poolCallbacks = [];

            // 1. Prepare Facebook CAPI Request
            $metaConfig = $this->serverConfig['meta'] ?? [];
            if (! empty($metaConfig['capi_enabled']) && filled($metaConfig['pixel_id']) && filled($metaConfig['access_token'])) {
                $metaPayload = $this->buildMetaPayload($metaConfig);
                $pixelId = urlencode(trim((string) $metaConfig['pixel_id']));
                $metaUrl = sprintf('https://graph.facebook.com/v19.0/%s/events', $pixelId);
                $metaToken = trim((string) $metaConfig['access_token']);

                $poolCallbacks['facebook'] = function (Pool $pool) use ($metaUrl, $metaToken, $metaPayload) {
                    return $pool->as('facebook')
                        ->timeout(5)
                        ->withToken($metaToken)
                        ->post($metaUrl, $metaPayload);
                };
            }

            // 2. Prepare TikTok Events API Request
            $tiktokConfig = $this->serverConfig['tiktok'] ?? [];
            if (! empty($tiktokConfig['api_enabled']) && filled($tiktokConfig['pixel_id']) && filled($tiktokConfig['access_token'])) {
                $tiktokPayload = $this->buildTiktokPayload($tiktokConfig);
                $tiktokToken = trim((string) $tiktokConfig['access_token']);

                $poolCallbacks['tiktok'] = function (Pool $pool) use ($tiktokToken, $tiktokPayload) {
                    return $pool->as('tiktok')
                        ->timeout(5)
                        ->withHeaders([
                            'Access-Token' => $tiktokToken,
                            'Content-Type' => 'application/json',
                        ])
                        ->post('https://business-api.tiktok.com/open_api/v1.3/event/track/', $tiktokPayload);
                };
            }

            // 3. Prepare GA4 Measurement Protocol Request
            $ga4Config = $this->serverConfig['ga4'] ?? [];
            if (! empty($ga4Config['api_enabled']) && filled($ga4Config['measurement_id']) && filled($ga4Config['api_secret'])) {
                $ga4Payload = $this->buildGa4Payload();
                $ga4Url = sprintf(
                    'https://www.google-analytics.com/mp/collect?measurement_id=%s&api_secret=%s',
                    urlencode(trim((string) $ga4Config['measurement_id'])),
                    urlencode(trim((string) $ga4Config['api_secret'])),
                );

                $poolCallbacks['ga4'] = function (Pool $pool) use ($ga4Url, $ga4Payload) {
                    return $pool->as('ga4')
                        ->timeout(5)
                        ->post($ga4Url, $ga4Payload);
                };
            }

            // If no server-side providers are active, exit early
            if ($poolCallbacks === []) {
                return;
            }

            // Dispatch all API calls concurrently in parallel via Http::pool()
            $responses = Http::pool(function (Pool $pool) use ($poolCallbacks): array {
                $calls = [];
                foreach ($poolCallbacks as $callback) {
                    $calls[] = $callback($pool);
                }

                return $calls;
            });

            // Inspect responses softly for debugging logs without throwing
            foreach ($responses as $key => $response) {
                if ($response instanceof Response && $response->failed()) {
                    Log::warning("Server-side tracking provider [{$key}] returned non-success HTTP status", [
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                        'event_name' => $this->eventPayload['event_name'] ?? 'Unknown',
                        'event_id' => $this->eventPayload['event_id'] ?? 'Unknown',
                    ]);
                }
            }
        } catch (Throwable $e) {
            // Guarantee that external tracking failures NEVER impact order placement or customer flow
            Log::warning('SendServerSideTrackingJob execution exception caught softly: '.$e->getMessage(), [
                'event_name' => $this->eventPayload['event_name'] ?? 'Unknown',
                'event_id' => $this->eventPayload['event_id'] ?? 'Unknown',
            ]);
        }
    }

    /**
     * @param  array{test_event_code?: ?string}  $metaConfig
     * @return array<string, mixed>
     */
    private function buildMetaPayload(array $metaConfig): array
    {
        $userDataRaw = $this->eventPayload['user_data'] ?? [];
        $customData = $this->eventPayload['custom_data'] ?? [];

        $userData = array_filter([
            'client_ip_address' => $userDataRaw['client_ip_address'] ?? null,
            'client_user_agent' => $userDataRaw['client_user_agent'] ?? null,
            'fbp' => $userDataRaw['fbp'] ?? null,
            'fbc' => $userDataRaw['fbc'] ?? null,
            'ph' => ! empty($userDataRaw['ph']) ? (array) $userDataRaw['ph'] : null,
            'em' => ! empty($userDataRaw['em']) ? (array) $userDataRaw['em'] : null,
            'fn' => ! empty($userDataRaw['fn']) ? (array) $userDataRaw['fn'] : null,
            'ln' => ! empty($userDataRaw['ln']) ? (array) $userDataRaw['ln'] : null,
            'ct' => ! empty($userDataRaw['ct']) ? (array) $userDataRaw['ct'] : null,
            'country' => ! empty($userDataRaw['country']) ? (array) $userDataRaw['country'] : null,
            'external_id' => ! empty($userDataRaw['external_id']) ? (array) $userDataRaw['external_id'] : null,
        ]);

        $eventData = [
            'event_name' => $this->eventPayload['event_name'],
            'event_time' => $this->eventPayload['event_time'] ?? now()->timestamp,
            'event_id' => $this->eventPayload['event_id'],
            'event_source_url' => $this->eventPayload['event_source_url'] ?? url('/'),
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => array_filter([
                'currency' => $customData['currency'] ?? 'BDT',
                'value' => isset($customData['value']) ? (float) $customData['value'] : null,
                'order_id' => $customData['order_id'] ?? null,
                'content_type' => 'product',
                'contents' => $customData['contents'] ?? [],
                'content_ids' => $customData['content_ids'] ?? [],
                'num_items' => isset($customData['num_items']) ? (int) $customData['num_items'] : null,
            ]),
        ];

        $payload = ['data' => [$eventData]];

        if (filled($metaConfig['test_event_code'] ?? null)) {
            $payload['test_event_code'] = trim((string) $metaConfig['test_event_code']);
        }

        return $payload;
    }

    /**
     * @param  array{pixel_id: ?string, test_event_code?: ?string}  $tiktokConfig
     * @return array<string, mixed>
     */
    private function buildTiktokPayload(array $tiktokConfig): array
    {
        $userDataRaw = $this->eventPayload['user_data'] ?? [];
        $customData = $this->eventPayload['custom_data'] ?? [];

        // Map event name to TikTok standard
        $tiktokEvent = match ($this->eventPayload['event_name']) {
            'Purchase' => 'CompletePayment',
            'InitiateCheckout' => 'InitiateCheckout',
            'AddToCart' => 'AddToCart',
            'ViewContent' => 'ViewContent',
            default => $this->eventPayload['event_name'],
        };

        $user = array_filter([
            'phone' => is_array($userDataRaw['ph'] ?? null) ? ($userDataRaw['ph'][0] ?? null) : ($userDataRaw['ph'] ?? null),
            'email' => is_array($userDataRaw['em'] ?? null) ? ($userDataRaw['em'][0] ?? null) : ($userDataRaw['em'] ?? null),
            'external_id' => is_array($userDataRaw['external_id'] ?? null) ? ($userDataRaw['external_id'][0] ?? null) : ($userDataRaw['external_id'] ?? null),
            'ttp' => $userDataRaw['ttp'] ?? null,
            'ip' => $userDataRaw['client_ip_address'] ?? null,
            'user_agent' => $userDataRaw['client_user_agent'] ?? null,
        ]);

        $tiktokContents = [];
        foreach ($customData['contents'] ?? [] as $item) {
            $tiktokContents[] = [
                'content_id' => (string) ($item['id'] ?? $item['item_id'] ?? ''),
                'content_type' => 'product',
                'content_name' => $item['item_name'] ?? $item['name'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? $item['qty'] ?? 1),
                'price' => (float) ($item['item_price'] ?? $item['price'] ?? 0),
            ];
        }

        $properties = array_filter([
            'currency' => $customData['currency'] ?? 'BDT',
            'value' => isset($customData['value']) ? (float) $customData['value'] : null,
            'order_id' => $customData['order_id'] ?? null,
            'contents' => $tiktokContents,
        ]);

        $dataItem = [
            'event' => $tiktokEvent,
            'event_time' => $this->eventPayload['event_time'] ?? now()->timestamp,
            'event_id' => $this->eventPayload['event_id'],
            'user' => $user,
            'properties' => $properties,
        ];

        $payload = [
            'event_source' => 'web',
            'event_source_id' => trim((string) $tiktokConfig['pixel_id']),
            'data' => [$dataItem],
        ];

        if (filled($tiktokConfig['test_event_code'] ?? null)) {
            $payload['test_event_code'] = trim((string) $tiktokConfig['test_event_code']);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGa4Payload(): array
    {
        $userDataRaw = $this->eventPayload['user_data'] ?? [];
        $customData = $this->eventPayload['custom_data'] ?? [];

        $ga4EventName = match ($this->eventPayload['event_name']) {
            'Purchase' => 'purchase',
            'InitiateCheckout' => 'begin_checkout',
            'AddToCart' => 'add_to_cart',
            'ViewContent' => 'view_item',
            default => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->eventPayload['event_name'])),
        };

        $clientId = $userDataRaw['ga_client_id']
            ?? $userDataRaw['ga']
            ?? sprintf('%d.%d', mt_rand(100000000, 999999999), time());

        $ga4Items = [];
        foreach ($customData['contents'] ?? [] as $item) {
            $ga4Items[] = [
                'item_id' => (string) ($item['id'] ?? $item['item_id'] ?? ''),
                'item_name' => $item['item_name'] ?? $item['name'] ?? 'Product',
                'item_category' => $item['item_category'] ?? $item['category'] ?? '',
                'price' => (float) ($item['item_price'] ?? $item['price'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? $item['qty'] ?? 1),
            ];
        }

        $params = array_filter([
            'transaction_id' => $customData['order_id'] ?? null,
            'value' => isset($customData['value']) ? (float) $customData['value'] : null,
            'currency' => $customData['currency'] ?? 'BDT',
            'items' => $ga4Items,
            'shipping' => isset($customData['shipping']) ? (float) $customData['shipping'] : null,
            'tax' => isset($customData['tax']) ? (float) $customData['tax'] : 0,
            'event_id' => $this->eventPayload['event_id'],
        ]);

        $payload = [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => $ga4EventName,
                    'params' => $params,
                ],
            ],
        ];

        if (! empty($userDataRaw['external_id'])) {
            $extId = is_array($userDataRaw['external_id']) ? ($userDataRaw['external_id'][0] ?? null) : $userDataRaw['external_id'];
            if (filled($extId)) {
                $payload['user_id'] = (string) $extId;
            }
        }

        return $payload;
    }
}
