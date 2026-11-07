<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function chat(Request $request, GeminiService $gemini): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:600'],
            'history' => ['nullable', 'array', 'max:10'],
            'history.*.role' => ['nullable', 'string'],
            'history.*.text' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $gemini->isAssistantEnabled()) {
            return response()->json([
                'ok' => false,
                'reply' => 'AI Assistant is currently offline.',
            ], 503);
        }

        $reply = $gemini->chatWithAssistant(
            $validated['message'],
            $validated['history'] ?? [],
        );

        return response()->json([
            'ok' => true,
            'reply' => $reply,
        ]);
    }
}
