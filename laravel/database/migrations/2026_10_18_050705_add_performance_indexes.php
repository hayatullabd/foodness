<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // invoice_no already has a unique index from the create migration.
            // courier_api_id already has an FK index from its migration.
            // source_platform already has an index from the attribution migration.

            if (! $this->hasIndex('orders', 'orders_status_index')) {
                $table->index('status');
            }

            if (! $this->hasIndex('orders', 'orders_created_at_index')) {
                $table->index('created_at');
            }

            if (! $this->hasIndex('orders', 'orders_customer_phone_index')) {
                $table->index('customer_phone');
            }

            if (! $this->hasIndex('orders', 'orders_tracking_number_index')) {
                $table->index('tracking_number');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! $this->hasIndex('products', 'products_is_active_index')) {
                $table->index('is_active');
            }

            if (! $this->hasIndex('products', 'products_source_index')) {
                $table->index('source');
            }

            if (! $this->hasIndex('products', 'products_is_featured_index')) {
                $table->index('is_featured');
            }
        });

        Schema::table('incomplete_orders', function (Blueprint $table) {
            if (! $this->hasIndex('incomplete_orders', 'incomplete_orders_customer_phone_index')) {
                $table->index('customer_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if ($this->hasIndex('orders', 'orders_status_index')) {
                $table->dropIndex('orders_status_index');
            }

            if ($this->hasIndex('orders', 'orders_created_at_index')) {
                $table->dropIndex('orders_created_at_index');
            }

            if ($this->hasIndex('orders', 'orders_customer_phone_index')) {
                $table->dropIndex('orders_customer_phone_index');
            }

            if ($this->hasIndex('orders', 'orders_tracking_number_index')) {
                $table->dropIndex('orders_tracking_number_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if ($this->hasIndex('products', 'products_is_active_index')) {
                $table->dropIndex('products_is_active_index');
            }

            if ($this->hasIndex('products', 'products_source_index')) {
                $table->dropIndex('products_source_index');
            }

            if ($this->hasIndex('products', 'products_is_featured_index')) {
                $table->dropIndex('products_is_featured_index');
            }
        });

        Schema::table('incomplete_orders', function (Blueprint $table) {
            if ($this->hasIndex('incomplete_orders', 'incomplete_orders_customer_phone_index')) {
                $table->dropIndex('incomplete_orders_customer_phone_index');
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
