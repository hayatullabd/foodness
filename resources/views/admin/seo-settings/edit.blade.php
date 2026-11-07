@extends('layouts.admin')

@section('title', 'SEO Settings')
@section('heading', 'SEO Configuration')
@section('subheading', 'Control how your store appears in search engines')

@section('content')
    @php
        $siteTitle = old('site_title', $seoSetting->site_title);
        $metaDescription = old('meta_description', $seoSetting->meta_description);
        $metaKeywords = old('meta_keywords', $seoSetting->meta_keywords);
        $googleVerification = old('google_site_verification', $seoSetting->google_site_verification);
        $previewHost = parse_url($storeUrl, PHP_URL_HOST) ?: 'foodness.com';
    @endphp

    <form method="POST" action="{{ route('admin.seo-settings.update') }}" id="seo-settings-form">
        @csrf
        @method('PUT')

        <div class="grid gap-4 lg:grid-cols-5">
            <div class="space-y-4 lg:col-span-3">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-ink">General Meta</h3>
                                <p class="text-xs text-muted">Title, description, and keywords for the storefront</p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card-body space-y-4">
                        <div>
                            <label class="admin-label" for="site_title">Site Title</label>
                            <input
                                id="site_title"
                                name="site_title"
                                type="text"
                                maxlength="70"
                                value="{{ $siteTitle }}"
                                class="field"
                                placeholder="Foodness"
                                data-seo-title
                            >
                            <div class="mt-1.5 flex flex-wrap items-center gap-3 text-xs">
                                <span data-title-count class="text-muted">0 / 60</span>
                                <span data-title-status class="inline-flex items-center gap-1 font-medium text-sage">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Ideal
                                </span>
                            </div>
                            @error('site_title') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="meta_description">Meta Description</label>
                            <textarea
                                id="meta_description"
                                name="meta_description"
                                rows="5"
                                maxlength="320"
                                class="field"
                                placeholder="Shop fashion & lifestyle at Foodness — quality products delivered across Bangladesh."
                                data-seo-description
                            >{{ $metaDescription }}</textarea>
                            <div class="mt-1.5 flex flex-wrap items-center gap-3 text-xs">
                                <span data-description-count class="text-muted">0 / 160</span>
                                <span data-description-status class="inline-flex items-center gap-1 font-medium text-sage">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Ideal
                                </span>
                            </div>
                            @error('meta_description') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="meta_keywords">Meta Keywords</label>
                            <div class="rounded-md border border-ink/15 bg-white px-2.5 py-2 focus-within:border-sage focus-within:ring-1 focus-within:ring-sage">
                                <div class="mb-2 flex flex-wrap gap-1.5" data-keyword-chips></div>
                                <input
                                    id="keyword_input"
                                    type="text"
                                    class="w-full border-0 bg-transparent p-0 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-0"
                                    placeholder="foodness, fashion, lifestyle"
                                    data-keyword-input
                                    autocomplete="off"
                                >
                                <input type="hidden" name="meta_keywords" value="{{ $metaKeywords }}" data-keywords-value>
                            </div>
                            <p class="mt-1 text-xs text-muted">Optional. Separate keywords with Enter or comma.</p>
                            @error('meta_keywords') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-ink">Webmaster Tools</h3>
                                <p class="text-xs text-muted">Google Search Console verification</p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card-body space-y-4">
                        <div>
                            <label class="admin-label" for="google_site_verification">Google verification code</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                    </svg>
                                </span>
                                <input
                                    id="google_site_verification"
                                    name="google_site_verification"
                                    type="text"
                                    maxlength="255"
                                    value="{{ $googleVerification }}"
                                    class="field !pl-9.5"
                                    placeholder="foodness-google-verification-token"
                                >
                            </div>
                            <p class="mt-1 text-xs text-muted">
                                Use only the verification token, not the full HTML meta tag.
                            </p>
                            @error('google_site_verification') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="admin-btn-primary w-full py-2.5 text-sm sm:w-auto">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                            </svg>
                            Save Settings
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-4 lg:col-span-2">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </span>
                            <h3 class="text-sm font-semibold text-ink">Google Preview</h3>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="rounded-md border border-ink/10 bg-white px-3 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-mist text-[10px] font-semibold text-ink">
                                    {{ strtoupper(mb_substr((string) $storeName, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-medium text-ink">{{ $storeName }}</p>
                                    <p class="truncate text-[11px] text-muted">{{ $previewHost }}</p>
                                </div>
                            </div>
                            <p class="mt-2 text-lg leading-snug text-[#1a0dab]" data-preview-title>
                                {{ $siteTitle ?: ($storeName ?: 'Foodness') }}
                            </p>
                            <p class="mt-1 text-sm leading-relaxed text-[#4d5156]" data-preview-description>
                                {{ $metaDescription ?: 'Shop fashion & lifestyle at Foodness — quality products delivered across Bangladesh.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-md border border-sky-200 bg-sky-50 px-3 py-3 text-sm text-sky-950">
                    <h4 class="flex items-center gap-1.5 text-sm font-semibold">
                        <svg class="h-4 w-4 text-sky-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                        Pro Tips
                    </h4>
                    <ul class="mt-2 list-disc space-y-1.5 pl-4 text-xs text-sky-900/90">
                        <li>Keep the site title around 50–60 characters so it doesn’t get cut off in Google.</li>
                        <li>Aim for a meta description of about 150–160 characters with a clear call to action.</li>
                        <li>Use keywords that match what customers actually search for.</li>
                        <li>Paste only the Google verification token into Webmaster Tools.</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>

    <script>
        (() => {
            const form = document.getElementById('seo-settings-form');
            if (!form) return;

            const titleInput = form.querySelector('[data-seo-title]');
            const descriptionInput = form.querySelector('[data-seo-description]');
            const previewTitle = form.querySelector('[data-preview-title]');
            const previewDescription = form.querySelector('[data-preview-description]');
            const titleCount = form.querySelector('[data-title-count]');
            const descriptionCount = form.querySelector('[data-description-count]');
            const titleStatus = form.querySelector('[data-title-status]');
            const descriptionStatus = form.querySelector('[data-description-status]');
            const keywordChips = form.querySelector('[data-keyword-chips]');
            const keywordInput = form.querySelector('[data-keyword-input]');
            const keywordsValue = form.querySelector('[data-keywords-value]');
            const fallbackTitle = @json($storeName ?: 'Foodness');
            const fallbackDescription = 'Shop fashion & lifestyle at Foodness — quality products delivered across Bangladesh.';

            const okIcon = '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>';
            const warnIcon = '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>';

            function setStatus(el, kind, label) {
                el.className = 'inline-flex items-center gap-1 font-medium ' + (kind === 'ok' ? 'text-sage' : 'text-amber-700');
                el.innerHTML = (kind === 'ok' ? okIcon : warnIcon) + label;
            }

            function updateTitle() {
                const value = (titleInput.value || '').trim();
                const length = [...value].length;
                titleCount.textContent = length + ' / 60';
                previewTitle.textContent = value || fallbackTitle;

                if (length === 0) {
                    setStatus(titleStatus, 'warn', 'Empty');
                } else if (length <= 60) {
                    setStatus(titleStatus, 'ok', 'Ideal');
                } else {
                    setStatus(titleStatus, 'warn', 'Too long');
                }
            }

            function updateDescription() {
                const value = (descriptionInput.value || '').trim();
                const length = [...value].length;
                descriptionCount.textContent = length + ' / 160';
                const snippet = value || fallbackDescription;
                previewDescription.textContent = [...snippet].length > 160
                    ? [...snippet].slice(0, 157).join('') + '…'
                    : snippet;

                if (length === 0) {
                    setStatus(descriptionStatus, 'warn', 'Empty');
                } else if (length <= 160) {
                    setStatus(descriptionStatus, 'ok', 'Ideal');
                } else {
                    setStatus(descriptionStatus, 'warn', 'Too long');
                }
            }

            let keywords = (keywordsValue.value || '')
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean);

            function syncKeywords() {
                keywordsValue.value = keywords.join(', ');
                keywordChips.innerHTML = '';
                keywords.forEach((keyword, index) => {
                    const chip = document.createElement('span');
                    chip.className = 'inline-flex items-center gap-1 rounded-md bg-mist px-2 py-1 text-xs font-medium text-ink';
                    chip.innerHTML = '<span></span><button type="button" class="text-muted hover:text-accent" aria-label="Remove keyword">&times;</button>';
                    chip.querySelector('span').textContent = keyword;
                    chip.querySelector('button').addEventListener('click', () => {
                        keywords.splice(index, 1);
                        syncKeywords();
                    });
                    keywordChips.appendChild(chip);
                });
            }

            function addKeyword(raw) {
                const value = (raw || '').trim().replace(/,$/, '').trim();
                if (!value) return;
                if (!keywords.some((item) => item.toLowerCase() === value.toLowerCase())) {
                    keywords.push(value);
                    syncKeywords();
                }
                keywordInput.value = '';
            }

            titleInput.addEventListener('input', updateTitle);
            descriptionInput.addEventListener('input', updateDescription);

            keywordInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ',') {
                    event.preventDefault();
                    addKeyword(keywordInput.value);
                } else if (event.key === 'Backspace' && !keywordInput.value && keywords.length) {
                    keywords.pop();
                    syncKeywords();
                }
            });

            keywordInput.addEventListener('blur', () => addKeyword(keywordInput.value));

            updateTitle();
            updateDescription();
            syncKeywords();
        })();
    </script>
@endsection
