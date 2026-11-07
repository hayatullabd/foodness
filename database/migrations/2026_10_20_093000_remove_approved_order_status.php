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

        if (Schema::hasTable('orders')) {
            $processingStatus = DB::table('order_statuses')
                ->where('section', 'orders')
                ->where('slug', 'processing')
                ->first();

            if ($processingStatus) {
                DB::table('orders')
                    ->where('status', 'approved')
                    ->update([
                        'status' => 'processing',
                        'order_status_id' => $processingStatus->id,
                    ]);
            }
        }

        DB::table('order_statuses')->where('slug', 'approved')->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        DB::table('order_statuses')->updateOrInsert(
            ['section' => 'orders', 'slug' => 'approved'],
            [
                'name' => 'Approved',
                'sort_order' => 60,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
