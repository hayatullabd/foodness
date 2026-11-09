<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('notes');
            $table->decimal('fraud_score', 5, 2)->nullable()->after('ip_address');
            $table->text('admin_notes')->nullable()->after('fraud_score');
            $table->string('tracking_number')->nullable()->after('admin_notes');
            $table->string('tracking_url')->nullable()->after('tracking_number');
            $table->string('source')->nullable()->after('tracking_url');
            $table->foreignId('order_status_id')->nullable()->after('status')->constrained('order_statuses')->nullOnDelete();
            $table->index('status');
        });

        $slugMap = [
            'pending' => 'pending',
            'confirmed' => 'processing',
            'processing' => 'processing',
            'shipped' => 'on_the_way',
            'on_the_way' => 'on_the_way',
            'in_courier' => 'in_courier',
            'delivered' => 'completed',
            'completed' => 'completed',
            'unpaid' => 'unpaid',
            'cancelled' => 'cancelled',
        ];

        $statusIds = DB::table('order_statuses')->pluck('id', 'slug');

        foreach (DB::table('orders')->select('id', 'status')->cursor() as $order) {
            $slug = $slugMap[$order->status] ?? 'pending';
            $statusId = $statusIds[$slug] ?? null;

            DB::table('orders')->where('id', $order->id)->update([
                'status' => $slug,
                'order_status_id' => $statusId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_status_id');
            $table->dropIndex(['status']);
            $table->dropColumn([
                'ip_address',
                'fraud_score',
                'admin_notes',
                'tracking_number',
                'tracking_url',
                'source',
            ]);
        });
    }
};
