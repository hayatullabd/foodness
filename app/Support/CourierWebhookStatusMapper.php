<?php

namespace App\Support;

class CourierWebhookStatusMapper
{
    /**
     * Map a Pathao / Steadfast / RedX / Carrybee courier status string to an order status slug.
     *
     * User Courier Statuses:
     * - Processing
     * - RTS
     * - Shipped
     * - Delivered
     * - Pending Return
     * - Returned
     * - Partial
     * - Cancelled
     * - Lost
     */
    public static function toOrderStatusSlug(?string $status): ?string
    {
        $normalized = self::normalize($status);

        if ($normalized === '') {
            return null;
        }

        if (self::matches($normalized, self::lostStatuses()) || self::looksLost($normalized)) {
            return 'lost';
        }

        if (self::matches($normalized, self::partialStatuses()) || self::looksPartial($normalized)) {
            return 'partial';
        }

        if (self::matches($normalized, self::returnedStatuses()) || self::looksReturned($normalized)) {
            return 'returned';
        }

        if (self::matches($normalized, self::pendingReturnStatuses()) || self::looksPendingReturn($normalized)) {
            return 'pending_return';
        }

        if (self::matches($normalized, self::cancelledStatuses()) || self::looksCancelled($normalized)) {
            return 'cancelled';
        }

        if (self::matches($normalized, self::deliveredStatuses()) || self::looksDelivered($normalized)) {
            return 'delivered';
        }

        if (self::matches($normalized, self::shippedStatuses()) || self::looksShipped($normalized)) {
            return 'shipped';
        }

        if (self::matches($normalized, self::rtsStatuses()) || self::looksRts($normalized)) {
            return 'rts';
        }

        if (self::matches($normalized, self::processingStatuses()) || self::looksProcessing($normalized)) {
            return 'processing';
        }

        return null;
    }

    public static function normalize(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));
        $normalized = str_replace([' ', '-', '.'], '_', $normalized);
        $normalized = preg_replace('/_+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    /**
     * @return list<string>
     */
    public static function lostStatuses(): array
    {
        return [
            'lost',
            'damaged',
            'damage',
            'exceptional',
            'compensation',
            'missing',
        ];
    }

    /**
     * @return list<string>
     */
    public static function partialStatuses(): array
    {
        return [
            'partial',
            'partial_delivered',
            'partially_delivered',
            'partial_delivery',
            'partial_delivered_approval_pending',
            'partial_delivered_return_received',
            'partial_delivered_return_proccessing',
            'partial_delivered_return_processing',
            'partial_delivered_return_rider_assigned',
        ];
    }

    /**
     * @return list<string>
     */
    public static function returnedStatuses(): array
    {
        return [
            'returned',
            'returned_to_merchant',
            'return_completed',
            'return_received',
            'cancelled_return_received',
            'returned_to_hub',
        ];
    }

    /**
     * @return list<string>
     */
    public static function pendingReturnStatuses(): array
    {
        return [
            'pending_return',
            'return_to_merchant',
            'return_in_transit',
            'picked_return',
            'returning',
            'delivery_failed',
            'failed_delivery',
            'delivery_failure',
            'undelivered',
            'return_pending',
            'courier_returned',
            'return_requested',
            'on_return',
            'cancelled_return_proccessing',
            'cancelled_return_processing',
            'cancelled_return_rider_assigned',
            'cancelled_approval_pending',
        ];
    }

    /**
     * @return list<string>
     */
    public static function cancelledStatuses(): array
    {
        return [
            'cancelled',
            'canceled',
            'cancel',
            'cancellation',
            'cancellation_request',
            'pickup_failed',
            'failed',
        ];
    }

    /**
     * @return list<string>
     */
    public static function deliveredStatuses(): array
    {
        return [
            'delivered',
            'delivery_completed',
            'delivery_success',
            'successfully_delivered',
            'completed',
        ];
    }

    /**
     * @return list<string>
     */
    public static function shippedStatuses(): array
    {
        return [
            'shipped',
            'delivered_approval_pending',
            'in_transit',
            'transit',
            'on_the_way',
            'on_way',
            'out_for_delivery',
            'out_for_deliver',
            'assigned_for_delivery',
            'delivery_in_progress',
            'at_delivery',
            'holding',
            'at_the_hub',
            'at_hub',
            'hub',
            'in_hub',
            'agent_assigned',
            'received_at_last_hub',
            'received_at_last_mile_hub',
            'at_the_sorting_hub',
            'at_central_warehouse',
            'on_the_way_to_central_warehouse',
            'delivery_on_hold',
            'area_change',
        ];
    }

    /**
     * @return list<string>
     */
    public static function rtsStatuses(): array
    {
        return [
            'rts',
            'ready_to_ship',
            'ready_for_pickup',
            'picked',
            'picked_up',
            'collected',
            'pickup_assigned',
            'assigned_for_pickup',
        ];
    }

    /**
     * @return list<string>
     */
    public static function processingStatuses(): array
    {
        return [
            'processing',
            'in_courier',
            'pending',
            'in_review',
            'hold',
            'unknown_approval_pending',
            'pending_pickup',
            'pickup_pending',
            'pickup_requested',
            'consignment',
            'created',
            'submitted',
            'booked',
            'entry',
            'new',
            'unknown',
            'exchange',
            'order_created',
            'order_updated',
        ];
    }

    /**
     * @param  list<string>  $list
     */
    private static function matches(string $normalized, array $list): bool
    {
        return in_array($normalized, $list, true);
    }

    private static function looksLost(string $normalized): bool
    {
        return str_contains($normalized, 'lost') || str_contains($normalized, 'damag');
    }

    private static function looksPartial(string $normalized): bool
    {
        return str_contains($normalized, 'partial');
    }

    private static function looksReturned(string $normalized): bool
    {
        return $normalized === 'returned'
            || str_contains($normalized, 'returned_to')
            || str_contains($normalized, 'return_completed')
            || str_contains($normalized, 'return_received');
    }

    private static function looksPendingReturn(string $normalized): bool
    {
        return str_contains($normalized, 'return')
            || str_contains($normalized, 'delivery_failed')
            || str_contains($normalized, 'undelivered')
            || str_contains($normalized, 'returning');
    }

    private static function looksCancelled(string $normalized): bool
    {
        return str_contains($normalized, 'cancel');
    }

    private static function looksDelivered(string $normalized): bool
    {
        if (str_contains($normalized, 'out_for_deliver')
            || str_contains($normalized, 'assigned_for_deliver')
            || str_contains($normalized, 'delivery_on_hold')
            || str_contains($normalized, 'delivery_in_progress')) {
            return false;
        }

        if (str_contains($normalized, 'deliver') && ! str_contains($normalized, 'fail')) {
            return true;
        }

        return false;
    }

    private static function looksShipped(string $normalized): bool
    {
        return str_contains($normalized, 'transit')
            || str_contains($normalized, 'shipped')
            || str_contains($normalized, 'on_the_way')
            || str_contains($normalized, 'out_for')
            || str_contains($normalized, 'hub');
    }

    private static function looksRts(string $normalized): bool
    {
        return str_contains($normalized, 'rts')
            || str_contains($normalized, 'ready')
            || str_contains($normalized, 'picked');
    }

    private static function looksProcessing(string $normalized): bool
    {
        return str_contains($normalized, 'pickup')
            || str_contains($normalized, 'consign')
            || str_contains($normalized, 'courier')
            || str_contains($normalized, 'process');
    }
}
