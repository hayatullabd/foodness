<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('refund_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('method')->default('original_payment');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('account')->nullable();
            $table->string('account_name')->nullable();
            $table->string('transaction_id')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
