<?php

namespace App\Services\Courier;

use App\Models\Order;
use App\Support\CourierWebhookStatusMapper;
use Illuminate\Support\Facades\Log;

class CourierWebhookOrderUpdater
{
    public function findOrder(mixed $tracking, mixed $invoice): ?Order
    {
        if ($tracking !== null && trim((string) $tracking) !== '') {
            $trackingValue = trim((string) $tracking);

            $byTracking = Order::query()
                ->where('tracking_number', $trackingValue)
                ->orderByDesc('id')
                ->first();

            if ($byTracking !== null) {
                return $byTracking;
            }
        }

        if ($invoice !== null && trim((string) $invoice) !== '') {
            $invoiceValue = trim((string) $invoice);

            return Order::query()
                ->where('invoice_no', $invoiceValue)
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    public function applyRemoteStatus(Order $order, ?string $remoteStatus, string $provider, ?string $tracking = null): bool
    {
        if ($tracking !== null && trim($tracking) !== '' && blank($order->tracking_number)) {
            $order->tracking_number = trim($tracking);
        }

        if (blank($order->courier_provider)) {
            $order->courier_provider = $provider;
        }

        $order->courier_synced_at = now();
        $order->save();

        $slug = CourierWebhookStatusMapper::toOrderStatusSlug($remoteStatus);

        if ($slug === null || $slug === $order->status) {
            Log::info('Courier webhook: no status change', [
                'provider' => $provider,
                'order_id' => $order->id,
                'remote_status' => $remoteStatus,
            ]);

            return false;
        }

        $order->applyStatus($slug);

        Log::info('Courier webhook: order status updated', [
            'provider' => $provider,
            'order_id' => $order->id,
            'remote_status' => $remoteStatus,
            'new_status' => $slug,
        ]);

        return true;
    }
}
