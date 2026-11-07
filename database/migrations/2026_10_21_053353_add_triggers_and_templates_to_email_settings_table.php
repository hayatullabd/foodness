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
        Schema::table('email_settings', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('from_name');
            $table->string('admin_emails', 500)->nullable()->after('is_active');
            $table->json('triggers')->nullable()->after('admin_emails');
            $table->json('templates')->nullable()->after('triggers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_settings', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'admin_emails', 'triggers', 'templates']);
        });
    }
};
