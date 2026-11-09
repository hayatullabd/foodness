<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use App\Models\ApiIntegration;
use App\Services\Ai\Contracts\AiDriver;
use App\Services\Ai\Drivers\AnthropicDriver;
use App\Services\Ai\Drivers\GeminiDriver;
use App\Services\Ai\Drivers\OpenAiCompatibleDriver;
use App\Support\StoreSettings;

class AiManager
{
    public function contentProvider(): ?AiProvider
    {
        return AiProvider::query()
            ->active()
            ->where('is_default_content', true)
            ->orderBy('sort_order')
            ->first()
            ?? AiProvider::query()->active()->orderBy('sort_order')->first();
    }

    public function assistantProvider(): ?AiProvider
    {
        return AiProvider::query()
            ->active()
            ->where('is_default_assistant', true)
            ->orderBy('sort_order')
            ->first()
            ?? $this->contentProvider();
    }

    public function driver(?AiProvider $provider = null): ?AiDriver
    {
        $provider ??= $this->contentProvider();
        if (! $provider) {
            return null;
        }

        return match ($provider->driver) {
            AiProvider::DRIVER_GEMINI => new GeminiDriver($provider),
            AiProvider::DRIVER_ANTHROPIC => new AnthropicDriver($provider),
            AiProvider::DRIVER_OPENAI,
            AiProvider::DRIVER_OPENAI_COMPATIBLE => new OpenAiCompatibleDriver($provider),
            default => null,
        };
    }

    /**
     * @param  array{temperature?: float, maxOutputTokens?: int, timeout?: int}  $options
     */
    public function generate(string $prompt, ?string $systemInstruction = null, array $options = [], ?AiProvider $provider = null): ?string
    {
        try {
            $driver = $this->driver($provider);
            if (! $driver) {
                return null;
            }

            return $driver->generate($prompt, $systemInstruction, $options);
        } catch (\Throwable) {
            return null;
        }
    }

    public function isContentGenerationEnabled(): bool
    {
        try {
            $provider = $this->contentProvider();
            if ($provider && filled($provider->api_key)) {
                return (bool) $this->settingsValue('content_generation_enabled', true);
            }

            // Legacy fallback: old Gemini integration toggle.
            $legacy = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GEMINI_AI);

            return (bool) $legacy->is_active
                && (bool) $legacy->credential('content_generation_enabled', true)
                && filled($legacy->credential('api_key'));
        } catch (\Throwable) {
            return false;
        }
    }

    public function isAssistantEnabled(): bool
    {
        try {
            $provider = $this->assistantProvider();
            if ($provider && filled($provider->api_key)) {
                return (bool) $this->settingsValue('assistant_enabled', true);
            }

            $legacy = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GEMINI_AI);

            return (bool) $legacy->is_active
                && (bool) $legacy->credential('assistant_enabled', true)
                && filled($legacy->credential('api_key'));
        } catch (\Throwable) {
            return false;
        }
    }

    public function systemPrompt(): string
    {
        $prompt = (string) $this->settingsValue('system_prompt', '');
        if ($prompt === '') {
            $prompt = 'You are a polite, helpful AI shopping assistant for {store_name}. Help customers find products, answer questions regarding orders, shipping, and store policies, and provide concise, accurate information in a friendly tone.';
        }

        $store = StoreSettings::get('store_name') ?: config('app.name', 'Our Store');

        return trim(str_replace('{store_name}', (string) $store, $prompt));
    }

    public function settingsValue(string $key, mixed $default = null): mixed
    {
        $legacy = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GEMINI_AI);

        // Prefer values stored on default content provider meta, then legacy gemini credentials.
        $provider = $this->contentProvider();
        if ($provider && array_key_exists($key, $provider->meta ?? [])) {
            return $provider->metaValue($key, $default);
        }

        return $legacy->credential($key, $default);
    }

    /**
     * Persist global AI feature flags (shared across providers).
     *
     * @param  array<string, mixed>  $flags
     */
    public function saveGlobalFlags(array $flags): void
    {
        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GEMINI_AI);
        $integration->mergeCredentials([
            'content_generation_enabled' => (bool) ($flags['content_generation_enabled'] ?? true),
            'assistant_enabled' => (bool) ($flags['assistant_enabled'] ?? true),
            'system_prompt' => (string) ($flags['system_prompt'] ?? ''),
        ], $integration->secretKeys());
        // Master active if any provider is active.
        $integration->is_active = AiProvider::query()->active()->exists()
            || filled($integration->credential('api_key'));
        $integration->save();

        // Mirror onto default content provider meta for convenience.
        $provider = $this->contentProvider();
        if ($provider) {
            $meta = $provider->meta ?? [];
            $meta['content_generation_enabled'] = (bool) ($flags['content_generation_enabled'] ?? true);
            $meta['assistant_enabled'] = (bool) ($flags['assistant_enabled'] ?? true);
            $meta['system_prompt'] = (string) ($flags['system_prompt'] ?? '');
            $provider->meta = $meta;
            $provider->save();
        }
    }
}
