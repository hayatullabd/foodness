<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Services\Ai\AiManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiProviderController extends Controller
{
    public function index(AiManager $ai): View
    {
        $providers = AiProvider::query()->orderBy('sort_order')->orderBy('id')->get();

        $defaultPrompt = 'You are a friendly, helpful AI shopping assistant for {store_name}. Help customers discover products, answer questions about orders, delivery, and store policies, and provide polite, concise answers.';

        return view('admin.ai-providers.index', [
            'providers' => $providers,
            'drivers' => AiProvider::driverLabels(),
            'contentGenerationEnabled' => (bool) $ai->settingsValue('content_generation_enabled', true),
            'assistantEnabled' => (bool) $ai->settingsValue('assistant_enabled', true),
            'systemPrompt' => (string) ($ai->settingsValue('system_prompt', '') ?: $defaultPrompt),
            'defaultPrompt' => $defaultPrompt,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        if ($request->boolean('is_default_content')) {
            AiProvider::query()->update(['is_default_content' => false]);
        }
        if ($request->boolean('is_default_assistant')) {
            AiProvider::query()->update(['is_default_assistant' => false]);
        }

        AiProvider::query()->create([
            'name' => $validated['name'],
            'driver' => $validated['driver'],
            'api_key' => $validated['api_key'] ?: null,
            'base_url' => $validated['base_url'] ?: null,
            'model' => $validated['model'] ?: null,
            'is_active' => $request->boolean('is_active', true),
            'is_default_content' => $request->boolean('is_default_content'),
            'is_default_assistant' => $request->boolean('is_default_assistant'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'meta' => [],
        ]);

        return back()->with('success', 'AI provider added.');
    }

    public function update(Request $request, AiProvider $aiProvider): RedirectResponse
    {
        $validated = $this->validated($request, updating: true);

        if ($request->boolean('is_default_content')) {
            AiProvider::query()->where('id', '!=', $aiProvider->id)->update(['is_default_content' => false]);
        }
        if ($request->boolean('is_default_assistant')) {
            AiProvider::query()->where('id', '!=', $aiProvider->id)->update(['is_default_assistant' => false]);
        }

        $aiProvider->name = $validated['name'];
        $aiProvider->driver = $validated['driver'];
        $aiProvider->base_url = $validated['base_url'] ?: null;
        $aiProvider->model = $validated['model'] ?: null;
        $aiProvider->is_active = $request->boolean('is_active');
        $aiProvider->is_default_content = $request->boolean('is_default_content');
        $aiProvider->is_default_assistant = $request->boolean('is_default_assistant');
        $aiProvider->sort_order = (int) ($validated['sort_order'] ?? 0);

        if (filled($validated['api_key'] ?? null)) {
            $aiProvider->api_key = $validated['api_key'];
        }

        $aiProvider->save();

        return back()->with('success', 'AI provider updated.');
    }

    public function destroy(AiProvider $aiProvider): RedirectResponse
    {
        $aiProvider->delete();

        return back()->with('success', 'AI provider removed.');
    }

    public function saveFlags(Request $request, AiManager $ai): RedirectResponse
    {
        $validated = $request->validate([
            'content_generation_enabled' => ['nullable', 'boolean'],
            'assistant_enabled' => ['nullable', 'boolean'],
            'system_prompt' => ['nullable', 'string', 'max:10000'],
        ]);

        $ai->saveGlobalFlags([
            'content_generation_enabled' => $request->boolean('content_generation_enabled'),
            'assistant_enabled' => $request->boolean('assistant_enabled'),
            'system_prompt' => $validated['system_prompt'] ?? '',
        ]);

        return back()->with('success', 'AI feature settings saved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $updating = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'driver' => ['required', Rule::in(AiProvider::DRIVERS)],
            'api_key' => [$updating ? 'nullable' : 'required', 'string', 'max:4000'],
            'base_url' => ['nullable', 'url', 'max:500'],
            'model' => ['nullable', 'string', 'max:191'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'is_default_content' => ['nullable', 'boolean'],
            'is_default_assistant' => ['nullable', 'boolean'],
        ];

        $validated = $request->validate($rules);

        if (($validated['driver'] ?? '') === AiProvider::DRIVER_OPENAI_COMPATIBLE && blank($validated['base_url'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'base_url' => 'Base URL is required for OpenAI-Compatible providers (e.g. https://openrouter.ai/api/v1).',
            ]);
        }

        return $validated;
    }
}
