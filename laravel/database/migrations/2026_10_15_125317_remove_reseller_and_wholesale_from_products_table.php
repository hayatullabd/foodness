<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = collect(['reseller_price', 'is_wholesale'])
            ->filter(fn (string $column): bool => Schema::hasColumn('products', $column))
            ->values()
            ->all();

        if ($columns === []) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'reseller_price')) {
                $table->decimal('reseller_price', 10, 2)->nullable()->after('compare_at_price');
            }

            if (! Schema::hasColumn('products', 'is_wholesale')) {
                $table->boolean('is_wholesale')->default(false)->after('is_featured');
            }
        });
    }
};
