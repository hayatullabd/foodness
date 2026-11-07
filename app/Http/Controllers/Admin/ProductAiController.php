<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductAiController extends Controller
{
    public function generate(Request $request, GeminiService $gemini): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'prompt' => ['nullable', 'string', 'max:3000'],
        ]);

        if (! $gemini->isEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Gemini AI is not enabled. Please enter and save your Gemini API key in API Integration → Gemini AI.',
            ], 422);
        }

        if (! $gemini->isContentGenerationEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Content Generation is disabled in Gemini AI settings.',
            ], 422);
        }

        $customPrompt = $validated['prompt'] ?? $validated['notes'] ?? null;

        $result = $gemini->generateProductDetails(
            $validated['name'],
            $validated['category'] ?? null,
            $customPrompt,
        );

        if (! $result) {
            return response()->json([
                'ok' => false,
                'message' => 'Unable to generate product content from Gemini AI. Please check your API key and quota.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'data' => $result,
        ]);
    }
}
