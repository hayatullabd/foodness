<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('subcategory_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->foreignId('childcategory_id')->nullable()->after('subcategory_id')->constrained('childcategories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->after('childcategory_id')->constrained()->nullOnDelete();
            $table->decimal('purchase_price', 10, 2)->nullable()->after('sku');
            $table->decimal('reseller_price', 10, 2)->nullable()->after('compare_at_price');
            $table->string('unit')->nullable()->after('stock');
            $table->text('note')->nullable()->after('description');
            $table->string('youtube_video_id')->nullable()->after('note');
            $table->string('meta_title')->nullable()->after('youtube_video_id');
            $table->string('meta_keywords')->nullable()->after('meta_title');
            $table->text('meta_description')->nullable()->after('meta_keywords');
            $table->string('meta_image')->nullable()->after('meta_description');
            $table->string('product_type')->default('physical')->after('meta_image');
            $table->string('source')->default('inhouse')->after('product_type');
            $table->unsignedInteger('sold')->default(0)->after('source');
            $table->boolean('is_wholesale')->default(false)->after('is_featured');
            $table->boolean('free_delivery')->default(false)->after('is_wholesale');
            $table->boolean('is_hot')->default(false)->after('free_delivery');
            $table->boolean('is_flash')->default(false)->after('is_hot');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcategory_id');
            $table->dropConstrainedForeignId('childcategory_id');
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn([
                'purchase_price',
                'reseller_price',
                'unit',
                'note',
                'youtube_video_id',
                'meta_title',
                'meta_keywords',
                'meta_description',
                'meta_image',
                'product_type',
                'source',
                'sold',
                'is_wholesale',
                'free_delivery',
                'is_hot',
                'is_flash',
            ]);
        });
    }
};
