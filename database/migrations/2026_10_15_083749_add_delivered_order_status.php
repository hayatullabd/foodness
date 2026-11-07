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
            ['slug' => 'delivered'],
            [
                'name' => 'Delivered',
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('order_statuses')
            ->where('slug', 'completed')
            ->update(['sort_order' => 55, 'updated_at' => $now]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        DB::table('order_statuses')->where('slug', 'delivered')->delete();

        DB::table('order_statuses')
            ->where('slug', 'completed')
            ->update(['sort_order' => 50, 'updated_at' => now()]);
    }
};
