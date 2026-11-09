@extends('layouts.admin')

@section('title', 'AI Providers')
@section('heading', 'AI Providers & Models')
@section('subheading', 'Add Gemini, OpenAI, Claude, or any OpenAI-compatible API (OpenRouter, Groq, DeepSeek…).')

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-full border border-ink/10 bg-mist px-2.5 py-1 font-mono text-[11px] text-ink">Multi-AI Hub</span>
        <span class="text-xs text-muted">Landing AI · Product AI · Store Assistant use the defaults below</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-3">
            {{-- Global flags --}}
            <form method="POST" action="{{ route('admin.ai-providers.flags') }}" class="admin-card overflow-hidden">
                @csrf
                @method('PUT')
                <div class="admin-card-header">
                    <h2 class="text-sm font-semibold text-ink">AI Feature Switches</h2>
                </div>
                <div class="admin-card-body space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex items-center justify-between rounded-md border border-ink/10 bg-white px-3 py-2.5">
                            <span class="text-sm font-medium text-ink">Content Generation</span>
                            <input type="checkbox" name="content_generation_enabled" value="1" class="h-4 w-4" @checked($contentGenerationEnabled)>
                        </label>
                        <label class="flex items-center justify-between rounded-md border border-ink/10 bg-white px-3 py-2.5">
                            <span class="text-sm font-medium text-ink">Store Assistant</span>
                            <input type="checkbox" name="assistant_enabled" value="1" class="h-4 w-4" @checked($assistantEnabled)>
                        </label>
                    </div>
                    <div>
                        <label class="admin-label" for="system_prompt">Assistant system prompt</label>
                        <textarea id="system_prompt" name="system_prompt" rows="4" class="field text-xs">{{ old('system_prompt', $systemPrompt) }}</textarea>
                        <p class="mt-1 text-xs text-muted">Use <code>{store_name}</code> placeholder. Default example available in docs.</p>
                    </div>
                    <button type="submit" class="btn-primary">Save feature settings</button>
                </div>
            </form>

            {{-- Provider list --}}
            <div class="admin-card overflow-hidden">
                <div class="admin-card-header flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-ink">Configured Providers</h2>
                    <span class="text-xs text-muted">{{ $providers->count() }} total</span>
                </div>
                <div class="admin-card-body space-y-4">
                    @forelse ($providers as $provider)
                        <form method="POST" action="{{ route('admin.ai-providers.update', $provider) }}" class="rounded-lg border border-ink/10 p-3 space-y-3">
                            @csrf
                            @method('PUT')
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-semibold text-ink">{{ $provider->name }}</div>
                                <div class="flex flex-wrap gap-1 text-[10px] font-semibold uppercase">
                                    @if($provider->is_default_content)
                                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-emerald-700">Default Content</span>
                                    @endif
                                    @if($provider->is_default_assistant)
                                        <span class="rounded bg-indigo-50 px-2 py-0.5 text-indigo-700">Default Assistant</span>
                                    @endif
                                    <span class="rounded bg-mist px-2 py-0.5 text-ink">{{ $provider->driver }}</span>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="admin-label">Name</label>
                                    <input name="name" class="field" value="{{ $provider->name }}" required>
                                </div>
                                <div>
                                    <label class="admin-label">Driver</label>
                                    <select name="driver" class="field bg-white" required>
                                        @foreach ($drivers as $key => $label)
                                            <option value="{{ $key }}" @selected($provider->driver === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="admin-label">Model</label>
                                    <input name="model" class="field font-mono text-xs" value="{{ $provider->model }}" placeholder="e.g. gpt-4o-mini / gemini-3.5-flash-lite">
                                </div>
                                <div>
                                    <label class="admin-label">Base URL (compatible APIs)</label>
                                    <input name="base_url" class="field font-mono text-xs" value="{{ $provider->base_url }}" placeholder="https://openrouter.ai/api/v1">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="admin-label">API Key</label>
                                    <input name="api_key" type="password" class="field" placeholder="{{ $provider->maskedApiKey() ? 'Leave blank to keep: '.$provider->maskedApiKey() : 'Paste API key' }}" autocomplete="off">
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-xs">
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($provider->is_active)> Active</label>
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_default_content" value="1" @checked($provider->is_default_content)> Default for Content / Landing AI</label>
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_default_assistant" value="1" @checked($provider->is_default_assistant)> Default for Assistant</label>
                                <input type="number" name="sort_order" value="{{ $provider->sort_order }}" class="field !w-20 !py-1" min="0">
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="btn-primary">Update</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.ai-providers.destroy', $provider) }}" onsubmit="return confirm('Remove this AI provider?');" class="-mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Delete provider</button>
                        </form>
                    @empty
                        <p class="text-sm text-muted">No providers yet. Add Gemini or any OpenAI-compatible API on the right.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <form method="POST" action="{{ route('admin.ai-providers.store') }}" class="admin-card overflow-hidden">
                @csrf
                <div class="admin-card-header">
                    <h2 class="text-sm font-semibold text-ink">Add AI Provider</h2>
                </div>
                <div class="admin-card-body space-y-3">
                    <div>
                        <label class="admin-label">Display name *</label>
                        <input name="name" class="field" required placeholder="e.g. OpenRouter / Claude / Groq">
                    </div>
                    <div>
                        <label class="admin-label">Provider type *</label>
                        <select name="driver" id="ai-driver" class="field bg-white" required>
                            @foreach ($drivers as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">API Key *</label>
                        <input name="api_key" type="password" class="field" required autocomplete="off" placeholder="sk-... / AIza...">
                    </div>
                    <div>
                        <label class="admin-label">Model</label>
                        <input name="model" class="field font-mono text-xs" placeholder="gpt-4o-mini / claude-3-5-haiku-latest / gemini-3.5-flash-lite">
                    </div>
                    <div>
                        <label class="admin-label">Base URL</label>
                        <input name="base_url" class="field font-mono text-xs" placeholder="Required for OpenAI-Compatible">
                        <p class="mt-1 text-[11px] text-muted">Examples: <code>https://openrouter.ai/api/v1</code>, <code>https://api.groq.com/openai/v1</code>, <code>https://api.deepseek.com/v1</code></p>
                    </div>
                    <div class="space-y-2 text-xs">
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_default_content" value="1"> Set as default Content / Landing AI</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_default_assistant" value="1"> Set as default Assistant</label>
                    </div>
                    <input type="hidden" name="sort_order" value="10">
                    <button type="submit" class="btn-primary w-full">Add provider</button>
                </div>
            </form>

            <div class="admin-card admin-card-body text-xs text-muted space-y-2">
                <p class="font-semibold text-ink">Tips</p>
                <p>• <strong>Gemini</strong> — Google AI Studio key</p>
                <p>• <strong>OpenAI</strong> — platform.openai.com key</p>
                <p>• <strong>Claude</strong> — console.anthropic.com key</p>
                <p>• <strong>OpenAI-Compatible</strong> — any provider with Chat Completions API (OpenRouter, Groq, DeepSeek, Together, local LM Studio, etc.)</p>
                <p>• Landing page <strong>AI Design</strong> uses the Content default provider.</p>
            </div>
        </div>
    </div>
@endsection
