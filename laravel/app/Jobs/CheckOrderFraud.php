<?php

namespace App\Jobs;

use App\Models\FraudSetting;
use App\Models\Order;
use App\Services\BdCourierFraudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckOrderFraud implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30];

    public function __construct(public Order $order) {}

    public function handle(BdCourierFraudService $fraudService): void
    {
        if (! FraudSetting::current()->isConfigured()) {
            return;
        }

        $order = $this->order->fresh();

        if (! $order || blank($order->customer_phone)) {
            return;
        }

        try {
            $fraudService->applyToOrder($order, softFail: true);
        } catch (Throwable $exception) {
            Log::warning('Queued fraud check failed softly.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
