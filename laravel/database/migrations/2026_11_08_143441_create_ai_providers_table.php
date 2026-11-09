<?php

use App\Models\ApiIntegration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('driver', 40); // gemini|openai|anthropic|openai_compatible
            $table->text('api_key')->nullable();
            $table->string('base_url', 500)->nullable();
            $table->string('model', 191)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default_content')->default(false);
            $table->boolean('is_default_assistant')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'driver']);
        });

        // Migrate existing Gemini credentials into the new provider table.
        if (Schema::hasTable('api_integrations')) {
            $row = DB::table('api_integrations')->where('provider', ApiIntegration::PROVIDER_GEMINI_AI)->first();
            if ($row) {
                $creds = json_decode((string) ($row->credentials ?? '{}'), true) ?: [];
                $apiKey = (string) ($creds['api_key'] ?? '');
                if ($apiKey !== '' || (bool) ($row->is_active ?? false)) {
                    DB::table('ai_providers')->insert([
                        'name' => 'Google Gemini',
                        'driver' => 'gemini',
                        'api_key' => $apiKey !== '' ? Crypt::encryptString($apiKey) : null,
                        'base_url' => null,
                        'model' => (string) ($creds['model'] ?? 'gemini-3.5-flash-lite'),
                        'is_active' => (bool) ($row->is_active ?? false),
                        'is_default_content' => true,
                        'is_default_assistant' => true,
                        'sort_order' => 1,
                        'meta' => json_encode([
                            'content_generation_enabled' => (bool) ($creds['content_generation_enabled'] ?? true),
                            'assistant_enabled' => (bool) ($creds['assistant_enabled'] ?? true),
                            'system_prompt' => (string) ($creds['system_prompt'] ?? ''),
                        ], JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
