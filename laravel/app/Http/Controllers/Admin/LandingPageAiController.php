<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use App\Support\LandingHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageAiController extends Controller
{
    public function generate(Request $request, GeminiService $gemini): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'offer_text' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'language' => ['nullable', 'string', 'in:bn,en'],
            'image_urls' => ['nullable', 'array', 'max:8'],
            'image_urls.*' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $gemini->isEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Gemini AI is not enabled. Save your API key in API Integration → Gemini AI.',
            ], 422);
        }

        if (! $gemini->isContentGenerationEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Content Generation is disabled in Gemini AI settings.',
            ], 422);
        }

        $result = $gemini->generateLandingPage(
            $validated['product_name'],
            $validated['offer_text'] ?? null,
            $validated['notes'] ?? null,
            array_values(array_filter($validated['image_urls'] ?? [])),
            $validated['language'] ?? 'bn',
        );

        if (! $result) {
            return response()->json([
                'ok' => false,
                'message' => 'Could not generate landing page. Check Gemini API key, model, and quota — then try again.',
            ], 500);
        }

        $clean = LandingHtml::sanitizeAiOutput(
            $result['html'],
            $result['css'],
            $result['js'],
        );

        return response()->json([
            'ok' => true,
            'data' => $clean,
            'message' => 'Premium mobile-friendly landing page generated. Review HTML/CSS, then Save.',
        ]);
    }
}
