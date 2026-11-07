@extends('layouts.admin')

@section('title', 'Sitemap Management')
@section('heading', 'Sitemap Management')
@section('subheading', 'Generate and publish your XML sitemap for search engines')

@section('content')
    @php
        $exists = (bool) ($status['exists'] ?? false);
        $urlCount = $status['url_count'] ?? null;
        $generatedAt = $status['generated_at'] ?? null;
        $sitemapUrl = $status['url'] ?? url('sitemap.xml');
        $relativePath = $status['relative_path'] ?? '/public/sitemap.xml';
        $sizeLabel = isset($status['size_bytes']) && $status['size_bytes'] !== null
            ? number_format(((int) $status['size_bytes']) / 1024, 1).' KB'
            : 'Not generated';
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="overflow-hidden rounded-lg bg-gradient-to-r from-ink to-sage px-4 py-5 text-white shadow-sm sm:px-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-sage shadow-sm">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5zM6 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM6 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM18 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM18 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM18 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                    </svg>
                </span>
                <div>
                    <h2 class="font-display text-lg font-semibold tracking-tight sm:text-xl">Sitemap Management</h2>
                    <p class="mt-0.5 text-sm text-white/80">Build an XML sitemap for Google and other search engines.</p>
                </div>
            </div>
        </div>

        <span @class([
            'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold',
            'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' => $exists,
            'bg-amber-50 text-amber-800 ring-1 ring-amber-200' => ! $exists,
        ])>
            <span @class([
                'h-1.5 w-1.5 rounded-full',
                'bg-emerald-500' => $exists,
                'bg-amber-500' => ! $exists,
            ])></span>
            {{ $exists ? 'Sitemap ready' : 'Not generated yet' }}
        </span>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-sm font-semibold text-ink">XML Sitemap Configuration</h3>
                    <p class="text-xs text-muted">Includes homepage, shop, categories, and active products</p>
                </div>
            </div>
        </div>

        <div class="admin-card-body space-y-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-md border border-ink/10 bg-mist/70 px-3 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">Includes</p>
                    <p class="mt-1 text-sm font-medium text-ink">Home, Shop, Categories, Products</p>
                </div>
                <div class="rounded-md border border-ink/10 bg-mist/70 px-3 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">File path</p>
                    <p class="mt-1 font-mono text-sm font-medium text-ink">{{ $relativePath }}</p>
                </div>
                <div class="rounded-md border border-ink/10 bg-mist/70 px-3 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">URLs / Size</p>
                    <p class="mt-1 text-sm font-medium text-ink">
                        {{ $urlCount !== null ? number_format($urlCount).' URLs' : '—' }}
                        <span class="text-muted">·</span>
                        {{ $sizeLabel }}
                    </p>
                </div>
            </div>

            <div>
                <label class="admin-label" for="sitemap_url">Public sitemap URL</label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative min-w-0 flex-1">
                        <input
                            id="sitemap_url"
                            type="text"
                            readonly
                            value="{{ $sitemapUrl }}"
                            class="field font-mono text-sm"
                            data-sitemap-url
                        >
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button
                            type="button"
                            class="admin-btn-secondary"
                            data-copy-sitemap
                            title="Copy URL"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                            </svg>
                            Copy
                        </button>
                        <a
                            href="{{ $sitemapUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="admin-btn-secondary"
                            title="Open sitemap"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            Open
                        </a>
                        <form method="POST" action="{{ route('admin.sitemap.generate') }}">
                            @csrf
                            <button type="submit" class="admin-btn-primary">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Generate Sitemap
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 admin-card">
        <div class="admin-card-body">
            @if ($exists && $generatedAt)
                <div class="flex gap-2 text-sm text-ink">
                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </span>
                    <p>
                        Last generated
                        <span class="font-semibold">{{ $generatedAt->timezone(config('app.timezone'))->format('d M Y, h:i A') }}</span>
                        with
                        <span class="font-semibold">{{ number_format((int) $urlCount) }}</span>
                        URLs.
                        Submit this URL in Google Search Console after generating.
                    </p>
                </div>
            @else
                <div class="flex gap-2 text-sm text-ink">
                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-700">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </span>
                    <p>
                        No sitemap file found yet. Click
                        <span class="font-semibold">Generate Sitemap</span>
                        to create <span class="font-mono">public/sitemap.xml</span>.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <script>
        (() => {
            const button = document.querySelector('[data-copy-sitemap]');
            const input = document.querySelector('[data-sitemap-url]');
            if (!button || !input) return;

            button.addEventListener('click', async () => {
                const value = input.value;
                try {
                    await navigator.clipboard.writeText(value);
                    button.textContent = 'Copied';
                    setTimeout(() => {
                        button.innerHTML = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" /></svg> Copy';
                    }, 1500);
                } catch (_) {
                    input.select();
                    document.execCommand('copy');
                }
            });
        })();
    </script>
@endsection
