<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\Contracts\AiDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnthropicDriver implements AiDriver
{
    public function __construct(private readonly AiProvider $provider) {}

    public function generate(string $prompt, ?string $systemInstruction = null, array $options = []): ?string
    {
        $apiKey = (string) ($this->provider->api_key ?? '');
        if ($apiKey === '') {
            return null;
        }

        $base = $this->provider->resolvedBaseUrl() ?: 'https://api.anthropic.com/v1';
        $timeout = (int) ($options['timeout'] ?? 45);
        $payload = [
            'model' => $this->provider->resolvedModel(),
            'max_tokens' => (int) ($options['maxOutputTokens'] ?? 4096),
            'temperature' => (float) ($options['temperature'] ?? 0.5),
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];
        if (filled($systemInstruction)) {
            $payload['system'] = $systemInstruction;
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->acceptJson()
                ->post($base.'/messages', $payload);

            if ($response->successful()) {
                $parts = $response->json('content');
                if (is_array($parts)) {
                    $text = collect($parts)
                        ->where('type', 'text')
                        ->pluck('text')
                        ->implode("\n");

                    return trim($text);
                }
            }

            Log::warning('AnthropicDriver non-200.', ['status' => $response->status(), 'body' => $response->json()]);
        } catch (Throwable $e) {
            Log::error('AnthropicDriver failed.', ['message' => $e->getMessage()]);
        }

        return null;
    }
}
