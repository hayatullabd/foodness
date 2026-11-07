<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('delivery_charge_type', 20)->default('default')->after('stock');
            $table->decimal('custom_delivery_inside', 10, 2)->nullable()->after('delivery_charge_type');
            $table->decimal('custom_delivery_outside', 10, 2)->nullable()->after('custom_delivery_inside');
        });

        Schema::table('landing_pages', function (Blueprint $table) {
            $table->json('variant_delivery_charges')->nullable()->after('custom_delivery_outside');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_charge_type',
                'custom_delivery_inside',
                'custom_delivery_outside',
            ]);
        });

        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('variant_delivery_charges');
        });
    }
};
