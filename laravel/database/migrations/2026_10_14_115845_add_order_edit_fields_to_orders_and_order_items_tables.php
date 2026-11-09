<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('payment_method');
            $table->string('delivery_division')->nullable()->after('customer_address');
            $table->string('delivery_district')->nullable()->after('delivery_division');
            $table->string('delivery_upazila')->nullable()->after('delivery_district');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'delivery_division',
                'delivery_district',
                'delivery_upazila',
            ]);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
