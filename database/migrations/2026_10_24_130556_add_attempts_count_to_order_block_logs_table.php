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
        Schema::table('order_block_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_block_logs', 'attempts_count')) {
                $table->unsignedInteger('attempts_count')->default(1)->after('customer_address');
            }
            if (! Schema::hasColumn('order_block_logs', 'last_attempt_at')) {
                $table->timestamp('last_attempt_at')->nullable()->after('attempts_count')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_block_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('order_block_logs', 'last_attempt_at')) {
                $table->dropIndex(['last_attempt_at']);
                $table->dropColumn('last_attempt_at');
            }
            if (Schema::hasColumn('order_block_logs', 'attempts_count')) {
                $table->dropColumn('attempts_count');
            }
        });
    }
};
