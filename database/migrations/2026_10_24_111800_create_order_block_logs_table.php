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
        Schema::create('order_block_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('block_type', 50)->index();
            $table->string('offending_value', 255)->nullable();
            $table->string('reason', 500);
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('phone', 50)->nullable()->index();
            $table->string('device_id', 100)->nullable()->index();
            $table->string('customer_name', 255)->nullable();
            $table->text('customer_address')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_block_logs');
    }
};
