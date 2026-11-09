<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Courier\CourierShipmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrdersToCourier implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * @param  list<int>  $orderIds
     */
    public function __construct(
        public array $orderIds,
        public int|string $courierApiId,
        public bool $force = false,
    ) {}

    public function handle(CourierShipmentService $shipments): void
    {
        $orders = Order::query()->whereIn('id', $this->orderIds)->get();

        $summary = $shipments->sendMany($orders, $this->courierApiId, $this->force);

        Log::info('Queued courier bulk send finished', [
            'courier_api_id' => $this->courierApiId,
            'success' => $summary['success'],
            'failed' => $summary['failed'],
            'skipped' => $summary['skipped'],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Queued courier bulk send failed', [
            'courier_api_id' => $this->courierApiId,
            'order_ids' => $this->orderIds,
            'error' => $exception?->getMessage(),
        ]);
    }
}
