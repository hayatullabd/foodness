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
        Schema::create('order_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('causer_type')->nullable(); // 'admin', 'user', 'system'
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->string('causer_name')->nullable();
            $table->string('action')->index(); // 'updated', 'status_changed', 'item_added', 'item_removed', 'note_added', 'courier_sent', etc.
            $table->text('description')->nullable();
            $table->json('changes')->nullable(); // diff: ['field' => ['old' => ..., 'new' => ...]]
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_activity_logs');
    }
};
