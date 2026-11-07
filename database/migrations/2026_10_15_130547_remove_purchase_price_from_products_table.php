<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'purchase_price')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('purchase_price');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'purchase_price')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('purchase_price', 10, 2)->nullable()->after('sku');
        });
    }
};
