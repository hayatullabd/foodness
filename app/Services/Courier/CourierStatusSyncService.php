<?php

namespace App\Services\Courier;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class CourierStatusSyncService
{
    /** @var list<string> */
    public const ELIGIBLE_STATUSES = ['processing', 'rts', 'shipped', 'in_courier', 'on_the_way', 'pending_return'];

    /** @var array<string, CourierStatusProvider> */
    private array $providers;

    public function __construct(
        PathaoCourierStatusProvider $pathao,
        SteadfastCourierStatusProvider $steadfast,
        RedXCourierStatusProvider $redx,
        CarrybeeCourierStatusProvider $carrybee,
    ) {
        $this->providers = [
            $pathao->providerKey() => $pathao,
            $steadfast->providerKey() => $steadfast,
            $redx->providerKey() => $redx,
            $carrybee->providerKey() => $carrybee,
        ];
    }

    /**
     * @return array{checked: int, updated: int, failed: int, unchanged: int}
     */
    public function sync(int $limit = 50): array
    {
        $orders = $this->eligibleOrders($limit);
        $updated = 0;
        $failed = 0;
        $unchanged = 0;

        foreach ($orders as $order) {
            try {
                $provider = $this->resolveProvider($order);

                if ($provider === null) {
                    $failed++;

                    continue;
                }

                $newStatus = $provider->resolveStatus($order);

                $order->forceFill([
                    'courier_synced_at' => now(),
                ]);

                if ($newStatus === null || $newStatus === $order->status) {
                    $order->save();
                    $unchanged++;

                    continue;
                }

                $order->applyStatus($newStatus);
                $updated++;
            } catch (Throwable $e) {
                $failed++;

                Log::warning('Courier status sync soft-failed for order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'checked' => $orders->count(),
            'updated' => $updated,
            'failed' => $failed,
            'unchanged' => $unchanged,
        ];
    }

    /**
     * @return Collection<int, Order>
     */
    private function eligibleOrders(int $limit): Collection
    {
        return Order::query()
            ->where(function ($query): void {
                $query->whereIn('status', self::ELIGIBLE_STATUSES)
                    ->orWhereHas('orderStatus', fn ($status) => $status->whereIn('slug', self::ELIGIBLE_STATUSES));
            })
            ->whereNotNull('tracking_number')
            ->where('tracking_number', '!=', '')
            ->whereNotNull('courier_provider')
            ->where('courier_provider', '!=', '')
            ->whereIn('courier_provider', array_keys($this->providers))
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function resolveProvider(Order $order): ?CourierStatusProvider
    {
        $key = strtolower(trim((string) $order->courier_provider));

        return $this->providers[$key] ?? null;
    }
}
