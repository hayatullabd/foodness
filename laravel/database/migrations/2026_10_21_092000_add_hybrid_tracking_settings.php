<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $defaultSettings = [
            // GTM Client-Side & Server Container Settings
            'gtm_enabled' => '0',
            'gtm_container_id' => '',
            'custom_gtm_server_url' => '',

            // Facebook Conversion API (CAPI) Settings
            'fb_capi_enabled' => '0',
            'fb_pixel_id' => '',
            'fb_access_token' => '',
            'fb_test_event_code' => '',

            // TikTok Events API Settings
            'tiktok_api_enabled' => '0',
            'tiktok_pixel_id' => '',
            'tiktok_access_token' => '',
            'tiktok_test_event_code' => '',

            // GA4 Measurement Protocol API Settings
            'ga4_api_enabled' => '0',
            'ga4_measurement_id' => '',
            'ga4_api_secret' => '',
        ];

        $now = now();

        foreach ($defaultSettings as $key => $defaultValue) {
            $exists = DB::table('settings')->where('key', $key)->exists();

            if (! $exists) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $defaultValue,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $keys = [
            'gtm_enabled',
            'gtm_container_id',
            'custom_gtm_server_url',
            'fb_capi_enabled',
            'fb_pixel_id',
            'fb_access_token',
            'fb_test_event_code',
            'tiktok_api_enabled',
            'tiktok_pixel_id',
            'tiktok_access_token',
            'tiktok_test_event_code',
            'ga4_api_enabled',
            'ga4_measurement_id',
            'ga4_api_secret',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
