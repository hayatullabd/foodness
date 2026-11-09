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

        $newStatuses = [
            ['slug' => 'incomplete', 'name' => 'Incomplete', 'sort_order' => 12],
            ['slug' => 'approved', 'name' => 'Approved', 'sort_order' => 16],
            ['slug' => 'advance_payment', 'name' => 'Advance Payment', 'sort_order' => 22],
            ['slug' => 'preorder', 'name' => 'Preorder', 'sort_order' => 26],
            ['slug' => 'rts', 'name' => 'RTS', 'sort_order' => 32],
            ['slug' => 'shipped', 'name' => 'Shipped', 'sort_order' => 35],
            ['slug' => 'partial', 'name' => 'Partial', 'sort_order' => 52],
            ['slug' => 'pending_return', 'name' => 'Pending Return', 'sort_order' => 58],
            ['slug' => 'lost', 'name' => 'Lost', 'sort_order' => 78],
        ];

        foreach ($newStatuses as $status) {
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
            ->whereIn('slug', [
                'incomplete',
                'approved',
                'advance_payment',
                'preorder',
                'rts',
                'shipped',
                'partial',
                'pending_return',
                'lost',
            ])
            ->delete();
    }
};
