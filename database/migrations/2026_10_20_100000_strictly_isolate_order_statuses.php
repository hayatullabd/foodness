<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Normalize any legacy order statuses in orders table
        DB::table('orders')->where('status', 'in_courier')->update(['status' => 'processing']);
        DB::table('orders')->where('status', 'on_the_way')->update(['status' => 'shipped']);
        DB::table('orders')->where('status', 'completed')->update(['status' => 'delivered']);
        DB::table('orders')->where('status', 'courier_returned')->update(['status' => 'returned']);
        DB::table('orders')->where('status', 'unpaid')->update(['status' => 'pending']);

        // 2. Strictly clean up order_statuses table to only keep isolated statuses
        $allowedOrders = [
            'pending',
            'processing',
            'incomplete',
            'confirmed',
            'incomplete_to_confirmed',
            'no_response',
            'advance_payment',
            'on_hold',
            'preorder',
            'cancelled',
        ];

        $allowedCourier = [
            'processing',
            'rts',
            'shipped',
            'delivered',
            'pending_return',
            'returned',
            'partial',
            'cancelled',
            'lost',
        ];

        DB::table('order_statuses')
            ->where('section', 'orders')
            ->whereNotIn('slug', $allowedOrders)
            ->delete();

        DB::table('order_statuses')
            ->where('section', 'courier')
            ->whereNotIn('slug', $allowedCourier)
            ->delete();

        // 3. Ensure all valid courier and order statuses exist
        $now = now();
        $courierDefs = [
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

        foreach ($courierDefs as $def) {
            DB::table('order_statuses')->updateOrInsert(
                ['section' => 'courier', 'slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'sort_order' => $def['sort_order'],
                    'is_active' => true,
                    'updated_at' => $now,
                ]
            );
        }

        $orderDefs = [
            ['name' => 'Pending', 'slug' => 'pending', 'sort_order' => 5],
            ['name' => 'Processing', 'slug' => 'processing', 'sort_order' => 10],
            ['name' => 'Incomplete', 'slug' => 'incomplete', 'sort_order' => 12],
            ['name' => 'Confirmed', 'slug' => 'confirmed', 'sort_order' => 15],
            ['name' => 'Incomplete to Confirmed', 'slug' => 'incomplete_to_confirmed', 'sort_order' => 18],
            ['name' => 'No Response', 'slug' => 'no_response', 'sort_order' => 20],
            ['name' => 'Advance Payment', 'slug' => 'advance_payment', 'sort_order' => 30],
            ['name' => 'On Hold', 'slug' => 'on_hold', 'sort_order' => 40],
            ['name' => 'Preorder', 'slug' => 'preorder', 'sort_order' => 50],
            ['name' => 'Cancel', 'slug' => 'cancelled', 'sort_order' => 70],
        ];

        foreach ($orderDefs as $def) {
            DB::table('order_statuses')->updateOrInsert(
                ['section' => 'orders', 'slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'sort_order' => $def['sort_order'],
                    'is_active' => true,
                    'updated_at' => $now,
                ]
            );
        }

        // 4. Re-link order_status_id on orders
        $courierMap = DB::table('order_statuses')->where('section', 'courier')->pluck('id', 'slug');
        $ordersMap = DB::table('order_statuses')->where('section', 'orders')->pluck('id', 'slug');

        $orders = DB::table('orders')->select('id', 'status', 'tracking_number')->get();
        foreach ($orders as $order) {
            $isCourier = ! empty($order->tracking_number);
            $targetId = $isCourier
                ? ($courierMap[$order->status] ?? $courierMap['processing'] ?? null)
                : ($ordersMap[$order->status] ?? $ordersMap['pending'] ?? null);

            if ($targetId) {
                DB::table('orders')->where('id', $order->id)->update(['order_status_id' => $targetId]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible data normalization
    }
};
