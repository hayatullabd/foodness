<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        $now = now();

        DB::table('order_statuses')->updateOrInsert(
            ['slug' => 'returned'],
            [
                'name' => 'Customer Returned',
                'is_active' => true,
                'sort_order' => 60,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('order_statuses')->updateOrInsert(
            ['slug' => 'courier_returned'],
            [
                'name' => 'Courier Return',
                'is_active' => true,
                'sort_order' => 65,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('order_statuses')->where('slug', 'refunded')->update(['sort_order' => 70, 'updated_at' => $now]);
        DB::table('order_statuses')->where('slug', 'cancelled')->update(['sort_order' => 75, 'updated_at' => $now]);
        DB::table('order_statuses')->where('slug', 'unpaid')->update(['sort_order' => 80, 'updated_at' => $now]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        DB::table('order_statuses')
            ->whereIn('slug', ['returned', 'courier_returned'])
            ->delete();

        DB::table('order_statuses')->where('slug', 'refunded')->update(['sort_order' => 65]);
        DB::table('order_statuses')->where('slug', 'cancelled')->update(['sort_order' => 70]);
        DB::table('order_statuses')->where('slug', 'unpaid')->update(['sort_order' => 60]);
    }
};
