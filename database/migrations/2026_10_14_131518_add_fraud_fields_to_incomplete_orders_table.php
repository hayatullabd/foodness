<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incomplete_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('incomplete_orders', 'fraud_score')) {
                $table->decimal('fraud_score', 5, 2)->nullable()->after('total');
            }

            if (! Schema::hasColumn('incomplete_orders', 'fraud_report')) {
                $table->json('fraud_report')->nullable()->after('fraud_score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incomplete_orders', function (Blueprint $table) {
            if (Schema::hasColumn('incomplete_orders', 'fraud_report')) {
                $table->dropColumn('fraud_report');
            }

            if (Schema::hasColumn('incomplete_orders', 'fraud_score')) {
                $table->dropColumn('fraud_score');
            }
        });
    }
};
