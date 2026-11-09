<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;
use App\Support\CourierWebhookStatusMapper;
use Illuminate\Support\Facades\Log;

abstract class AbstractCourierStatusProvider implements CourierStatusProvider
{
    abstract public function providerKey(): string;

    public function resolveStatus(Order $order): ?string
    {
        $credentials = $this->credentialsFor($order);

        if ($credentials === null) {
            Log::warning('Courier credentials missing or inactive', [
                'provider' => $this->providerKey(),
                'order_id' => $order->id,
            ]);

            return null;
        }

        $remoteStatus = $this->fetchRemoteStatus($order, $credentials);

        if ($remoteStatus === null || $remoteStatus === '') {
            return null;
        }

        return CourierWebhookStatusMapper::toOrderStatusSlug($remoteStatus);
    }

    protected function credentialsFor(Order $order): ?CourierApi
    {
        if ($order->courier_api_id) {
            $specific = CourierApi::query()
                ->active()
                ->whereKey($order->courier_api_id)
                ->whereRaw('LOWER(provider) = ?', [strtolower($this->providerKey())])
                ->first();

            if ($specific) {
                return $specific;
            }
        }

        return CourierApi::query()
            ->active()
            ->whereRaw('LOWER(provider) = ?', [strtolower($this->providerKey())])
            ->orderByDesc('is_preferred')
            ->orderBy('id')
            ->first();
    }

    abstract protected function fetchRemoteStatus(Order $order, CourierApi $credentials): ?string;
}
