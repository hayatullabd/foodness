<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_restriction_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('order_restriction_settings', 'block_ip_enabled')) {
                $table->boolean('block_ip_enabled')->default(false)->after('max_per_product');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'blocked_ips')) {
                $table->text('blocked_ips')->nullable()->after('block_ip_enabled');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'max_per_ip')) {
                $table->unsignedInteger('max_per_ip')->nullable()->default(3)->after('blocked_ips');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'block_phone_enabled')) {
                $table->boolean('block_phone_enabled')->default(false)->after('max_per_ip');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'blocked_phones')) {
                $table->text('blocked_phones')->nullable()->after('block_phone_enabled');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'max_per_phone')) {
                $table->unsignedInteger('max_per_phone')->nullable()->default(2)->after('blocked_phones');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'validate_bd_phone')) {
                $table->boolean('validate_bd_phone')->default(true)->after('max_per_phone');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'block_device_enabled')) {
                $table->boolean('block_device_enabled')->default(false)->after('validate_bd_phone');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'blocked_devices')) {
                $table->text('blocked_devices')->nullable()->after('block_device_enabled');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'max_per_device')) {
                $table->unsignedInteger('max_per_device')->nullable()->default(2)->after('blocked_devices');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'block_words_enabled')) {
                $table->boolean('block_words_enabled')->default(false)->after('max_per_device');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'blocked_words')) {
                $table->text('blocked_words')->nullable()->after('block_words_enabled');
            }
            if (! Schema::hasColumn('order_restriction_settings', 'min_address_length')) {
                $table->unsignedInteger('min_address_length')->nullable()->default(10)->after('blocked_words');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_restriction_settings', function (Blueprint $table) {
            $columns = [
                'block_ip_enabled',
                'blocked_ips',
                'max_per_ip',
                'block_phone_enabled',
                'blocked_phones',
                'max_per_phone',
                'validate_bd_phone',
                'block_device_enabled',
                'blocked_devices',
                'max_per_device',
                'block_words_enabled',
                'blocked_words',
                'min_address_length',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('order_restriction_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
