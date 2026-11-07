<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use App\Models\Order;

class CarrybeeCourierStatusProvider extends AbstractCourierStatusProvider
{
    public function __construct(
        private readonly CarrybeeShipmentClient $client,
    ) {}

    public function providerKey(): string
    {
        return CourierApi::PROVIDER_CARRYBEE;
    }

    protected function fetchRemoteStatus(Order $order, CourierApi $credentials): ?string
    {
        return $this->client->transferStatus((string) $order->tracking_number, $credentials);
    }
}
