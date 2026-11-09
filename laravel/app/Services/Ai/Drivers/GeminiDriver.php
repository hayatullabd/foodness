<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\Contracts\AiDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiDriver implements AiDriver
{
    public function __construct(private readonly AiProvider $provider) {}

    public function generate(string $prompt, ?string $systemInstruction = null, array $options = []): ?string
    {
        $apiKey = (string) ($this->provider->api_key ?? '');
        if ($apiKey === '') {
            return null;
        }

        $model = $this->provider->resolvedModel();
        $timeout = (int) ($options['timeout'] ?? 45);
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $prompt]],
                ],
            ],
            'generationConfig' => [
                'temperature' => (float) ($options['temperature'] ?? 0.5),
                'maxOutputTokens' => (int) ($options['maxOutputTokens'] ?? 4096),
            ],
        ];

        if (filled($systemInstruction)) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $base = $this->provider->resolvedBaseUrl() ?: 'https://generativelanguage.googleapis.com/v1beta';
        $url = $base.'/models/'.$model.':generateContent?key='.$apiKey;

        try {
            $response = Http::timeout($timeout)->acceptJson()->post($url, $payload);
            if ($response->successful()) {
                return trim((string) $response->json('candidates.0.content.parts.0.text'));
            }

            if ($response->status() === 404 && $model !== 'gemini-3.5-flash-lite') {
                $fallback = $base.'/models/gemini-3.5-flash-lite:generateContent?key='.$apiKey;
                $retry = Http::timeout($timeout)->acceptJson()->post($fallback, $payload);
                if ($retry->successful()) {
                    return trim((string) $retry->json('candidates.0.content.parts.0.text'));
                }
            }

            Log::warning('GeminiDriver non-200.', ['status' => $response->status(), 'body' => $response->json()]);
        } catch (Throwable $e) {
            Log::error('GeminiDriver failed.', ['message' => $e->getMessage()]);
        }

        return null;
    }
}
