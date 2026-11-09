<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('landing_pages') && ! Schema::hasColumn('landing_pages', 'product_selection_type')) {
            Schema::table('landing_pages', function (Blueprint $table): void {
                $table->string('product_selection_type', 20)->default('single')->after('title');
            });
        }

        if (! Schema::hasTable('landing_page_products')) {
            Schema::create('landing_page_products', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('landing_page_id')->constrained('landing_pages')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('custom_price', 10, 2)->nullable();
                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['landing_page_id', 'product_id']);
            });
        }

        if (Schema::hasTable('landing_pages')) {
            $now = now();
            $existingPages = DB::table('landing_pages')->whereNotNull('product_id')->get(['id', 'product_id', 'custom_price']);
            foreach ($existingPages as $lp) {
                DB::table('landing_page_products')->updateOrInsert(
                    ['landing_page_id' => $lp->id, 'product_id' => $lp->product_id],
                    [
                        'custom_price' => $lp->custom_price,
                        'is_default' => true,
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_products');

        if (Schema::hasTable('landing_pages') && Schema::hasColumn('landing_pages', 'product_selection_type')) {
            Schema::table('landing_pages', function (Blueprint $table): void {
                $table->dropColumn('product_selection_type');
            });
        }
    }
};
