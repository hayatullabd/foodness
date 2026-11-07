<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use App\Support\CourierWebhookStatusMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class CourierShipmentService
{
    public const QUEUE_THRESHOLD = 15;

    public function __construct(
        private SteadfastShipmentClient $steadfast,
        private PathaoShipmentClient $pathao,
        private RedXShipmentClient $redx,
        private CarrybeeShipmentClient $carrybee,
    ) {}

    /**
     * Active delivery methods usable for send-to-courier dropdowns.
     *
     * @return list<array{id: int, provider: string, label: string}>
     */
    public function enabledProviders(): array
    {
        return CourierApi::query()
            ->active()
            ->whereIn('provider', CourierApi::PROVIDERS)
            ->orderByDesc('is_preferred')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->filter(fn (CourierApi $api) => $this->hasShippingCredentials($api))
            ->map(fn (CourierApi $api) => [
                'id' => $api->id,
                'provider' => $api->provider,
                'label' => $api->displayName(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{ok: bool, message: string, skipped: bool, tracking_number: ?string}
     */
    public function sendOrder(Order $order, int|string $courierApiIdOrProvider, bool $force = false): array
    {
        $credentials = $this->resolveCredentials($courierApiIdOrProvider);

        if ($credentials === null) {
            return [
                'ok' => false,
                'message' => 'Delivery method is inactive, missing, or has no credentials.',
                'skipped' => false,
                'tracking_number' => null,
            ];
        }

        $provider = $credentials->provider;

        if (! in_array($provider, CourierApi::PROVIDERS, true)) {
            return [
                'ok' => false,
                'message' => 'Unknown courier provider.',
                'skipped' => false,
                'tracking_number' => null,
            ];
        }

        if (! $force && filled($order->tracking_number)) {
            return [
                'ok' => false,
                'message' => 'Order already has a tracking number. Enable force to resend.',
                'skipped' => true,
                'tracking_number' => $order->tracking_number,
            ];
        }

        if (! $this->hasShippingCredentials($credentials)) {
            return [
                'ok' => false,
                'message' => $credentials->displayName().' is missing credentials.',
                'skipped' => false,
                'tracking_number' => null,
            ];
        }

        if (blank($order->customer_name) || blank($order->customer_phone) || blank($order->customer_address)) {
            return [
                'ok' => false,
                'message' => 'Customer name, phone, and address are required.',
                'skipped' => false,
                'tracking_number' => null,
            ];
        }

        try {
            $result = match ($provider) {
                CourierApi::PROVIDER_STEADFAST => $this->steadfast->create($order, $credentials),
                CourierApi::PROVIDER_PATHAO => $this->pathao->create($order, $credentials),
                CourierApi::PROVIDER_REDX => $this->redx->create($order, $credentials),
                CourierApi::PROVIDER_CARRYBEE => $this->carrybee->create($order, $credentials),
            };
        } catch (Throwable $e) {
            Log::warning('Courier shipment unexpected failure', [
                'order_id' => $order->id,
                'courier_api_id' => $credentials->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Courier request failed unexpectedly.',
                'skipped' => false,
                'tracking_number' => null,
            ];
        }

        if (! $result['ok'] || blank($result['tracking_number'])) {
            return [
                'ok' => false,
                'message' => $result['message'],
                'skipped' => false,
                'tracking_number' => null,
            ];
        }

        $order->forceFill([
            'tracking_number' => $result['tracking_number'],
            'tracking_url' => $result['tracking_url'] ?? $order->tracking_url,
            'courier_provider' => $provider,
            'courier_api_id' => $credentials->id,
            'courier_synced_at' => now(),
        ])->save();

        $statusSlug = $this->initialStatusSlug($result) ?? 'processing';

        try {
            $order->applyStatus($statusSlug);
        } catch (Throwable $e) {
            Log::warning('Could not set courier status after shipment', [
                'order_id' => $order->id,
                'status' => $statusSlug,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'ok' => true,
            'message' => $result['message'].' Status sync uses webhooks and the courier:check-status cron (Cron Jobs → Run Now).',
            'skipped' => false,
            'tracking_number' => $result['tracking_number'],
        ];
    }

    /**
     * @param  array{ok: bool, message: string, tracking_number: ?string, tracking_url: ?string, raw: mixed}  $result
     */
    private function initialStatusSlug(array $result): ?string
    {
        $raw = $result['raw'] ?? null;

        if (! is_array($raw)) {
            return null;
        }

        $candidates = [
            data_get($raw, 'delivery_status'),
            data_get($raw, 'status'),
            data_get($raw, 'consignment.delivery_status'),
            data_get($raw, 'data.order_status_slug'),
            data_get($raw, 'data.order_status'),
            data_get($raw, 'parcel.status'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $slug = CourierWebhookStatusMapper::toOrderStatusSlug($candidate);

            if ($slug !== null && ! in_array($slug, ['delivered', 'completed', 'cancelled', 'courier_returned', 'returned'], true)) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Order>|iterable<int, Order>  $orders
     * @return array{success: int, failed: int, skipped: int, message: string}
     */
    public function sendMany(iterable $orders, int|string $courierApiIdOrProvider, bool $force = false): array
    {
        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            if (! $order instanceof Order) {
                $failed++;

                continue;
            }

            $result = $this->sendOrder($order, $courierApiIdOrProvider, $force);

            if ($result['skipped']) {
                $skipped++;
            } elseif ($result['ok']) {
                $success++;
            } else {
                $failed++;
            }
        }

        $parts = [];
        $parts[] = $success.' sent';
        if ($skipped > 0) {
            $parts[] = $skipped.' skipped (already tracked)';
        }
        if ($failed > 0) {
            $parts[] = $failed.' failed';
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped,
            'message' => 'Courier: '.implode(', ', $parts).'.',
        ];
    }

    public function hasShippingCredentials(CourierApi $api): bool
    {
        return match ($api->provider) {
            CourierApi::PROVIDER_STEADFAST => filled($api->api_key) && filled($api->secret_key),
            CourierApi::PROVIDER_PATHAO => filled($api->token)
                || (filled($api->api_key) && filled($api->secret_key) && filled($api->username) && filled($api->password)),
            CourierApi::PROVIDER_REDX => filled($api->token) || filled($api->api_key),
            CourierApi::PROVIDER_CARRYBEE => filled($api->api_key) && filled($api->secret_key) && filled($api->username),
            default => false,
        };
    }

    private function resolveCredentials(int|string $courierApiIdOrProvider): ?CourierApi
    {
        if (is_numeric($courierApiIdOrProvider)) {
            return CourierApi::resolveForSend((int) $courierApiIdOrProvider);
        }

        $provider = strtolower(trim((string) $courierApiIdOrProvider));

        return CourierApi::resolveForSend(null, $provider);
    }
}
