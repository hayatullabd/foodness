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
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                $existingIndexes = collect(Schema::getIndexes('orders'))->pluck('name')->all();

                if (Schema::hasColumn('orders', 'payment_status') && ! in_array('orders_payment_status_index', $existingIndexes, true)) {
                    $table->index('payment_status', 'orders_payment_status_index');
                }

                if (Schema::hasColumn('orders', 'payment_method') && ! in_array('orders_payment_method_index', $existingIndexes, true)) {
                    $table->index('payment_method', 'orders_payment_method_index');
                }

                if (Schema::hasColumns('orders', ['payment_status', 'created_at']) && ! in_array('orders_payment_status_created_at_index', $existingIndexes, true)) {
                    $table->index(['payment_status', 'created_at'], 'orders_payment_status_created_at_index');
                }
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $existingIndexes = collect(Schema::getIndexes('order_items'))->pluck('name')->all();

                if (Schema::hasColumn('order_items', 'variant_id') && ! in_array('order_items_variant_id_index', $existingIndexes, true)) {
                    $table->index('variant_id', 'order_items_variant_id_index');
                }
            });
        }

        if (Schema::hasTable('landing_pages')) {
            Schema::table('landing_pages', function (Blueprint $table): void {
                $existingIndexes = collect(Schema::getIndexes('landing_pages'))->pluck('name')->all();

                if (Schema::hasColumn('landing_pages', 'is_active') && ! in_array('landing_pages_is_active_index', $existingIndexes, true)) {
                    $table->index('is_active', 'landing_pages_is_active_index');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                $existingIndexes = collect(Schema::getIndexes('users'))->pluck('name')->all();

                if (Schema::hasColumn('users', 'is_active') && ! in_array('users_is_active_index', $existingIndexes, true)) {
                    $table->index('is_active', 'users_is_active_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                $existingIndexes = collect(Schema::getIndexes('orders'))->pluck('name')->all();

                if (in_array('orders_payment_status_created_at_index', $existingIndexes, true)) {
                    $table->dropIndex('orders_payment_status_created_at_index');
                }
                if (in_array('orders_payment_method_index', $existingIndexes, true)) {
                    $table->dropIndex('orders_payment_method_index');
                }
                if (in_array('orders_payment_status_index', $existingIndexes, true)) {
                    $table->dropIndex('orders_payment_status_index');
                }
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $existingIndexes = collect(Schema::getIndexes('order_items'))->pluck('name')->all();

                if (in_array('order_items_variant_id_index', $existingIndexes, true)) {
                    $table->dropIndex('order_items_variant_id_index');
                }
            });
        }

        if (Schema::hasTable('landing_pages')) {
            Schema::table('landing_pages', function (Blueprint $table): void {
                $existingIndexes = collect(Schema::getIndexes('landing_pages'))->pluck('name')->all();

                if (in_array('landing_pages_is_active_index', $existingIndexes, true)) {
                    $table->dropIndex('landing_pages_is_active_index');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                $existingIndexes = collect(Schema::getIndexes('users'))->pluck('name')->all();

                if (in_array('users_is_active_index', $existingIndexes, true)) {
                    $table->dropIndex('users_is_active_index');
                }
            });
        }
    }
};
