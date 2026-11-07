<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_variants', 'sku')) {
                $table->string('sku', 100)->nullable()->after('size_name');
            }

            if (! Schema::hasColumn('product_variants', 'image_2')) {
                $table->string('image_2')->nullable()->after('image');
            }

            if (! Schema::hasColumn('product_variants', 'image_3')) {
                $table->string('image_3')->nullable()->after('image_2');
            }

            if (! Schema::hasColumn('product_variants', 'image_4')) {
                $table->string('image_4')->nullable()->after('image_3');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $columns = collect(['sku', 'image_2', 'image_3', 'image_4'])
                ->filter(fn (string $column): bool => Schema::hasColumn('product_variants', $column))
                ->values()
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
