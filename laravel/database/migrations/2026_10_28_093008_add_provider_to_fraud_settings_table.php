<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fraud_settings', function (Blueprint $table) {
            $table->string('provider', 50)->default('bdcourier')->after('id');
            $table->json('provider_keys')->nullable()->after('api_key');
        });
    }

    public function down(): void
    {
        Schema::table('fraud_settings', function (Blueprint $table) {
            $table->dropColumn(['provider', 'provider_keys']);
        });
    }
};
