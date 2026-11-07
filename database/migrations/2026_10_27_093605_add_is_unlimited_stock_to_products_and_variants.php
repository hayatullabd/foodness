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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_unlimited_stock')->default(false)->after('stock');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('is_unlimited_stock')->default(false)->after('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('is_unlimited_stock');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_unlimited_stock');
        });
    }
};
