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
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->boolean('show_quantity')->default(true)->after('product_selection_type');
            $table->string('checkout_subheading', 500)->nullable()->after('checkout_heading');
            $table->string('product_selection_label')->nullable()->after('checkout_subheading');
            $table->string('variant_label')->nullable()->after('product_selection_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn([
                'show_quantity',
                'checkout_subheading',
                'product_selection_label',
                'variant_label',
            ]);
        });
    }
};
