<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CourierDashboardService
{
    public function __construct(
        private SteadfastShipmentClient $steadfast,
        private PathaoShipmentClient $pathao,
        private RedXShipmentClient $redx,
        private CarrybeeShipmentClient $carrybee,
    ) {}

    /**
     * @return array{
     *     panels: list<array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     recent_bookings: Collection<int, Order>
     * }
     */
    public function build(bool $refresh = false): array
    {
        $methods = CourierApi::query()
            ->where('is_active', true)
            ->orderByDesc('is_preferred')
            ->orderBy('provider')
            ->orderBy('name')
            ->get();

        $panels = $methods
            ->map(fn (CourierApi $method): array => $this->panelFor($method, $refresh))
            ->values()
            ->all();

        $summary = [
            'active_methods' => count($panels),
            'api_ok' => collect($panels)->where('api.ok', true)->count(),
            'api_failed' => collect($panels)->where('api.ok', false)->count(),
            'total_balance' => collect($panels)
                ->pluck('api.balance')
                ->filter(fn ($balance): bool => is_numeric($balance))
                ->sum(),
            'has_live_balance' => collect($panels)->contains(fn (array $panel): bool => is_numeric($panel['api']['balance'] ?? null)),
            'booked_total' => collect($panels)->sum(fn (array $panel): int => (int) ($panel['stats']['booked'] ?? 0)),
            'in_courier_total' => collect($panels)->sum(fn (array $panel): int => (int) ($panel['stats']['in_courier'] ?? 0)),
            'on_the_way_total' => collect($panels)->sum(fn (array $panel): int => (int) ($panel['stats']['on_the_way'] ?? 0)),
            'delivered_total' => collect($panels)->sum(fn (array $panel): int => (int) ($panel['stats']['delivered'] ?? 0)),
            'cod_open_total' => collect($panels)->sum(fn (array $panel): float => (float) ($panel['stats']['cod_open'] ?? 0)),
        ];

        $recentBookings = Order::query()
            ->with('courierApi')
            ->whereNotNull('courier_api_id')
            ->whereNotNull('tracking_number')
            ->where('tracking_number', '!=', '')
            ->latest('courier_synced_at')
            ->latest('id')
            ->limit(12)
            ->get();

        return [
            'panels' => $panels,
            'summary' => $summary,
            'recent_bookings' => $recentBookings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function panelFor(CourierApi $method, bool $refresh): array
    {
        $cacheKey = 'courier-dashboard.api.'.$method->id;
        $ttl = now()->addMinutes(2);

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        /** @var array{ok: bool, message: string, balance: float|null, details: list<array{label: string, value: string}>, raw: mixed} $api */
        $api = Cache::remember($cacheKey, $ttl, fn (): array => $this->fetchApiSnapshot($method));

        return [
            'id' => $method->id,
            'provider' => $method->provider,
            'provider_label' => $method->providerBadge(),
            'label' => $method->displayName(),
            'is_preferred' => (bool) $method->is_preferred,
            'api' => [
                'ok' => (bool) ($api['ok'] ?? false),
                'message' => (string) ($api['message'] ?? ''),
                'balance' => is_numeric($api['balance'] ?? null) ? (float) $api['balance'] : null,
                'details' => is_array($api['details'] ?? null) ? $api['details'] : [],
            ],
            'stats' => $this->localStatsFor($method->id),
        ];
    }

    /**
     * @return array{ok: bool, message: string, balance: float|null, details: list<array{label: string, value: string}>, raw: mixed}
     */
    private function fetchApiSnapshot(CourierApi $method): array
    {
        return match ($method->provider) {
            CourierApi::PROVIDER_STEADFAST => $this->steadfast->accountSnapshot($method),
            CourierApi::PROVIDER_PATHAO => $this->pathao->accountSnapshot($method),
            CourierApi::PROVIDER_REDX => $this->redx->accountSnapshot($method),
            CourierApi::PROVIDER_CARRYBEE => $this->carrybee->accountSnapshot($method),
            default => [
                'ok' => false,
                'message' => 'Unsupported courier provider.',
                'balance' => null,
                'details' => [],
                'raw' => null,
            ],
        };
    }

    /**
     * @return array{
     *     booked: int,
     *     in_courier: int,
     *     on_the_way: int,
     *     delivered: int,
     *     cancelled: int,
     *     cod_open: float,
     *     delivered_value: float
     * }
     */
    private function localStatsFor(int $courierApiId): array
    {
        $base = Order::query()
            ->where('courier_api_id', $courierApiId)
            ->whereNotNull('tracking_number')
            ->where('tracking_number', '!=', '');

        $statusCounts = (clone $base)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $codOpen = (float) (clone $base)
            ->whereIn('status', ['in_courier', 'on_the_way', 'processing'])
            ->where('payment_status', '!=', 'paid')
            ->sum('total');

        $deliveredValue = (float) (clone $base)
            ->whereIn('status', ['delivered', 'completed'])
            ->sum('total');

        return [
            'booked' => (int) $statusCounts->sum(),
            'in_courier' => (int) ($statusCounts['in_courier'] ?? 0),
            'on_the_way' => (int) ($statusCounts['on_the_way'] ?? 0),
            'delivered' => (int) (($statusCounts['delivered'] ?? 0) + ($statusCounts['completed'] ?? 0)),
            'cancelled' => (int) (($statusCounts['cancelled'] ?? 0) + ($statusCounts['refunded'] ?? 0)),
            'cod_open' => $codOpen,
            'delivered_value' => $deliveredValue,
        ];
    }
}
