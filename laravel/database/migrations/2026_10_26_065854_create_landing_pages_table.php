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
        Schema::create('landing_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->longText('custom_html')->nullable();
            $table->longText('custom_css')->nullable();
            $table->longText('custom_js')->nullable();
            $table->string('checkout_heading')->nullable()->default('অর্ডার কনফার্ম করতে নিচের তথ্যগুলো পূরণ করুন');
            $table->string('checkout_button_text')->nullable()->default('অর্ডার কনফার্ম করুন');
            $table->string('delivery_charge_type')->default('default'); // 'default', 'free', 'custom'
            $table->decimal('custom_delivery_inside', 10, 2)->nullable();
            $table->decimal('custom_delivery_outside', 10, 2)->nullable();
            $table->string('fb_pixel_id')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('orders_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'landing_page_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->foreignId('landing_page_id')->nullable()->after('id')->constrained('landing_pages')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'landing_page_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropForeign(['landing_page_id']);
                $table->dropColumn('landing_page_id');
            });
        }

        Schema::dropIfExists('landing_pages');
    }
};
