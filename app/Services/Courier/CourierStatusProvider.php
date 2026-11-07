<?php

namespace App\Services\Courier;

use App\Models\Order;

interface CourierStatusProvider
{
    public function providerKey(): string;

    /**
     * Resolve a new order status slug (e.g. completed, cancelled) or null when unchanged / unknown.
     */
    public function resolveStatus(Order $order): ?string;
}
