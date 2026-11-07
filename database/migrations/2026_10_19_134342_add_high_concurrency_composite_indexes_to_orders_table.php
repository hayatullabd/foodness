<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! $this->hasIndex('orders', 'orders_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'orders_status_created_at_index');
            }

            if (! $this->hasIndex('orders', 'orders_phone_created_at_index')) {
                $table->index(['customer_phone', 'created_at'], 'orders_phone_created_at_index');
            }

            if (! $this->hasIndex('orders', 'orders_status_id_created_at_index')) {
                $table->index(['order_status_id', 'created_at'], 'orders_status_id_created_at_index');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! $this->hasIndex('order_items', 'order_items_order_product_index')) {
                $table->index(['order_id', 'product_id'], 'order_items_order_product_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! $this->hasIndex('products', 'products_active_featured_created_index')) {
                $table->index(['is_active', 'is_featured', 'created_at'], 'products_active_featured_created_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if ($this->hasIndex('orders', 'orders_status_created_at_index')) {
                $table->dropIndex('orders_status_created_at_index');
            }

            if ($this->hasIndex('orders', 'orders_phone_created_at_index')) {
                $table->dropIndex('orders_phone_created_at_index');
            }

            if ($this->hasIndex('orders', 'orders_status_id_created_at_index')) {
                $table->dropIndex('orders_status_id_created_at_index');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if ($this->hasIndex('order_items', 'order_items_order_product_index')) {
                $table->dropIndex('order_items_order_product_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if ($this->hasIndex('products', 'products_active_featured_created_index')) {
                $table->dropIndex('products_active_featured_created_index');
            }
        });
    }

    /**
     * Check whether an index exists on the given table.
     */
    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $idx): bool => $idx['name'] === $index);
    }
};
