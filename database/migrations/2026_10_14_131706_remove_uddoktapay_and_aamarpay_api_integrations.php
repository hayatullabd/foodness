<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove retired payment gateway provider rows.
     */
    public function up(): void
    {
        DB::table('api_integrations')
            ->whereIn('provider', ['uddoktapay', 'aamarpay'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['uddoktapay', 'aamarpay'] as $provider) {
            $exists = DB::table('api_integrations')
                ->where('provider', $provider)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('api_integrations')->insert([
                'provider' => $provider,
                'credentials' => json_encode([]),
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
