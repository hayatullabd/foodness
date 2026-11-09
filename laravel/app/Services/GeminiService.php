<?php

namespace App\Services;

use App\Models\ApiIntegration;
use App\Models\Category;
use App\Models\ShippingCharge;
use App\Services\Ai\AiManager;
use App\Support\StoreSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiService
{
    public const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public function integration(): ApiIntegration
    {
        return ApiIntegration::forProvider(ApiIntegration::PROVIDER_GEMINI_AI);
    }

    public function isEnabled(): bool
    {
        $manager = app(AiManager::class);
        if ($manager->contentProvider() || $manager->assistantProvider()) {
            return true;
        }

        $integration = $this->integration();

        return (bool) $integration->is_active && filled($integration->credential('api_key'));
    }

    public function isContentGenerationEnabled(): bool
    {
        return app(AiManager::class)->isContentGenerationEnabled();
    }

    public function isAssistantEnabled(): bool
    {
        return app(AiManager::class)->isAssistantEnabled();
    }

    public function apiKey(): string
    {
        return (string) $this->integration()->credential('api_key', '');
    }

    public function model(): string
    {
        $raw = (string) $this->integration()->credential('model', 'gemini-3.5-flash-lite');

        $model = filled($raw) ? trim($raw) : 'gemini-3.5-flash-lite';

        // Auto-migrate deprecated models that Google Gemini API now rejects with 404
        if (in_array($model, ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-2.5-flash-lite', 'gemini-1.5-flash'], true)) {
            return 'gemini-3.5-flash-lite';
        }

        return $model;
    }

    public function storeName(): string
    {
        $name = StoreSettings::get('store_name');

        if (filled($name)) {
            return trim((string) $name);
        }

        return (string) config('app.name', 'Our Store');
    }

    public function systemPrompt(): string
    {
        return app(AiManager::class)->systemPrompt();
    }

    /**
     * Send multimodal parts to Gemini API.
     *
     * @param  list<array<string, mixed>>  $parts
     */
    public function generateContentWithParts(array $parts, ?string $systemInstruction = null, ?array $generationConfig = null, int $timeoutSeconds = 35): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $apiKey = $this->apiKey();
        $model = $this->model();
        $timeoutSeconds = max(10, min(90, $timeoutSeconds));

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => $generationConfig ?? [
                'temperature' => 0.2,
                'maxOutputTokens' => 2048,
            ],
        ];

        if ($systemInstruction) {
            $payload['system_instruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $url = self::API_BASE_URL.'/models/'.$model.':generateContent?key='.$apiKey;

        try {
            $response = Http::timeout($timeoutSeconds)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                $text = (string) $response->json('candidates.0.content.parts.0.text');

                return trim($text);
            }

            // If the model was not found / deprecated (404), fallback to gemini-3.5-flash-lite
            if ($response->status() === 404 && $model !== 'gemini-3.5-flash-lite') {
                Log::info("Gemini model {$model} returned 404. Retrying with gemini-3.5-flash-lite.");
                $fallbackUrl = self::API_BASE_URL.'/models/gemini-3.5-flash-lite:generateContent?key='.$apiKey;
                $fallbackRes = Http::timeout($timeoutSeconds)->acceptJson()->post($fallbackUrl, $payload);
                if ($fallbackRes->successful()) {
                    return trim((string) $fallbackRes->json('candidates.0.content.parts.0.text'));
                }
            }

            Log::warning('Gemini API call returned non-200.', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return null;
        } catch (Throwable $exception) {
            Log::error('Gemini API request failed.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send content generation request to Gemini API.
     */
    public function generateContent(string $prompt, ?string $systemInstruction = null): ?string
    {
        $managed = app(AiManager::class)->generate($prompt, $systemInstruction, [
            'temperature' => 0.7,
            'maxOutputTokens' => 2048,
            'timeout' => 45,
        ]);

        if ($managed !== null && $managed !== '') {
            return $managed;
        }

        return $this->generateContentWithParts([['text' => $prompt]], $systemInstruction, [
            'temperature' => 0.7,
            'maxOutputTokens' => 2048,
        ]);
    }

    /**
     * Generate product descriptions and SEO metadata.
     *
     * @return array{short_description: string, description: string, meta_title: string, meta_description: string}|null
     */
    public function generateProductDetails(string $productName, ?string $category = null, ?string $notes = null): ?array
    {
        if (! $this->isContentGenerationEnabled()) {
            return null;
        }

        $storeName = $this->storeName();

        $userPromptSection = '';
        if (filled($notes)) {
            $userPromptSection = "IMPORTANT CUSTOM INSTRUCTIONS / PROMPT FROM USER:\n".
                "\"{$notes}\"\n".
                "Follow the above user instructions strictly regarding tone, key points, language, fabric/style details, or special offers.\n\n";
        }

        $prompt = "You are a professional e-commerce copywriter for an online store named '{$storeName}'.\n".
            "Write high-converting, professional product copy for this item:\n".
            "Product Name: {$productName}\n".
            ($category ? "Category: {$category}\n" : '').
            $userPromptSection.
            "\n".
            "Please respond ONLY with a raw JSON object (no markdown code blocks, no ```json ```, just { ... }) containing these exact keys:\n".
            "1. \"short_description\": A concise 1-2 sentence hook (max 180 characters) highlighting the main benefit and fabric/style.\n".
            "2. \"description\": A rich HTML description suitable for a WYSIWYG editor containing:\n".
            "   - An engaging introductory paragraph\n".
            "   - <h3>Key Features:</h3> followed by a <ul> of 4-6 bullet points highlighting material, comfort, craftsmanship, fit\n".
            "   - <h3>Specifications & Care:</h3> with practical wash or care advice\n".
            "3. \"meta_title\": SEO-optimized meta title under 60 characters (e.g. \"{$productName} - Buy Online | {$storeName}\")\n".
            "4. \"meta_description\": Compelling Google search snippet under 160 characters encouraging clicks.\n".
            "Language Rule: If the user custom instructions/prompt is written in Bengali or asks for Bengali, write all copy in natural, high-quality Bengali. Otherwise, write in engaging English.\n";

        $rawResponse = $this->generateContent($prompt);

        if (! $rawResponse) {
            return null;
        }

        // Clean any markdown code fences
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($rawResponse));
        $cleaned = preg_replace('/\s*```$/i', '', $cleaned);

        $data = json_decode((string) $cleaned, true);

        if (! is_array($data) || empty($data['description'])) {
            return [
                'short_description' => substr(strip_tags($rawResponse), 0, 180),
                'description' => nl2br(e($rawResponse)),
                'meta_title' => substr($productName.' | '.$storeName, 0, 60),
                'meta_description' => substr(strip_tags($rawResponse), 0, 160),
            ];
        }

        return [
            'short_description' => (string) ($data['short_description'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'meta_title' => (string) ($data['meta_title'] ?? substr($productName.' | '.$storeName, 0, 60)),
            'meta_description' => (string) ($data['meta_description'] ?? ''),
        ];
    }

    /**
     * Generate a premium, mobile-first landing page HTML/CSS fragment.
     *
     * @param  list<string>  $imageUrls
     * @return array{html: string, css: string, js: string}|null
     */
    public function generateLandingPage(
        string $productName,
        ?string $offerText = null,
        ?string $notes = null,
        array $imageUrls = [],
        string $language = 'bn',
    ): ?array {
        if (! $this->isContentGenerationEnabled()) {
            return null;
        }

        $storeName = $this->storeName();
        $language = in_array($language, ['bn', 'en'], true) ? $language : 'bn';
        $images = collect($imageUrls)
            ->filter(fn ($url) => is_string($url) && filled($url))
            ->take(6)
            ->values()
            ->all();

        $imageBlock = $images === []
            ? "Use placeholders: {{product_image_url}} for the main image.\n"
            : "Use these image URLs in <img src=\"...\"> tags (keep paths exact):\n- ".implode("\n- ", $images)."\nAlso support {{product_image_url}} and {{product_title}} / {{product_price}} placeholders where helpful.\n";

        $notesBlock = filled($notes)
            ? "Extra brand/offer instructions from admin:\n\"{$notes}\"\n"
            : '';

        $offerBlock = filled($offerText)
            ? "Offer / headline hint: {$offerText}\n"
            : '';

        $systemInstruction = <<<SYS
You are an expert conversion-focused landing page designer for Bangladesh COD e-commerce food brands.
Return ONLY raw JSON (no markdown fences) with keys: html, css, js.
Design must feel premium, clean, mobile-first, and high-converting.
SYS;

        $prompt = <<<PROMPT
Store: {$storeName}
Product: {$productName}
Language for all visible copy: {$language}
{$offerBlock}{$notesBlock}{$imageBlock}

Output JSON schema:
{
  "html": "HTML fragment only",
  "css": "CSS only",
  "js": "optional small JS or empty string"
}

HARD RULES for html:
1. NO <!DOCTYPE>, <html>, <head>, or <body> wrappers.
2. Must include the exact marker [checkout_form] once where the order form should appear (usually after benefits, before footer).
3. All "Order" CTAs must use href="#checkout-section".
4. Mobile-first structure: full-bleed hero image, clear product title, price area using {{product_price}}, short benefits, trust line, then [checkout_form], then compact footer with phone placeholder 01XXXXXXXXX.
5. Use semantic sections/classes like .hero-section, .btn-primary, .features-section — not Tailwind utility soup only.
6. Do not invent checkout form fields. Do not include <form> for orders.

HARD RULES for css:
1. Style only marketing classes (.hero-section, .btn-primary, etc.).
2. NEVER target #checkout-section, form, input, textarea, select, or .landing-order-form.
3. Avoid universal resets like * { margin:0; padding:0; }. box-sizing on .lp-content is OK.
4. Use CSS variables: --lp-theme for primary buttons/accents (fallback #044120), optional --lp-accent.
5. Must be responsive with @media (min-width: 768px).
6. If using Google Fonts @import, keep the entire @import on ONE line.

HARD RULES for js:
1. Optional carousel/animations only.
2. Never hijack form submit, never remove checkout inputs.

Create a premium food-commerce look: strong typography, generous spacing, one clear CTA color from var(--lp-theme), no clutter.
PROMPT;

        $rawResponse = app(AiManager::class)->generate($prompt, $systemInstruction, [
            'temperature' => 0.55,
            'maxOutputTokens' => 8192,
            'timeout' => 75,
        ]);

        if ($rawResponse === null || $rawResponse === '') {
            $rawResponse = $this->generateContentWithParts(
                [['text' => $prompt]],
                $systemInstruction,
                [
                    'temperature' => 0.55,
                    'maxOutputTokens' => 8192,
                ],
                75,
            );
        }

        if (! $rawResponse) {
            return null;
        }

        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($rawResponse)) ?? trim($rawResponse);
        $cleaned = preg_replace('/\s*```$/i', '', $cleaned) ?? $cleaned;
        $data = json_decode((string) $cleaned, true);

        if (! is_array($data)) {
            // Some models wrap JSON in prose — try first {...} block.
            if (preg_match('/\{[\s\S]*\}/', (string) $cleaned, $m)) {
                $data = json_decode($m[0], true);
            }
        }

        if (! is_array($data)) {
            return null;
        }

        $html = trim((string) ($data['html'] ?? ''));
        $css = trim((string) ($data['css'] ?? ''));
        $js = trim((string) ($data['js'] ?? ''));

        if ($html === '') {
            return null;
        }

        return [
            'html' => $html,
            'css' => $css,
            'js' => $js,
        ];
    }

    /**
     * Customer-facing assistant chat.
     *
     * @param  list<array{role?: string, text?: string, message?: string}>  $history
     */
    public function chatWithAssistant(string $message, array $history = []): string
    {
        if (! $this->isAssistantEnabled()) {
            return 'AI Assistant is currently offline. Please reach out via our contact page.';
        }

        $storeName = $this->storeName();

        $shippingList = ShippingCharge::query()->active()->get()->map(function ($s) {
            return "{$s->name}: Tk {$s->amount}";
        })->implode(', ');

        $categoriesList = Category::query()->active()->pluck('name')->take(10)->implode(', ');

        $supportPhone = (string) (StoreSettings::get('floating_call_number') ?: StoreSettings::get('store_phone') ?: '');
        $supportWa = (string) (StoreSettings::get('floating_whatsapp_number') ?: $supportPhone);

        $systemInstruction = $this->systemPrompt()."\n\n".
            "Store Context:\n".
            "- Store Name: {$storeName}\n".
            '- Available Delivery Options: '.($shippingList ?: 'Inside Dhaka: Tk 70, Outside Dhaka: Tk 130')."\n".
            '- Popular Categories: '.($categoriesList ?: 'Fashion, Men, Women, Panjabi, Saree, Lifestyle')."\n".
            "- Payment Methods: Cash on Delivery (COD) and Online Payment.\n".
            ($supportPhone ? "- Support Phone / Hotline: {$supportPhone}\n" : '').
            ($supportWa ? "- Support WhatsApp: {$supportWa}\n" : '').
            "- Live Agent Policy: If the customer requests to talk to a human agent, manager, or customer care representative (e.g. 'agent er sathe kotha bolbo', 'manush kothai', 'live agent', 'call support'), politely inform them that they can directly chat with our human support team on WhatsApp or call our support hotline (".($supportPhone ?: 'our support line')."). Always encourage them to use the WhatsApp / Call button in the chat header or say you are connecting them to live support.\n".
            '- Language Guidelines: If the customer talks in Bengali (or Banglish), reply politely in natural conversational Bengali. If they talk in English, reply in English. Keep answers concise, helpful, and under 3-4 sentences.';

        $prompt = '';
        if (! empty($history)) {
            $prompt .= "Recent conversation context:\n";
            foreach (array_slice($history, -4) as $h) {
                $role = ($h['role'] ?? 'user') === 'user' ? 'Customer' : 'Assistant';
                $text = (string) ($h['text'] ?? $h['message'] ?? '');
                $prompt .= "{$role}: {$text}\n";
            }
            $prompt .= "\n";
        }
        $prompt .= "Customer: {$message}\nAssistant:";

        $manager = app(AiManager::class);
        $reply = $manager->generate($prompt, $systemInstruction, [
            'temperature' => 0.5,
            'maxOutputTokens' => 2048,
            'timeout' => 45,
        ], $manager->assistantProvider()) ?? $this->generateContent($prompt, $systemInstruction);

        return $reply ?: 'Thank you for reaching out! Our team is available to assist you. Please let us know what you are looking for.';
    }

    /**
     * Extract structured order information from customer text messages or screenshots.
     *
     * @return array{
     *     customer_name: string|null,
     *     customer_phone: string|null,
     *     customer_address: string|null,
     *     delivery_division: string|null,
     *     delivery_district: string|null,
     *     delivery_upazila: string|null,
     *     source_platform: string|null,
     *     notes: string|null,
     *     payment_method: string|null,
     *     items: list<array{product_name: string, color: string|null, size: string|null, qty: int}>
     * }
     */
    public function parseOrderFromTextOrImage(?string $text = null, ?string $imageBase64 = null, ?string $imageMime = null): array
    {
        $parts = [];

        $systemInstruction = "You are an expert e-commerce order extractor for an online store in Bangladesh.\n".
            "Extract customer order details from the provided customer message or chat screenshot (WhatsApp, Messenger, Facebook, SMS).\n".
            "Rules:\n".
            "- Normalize Bengali numerals (০-৯) in phone numbers to standard 11 English digits (e.g. 017XXXXXXXX). Remove country code (+88 or 88).\n".
            "- Identify the customer full name, complete delivery address, division, district, and upazila/thana in Bangladesh.\n".
            "- Identify products/items mentioned with their color, size, and quantity (default qty 1).\n".
            "- Determine source_platform ('whatsapp', 'facebook', 'message', 'phone_call', or 'manual') if visible from screenshot UI or chat context.\n".
            "- Output valid raw JSON only (no markdown fences, no explanatory text, just the JSON object).\n".
            "Format:\n".
            "{\n".
            "  \"customer_name\": \"...\",\n".
            "  \"customer_phone\": \"01XXXXXXXXX\",\n".
            "  \"customer_address\": \"...\",\n".
            "  \"delivery_division\": \"...\",\n".
            "  \"delivery_district\": \"...\",\n".
            "  \"delivery_upazila\": \"...\",\n".
            "  \"source_platform\": \"whatsapp|facebook|message|phone_call|manual\",\n".
            "  \"notes\": \"...\",\n".
            "  \"payment_method\": \"cod\",\n".
            "  \"items\": [\n".
            "    {\"product_name\": \"...\", \"color\": \"...\", \"size\": \"...\", \"qty\": 1}\n".
            "  ]\n".
            '}';

        if (filled($text)) {
            $parts[] = ['text' => "Customer Message / Chat text:\n".$text];
        } else {
            $parts[] = ['text' => 'Please analyze this customer message screenshot and extract order details (customer name, 11-digit phone number, address, division, district, upazila, products, colors, sizes, and quantities).'];
        }

        if (filled($imageBase64) && filled($imageMime)) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $imageMime,
                    'data' => $imageBase64,
                ],
            ];
        }

        if ($this->isEnabled() && ! empty($parts)) {
            $rawJson = $this->generateContentWithParts($parts, $systemInstruction, [
                'temperature' => 0.1,
                'maxOutputTokens' => 2048,
            ]);

            if ($rawJson) {
                $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($rawJson));
                $cleaned = preg_replace('/\s*```$/i', '', (string) $cleaned);
                $parsed = json_decode((string) $cleaned, true);

                if (is_array($parsed)) {
                    return $this->formatOrderExtractionResult($parsed);
                }
            }
        }

        return $this->fallbackHeuristicParser($text ?? '');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     customer_name: string|null,
     *     customer_phone: string|null,
     *     customer_address: string|null,
     *     delivery_division: string|null,
     *     delivery_district: string|null,
     *     delivery_upazila: string|null,
     *     source_platform: string|null,
     *     notes: string|null,
     *     payment_method: string|null,
     *     items: list<array{product_name: string, color: string|null, size: string|null, qty: int}>
     * }
     */
    private function formatOrderExtractionResult(array $data): array
    {
        $phone = isset($data['customer_phone']) ? trim((string) $data['customer_phone']) : null;
        if ($phone) {
            $phone = strtr($phone, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
            $phone = preg_replace('/[^\d+]/', '', $phone);
            if (str_starts_with($phone, '+880')) {
                $phone = '0'.substr($phone, 4);
            } elseif (str_starts_with($phone, '880')) {
                $phone = '0'.substr($phone, 3);
            }
        }

        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                if (is_array($item) && filled($item['product_name'] ?? null)) {
                    $items[] = [
                        'product_name' => trim((string) $item['product_name']),
                        'color' => filled($item['color'] ?? null) ? trim((string) $item['color']) : null,
                        'size' => filled($item['size'] ?? null) ? trim((string) $item['size']) : null,
                        'qty' => max(1, (int) ($item['qty'] ?? 1)),
                    ];
                }
            }
        }

        return [
            'customer_name' => filled($data['customer_name'] ?? null) ? trim((string) $data['customer_name']) : null,
            'customer_phone' => $phone,
            'customer_address' => filled($data['customer_address'] ?? null) ? trim((string) $data['customer_address']) : null,
            'delivery_division' => filled($data['delivery_division'] ?? null) ? trim((string) $data['delivery_division']) : null,
            'delivery_district' => filled($data['delivery_district'] ?? null) ? trim((string) $data['delivery_district']) : null,
            'delivery_upazila' => filled($data['delivery_upazila'] ?? null) ? trim((string) $data['delivery_upazila']) : null,
            'source_platform' => filled($data['source_platform'] ?? null) ? trim((string) $data['source_platform']) : 'manual',
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            'payment_method' => filled($data['payment_method'] ?? null) ? trim((string) $data['payment_method']) : 'cod',
            'items' => $items,
        ];
    }

    /**
     * Regex and heuristic fallback parser for plain text.
     *
     * @return array{
     *     customer_name: string|null,
     *     customer_phone: string|null,
     *     customer_address: string|null,
     *     delivery_division: string|null,
     *     delivery_district: string|null,
     *     delivery_upazila: string|null,
     *     source_platform: string|null,
     *     notes: string|null,
     *     payment_method: string|null,
     *     items: list<array{product_name: string, color: string|null, size: string|null, qty: int}>
     * }
     */
    public function fallbackHeuristicParser(string $text): array
    {
        $normalizedText = strtr($text, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);

        // Phone
        $phone = null;
        if (preg_match('/(?:\+?88\s*)?(01[3-9]\d{2}[\s-]?\d{3}[\s-]?\d{3})/u', $normalizedText, $m)) {
            $phone = preg_replace('/[^\d]/', '', $m[1]);
        }

        // Name
        $name = null;
        if (preg_match('/(?:নাম|Name|Customer)\s*[:ঃ-]?\s*([^\n\r,]+)/iu', $text, $m)) {
            $name = trim($m[1]);
        }

        // Address
        $address = null;
        if (preg_match('/(?:ঠিকানা|Address|Location)\s*[:ঃ-]?\s*([^\n\r]+(?:\n[^\n\r]+)?)/iu', $text, $m)) {
            $address = trim($m[1]);
        }

        return [
            'customer_name' => $name,
            'customer_phone' => $phone,
            'customer_address' => $address,
            'delivery_division' => null,
            'delivery_district' => null,
            'delivery_upazila' => null,
            'source_platform' => 'manual',
            'notes' => null,
            'payment_method' => 'cod',
            'items' => [],
        ];
    }
}
