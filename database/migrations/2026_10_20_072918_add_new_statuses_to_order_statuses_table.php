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

        $statuses = [
            ['slug' => 'confirmed', 'name' => 'Confirmed', 'sort_order' => 15],
            ['slug' => 'incomplete_to_confirmed', 'name' => 'Incomplete to Confirmed', 'sort_order' => 18],
            ['slug' => 'on_hold', 'name' => 'On Hold', 'sort_order' => 25],
            ['slug' => 'no_response', 'name' => 'No Response', 'sort_order' => 28],
        ];

        foreach ($statuses as $status) {
            DB::table('order_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'is_active' => true,
                    'sort_order' => $status['sort_order'],
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

        DB::table('order_statuses')
            ->whereIn('slug', ['confirmed', 'incomplete_to_confirmed', 'on_hold', 'no_response'])
            ->delete();
    }
};
