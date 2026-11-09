<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_apis', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('api_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('base_url')->nullable();
            $table->text('token')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_apis');
    }
};
