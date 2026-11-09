<?php

namespace App\Services\Ai\Contracts;

interface AiDriver
{
    /**
     * @param  array{temperature?: float, maxOutputTokens?: int, timeout?: int}  $options
     */
    public function generate(string $prompt, ?string $systemInstruction = null, array $options = []): ?string;
}
