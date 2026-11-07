<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\BulkSmsBdService;
use App\Services\StoreEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30];

    public function __construct(public Order $order) {}

    public function handle(BulkSmsBdService $smsService, StoreEmailService $emailService): void
    {
        $order = $this->order->fresh();

        if (! $order) {
            return;
        }

        // 1. Bulk SMS notification
        try {
            $smsService->notifyOrderPlaced($order);
        } catch (Throwable $e) {
            Log::warning('Queued SMS notification failed softly.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 2. Email notification
        try {
            $emailService->notifyOrderPlaced($order);
        } catch (Throwable $e) {
            Log::warning('Queued Email notification failed softly.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
