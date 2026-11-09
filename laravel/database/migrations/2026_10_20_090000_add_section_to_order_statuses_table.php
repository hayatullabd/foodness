<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        if (! Schema::hasColumn('order_statuses', 'section')) {
            Schema::table('order_statuses', function (Blueprint $table): void {
                $table->string('section')->default('orders')->after('slug')->index();
            });
        }

        // Drop single slug unique index to allow composite unique (section, slug)
        try {
            Schema::table('order_statuses', function (Blueprint $table): void {
                $table->dropUnique('order_statuses_slug_unique');
            });
        } catch (Throwable $e) {
            // Index might already be dropped or named differently
        }

        // Add composite unique
        try {
            Schema::table('order_statuses', function (Blueprint $table): void {
                $table->unique(['section', 'slug']);
            });
        } catch (Throwable $e) {
            // Already added
        }

        $now = now();

        // 1. Clean up any non-order statuses from 'orders' section
        DB::table('order_statuses')
            ->where('section', 'orders')
            ->whereNotIn('slug', [
                'processing', 'incomplete', 'no_response', 'advance_payment',
                'on_hold', 'preorder', 'approved', 'cancelled',
                'pending', 'confirmed', 'incomplete_to_confirmed', 'unpaid',
            ])
            ->delete();

        // 2. All Orders statuses (Internal lifecycle)
        $orderStatuses = [
            ['slug' => 'processing', 'name' => 'Processing', 'sort_order' => 10],
            ['slug' => 'incomplete', 'name' => 'Incomplete', 'sort_order' => 12],
            ['slug' => 'no_response', 'name' => 'No Response', 'sort_order' => 20],
            ['slug' => 'advance_payment', 'name' => 'Advance Payment', 'sort_order' => 30],
            ['slug' => 'on_hold', 'name' => 'On Hold', 'sort_order' => 40],
            ['slug' => 'preorder', 'name' => 'Preorder', 'sort_order' => 50],
            ['slug' => 'approved', 'name' => 'Approved', 'sort_order' => 60],
            ['slug' => 'cancelled', 'name' => 'Cancel', 'sort_order' => 70],
            ['slug' => 'pending', 'name' => 'Pending', 'sort_order' => 5],
            ['slug' => 'confirmed', 'name' => 'Confirmed', 'sort_order' => 15],
            ['slug' => 'incomplete_to_confirmed', 'name' => 'Incomplete to Confirmed', 'sort_order' => 18],
            ['slug' => 'unpaid', 'name' => 'Unpaid', 'sort_order' => 80],
        ];

        foreach ($orderStatuses as $status) {
            DB::table('order_statuses')->updateOrInsert(
                ['section' => 'orders', 'slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        // 2. Courier statuses (Courier delivery lifecycle)
        $courierStatuses = [
            ['slug' => 'processing', 'name' => 'Processing', 'sort_order' => 10],
            ['slug' => 'rts', 'name' => 'RTS', 'sort_order' => 20],
            ['slug' => 'shipped', 'name' => 'Shipped', 'sort_order' => 30],
            ['slug' => 'delivered', 'name' => 'Delivered', 'sort_order' => 40],
            ['slug' => 'pending_return', 'name' => 'Pending Return', 'sort_order' => 50],
            ['slug' => 'returned', 'name' => 'Returned', 'sort_order' => 60],
            ['slug' => 'partial', 'name' => 'Partial', 'sort_order' => 70],
            ['slug' => 'cancelled', 'name' => 'Cancelled', 'sort_order' => 80],
            ['slug' => 'lost', 'name' => 'Lost', 'sort_order' => 90],
            ['slug' => 'in_courier', 'name' => 'In Courier', 'sort_order' => 15],
            ['slug' => 'on_the_way', 'name' => 'On The Way', 'sort_order' => 35],
            ['slug' => 'completed', 'name' => 'Completed', 'sort_order' => 45],
            ['slug' => 'courier_returned', 'name' => 'Courier Return', 'sort_order' => 65],
            ['slug' => 'refunded', 'name' => 'Refunded', 'sort_order' => 85],
        ];

        foreach ($courierStatuses as $status) {
            DB::table('order_statuses')->updateOrInsert(
                ['section' => 'courier', 'slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        try {
            Schema::table('order_statuses', function (Blueprint $table): void {
                $table->dropUnique(['section', 'slug']);
            });
        } catch (Throwable $e) {
        }

        if (Schema::hasColumn('order_statuses', 'section')) {
            // Delete courier section rows before restoring single unique slug
            DB::table('order_statuses')->where('section', 'courier')->delete();

            $duplicateSlugs = DB::table('order_statuses')
                ->select('slug')
                ->groupBy('slug')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('slug');

            foreach ($duplicateSlugs as $dupSlug) {
                $keepId = DB::table('order_statuses')->where('slug', $dupSlug)->min('id');
                DB::table('order_statuses')->where('slug', $dupSlug)->where('id', '!=', $keepId)->delete();
            }

            Schema::table('order_statuses', function (Blueprint $table): void {
                $table->dropColumn('section');
                $table->unique('slug');
            });
        }
    }
};
