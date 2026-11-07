@extends('layouts.admin')

@section('title', 'Gemini AI & Assistant')
@section('heading', 'Google Gemini AI & Assistant')
@section('subheading', 'Unified configuration for automated content generation and the AI shopping assistant.')

@section('content')
    @php
        $geminiActive = (bool) old('is_active', $integration->is_active);
        $contentGenActive = (bool) old('content_generation_enabled', $integration->credential('content_generation_enabled', true));
        $assistantActive = (bool) old('assistant_enabled', $integration->credential('assistant_enabled', true));
        $currentModel = old('model', $integration->credential('model', 'gemini-2.5-flash'));
        $currentPrompt = old('system_prompt', $systemPrompt);
    @endphp

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-full border border-ink/10 bg-mist px-2.5 py-1 font-mono text-[11px] text-ink">
            Google Generative AI
        </span>
        <span class="text-xs text-muted">Unified Key · Content Generation &amp; Chat Assistant</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-3">
            <form method="POST" action="{{ route('admin.api-integrations.gemini-ai.update') }}" class="admin-card overflow-hidden" id="gemini-settings-form">
                @csrf
                @method('PUT')

                {{-- API Settings --}}
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">API Credentials</h2>
                            <p class="text-xs text-muted">Google Gemini API key and model selection</p>
                        </div>
                    </div>
                </div>

                <div class="admin-card-body space-y-3.5">
                    <div>
                        <label class="admin-label" for="api_key">Gemini API Key *</label>
                        <input
                            id="api_key"
                            name="api_key"
                            type="password"
                            value=""
                            class="field"
                            autocomplete="off"
                            placeholder="{{ $maskedApiKey ? 'Leave blank to keep current key' : 'AIzaSy...' }}"
                        >
                        @if ($maskedApiKey)
                            <p class="mt-1 text-xs text-muted">Current key: <span class="font-mono tracking-wide">{{ $maskedApiKey }}</span></p>
                        @endif
                        <p class="mt-1 text-xs text-muted">Get your free API key from <a href="https://aistudio.google.com/" target="_blank" rel="noopener" class="font-medium text-ink underline">aistudio.google.com</a>.</p>
                        @error('api_key') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="model">Default Model</label>
                        <input
                            id="model"
                            name="model"
                            type="text"
                            list="gemini-models"
                            value="{{ $currentModel ?: 'gemini-3.5-flash-lite' }}"
                            class="field font-mono text-xs"
                            placeholder="gemini-3.5-flash-lite"
                        >
                        <datalist id="gemini-models">
                            <option value="gemini-3.5-flash-lite">gemini-3.5-flash-lite (Recommended · Ultra Fast &amp; High Quality)</option>
                            <option value="gemini-3.5-flash">gemini-3.5-flash (Standard Flash Model)</option>
                            <option value="gemini-3.8-flash">gemini-3.8-flash (Latest Flash Model)</option>
                            <option value="gemini-flash-latest">gemini-flash-latest (Auto-Updating Flash)</option>
                        </datalist>
                        <p class="mt-1 text-xs text-muted">Recommended: <span class="font-mono font-medium">gemini-3.5-flash-lite</span> for fastest response time and free tier compatibility.</p>
                        @error('model') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- AI Features & Switches --}}
                <div class="border-t border-ink/10">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-ink">AI Features &amp; Switches</h2>
                                <p class="text-xs text-muted">Master switch and individual capability toggles</p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card-body space-y-3">
                        {{-- Master Toggle --}}
                        <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-ink">Gemini AI Master Switch</span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide {{ $geminiActive ? 'text-sage' : 'text-muted' }}">{{ $geminiActive ? 'On' : 'Off' }}</span>
                                </div>
                                <p class="text-xs text-muted">Enable or completely disable all Gemini AI services across the store.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" name="is_active" value="1" class="peer sr-only" @checked($geminiActive)>
                                <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                            </label>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            {{-- Feature 1: Content Generation --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">Content &amp; SEO Generation</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $contentGenActive ? 'text-sage' : 'text-muted' }}">{{ $contentGenActive ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Auto product description, SEO meta title, and tags generation.</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="content_generation_enabled" value="1" class="peer sr-only" @checked($contentGenActive)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Feature 2: AI Assistant --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">AI Shopping Assistant</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $assistantActive ? 'text-sage' : 'text-muted' }}">{{ $assistantActive ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Customer-facing interactive chat assistant for store support.</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="assistant_enabled" value="1" class="peer sr-only" @checked($assistantActive)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- AI Assistant Personality & Instructions --}}
                <div class="border-t border-ink/10">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-ink">AI Assistant System Prompt</h2>
                                <p class="text-xs text-muted">Instruct the assistant on behavior, tone, and store guidelines</p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card-body space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="admin-label !mb-0" for="system_prompt">System Prompt</label>
                            <button
                                type="button"
                                id="reset-prompt-btn"
                                class="text-xs text-muted transition hover:text-ink underline"
                                data-default-prompt="{{ $defaultPrompt }}"
                            >
                                Reset to default prompt
                            </button>
                        </div>

                        <textarea
                            id="system_prompt"
                            name="system_prompt"
                            rows="6"
                            class="field font-sans text-xs leading-relaxed"
                            placeholder="You are a helpful store assistant..."
                        >{{ $currentPrompt }}</textarea>

                        <div class="flex items-center justify-between text-[11px] text-muted">
                            <span>Specify store policies, accepted payment methods, and communication tone.</span>
                            <span id="prompt-char-count">0 characters</span>
                        </div>
                        @error('system_prompt') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-between border-t border-ink/10 bg-mist/30 px-4 py-3">
                    <p class="text-xs text-muted">All AI capabilities share this single API key.</p>
                    <button type="submit" class="admin-btn-primary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                        Save Gemini AI Settings
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4 lg:col-span-2">
            {{-- Unified Architecture Notice --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink font-semibold text-xs">AI</span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Unified AI Hub</h2>
                            <p class="text-xs text-muted">One key powers two features</p>
                        </div>
                    </div>
                </div>
                <div class="admin-card-body space-y-2.5 text-xs text-ink leading-relaxed">
                    <p>
                        You no longer need separate API configurations. A single Google Gemini API key powers both:
                    </p>
                    <ul class="list-disc pl-4 space-y-1 text-muted">
                        <li><span class="font-medium text-ink">Content &amp; SEO:</span> Automated product descriptions &amp; meta tags.</li>
                        <li><span class="font-medium text-ink">Virtual Assistant:</span> Customer chat helpdesk with your custom system prompt.</li>
                    </ul>
                    <p class="text-[11px] text-muted pt-1">
                        Each feature can be toggled On/Off independently above.
                    </p>
                </div>
            </div>

            {{-- Quick Guide --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Setup Guide</h2>
                            <p class="text-xs text-muted">How to generate a Gemini key</p>
                        </div>
                    </div>
                </div>
                <div class="admin-card-body space-y-3">
                    <ol class="list-decimal space-y-1.5 pl-4 text-xs text-ink leading-relaxed">
                        <li>Go to <a href="https://aistudio.google.com/" target="_blank" rel="noopener" class="font-medium underline">aistudio.google.com</a>.</li>
                        <li>Sign in with your Google Workspace or Personal account.</li>
                        <li>Click <span class="font-medium">"Get API key"</span> and create a new key.</li>
                        <li>Paste the key into the API key field and click Save.</li>
                    </ol>
                    <div class="rounded-md border border-ink/10 bg-mist p-2.5 text-[11px] text-muted">
                        Google Gemini provides free tier requests suitable for most store operations.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const promptInput = document.getElementById('system_prompt');
            const counter = document.getElementById('prompt-char-count');
            const resetBtn = document.getElementById('reset-prompt-btn');

            if (promptInput && counter) {
                const updateCount = () => {
                    counter.textContent = `${promptInput.value.length} characters`;
                };
                promptInput.addEventListener('input', updateCount);
                updateCount();
            }

            if (resetBtn && promptInput) {
                resetBtn.addEventListener('click', () => {
                    promptInput.value = resetBtn.dataset.defaultPrompt || '';
                    promptInput.focus();
                    promptInput.dispatchEvent(new Event('input'));
                });
            }
        })();
    </script>
@endsection
