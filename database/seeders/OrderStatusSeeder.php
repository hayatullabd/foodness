<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        // 1. All Orders statuses (Internal lifecycle)
        $orderStatuses = [
            ['name' => 'Pending', 'slug' => 'pending', 'sort_order' => 5],
            ['name' => 'Processing', 'slug' => 'processing', 'sort_order' => 10],
            ['name' => 'Incomplete', 'slug' => 'incomplete', 'sort_order' => 12],
            ['name' => 'Incomplete to Confirmed', 'slug' => 'incomplete_to_confirmed', 'sort_order' => 18],
            ['name' => 'No Response', 'slug' => 'no_response', 'sort_order' => 20],
            ['name' => 'Advance Payment', 'slug' => 'advance_payment', 'sort_order' => 30],
            ['name' => 'On Hold', 'slug' => 'on_hold', 'sort_order' => 40],
            ['name' => 'Preorder', 'slug' => 'preorder', 'sort_order' => 50],
            ['name' => 'Cancel', 'slug' => 'cancelled', 'sort_order' => 70],
        ];

        // Clean up any extra/legacy statuses for orders
        OrderStatus::query()
            ->where('section', OrderStatus::SECTION_ORDERS)
            ->whereNotIn('slug', array_column($orderStatuses, 'slug'))
            ->delete();

        foreach ($orderStatuses as $status) {
            OrderStatus::query()->updateOrCreate(
                ['section' => OrderStatus::SECTION_ORDERS, 'slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'is_active' => true,
                    'sort_order' => $status['sort_order'],
                ],
            );
        }

        // 2. Courier statuses (Courier delivery lifecycle)
        $courierStatuses = [
            ['name' => 'Pending', 'slug' => 'pending', 'sort_order' => 5],
            ['name' => 'Processing', 'slug' => 'processing', 'sort_order' => 10],
            ['name' => 'RTS', 'slug' => 'rts', 'sort_order' => 20],
            ['name' => 'Shipped', 'slug' => 'shipped', 'sort_order' => 30],
            ['name' => 'Delivered', 'slug' => 'delivered', 'sort_order' => 40],
            ['name' => 'Pending Return', 'slug' => 'pending_return', 'sort_order' => 50],
            ['name' => 'Returned', 'slug' => 'returned', 'sort_order' => 60],
            ['name' => 'Partial', 'slug' => 'partial', 'sort_order' => 70],
            ['name' => 'Cancelled', 'slug' => 'cancelled', 'sort_order' => 80],
            ['name' => 'Lost', 'slug' => 'lost', 'sort_order' => 90],
        ];

        // Clean up any extra/legacy statuses for courier
        OrderStatus::query()
            ->where('section', OrderStatus::SECTION_COURIER)
            ->whereNotIn('slug', array_column($courierStatuses, 'slug'))
            ->delete();

        foreach ($courierStatuses as $status) {
            OrderStatus::query()->updateOrCreate(
                ['section' => OrderStatus::SECTION_COURIER, 'slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'is_active' => true,
                    'sort_order' => $status['sort_order'],
                ],
            );
        }
    }
}
