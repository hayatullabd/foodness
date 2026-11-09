<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\Contracts\AiDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiCompatibleDriver implements AiDriver
{
    public function __construct(private readonly AiProvider $provider) {}

    public function generate(string $prompt, ?string $systemInstruction = null, array $options = []): ?string
    {
        $apiKey = (string) ($this->provider->api_key ?? '');
        $base = $this->provider->resolvedBaseUrl();
        if ($apiKey === '' || $base === '') {
            return null;
        }

        $messages = [];
        if (filled($systemInstruction)) {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $timeout = (int) ($options['timeout'] ?? 45);
        $payload = [
            'model' => $this->provider->resolvedModel(),
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? 0.5),
            'max_tokens' => (int) ($options['maxOutputTokens'] ?? 4096),
        ];

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->acceptJson()
                ->post($base.'/chat/completions', $payload);

            if ($response->successful()) {
                return trim((string) $response->json('choices.0.message.content'));
            }

            Log::warning('OpenAiCompatibleDriver non-200.', [
                'provider' => $this->provider->name,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        } catch (Throwable $e) {
            Log::error('OpenAiCompatibleDriver failed.', [
                'provider' => $this->provider->name,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
