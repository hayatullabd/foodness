<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. Create or ensure Courier Pending status
        DB::table('order_statuses')->updateOrInsert(
            ['section' => 'courier', 'slug' => 'pending'],
            [
                'name' => 'Pending',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $courierPending = DB::table('order_statuses')
            ->where('section', 'courier')
            ->where('slug', 'pending')
            ->first();

        // 2. Transition any existing orders with confirmed status to courier pending
        if ($courierPending) {
            DB::table('orders')
                ->where('status', 'confirmed')
                ->update([
                    'status' => 'pending',
                    'order_status_id' => $courierPending->id,
                    'updated_at' => $now,
                ]);
        }

        // 3. Remove 'confirmed' from All Orders section
        DB::table('order_statuses')
            ->where('section', 'orders')
            ->where('slug', 'confirmed')
            ->delete();
    }

    public function down(): void
    {
        $now = now();

        DB::table('order_statuses')->updateOrInsert(
            ['section' => 'orders', 'slug' => 'confirmed'],
            [
                'name' => 'Confirmed',
                'sort_order' => 15,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('order_statuses')
            ->where('section', 'courier')
            ->where('slug', 'pending')
            ->delete();
    }
};
