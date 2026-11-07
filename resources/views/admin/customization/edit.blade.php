@extends('layouts.admin')

@php
    $czTab = request('tab', 'header');
    $czPages = [
        'home' => ['Homepage', 'Slider, sections, and homepage blocks.'],
        'footer' => ['Footer', 'Footer columns, partners, and the mobile dock.'],
        'theme' => ['Theme', 'Colors, customer login, and widgets.'],
    ];
    $czHomeTabs = ['home', 'hero', 'sections', 'stories', 'categories', 'gur_showcase', 'featured', 'story_reel', 'promises', 'faq'];
    $czThemeTabs = ['theme', 'colors', 'widgets'];
    if (in_array($czTab, $czHomeTabs, true)) {
        [$czHeading, $czSub] = $czPages['home'];
    } elseif (in_array($czTab, $czThemeTabs, true)) {
        [$czHeading, $czSub] = $czPages['theme'];
    } elseif ($czTab === 'footer') {
        [$czHeading, $czSub] = $czPages['footer'];
    } elseif ($czTab === 'checkout') {
        $czHeading = 'Checkout';
        $czSub = 'Checkout page and order modal voice.';
    } else {
        $czHeading = 'Header';
        $czSub = 'Logo, announcement bar, and navigation.';
    }
@endphp

@section('title', $czHeading.' — Foodness')
@section('heading', $czHeading)
@section('subheading', $czSub)

@section('actions')
    <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="cz-head-btn">Storefront</a>
    <form method="POST" action="{{ route('admin.customization.reset') }}" onsubmit="return confirm('Are you sure you want to restore default customization settings?');">
        @csrf
        <button type="submit" class="cz-head-btn cz-head-btn--danger">Reset</button>
    </form>
@endsection

@section('content')
<style>
    .cz-head-btn { display: inline-flex; align-items: center; height: 30px; padding: 0 10px; border-radius: 8px; border: 1px solid #e2e8e4; background: #fff; color: #24382e; font-size: 12px; font-weight: 650; }
    .cz-head-btn:hover { background: #f4f7f5; }
    .cz-head-btn--danger { border-color: #f3d0d0; background: #fff7f7; color: #9b2c2c; }
    .cz { display: grid; gap: 10px; }
    .cz-bar { position: sticky; top: 6.6rem; z-index: 30; display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 6px; border: 1px solid #e4ebe6; border-radius: 12px; background: rgba(255,255,255,.94); backdrop-filter: blur(10px); box-shadow: 0 8px 18px rgba(16,40,28,.05); }
    .cz-tabs, .cz-actions { display: flex; align-items: center; gap: 4px; min-width: 0; }
    .cz-tabs { overflow-x: auto; }
    .cz .tab-btn, .cz .cz-product-link { display: inline-flex; align-items: center; gap: 6px; height: 30px; padding: 0 10px; border-radius: 8px; border: 1px solid transparent; background: transparent; color: #3d5248; font-size: 12px; font-weight: 650; white-space: nowrap; }
    .cz .tab-btn:hover, .cz .cz-product-link:hover { background: #f3f7f4; }
    .cz .tab-btn.bg-sage { background: #1c4f38; border-color: #1c4f38; color: #fff; }
    .cz .tab-count-badge { min-width: 16px; height: 16px; padding: 0 4px; border-radius: 99px; background: #eef3f0; color: #3d5248; font-size: 10px; font-weight: 700; line-height: 16px; text-align: center; }
    .cz .tab-btn.bg-sage .tab-count-badge { background: rgba(255,255,255,.18); color: #fff; }
    .cz .cz-product-link { color: #1c4f38; }
    .cz-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 1px solid #e2e8e4; border-radius: 8px; background: #fff; color: #4b5e55; }
    .cz-icon-btn:hover { background: #f4f7f5; color: #143528; }
    .cz-icon-btn svg { width: 14px; height: 14px; }
    .cz .tab-content > .flex:has(.section-jump-link) { gap: 4px; margin: 0; padding: 0 2px; border: 0; border-radius: 0; background: transparent; }
    .cz .tab-content > .flex:has(.section-jump-link) > span { padding: 0 4px 0 0; color: #8a968f; letter-spacing: .08em; }
    .cz .section-jump-link { padding: 3px 8px !important; border-radius: 999px !important; box-shadow: none !important; font-size: 11px !important; line-height: 1.3; }
    .cz .admin-card { border-radius: 12px; box-shadow: 0 1px 2px rgba(16,40,28,.04); }
    .cz .admin-card-header { padding: 8px 12px !important; background: #f7faf8; }
    .cz .admin-card-header h3 { font-size: 11px !important; letter-spacing: .05em; }
    .cz .admin-card-header p { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; margin-top: 1px; font-size: 11px !important; line-height: 1.35; }
    .cz .admin-card-body { padding: 12px !important; }
    .cz .admin-card-body .grid { gap: 10px; }
    .cz .admin-label { margin-bottom: 3px; color: #5d6e66; font-size: 11px; font-weight: 650; }
    .cz [data-dropzone-box] { height: 84px !important; min-height: 0 !important; padding: 8px !important; border-radius: 10px !important; }
    .cz [data-dropzone-placeholder] { flex-direction: row !important; gap: 8px !important; }
    .cz [data-dropzone-placeholder] .h-10 { width: 28px; height: 28px; }
    .cz [data-dropzone-placeholder] .h-10 svg { width: 14px; height: 14px; }
    .cz #placements-container { gap: 10px !important; }
    .cz .placement-card { padding: 10px !important; }
    .cz .placement-card > .text-\[11px\] { display: none; }
    .cz .placement-btn { height: 26px; padding: 0 8px !important; font-size: 11px !important; }
    @media (max-width: 900px) {
        .cz-bar { top: 6.2rem; align-items: stretch; flex-direction: column; }
        .cz-actions { justify-content: flex-end; }
    }
</style>
<div class="cz">
    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-xs font-medium text-emerald-800 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    {{-- Main Customization Form --}}
    <form
        id="customization-form"
        method="POST"
        action="{{ route('admin.customization.update') }}"
        enctype="multipart/form-data"
        class="space-y-3"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="active_tab" id="active_tab_input" value="{{ request('tab', 'branding') }}">

        <div class="cz-bar">
            <div class="cz-tabs">
                <a href="{{ route('admin.customization.edit', ['tab' => 'header']) }}" data-tab-target="header" @class(['tab-btn', 'bg-sage text-white border-sage shadow-xs' => ! in_array($czTab, array_merge($czHomeTabs, $czThemeTabs, ['footer', 'checkout']), true), 'bg-white text-ink border-ink/15 hover:bg-mist/40' => in_array($czTab, array_merge($czHomeTabs, $czThemeTabs, ['footer', 'checkout']), true)])>Header</a>
                @if (in_array($czTab, $czHomeTabs, true))
                    <a href="{{ route('admin.customization.edit', ['tab' => 'home']) }}" data-tab-target="home" class="tab-btn bg-sage text-white border-sage shadow-xs">Homepage</a>
                @endif
                <a href="{{ route('admin.customization.edit', ['tab' => 'footer']) }}" data-tab-target="footer" @class(['tab-btn', 'bg-sage text-white border-sage shadow-xs' => $czTab === 'footer', 'bg-white text-ink border-ink/15 hover:bg-mist/40' => $czTab !== 'footer'])>Footer</a>
                <a href="{{ route('admin.customization.edit', ['tab' => 'checkout']) }}" data-tab-target="checkout" @class(['tab-btn', 'bg-sage text-white border-sage shadow-xs' => $czTab === 'checkout', 'bg-white text-ink border-ink/15 hover:bg-mist/40' => $czTab !== 'checkout'])>Checkout</a>
                @if (in_array($czTab, $czThemeTabs, true))
                    <a href="{{ route('admin.customization.edit', ['tab' => 'theme']) }}" data-tab-target="theme" class="tab-btn bg-sage text-white border-sage shadow-xs">Theme</a>
                @endif
                <a href="{{ route('admin.product-page.edit') }}" class="cz-product-link" title="Single product page">Product page</a>
            </div>
            <div class="cz-actions">
                <button type="button" id="btn-collapse-all" class="cz-icon-btn" title="Collapse all" aria-label="Collapse all">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
                </button>
                <button type="button" id="btn-expand-all" class="cz-icon-btn" title="Expand all" aria-label="Expand all">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h16"/></svg>
                </button>
                <button type="submit" class="admin-btn-primary !h-[30px] !px-3 !py-0 text-xs font-semibold">Save</button>
            </div>
        </div>

{{-- TAB 1: Header & Branding --}}
        <div id="tab-header" class="tab-content space-y-3">
            {{-- Header Sections Sub-Navigation Jump Bar --}}
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100/90 rounded-xl px-2.5 border border-slate-200">
                <span class="text-slate-400 uppercase text-[10px] tracking-wider shrink-0 font-bold px-1">HEADER:</span>
                <a href="#header-brand-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🏷️ Brand & Logo</a>
                <a href="#header-topbar-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">📢 Announcement Bar</a>
                <a href="#header-menu-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">📑 Navigation Menu</a>
                <a href="#header-features-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">⚙️ Sticky & Features</a>
                <a href="#mobile-drawer-customizer-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold shadow-2xs shrink-0 transition">☰ Mobile 3-Dash Menu</a>
            </div>

            <div class="admin-card" id="header-brand-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Brand Identity & Logo</h3>
                        <p class="text-[11px] text-slate-500">Configure store name, tagline, logo display height, and browser favicon.</p>
                    </div>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-3.5">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="admin-label" for="store_name">Store Name</label>
                            <input id="store_name" name="store_name" type="text" value="{{ old('store_name', $settings['store_name'] ?? 'Foodness') }}" class="field">
                        </div>
                        <div>
                            <label class="admin-label" for="store_tagline">Store Tagline</label>
                            <input id="store_tagline" name="store_tagline" type="text" value="{{ old('store_tagline', $settings['store_tagline'] ?? 'Premium Fashion & Cash on Delivery') }}" class="field">
                        </div>
                        <div>
                            <label class="admin-label" for="header_logo_height">Header Logo Display Height</label>
                            <select id="header_logo_height" name="header_logo_height" class="field bg-white">
                                <option value="36" {{ ($settings['header_logo_height'] ?? '44') === '36' ? 'selected' : '' }}>36px (Compact)</option>
                                <option value="40" {{ ($settings['header_logo_height'] ?? '44') === '40' ? 'selected' : '' }}>40px (Small)</option>
                                <option value="44" {{ ($settings['header_logo_height'] ?? '44') === '44' ? 'selected' : '' }}>44px (Standard Default)</option>
                                <option value="50" {{ ($settings['header_logo_height'] ?? '44') === '50' ? 'selected' : '' }}>50px (Medium)</option>
                                <option value="56" {{ ($settings['header_logo_height'] ?? '44') === '56' ? 'selected' : '' }}>56px (Large)</option>
                                <option value="64" {{ ($settings['header_logo_height'] ?? '44') === '64' ? 'selected' : '' }}>64px (Extra Large)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        {{-- Store Logo Dropzone --}}
                        <div class="rounded-xl border border-ink/15 bg-white p-3.5 space-y-2 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <label class="admin-label !mb-0 font-semibold" for="store_logo">Header & Invoice Logo</label>
                                <span class="rounded bg-mist px-2 py-0.5 text-[10px] font-bold text-muted">Ratio 3:1 to 4:1</span>
                            </div>
                            @include('admin.partials.dropzone', [
                                'name' => 'store_logo',
                                'id' => 'store_logo',
                                'current' => !empty($settings['store_logo']) && file_exists(public_path('storage/'.$settings['store_logo'])) ? asset('storage/'.$settings['store_logo']) : null,
                                'hint' => 'Recommended: 300 × 80 px · PNG, SVG, WEBP · Max 4 MB',
                                'maxHeight' => 'h-32',
                            ])
                        </div>

                        {{-- Browser Favicon Dropzone --}}
                        <div class="rounded-xl border border-ink/15 bg-white p-3.5 space-y-2 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <label class="admin-label !mb-0 font-semibold" for="site_favicon">Browser Tab Favicon</label>
                                <span class="rounded bg-mist px-2 py-0.5 text-[10px] font-bold text-muted">Ratio 1:1 (Square)</span>
                            </div>
                            @include('admin.partials.dropzone', [
                                'name' => 'site_favicon',
                                'id' => 'site_favicon',
                                'current' => !empty($settings['site_favicon']) && file_exists(public_path('storage/'.$settings['site_favicon'])) ? asset('storage/'.$settings['site_favicon']) : null,
                                'hint' => 'Recommended: 64 × 64 px · PNG, ICO, SVG · Max 2 MB',
                                'maxHeight' => 'h-32',
                            ])
                        </div>
                    </div>

                    {{-- Live Website Placement Previews for Logo & Favicon --}}
                    <div class="rounded-xl border border-ink/15 bg-slate-50/70 p-4 space-y-4 shadow-2xs">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-ink/10 pb-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-600"></span>
                                    </span>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                        Live Website & Browser Placement Previews
                                    </h4>
                                    <span class="rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5">Real-Time Sync</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Preview exactly where & how your Logo and Favicon render across the website, mobile phones, browser tabs, and customer invoices.
                                </p>
                            </div>

                            {{-- Filter Buttons --}}
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                                <button type="button" data-placement-btn="all" onclick="filterPlacements('all')" class="placement-btn active inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-slate-900 text-white shadow-2xs transition">
                                    <span>All Mockups</span>
                                    <span class="text-[10px] opacity-75">(4)</span>
                                </button>
                                <button type="button" data-placement-btn="favicon" onclick="filterPlacements('favicon')" class="placement-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                                    <span>🌐 Browser Tab</span>
                                </button>
                                <button type="button" data-placement-btn="desktop" onclick="filterPlacements('desktop')" class="placement-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                                    <span>💻 Desktop Header</span>
                                </button>
                                <button type="button" data-placement-btn="mobile" onclick="filterPlacements('mobile')" class="placement-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                                    <span>📱 Mobile Header</span>
                                </button>
                                <button type="button" data-placement-btn="invoice" onclick="filterPlacements('invoice')" class="placement-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                                    <span>🧾 Customer Invoice</span>
                                </button>
                            </div>
                        </div>

                        {{-- Previews Grid --}}
                        <div class="grid gap-4 lg:grid-cols-2" id="placements-container">
                            @php
                                $currentLogoUrl = !empty($settings['store_logo']) && file_exists(public_path('storage/'.$settings['store_logo'])) ? asset('storage/'.$settings['store_logo']) : null;
                                $currentFaviconUrl = !empty($settings['site_favicon']) && file_exists(public_path('storage/'.$settings['site_favicon'])) ? asset('storage/'.$settings['site_favicon']) : null;
                                $currentStoreName = old('store_name', $settings['store_name'] ?? 'Foodness');
                                $currentStoreTagline = old('store_tagline', $settings['store_tagline'] ?? 'Premium Fashion & Cash on Delivery');
                            @endphp

                            {{-- MOCKUP 1: Browser Tab & Address Bar --}}
                            <div class="placement-card flex flex-col rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs space-y-3" data-placement-type="favicon">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-5 w-5 items-center justify-center rounded bg-sky-100 text-sky-700 text-xs">🌐</span>
                                        <span class="text-xs font-bold text-slate-800">Browser Tab & Address Bar (Favicon)</span>
                                    </div>
                                    <span class="rounded bg-sky-50 text-sky-700 border border-sky-200/60 px-2 py-0.5 text-[10px] font-semibold">Square Favicon</span>
                                </div>

                                <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-100/70 shadow-xs">
                                    <div class="flex items-center justify-between border-b border-slate-200/80 bg-slate-200/60 px-3 py-1.5 text-xs text-slate-600">
                                        <div class="flex items-center gap-1.5">
                                            <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                        </div>
                                        <div class="flex max-w-[220px] items-center gap-1.5 rounded-t-md bg-white px-3 py-1 text-[11px] font-medium text-slate-800 shadow-2xs border-t border-x border-slate-200/80 -mb-1.5">
                                            <img id="live-preview-favicon-tab" src="{{ $currentFaviconUrl ?? '' }}" alt="Favicon" class="h-3.5 w-3.5 rounded-xs object-contain {{ empty($currentFaviconUrl) ? 'hidden' : '' }}">
                                            <div id="live-preview-favicon-tab-fallback" class="flex h-3.5 w-3.5 items-center justify-center rounded-xs bg-emerald-700 text-[8px] font-bold text-white {{ !empty($currentFaviconUrl) ? 'hidden' : '' }}">
                                                {{ strtoupper(substr($currentStoreName, 0, 1)) }}
                                            </div>
                                            <span id="live-preview-tab-title" class="truncate max-w-[150px] font-semibold">{{ $currentStoreName }} | {{ $currentStoreTagline }}</span>
                                            <span class="ml-1 text-[10px] text-slate-400">×</span>
                                        </div>
                                        <div class="w-6"></div>
                                    </div>

                                    <div class="flex items-center gap-2 border-b border-slate-200 bg-white px-3 py-1.5 text-xs">
                                        <div class="flex items-center gap-1 text-slate-400">
                                            <span>←</span><span>→</span><span>↻</span>
                                        </div>
                                        <div class="flex flex-1 items-center gap-1.5 rounded-full bg-slate-100 px-3 py-0.5 text-[11px] text-slate-600">
                                            <svg class="h-3 w-3 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                            <img id="live-preview-favicon-url" src="{{ $currentFaviconUrl ?? '' }}" alt="Favicon" class="h-3.5 w-3.5 shrink-0 rounded-xs object-contain {{ empty($currentFaviconUrl) ? 'hidden' : '' }}">
                                            <div id="live-preview-favicon-url-fallback" class="flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-xs bg-emerald-600 text-[8px] font-bold text-white {{ !empty($currentFaviconUrl) ? 'hidden' : '' }}">
                                                {{ strtoupper(substr($currentStoreName, 0, 1)) }}
                                            </div>
                                            <span class="text-slate-800 font-medium">https://foodness.com</span>
                                            <span class="text-slate-400">/collection/new</span>
                                            <span class="ml-auto text-amber-500 text-xs">★</span>
                                        </div>
                                    </div>

                                    <div class="bg-slate-50 p-2.5 text-center text-[11px] text-slate-500">
                                        Favicon renders at crisp 16×16 px / 32×32 px in browser tabs & phone home screens.
                                    </div>
                                </div>

                                <div class="text-[11px] text-slate-500">
                                    <strong class="text-slate-700">Where it appears:</strong> Google Chrome, Apple Safari, Firefox tabs, address bar, bookmarks, and mobile home screen web clip icons.
                                </div>
                            </div>

                            {{-- MOCKUP 2: Desktop Website Header --}}
                            <div class="placement-card flex flex-col rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs space-y-3" data-placement-type="desktop">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-5 w-5 items-center justify-center rounded bg-emerald-100 text-emerald-700 text-xs">💻</span>
                                        <span class="text-xs font-bold text-slate-800">Desktop Website Header (Main Logo)</span>
                                    </div>
                                    <span class="rounded bg-emerald-50 text-emerald-700 border border-emerald-200/60 px-2 py-0.5 text-[10px] font-semibold">Primary Logo</span>
                                </div>

                                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xs">
                                    <div id="live-preview-announcement-bar" class="flex items-center justify-between px-3 py-1 bg-emerald-700 text-[10px] text-white">
                                        <span class="truncate">New Exclusive Fashion Collection · Cash on Delivery</span>
                                        <span class="rounded-full bg-red-600 px-2 py-0.2 text-[9px] font-bold text-white shrink-0 ml-2">Shop Now</span>
                                    </div>

                                    <div class="flex items-center justify-between px-3.5 py-2.5 bg-[#FAF8F3] border-b border-black/10 gap-2">
                                        <div class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 shrink-0">
                                            <span class="font-bold text-slate-900 cursor-default">Menu</span>
                                            <span class="text-slate-600 cursor-default">Shop</span>
                                            <span class="text-slate-600 cursor-default">Collections</span>
                                        </div>

                                        <div class="flex items-center justify-center flex-1 min-w-0 px-2">
                                            <img id="live-preview-logo-desktop" src="{{ $currentLogoUrl ?? '' }}" alt="Store Logo" class="h-8 sm:h-9 w-auto max-w-[140px] sm:max-w-[180px] object-contain {{ empty($currentLogoUrl) ? 'hidden' : '' }}">
                                            <div id="live-preview-logo-desktop-fallback" class="font-display text-lg font-black tracking-tight text-slate-900 truncate {{ !empty($currentLogoUrl) ? 'hidden' : '' }}">
                                                <span class="text-red-600" id="preview-logo-desktop-text">{{ $currentStoreName }}</span><span class="text-emerald-700">.</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <div class="hidden sm:flex items-center gap-1 rounded-full border border-slate-300 bg-white px-2 py-0.5 text-[10px] text-slate-500 w-24">
                                                <svg class="h-3 w-3 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                <span>Search...</span>
                                            </div>
                                            <div class="relative flex h-7 w-7 items-center justify-center rounded-full bg-emerald-700 text-white shadow-xs">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                                <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-red-600 text-[8px] font-bold text-white">2</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gradient-to-r from-slate-100 to-amber-50/50 p-2 text-center text-[10px] text-slate-400">
                                        Sticky Top Header · Seen by desktop and laptop visitors
                                    </div>
                                </div>

                                <div class="text-[11px] text-slate-500">
                                    <strong class="text-slate-700">Where it appears:</strong> Front and center on the sticky header bar on every page of your store across all computer screens.
                                </div>
                            </div>

                            {{-- MOCKUP 3: Mobile Website Header --}}
                            <div class="placement-card flex flex-col rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs space-y-3" data-placement-type="mobile">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-5 w-5 items-center justify-center rounded bg-purple-100 text-purple-700 text-xs">📱</span>
                                        <span class="text-xs font-bold text-slate-800">Mobile Phone Header</span>
                                    </div>
                                    <span class="rounded bg-purple-50 text-purple-700 border border-purple-200/60 px-2 py-0.5 text-[10px] font-semibold">Mobile Viewport</span>
                                </div>

                                <div class="mx-auto w-full max-w-sm overflow-hidden rounded-xl border-2 border-slate-300 bg-white shadow-xs">
                                    <div class="flex items-center justify-between bg-slate-900 px-3 py-1 text-[10px] text-white">
                                        <span class="font-semibold">9:41</span>
                                        <div class="h-2 w-10 rounded-full bg-slate-800 mx-auto"></div>
                                        <div class="flex items-center gap-1 text-[9px]">
                                            <span>📶</span><span>🔋</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between px-3 py-2 bg-[#FAF8F3] border-b border-black/10">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-700 text-white shadow-xs">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                        </div>

                                        <div class="flex items-center justify-center px-2">
                                            <img id="live-preview-logo-mobile" src="{{ $currentLogoUrl ?? '' }}" alt="Mobile Logo" class="h-7 w-auto max-w-[120px] object-contain {{ empty($currentLogoUrl) ? 'hidden' : '' }}">
                                            <div id="live-preview-logo-mobile-fallback" class="font-display text-base font-black tracking-tight text-slate-900 {{ !empty($currentLogoUrl) ? 'hidden' : '' }}">
                                                <span class="text-red-600" id="preview-logo-mobile-text">{{ $currentStoreName }}</span><span class="text-emerald-700">.</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full text-slate-700">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                            </div>
                                            <div class="relative flex h-7 w-7 items-center justify-center rounded-full bg-emerald-700 text-white shadow-xs">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                                <span class="absolute -top-1 -right-1 flex h-3 w-3 items-center justify-center rounded-full bg-red-600 text-[8px] font-bold text-white">2</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-slate-50 p-2 text-center text-[10px] text-slate-400">
                                        Proportionately scaled for smartphones & touchscreens
                                    </div>
                                </div>

                                <div class="text-[11px] text-slate-500">
                                    <strong class="text-slate-700">Where it appears:</strong> Rendered cleanly on Android, iPhone, and iPad screens without pushing down menu actions.
                                </div>
                            </div>

                            {{-- MOCKUP 4: Customer Invoice & Shipping Receipt --}}
                            <div class="placement-card flex flex-col rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs space-y-3" data-placement-type="invoice">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-5 w-5 items-center justify-center rounded bg-amber-100 text-amber-700 text-xs">🧾</span>
                                        <span class="text-xs font-bold text-slate-800">Customer Invoice & Packing Receipt</span>
                                    </div>
                                    <span class="rounded bg-amber-50 text-amber-700 border border-amber-200/60 px-2 py-0.5 text-[10px] font-semibold">Printable Invoice</span>
                                </div>

                                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-xs space-y-2.5 font-sans">
                                    <div class="flex items-start justify-between border-b border-slate-200 pb-2">
                                        <div>
                                            <img id="live-preview-logo-invoice" src="{{ $currentLogoUrl ?? '' }}" alt="Invoice Logo" class="h-7 sm:h-8 w-auto max-w-[140px] object-contain {{ empty($currentLogoUrl) ? 'hidden' : '' }}">
                                            <div id="live-preview-logo-invoice-fallback" class="font-serif text-base font-bold text-slate-900 {{ !empty($currentLogoUrl) ? 'hidden' : '' }}">
                                                <span id="preview-logo-invoice-text">{{ $currentStoreName }}</span> Invoice
                                            </div>
                                            <p id="preview-invoice-tagline" class="text-[10px] text-slate-500 mt-0.5">
                                                {{ $currentStoreTagline }}
                                            </p>
                                        </div>

                                        <div class="text-right text-[10px] text-slate-600">
                                            <div class="font-bold text-slate-900">INVOICE #AS-89420</div>
                                            <div>Date: 19 Oct 2026</div>
                                            <div class="mt-0.5 inline-block rounded bg-emerald-50 text-emerald-700 font-semibold px-1.5 py-0.2 border border-emerald-200">
                                                Cash On Delivery (COD)
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-[10px] text-slate-600 bg-slate-50/70 p-2 rounded">
                                        <div>
                                            <strong class="text-slate-800">Bill To:</strong>
                                            <div>Mr. Rahim Ahmed</div>
                                            <div class="text-slate-400">Gulshan-2, Dhaka · 01700-000000</div>
                                        </div>
                                        <div class="text-right">
                                            <strong class="text-slate-800">Status:</strong>
                                            <div class="text-emerald-700 font-semibold">Order Confirmed</div>
                                            <div class="text-slate-400">Total: ৳ 2,450 (1 item)</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-[11px] text-slate-500">
                                    <strong class="text-slate-700">Where it appears:</strong> Printed at the top of every delivery packing slip, courier parcel, and customer downloadable PDF receipt.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top Announcement Bar Card --}}
            <div class="admin-card" id="header-topbar-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Top Announcement Bar</h3>
                        <p class="text-[11px] text-slate-500">Configure the 3 bullet points and ticker message displayed at the very top of the storefront.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="top_announcement_enabled" value="0">
                        <input type="checkbox" name="top_announcement_enabled" value="1" {{ ($settings['top_announcement_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-3.5">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="admin-label" for="top_announcement_point_1">Highlight Point 1</label>
                            <input id="top_announcement_point_1" name="top_announcement_point_1" type="text" value="{{ old('top_announcement_point_1', $settings['top_announcement_point_1'] ?? 'Free delivery on orders over ৳2,500') }}" class="field text-xs" placeholder="e.g. Free delivery on orders over ৳2,500">
                        </div>
                        <div>
                            <label class="admin-label" for="top_announcement_point_2">Highlight Point 2</label>
                            <input id="top_announcement_point_2" name="top_announcement_point_2" type="text" value="{{ old('top_announcement_point_2', $settings['top_announcement_point_2'] ?? 'Cash on delivery nationwide') }}" class="field text-xs" placeholder="e.g. Cash on delivery nationwide">
                        </div>
                        <div>
                            <label class="admin-label" for="top_announcement_point_3">Highlight Point 3</label>
                            <input id="top_announcement_point_3" name="top_announcement_point_3" type="text" value="{{ old('top_announcement_point_3', $settings['top_announcement_point_3'] ?? '1-2 days in Dhaka, 2-3 days outside') }}" class="field text-xs" placeholder="e.g. 1-2 days in Dhaka, 2-3 days outside">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Interactive Header Navigation Menu Builder --}}
            <div class="admin-card" id="header-menu-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <span>Header Navigation Menu Builder</span>
                        </h3>
                        <p class="text-[11px] text-slate-500">Customize header navigation links, reorder them, or quickly add category shortcuts.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <select id="quick_add_cat_select" class="field text-xs !py-1 !px-2 h-7.5 bg-white border-slate-300 max-w-[170px]">
                            <option value="">-- Add Category --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ route('shop.category', $cat->slug) }}" data-name="{{ $cat->name }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="btn_quick_add_cat" class="admin-btn-secondary !px-2 !py-1 text-xs whitespace-nowrap cursor-pointer">
                            + Add Category
                        </button>
                        <button type="button" id="btn_add_menu_link" class="admin-btn-primary !px-2 !py-1 text-xs whitespace-nowrap inline-flex items-center gap-1 cursor-pointer">
                            <span>+ Custom Link</span>
                        </button>
                    </div>
                </div>

                <div class="admin-card-body p-3.5 space-y-2">
                    {{-- Compact Table Column Guide on Desktop --}}
                    <div class="hidden md:grid grid-cols-12 gap-1.5 px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-100/70 rounded-md mb-1">
                        <div class="col-span-1"># Order</div>
                        <div class="col-span-4">Menu Label</div>
                        <div class="col-span-5">Destination URL</div>
                        <div class="col-span-2 text-right">New Tab & Del</div>
                    </div>

                    <input type="hidden" name="header_menu_links" id="header_menu_links_input" value="{{ old('header_menu_links', $settings['header_menu_links'] ?? '') }}">
                    
                    <div id="header-menu-links-container" class="space-y-1.5">
                        {{-- Dynamically rendered by JS --}}
                    </div>
                </div>
            </div>

            {{-- Header Controls & Contact Settings Card --}}
            <div class="admin-card" id="header-features-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Header Controls & Features</h3>
                        <p class="text-[11px] text-slate-500">Manage sticky header behavior, search visibility, cart button label, and customer support info.</p>
                    </div>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-3.5">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-slate-800 block">Sticky Header</span>
                                <span class="text-[10px] text-slate-500">Keep header fixed at top while scrolling</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="header_sticky" value="0">
                                <input type="checkbox" name="header_sticky" value="1" {{ ($settings['header_sticky'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-slate-800 block">Search Bar</span>
                                <span class="text-[10px] text-slate-500">Show search bar in header</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="header_search_enabled" value="0">
                                <input type="checkbox" name="header_search_enabled" value="1" {{ ($settings['header_search_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <div>
                            <label class="admin-label text-xs" for="header_search_text">Search Placeholder Text</label>
                            <input id="header_search_text" name="header_search_text" type="text" value="{{ old('header_search_text', $settings['header_search_text'] ?? 'Search products...') }}" class="field text-xs" placeholder="Search products...">
                        </div>

                        <div>
                            <label class="admin-label text-xs" for="header_cart_text">Cart Button Text</label>
                            <input id="header_cart_text" name="header_cart_text" type="text" value="{{ old('header_cart_text', $settings['header_cart_text'] ?? 'Bag') }}" class="field text-xs" placeholder="Bag">
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 pt-3 border-t border-slate-100">
                        <div>
                            <label class="admin-label" for="store_phone">Customer Care Hotline</label>
                            <input id="store_phone" name="store_phone" type="text" value="{{ old('store_phone', $settings['store_phone'] ?? '01700000000') }}" class="field">
                        </div>
                        <div>
                            <label class="admin-label" for="store_email">Support Email</label>
                            <input id="store_email" name="store_email" type="email" value="{{ old('store_email', $settings['store_email'] ?? 'support@foodness.com') }}" class="field">
                        </div>
                        <div>
                            <label class="admin-label" for="store_address">Office / Showroom Address</label>
                            <input id="store_address" name="store_address" type="text" value="{{ old('store_address', $settings['store_address'] ?? 'Dhaka, Bangladesh') }}" class="field">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mobile 3-Dash Menu (Hamburger Drawer) — A-Z Customizer --}}
            <div class="admin-card border-emerald-200 shadow-sm" id="mobile-drawer-customizer-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-gradient-to-r from-emerald-50/50 to-white">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-white text-sm shadow-xs">
                            ☰
                        </span>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                                <span>Mobile 3-Dash Menu (Drawer) — A-Z Customizer</span>
                                <span class="rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-1.5 py-0.2">Mobile App-Style</span>
                            </h3>
                            <p class="text-[11px] text-slate-500">Configure slide-out drawer menu links, category list, quick search, hotline/WhatsApp buttons, and promo highlights.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="hidden" name="mobile_drawer_enabled" value="0">
                        <input type="checkbox" name="mobile_drawer_enabled" value="1" {{ ($settings['mobile_drawer_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-semibold text-slate-800">Drawer Enabled</span>
                    </label>
                </div>

                <div class="admin-card-body p-3.5 space-y-3.5">
                    {{-- Section 1: Header & Search inside Drawer --}}
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5 mb-2">
                            <span class="text-emerald-700 font-black">①</span> Drawer Header & Search Controls
                        </h4>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                            <div class="flex items-center justify-between p-1.5 rounded-md bg-white border border-slate-200">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800 block">Show Store Logo</span>
                                    <span class="text-[10px] text-slate-500">Display logo in drawer bar</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="mobile_drawer_show_logo" value="0">
                                    <input type="checkbox" name="mobile_drawer_show_logo" value="1" {{ ($settings['mobile_drawer_show_logo'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>

                            <div>
                                <label class="admin-label text-[10px]" for="mobile_drawer_header_title">Fallback Header Title</label>
                                <input id="mobile_drawer_header_title" name="mobile_drawer_header_title" type="text" value="{{ old('mobile_drawer_header_title', $settings['mobile_drawer_header_title'] ?? '') }}" class="field text-xs !py-1 bg-white" placeholder="e.g. Foodness or মেনু">
                            </div>

                            <div class="flex items-center justify-between p-1.5 rounded-md bg-white border border-slate-200">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800 block">Show Search Bar</span>
                                    <span class="text-[10px] text-slate-500">Quick search shortcut in drawer</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="mobile_drawer_show_search" value="0">
                                    <input type="checkbox" name="mobile_drawer_show_search" value="1" {{ ($settings['mobile_drawer_show_search'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>

                            <div>
                                <label class="admin-label text-[10px]" for="mobile_drawer_search_placeholder">Search Box Placeholder</label>
                                <input id="mobile_drawer_search_placeholder" name="mobile_drawer_search_placeholder" type="text" value="{{ old('mobile_drawer_search_placeholder', $settings['mobile_drawer_search_placeholder'] ?? 'Search products or collections...') }}" class="field text-xs !py-1 bg-white" placeholder="Search products or collections...">
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Drawer Navigation Links (Dynamic Builder) --}}
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-1.5 border-b border-slate-100 mb-2">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <span class="text-emerald-700 font-black">②</span> Custom Drawer Navigation Links
                                </h4>
                                <p class="text-[11px] text-slate-500">Order, customize icons, add badges, and target pages for drawer links.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <select id="quick_add_drawer_cat_select" class="field text-xs !py-1 !px-2 h-7.5 bg-white border-slate-300 max-w-[170px]">
                                    <option value="">-- Add Category --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ route('shop.category', $cat->slug) }}" data-name="{{ $cat->name }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" id="btn_quick_add_drawer_cat" class="admin-btn-secondary !px-2 !py-1 text-xs whitespace-nowrap cursor-pointer">
                                    + Add Category
                                </button>
                                <button type="button" id="btn_add_drawer_link" class="admin-btn-primary !px-2 !py-1 text-xs whitespace-nowrap inline-flex items-center gap-1 cursor-pointer">
                                    <span>+ Custom Link</span>
                                </button>
                                <button type="button" id="btn_reset_drawer_links" class="admin-btn-secondary !px-2 !py-1 text-xs text-slate-500 hover:text-slate-700 whitespace-nowrap cursor-pointer" title="Reset to standard defaults">
                                    ↺ Reset
                                </button>
                            </div>
                        </div>

                        {{-- Compact Table Column Guide on Desktop --}}
                        <div class="hidden md:grid grid-cols-12 gap-1.5 px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-100/70 rounded-md mb-1">
                            <div class="col-span-1"># Order</div>
                            <div class="col-span-2">Icon</div>
                            <div class="col-span-3">Link Title</div>
                            <div class="col-span-3">Target URL</div>
                            <div class="col-span-1">Badge</div>
                            <div class="col-span-2 text-right">New Tab & Del</div>
                        </div>

                        <input type="hidden" name="mobile_drawer_menu_links" id="mobile_drawer_menu_links_input" value="{{ old('mobile_drawer_menu_links', $settings['mobile_drawer_menu_links'] ?? '') }}">
                        
                        <div id="mobile-drawer-links-container" class="space-y-1.5">
                            {{-- Dynamically rendered by JS --}}
                        </div>
                    </div>

                    {{-- Section 3: Categories & Account Sections in Drawer --}}
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5 mb-1.5">
                            <span class="text-emerald-700 font-black">③</span> Categories & Account Sections
                        </h4>
                        <div class="grid gap-2 sm:grid-cols-2">
                            {{-- Categories section --}}
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-2.5 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Show Categories Section</span>
                                        <span class="text-[10px] text-slate-500">List active store categories in drawer</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="mobile_drawer_show_categories" value="0">
                                        <input type="checkbox" name="mobile_drawer_show_categories" value="1" {{ ($settings['mobile_drawer_show_categories'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                                <div>
                                    <label class="admin-label text-[10px]" for="mobile_drawer_categories_title">Categories Section Heading</label>
                                    <input id="mobile_drawer_categories_title" name="mobile_drawer_categories_title" type="text" value="{{ old('mobile_drawer_categories_title', $settings['mobile_drawer_categories_title'] ?? 'ক্যাটাগরি ও কালেকশন') }}" class="field text-xs !py-1 bg-white" placeholder="ক্যাটাগরি ও কালেকশন">
                                </div>
                            </div>

                            {{-- Account section --}}
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-2.5 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Show Customer Account Section</span>
                                        <span class="text-[10px] text-slate-500">Login, Register, and Dashboard shortcuts</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="mobile_drawer_show_account" value="0">
                                        <input type="checkbox" name="mobile_drawer_show_account" value="1" {{ ($settings['mobile_drawer_show_account'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                                <div>
                                    <label class="admin-label text-[10px]" for="mobile_drawer_account_title">Account Section Heading</label>
                                    <input id="mobile_drawer_account_title" name="mobile_drawer_account_title" type="text" value="{{ old('mobile_drawer_account_title', $settings['mobile_drawer_account_title'] ?? 'মাই অ্যাকাউন্ট') }}" class="field text-xs !py-1 bg-white" placeholder="মাই অ্যাকাউন্ট">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4: Promo Mini Card in Drawer --}}
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5 mb-1.5">
                            <span class="text-emerald-700 font-black">④</span> Promotional Highlight Mini Banner
                        </h4>
                        <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-2.5 space-y-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">Show Promo Highlight Card</span>
                                    <span class="text-[10px] text-slate-500">Eye-catching seasonal announcement card in drawer</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="mobile_drawer_show_promo" value="0">
                                    <input type="checkbox" name="mobile_drawer_show_promo" value="1" {{ ($settings['mobile_drawer_show_promo'] ?? '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="admin-label text-xs" for="mobile_drawer_promo_badge">Promo Tag / Badge</label>
                                    <input id="mobile_drawer_promo_badge" name="mobile_drawer_promo_badge" type="text" value="{{ old('mobile_drawer_promo_badge', $settings['mobile_drawer_promo_badge'] ?? 'স্পেশাল অফার') }}" class="field text-xs bg-white" placeholder="স্পেশাল অফার">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="mobile_drawer_promo_text">Promo Message Text</label>
                                    <input id="mobile_drawer_promo_text" name="mobile_drawer_promo_text" type="text" value="{{ old('mobile_drawer_promo_text', $settings['mobile_drawer_promo_text'] ?? 'আজকের অর্ডারে বিশেষ ছাড়!') }}" class="field text-xs bg-white" placeholder="আজকের অর্ডারে বিশেষ ছাড়!">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="mobile_drawer_promo_url">Promo Action URL</label>
                                    <input id="mobile_drawer_promo_url" name="mobile_drawer_promo_url" type="text" value="{{ old('mobile_drawer_promo_url', $settings['mobile_drawer_promo_url'] ?? '/shop') }}" class="field text-xs bg-white" placeholder="/shop">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

{{-- TAB 2: Homepage Sections --}}
        <div id="tab-home" class="tab-content space-y-3 hidden">
            {{-- Homepage Sections Sub-Navigation Jump Bar --}}
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100/90 rounded-xl px-2.5 border border-slate-200">
                <span class="text-slate-400 uppercase text-[10px] tracking-wider shrink-0 font-bold px-1">HOMEPAGE:</span>
                <a href="#home-sections-reorder-manager" class="section-jump-link rounded-lg px-2.5 py-1 bg-emerald-600 text-white font-bold shadow-2xs shrink-0 transition flex items-center gap-1">
                    <span>↕️ Reorder</span>
                </a>
                <a href="#home-stories-card" class="section-jump-link rounded-lg px-2 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">📸 Stories</a>
                <a href="#home-categories-card" class="section-jump-link rounded-lg px-2 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🗂️ Categories</a>
                <a href="#home-gur-showcase-card" class="section-jump-link rounded-lg px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 font-bold shadow-2xs shrink-0 transition">🍯 Gur Showcase</a>
                <a href="#home-dynamic-sections-card" class="section-jump-link rounded-lg px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold shadow-2xs shrink-0 transition">📦 Dynamic Tabs</a>
                <a href="#home-featured-card" class="section-jump-link rounded-lg px-2 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">⭐ Featured</a>
                <a href="#home-reel-card" class="section-jump-link rounded-lg px-2 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🎬 Our Story</a>
                <a href="#home-promises-card" class="section-jump-link rounded-lg px-2 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🛡️ Trust Badges</a>
                <a href="#home-faq-card" class="section-jump-link rounded-lg px-2 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">💬 FAQ</a>
                <a href="#home-hero-card" class="section-jump-link rounded-lg px-2 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🌟 Hero Slider</a>
                <a href="#home-promo-banner-card" class="section-jump-link rounded-lg px-2 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🏷️ Promo Banner</a>
            </div>

            {{-- Drag & Drop Homepage Sections Reordering Manager --}}
            @php
                $allHomeSections = [
                    'stories' => [
                        'label' => 'Live Stories & Reels',
                        'desc' => 'Fullscreen Instagram/Facebook style story viewer with circle thumbnails',
                        'icon' => '📸',
                        'toggle_name' => 'home_show_stories',
                        'card_id' => 'home-stories-card',
                    ],
                    'categories' => [
                        'label' => 'Categories Showcase Grid',
                        'desc' => 'Curated category cards or circular icon badge showcase',
                        'icon' => '🗂️',
                        'toggle_name' => 'home_show_categories',
                        'card_id' => 'home-categories-card',
                    ],
                    'gur_showcase' => [
                        'label' => 'Interactive Gur Showcase (Accordion & Timeline)',
                        'desc' => 'Interactive 4-step accordion story with mobile timeline, product previews & order triggers',
                        'icon' => '🍯',
                        'toggle_name' => 'home_show_gur_showcase',
                        'card_id' => 'home-gur-showcase-card',
                    ],
                    'dynamic_sections' => [
                        'label' => 'Dynamic Product Sections (Tabs)',
                        'desc' => 'Multi-tab dynamic product showcase with category and subcategory filters',
                        'icon' => '📦',
                        'toggle_name' => null,
                        'card_id' => 'home-dynamic-sections-card',
                    ],
                    'featured' => [
                        'label' => 'Trending / Featured Products',
                        'desc' => 'Responsive showcase grid for trending and featured products',
                        'icon' => '⭐',
                        'toggle_name' => 'home_show_featured',
                        'card_id' => 'home-featured-card',
                    ],
                    'story_reel' => [
                        'label' => 'Our Story & Video Reel',
                        'desc' => 'Brand storytelling narrative, 3 process steps and vertical video reel',
                        'icon' => '🎬',
                        'toggle_name' => 'home_show_story_section',
                        'card_id' => 'home-reel-card',
                    ],
                    'promises' => [
                        'label' => 'Brand Commitments (4 Trust Badges)',
                        'desc' => 'Authenticity, cash on delivery, master craftsmanship, and fast delivery badges',
                        'icon' => '🛡️',
                        'toggle_name' => 'home_show_promises',
                        'card_id' => 'home-promises-card',
                    ],
                    'faq' => [
                        'label' => 'Customer FAQ (Chat Simulator)',
                        'desc' => 'Interactive customer question and answer thread with WhatsApp CTA',
                        'icon' => '💬',
                        'toggle_name' => 'home_show_faq',
                        'card_id' => 'home-faq-card',
                    ],
                    'hero' => [
                        'label' => 'Homepage Hero Image Slider',
                        'desc' => 'Multi-image sliding banners, destination URLs, and autoplay timing',
                        'icon' => '🌟',
                        'toggle_name' => 'home_show_hero',
                        'card_id' => 'home-hero-card',
                    ],
                    'promo_banner' => [
                        'label' => 'Promotional Full Banner',
                        'desc' => 'Wide mid-page promotional banner (e.g. 1024×266 / 1200×320 ratio)',
                        'icon' => '🏷️',
                        'toggle_name' => 'home_show_promo_banner',
                        'card_id' => 'home-promo-banner-card',
                    ],
                ];

                $rawOrder = $settings['home_sections_order'] ?? null;
                $currentOrder = [];
                if (!empty($rawOrder)) {
                    $decoded = is_array($rawOrder) ? $rawOrder : json_decode($rawOrder, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $k) {
                            if (isset($allHomeSections[$k]) && !in_array($k, $currentOrder, true)) {
                                $currentOrder[] = $k;
                            }
                        }
                    }
                }
                foreach (array_keys($allHomeSections) as $k) {
                    if (!in_array($k, $currentOrder, true)) {
                        $currentOrder[] = $k;
                    }
                }
            @endphp

            <div id="home-sections-reorder-manager" class="admin-card border-2 border-emerald-500/30 shadow-md bg-gradient-to-b from-white to-emerald-50/20">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-xs shrink-0">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Homepage Sections Reordering (Drag & Drop)</h3>
                                <span class="rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2 py-0.5">Drag & Drop</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">Drag cards or use the <span class="font-bold text-slate-700">▲ / ▼</span> buttons to customize section display sequence on the storefront.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            id="btn-reset-sections-order"
                            class="admin-btn-secondary !px-2.5 !py-1.5 text-xs inline-flex items-center gap-1 cursor-pointer"
                            title="Restore default sequence"
                        >
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Default Order</span>
                        </button>
                    </div>
                </div>

                <div class="admin-card-body p-4 sm:p-5 space-y-3">
                    {{-- Hidden storage input for form submit --}}
                    <input
                        type="hidden"
                        name="home_sections_order"
                        id="home_sections_order_input"
                        value="{{ old('home_sections_order', $settings['home_sections_order'] ?? json_encode($currentOrder)) }}"
                    >

                    {{-- Helper Tip --}}
                    <div class="flex items-center justify-between text-[11px] text-slate-500 pb-1 border-b border-slate-100">
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Sections will be displayed on the storefront in order from 1 to 8.</span>
                        </span>
                        <span class="hidden sm:inline text-slate-400">Drag to reorder or click arrow buttons</span>
                    </div>

                    {{-- Draggable Sections List --}}
                    <div id="home-sections-reorder-list" class="space-y-2">
                        @foreach ($currentOrder as $idx => $secKey)
                            @php
                                $secDef = $allHomeSections[$secKey] ?? null;
                                if (!$secDef) continue;
                                $toggleKey = $secDef['toggle_name'];
                                $isEnabled = $toggleKey ? (($settings[$toggleKey] ?? '1') === '1') : true;
                            @endphp
                            <div
                                class="section-reorder-item flex items-center justify-between gap-3 p-3 bg-white hover:bg-slate-50/80 border border-slate-200 hover:border-emerald-300 rounded-xl transition shadow-2xs select-none"
                                draggable="true"
                                data-section-key="{{ $secKey }}"
                                data-card-id="{{ $secDef['card_id'] }}"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    {{-- Drag Grip Handle --}}
                                    <div class="drag-grip text-slate-400 hover:text-slate-700 cursor-grab active:cursor-grabbing p-1 rounded hover:bg-slate-100 shrink-0" title="Drag up or down to reorder">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                                        </svg>
                                    </div>

                                    {{-- Position Rank Badge --}}
                                    <span class="sec-pos-badge flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 text-emerald-800 font-extrabold text-xs border border-emerald-200 shrink-0">
                                        {{ $idx + 1 }}
                                    </span>

                                    {{-- Section Icon & Info --}}
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base shrink-0">{{ $secDef['icon'] }}</span>
                                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ $secDef['label'] }}</h4>
                                        </div>
                                        <p class="text-[11px] text-slate-500 truncate hidden sm:block mt-0.5">{{ $secDef['desc'] }}</p>
                                    </div>
                                </div>

                                {{-- Right Controls: Status Badge + Move Buttons + Jump to Card --}}
                                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                                    <span
                                        class="sec-status-badge text-[10px] sm:text-[11px] px-2 py-0.5 rounded-full font-bold {{ $isEnabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}"
                                        data-sec-status="{{ $secKey }}"
                                    >
                                        {{ $isEnabled ? 'Active' : 'Hidden' }}
                                    </span>

                                    {{-- Move Up / Down Buttons for Easy Click Reorder --}}
                                    <div class="flex items-center bg-slate-100 rounded-lg p-0.5 border border-slate-200">
                                        <button
                                            type="button"
                                            class="btn-sec-up p-1 text-slate-600 hover:text-emerald-700 hover:bg-white rounded transition cursor-pointer"
                                            title="Move One Step Up"
                                            aria-label="Move Up"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn-sec-down p-1 text-slate-600 hover:text-emerald-700 hover:bg-white rounded transition cursor-pointer"
                                            title="Move One Step Down"
                                            aria-label="Move Down"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Smooth Jump to Section Editor Card --}}
                                    <a
                                        href="#{{ $secDef['card_id'] }}"
                                        class="text-xs text-slate-700 hover:text-[#013F1E] font-semibold px-2 py-1 bg-slate-100 hover:bg-emerald-50 rounded-lg border border-slate-200 hover:border-emerald-200 transition"
                                        title="Edit settings for this section"
                                    >
                                        Settings &darr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Container holding all section form cards --}}
            <div id="home-sections-cards-container" class="space-y-4">
            <div id="home-hero-card" data-section-card="hero">
                <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Homepage Hero Image Slider</h3>
                        <p class="text-[11px] text-slate-500">Manage banner slides, destination URLs, ordering, and autoplay timing.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="home_show_hero" value="0">
                        <input type="checkbox" name="home_show_hero" value="1" {{ ($settings['home_show_hero'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-4">
                    {{-- Hidden Fallback Inputs for Legacy Test Assertions --}}
                    <input type="hidden" name="hero_title" value="{{ old('hero_title', $settings['hero_title'] ?? 'New Exclusive Collection') }}">
                    <input type="hidden" name="hero_subtitle" value="{{ old('hero_subtitle', $settings['hero_subtitle'] ?? 'Premium quality fabrics') }}">
                    <input type="hidden" name="hero_custom_image" value="{{ $settings['hero_custom_image'] ?? '' }}">

                    {{-- Top Settings Bar: Autoplay, Layout Width, and Slide Counter --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-2">
                                <label class="admin-label !mb-0 font-bold text-xs" for="hero_slider_width">Layout Width:</label>
                                @php
                                    $selectedHeroWidth = old('hero_slider_width', $settings['hero_slider_width'] ?? 'full');
                                @endphp
                                <select id="hero_slider_width" name="hero_slider_width" class="field !py-1 !text-xs bg-white w-auto">
                                    <option value="full" {{ $selectedHeroWidth === 'full' ? 'selected' : '' }}>Full Width (Edge-to-Edge 100vw) — Recommended</option>
                                    <option value="wide" {{ $selectedHeroWidth === 'wide' ? 'selected' : '' }}>Wide Container (1440px)</option>
                                    <option value="boxed" {{ $selectedHeroWidth === 'boxed' ? 'selected' : '' }}>Boxed Container (1152px with Corners)</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="admin-label !mb-0 font-bold text-xs" for="hero_slider_autoplay">Slide Autoplay Speed:</label>
                                @php
                                    $selectedAutoplay = old('hero_slider_autoplay', $settings['hero_slider_autoplay'] ?? '5000');
                                @endphp
                                <select id="hero_slider_autoplay" name="hero_slider_autoplay" class="field !py-1 !text-xs bg-white w-auto max-w-[200px]">
                                    <option value="3000" {{ $selectedAutoplay === '3000' ? 'selected' : '' }}>3 Seconds</option>
                                    <option value="4000" {{ $selectedAutoplay === '4000' ? 'selected' : '' }}>4 Seconds</option>
                                    <option value="5000" {{ $selectedAutoplay === '5000' ? 'selected' : '' }}>5 Seconds (Recommended)</option>
                                    <option value="7000" {{ $selectedAutoplay === '7000' ? 'selected' : '' }}>7 Seconds</option>
                                    <option value="0" {{ $selectedAutoplay === '0' ? 'selected' : '' }}>Manual Only (No Autoplay)</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span id="hero-slides-counter" class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                0 Slides Configured
                            </span>
                        </div>
                    </div>

                    {{-- Upload Banner Box --}}
                    <div class="rounded-xl border-2 border-dashed border-slate-300 hover:border-indigo-400 bg-white p-4 sm:p-5 text-center transition cursor-pointer group" id="hero-slide-dropzone-box">
                        <input type="file" id="hero-slide-file-input" name="hero_new_slides[]" multiple accept="image/*" class="sr-only">
                        <div class="flex flex-col items-center justify-center space-y-2 pointer-events-none">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 ring-4 ring-indigo-50/50 group-hover:scale-105 transition">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">
                                    <span class="text-indigo-600 underline">Choose from Media Library</span> or drag and drop new banner slides
                                </p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Pick an existing image, or upload a new one inside the media window · 1920 × 750 px · PNG, JPG, WEBP · Max 8 MB
                                </p>
                            </div>
                            <div id="hero-upload-progress" class="hidden text-xs font-semibold text-indigo-600 flex items-center gap-1.5 pt-1">
                                <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Uploading banner slide...</span>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden JSON storage for hero slides --}}
                    @php
                        $initialHeroSlides = old('hero_slides', $settings['hero_slides'] ?? null);
                        if (empty($initialHeroSlides)) {
                            if (!empty($settings['hero_custom_image']) && file_exists(public_path('storage/'.$settings['hero_custom_image']))) {
                                $initialHeroSlides = json_encode([[
                                    'id' => 'slide_legacy_1',
                                    'image' => $settings['hero_custom_image'],
                                    'title' => 'Main Banner',
                                    'link_url' => '/shop',
                                    'is_active' => true,
                                ]]);
                            } else {
                                $initialHeroSlides = json_encode([]);
                            }
                        }
                    @endphp
                    <input type="hidden" name="hero_slides" id="hero_slides_input" value="{{ $initialHeroSlides }}">

                    {{-- Slide Items Container --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="admin-label !mb-0 font-bold text-xs">Banner Slides (Reorder with Up/Down buttons):</label>
                        </div>
                        <div id="hero-slides-list" class="space-y-3">
                            {{-- Populated dynamically by JavaScript --}}
                        </div>
                        <div id="hero-slides-empty" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-6 text-center text-slate-400 text-xs">
                            No banner slides added yet. Click the upload box above to add your first hero banner image.
                        </div>
                    </div>
                </div>
            </div>
            </div>

            

            {{-- Promotional Full Banner Card --}}
            <div id="home-promo-banner-card" data-section-card="promo_banner">
                <div class="admin-card">
                    <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Promotional Full Banner</h3>
                                <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700">Banner</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Wide full-container promotional strip banner (e.g. 1024×266, 1200×320 or 1920×500).</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="home_show_promo_banner" value="0">
                            <input type="checkbox" name="home_show_promo_banner" value="1" {{ ($settings['home_show_promo_banner'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                            <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                        </label>
                    </div>
                    <div class="admin-card-body p-4 sm:p-5 space-y-4">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="admin-label" for="promo_banner_width">Layout Width</label>
                                @php
                                    $selectedPromoWidth = old('promo_banner_width', $settings['promo_banner_width'] ?? 'full');
                                @endphp
                                <select id="promo_banner_width" name="promo_banner_width" class="field !py-2 !text-xs bg-white">
                                    <option value="full" {{ $selectedPromoWidth === 'full' ? 'selected' : '' }}>Full Width (Edge-to-Edge 100vw)</option>
                                    <option value="wide" {{ $selectedPromoWidth === 'wide' ? 'selected' : '' }}>Wide Container (1440px)</option>
                                    <option value="boxed" {{ $selectedPromoWidth === 'boxed' ? 'selected' : '' }}>Boxed Container (1152px with Corners)</option>
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" for="promo_banner_title">Banner Alt / Title (Optional)</label>
                                <input id="promo_banner_title" name="promo_banner_title" type="text" value="{{ old('promo_banner_title', $settings['promo_banner_title'] ?? 'Exclusive Special Promotion') }}" class="field" placeholder="e.g. Special Discount Campaign">
                            </div>
                            <div>
                                <label class="admin-label" for="promo_banner_link">Destination Link URL (Optional)</label>
                                <input id="promo_banner_link" name="promo_banner_link" type="text" value="{{ old('promo_banner_link', $settings['promo_banner_link'] ?? '/shop') }}" class="field" placeholder="e.g. /shop or /products/item">
                            </div>
                        </div>

                        {{-- Promo Banner Image Dropzone --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="admin-label !mb-0 font-semibold" for="promo_banner_image">Banner Image</label>
                                    <p class="text-[11px] text-slate-500">Full container wide banner graphic.</p>
                                </div>
                                <span class="rounded bg-slate-100 text-slate-600 px-2.5 py-0.5 text-[10px] font-bold shrink-0">Ratio ~3.8:1 (e.g. 1024×266 / 1200×320)</span>
                            </div>

                            @php
                                $promoRaw = $settings['promo_banner_image'] ?? null;
                                $currentPromoBanner = null;
                                if (!empty($promoRaw) && $promoRaw !== 'none') {
                                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($promoRaw) || file_exists(public_path('storage/'.$promoRaw))) {
                                        $currentPromoBanner = asset('storage/'.$promoRaw);
                                    } elseif (file_exists(public_path($promoRaw))) {
                                        $currentPromoBanner = asset($promoRaw);
                                    } elseif (str_starts_with($promoRaw, 'http') || str_starts_with($promoRaw, '/')) {
                                        $currentPromoBanner = $promoRaw;
                                    }
                                }
                            @endphp

                            @include('admin.partials.dropzone', [
                                'name' => 'promo_banner_image',
                                'id' => 'promo_banner_image',
                                'current' => $currentPromoBanner,
                                'hint' => 'Recommended: 1024 × 266 px, 1200 × 320 px or 1920 × 500 px · PNG, JPG, WEBP · Max 8 MB',
                                'maxHeight' => 'h-36',
                                'removeName' => 'remove_promo_banner_image',
                            ])
                        </div>
                    </div>
                </div>
            </div>

            <div id="home-stories-card" data-section-card="stories">
                {{-- Stories Section (Facebook / Instagram Style) --}}
            <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Facebook & Instagram Style Stories</h3>
                            <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">Story</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Show stories circles in the hero slider section with images/videos.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="home_show_stories" value="0">
                        <input type="checkbox" name="home_show_stories" value="1" {{ ($settings['home_show_stories'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-3.5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-xl border border-rose-100 bg-rose-50/40 p-3.5">
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-rose-900 block">Stories Content Management</span>
                            <p class="text-[11px] text-rose-700 mt-0.5">Use Stories Manager to create, edit, reorder or delete image and video stories.</p>
                        </div>
                        <a href="{{ route('admin.stories.index') }}" class="btn-primary shrink-0 inline-flex items-center gap-1.5 text-xs py-1.5 px-3">
                            <span>Manage Stories &rarr;</span>
                        </a>
                    </div>

                    <div>
                        <label class="admin-label" for="stories_section_title">Stories Section Title</label>
                        <input id="stories_section_title" name="stories_section_title" type="text" value="{{ old('stories_section_title', $settings['stories_section_title'] ?? 'Stories') }}" placeholder="Stories" class="field">
                    </div>
                </div>
            </div>
            </div>

            

            <div id="home-categories-card" data-section-card="categories">
                {{-- Categories Section --}}
            <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Categories Showcase Section</h3>
                        <p class="text-[11px] text-slate-500">Show/hide and configure category cards showcase.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="home_show_categories" value="0">
                        <input type="checkbox" name="home_show_categories" value="1" {{ ($settings['home_show_categories'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="admin-label" for="category_section_title">Section Title</label>
                            <input id="category_section_title" name="category_section_title" type="text" value="{{ old('category_section_title', $settings['category_section_title'] ?? 'আমাদের ক্যাটাগরিসমূহ') }}" class="field text-xs" placeholder="আমাদের ক্যাটাগরিসমূহ">
                        </div>
                        <div>
                            <label class="admin-label" for="category_card_style">Card Design Style</label>
                            <select id="category_card_style" name="category_card_style" class="field text-xs bg-white">
                                <option value="card" {{ ($settings['category_card_style'] ?? 'card') === 'card' ? 'selected' : '' }}>Modern Showcase Card Style (Default)</option>
                                <option value="circle" {{ ($settings['category_card_style'] ?? 'card') === 'circle' ? 'selected' : '' }}>Circular Icon Badge Style</option>
                            </select>
                        </div>
                    </div>

                    {{-- Category Grid & Limit Controls --}}
                    <div class="rounded-xl border border-ink/10 bg-mist/30 p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-ink uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-sage" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span>Category Layout & Count Settings</span>
                            </span>
                            <span class="text-[11px] text-muted">Control how categories appear on Homepage</span>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="admin-label text-xs" for="category_limit">
                                    Total Categories to Show
                                </label>
                                <select id="category_limit" name="category_limit" class="field bg-white text-xs">
                                    <option value="4" {{ ($settings['category_limit'] ?? '8') === '4' ? 'selected' : '' }}>4 Categories</option>
                                    <option value="6" {{ ($settings['category_limit'] ?? '8') === '6' ? 'selected' : '' }}>6 Categories</option>
                                    <option value="8" {{ ($settings['category_limit'] ?? '8') === '8' ? 'selected' : '' }}>8 Categories (Default)</option>
                                    <option value="10" {{ ($settings['category_limit'] ?? '8') === '10' ? 'selected' : '' }}>10 Categories</option>
                                    <option value="12" {{ ($settings['category_limit'] ?? '8') === '12' ? 'selected' : '' }}>12 Categories</option>
                                    <option value="16" {{ ($settings['category_limit'] ?? '8') === '16' ? 'selected' : '' }}>16 Categories</option>
                                    <option value="0" {{ ($settings['category_limit'] ?? '8') === '0' ? 'selected' : '' }}>All Active Categories</option>
                                </select>
                            </div>

                            <div>
                                <label class="admin-label text-xs" for="category_cols_mobile">
                                    📱 Mobile Columns
                                </label>
                                <select id="category_cols_mobile" name="category_cols_mobile" class="field bg-white text-xs">
                                    <option value="1" {{ ($settings['category_cols_mobile'] ?? '1') === '1' ? 'selected' : '' }}>1 Column (Full Width)</option>
                                    <option value="2" {{ ($settings['category_cols_mobile'] ?? '1') === '2' ? 'selected' : '' }}>2 Columns (Compact)</option>
                                </select>
                            </div>

                            <div>
                                <label class="admin-label text-xs" for="category_cols_desktop">
                                    🖥️ Desktop Columns
                                </label>
                                <select id="category_cols_desktop" name="category_cols_desktop" class="field bg-white text-xs">
                                    <option value="2" {{ ($settings['category_cols_desktop'] ?? '4') === '2' ? 'selected' : '' }}>2 Columns</option>
                                    <option value="3" {{ ($settings['category_cols_desktop'] ?? '4') === '3' ? 'selected' : '' }}>3 Columns</option>
                                    <option value="4" {{ ($settings['category_cols_desktop'] ?? '4') === '4' ? 'selected' : '' }}>4 Columns (Default)</option>
                                    <option value="5" {{ ($settings['category_cols_desktop'] ?? '4') === '5' ? 'selected' : '' }}>5 Columns</option>
                                    <option value="6" {{ ($settings['category_cols_desktop'] ?? '4') === '6' ? 'selected' : '' }}>6 Columns</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div id="home-gur-showcase-card" data-section-card="gur_showcase">
                {{-- Interactive Gur Showcase Section (Timeline & Story Accordion) --}}
                <div class="admin-card border-2 border-amber-500/30 shadow-md bg-white">
                    <div class="admin-card-header border-b border-slate-100 px-4 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-amber-50/60 via-white to-white">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">🍯</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Interactive Gur Showcase (Accordion & Timeline)</h3>
                                    <span class="rounded bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2 py-0.5">Interactive</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">Configure headlines, mobile timeline stepper, autoplay timing, and 4 interactive product/video cards.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="home_show_gur_showcase" value="0">
                            <input type="checkbox" name="home_show_gur_showcase" value="1" {{ ($settings['home_show_gur_showcase'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                            <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                        </label>
                    </div>

                    <div class="admin-card-body p-4 sm:p-5 space-y-5">
                        {{-- Top Header & Banner Settings --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-3.5">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                <span>Section Header & Main Action</span>
                            </span>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label class="admin-label text-xs" for="gur_badge_text">Top Pill Badge</label>
                                    <input id="gur_badge_text" name="gur_badge_text" type="text" value="{{ old('gur_badge_text', $settings['gur_badge_text'] ?? 'Foodness™ অরিজিনাল') }}" class="field bg-white text-xs" placeholder="Foodness™ অরিজিনাল">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="gur_title">Main Title Prefix</label>
                                    <input id="gur_title" name="gur_title" type="text" value="{{ old('gur_title', $settings['gur_title'] ?? 'খেজুরের') }}" class="field bg-white text-xs" placeholder="খেজুরের">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="gur_title_highlight">Highlighted Title (Red)</label>
                                    <input id="gur_title_highlight" name="gur_title_highlight" type="text" value="{{ old('gur_title_highlight', $settings['gur_title_highlight'] ?? 'খাঁটি গুড়') }}" class="field bg-white text-xs" placeholder="খাঁটি গুড়">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="gur_subtitle">Subtitle / Tagline</label>
                                    <input id="gur_subtitle" name="gur_subtitle" type="text" value="{{ old('gur_subtitle', $settings['gur_subtitle'] ?? 'শীতের ভোরের রস, মাটির চুলায় জ্বাল।') }}" class="field bg-white text-xs" placeholder="শীতের ভোরের রস, মাটির চুলায় জ্বাল।">
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 pt-1">
                                <div>
                                    <label class="admin-label text-xs" for="gur_btn_text">Order Button Text</label>
                                    <input id="gur_btn_text" name="gur_btn_text" type="text" value="{{ old('gur_btn_text', $settings['gur_btn_text'] ?? 'অর্ডার করুন') }}" class="field bg-white text-xs" placeholder="অর্ডার করুন">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="gur_btn_url">Order Button Link URL</label>
                                    <input id="gur_btn_url" name="gur_btn_url" type="text" value="{{ old('gur_btn_url', $settings['gur_btn_url'] ?? '/shop') }}" class="field bg-white text-xs" placeholder="/shop (Leave '#' to open modal)">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="gur_autoplay">Autoplay Accordion</label>
                                    <select id="gur_autoplay" name="gur_autoplay" class="field bg-white text-xs">
                                        <option value="1" {{ ($settings['gur_autoplay'] ?? '1') === '1' ? 'selected' : '' }}>Enabled (Auto-advances cards)</option>
                                        <option value="0" {{ ($settings['gur_autoplay'] ?? '1') === '0' ? 'selected' : '' }}>Disabled (Manual click only)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="gur_card_duration">Card Slide Duration (Seconds)</label>
                                    <input id="gur_card_duration" name="gur_card_duration" type="number" step="0.5" min="2" max="20" value="{{ old('gur_card_duration', $settings['gur_card_duration'] ?? '4.5') }}" class="field bg-white text-xs" placeholder="4.5">
                                </div>
                            </div>
                        </div>

                        {{-- 4 Interactive Cards Configuration --}}
                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🎴 4 Interactive Timeline & Accordion Cards</span>
                                </h4>
                                <span class="text-[11px] text-slate-400">Step 1 to 4 shown in sequential timeline</span>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                {{-- CARD 1: Video / Story Harvest --}}
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50/30 p-4 space-y-3">
                                    <div class="flex items-center justify-between pb-2 border-b border-emerald-100">
                                        <span class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-600 text-white text-xs font-bold">1</span>
                                            <span class="text-xs font-bold text-slate-900">Card 1: Video / Story Harvest</span>
                                        </span>
                                        <span class="text-[10px] font-bold text-emerald-800 uppercase bg-emerald-100 px-2 py-0.5 rounded">Video Card</span>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_1_time">Timeline Time</label>
                                            <input id="gur_card_1_time" name="gur_card_1_time" type="text" value="{{ old('gur_card_1_time', $settings['gur_card_1_time'] ?? 'ভোর ৬টা') }}" class="field bg-white text-xs" placeholder="ভোর ৬টা">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_1_title">Card Title</label>
                                            <input id="gur_card_1_title" name="gur_card_1_title" type="text" value="{{ old('gur_card_1_title', $settings['gur_card_1_title'] ?? 'রস নামানো') }}" class="field bg-white text-xs" placeholder="রস নামানো">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="admin-label text-xs" for="gur_card_1_desc">Description / Narrative</label>
                                        <textarea id="gur_card_1_desc" name="gur_card_1_desc" rows="2" class="field bg-white text-xs" placeholder="শীতের কুয়াশায়, গাছের ডগা থেকে রস নামানো হয় সূর্য ওঠার আগেই...">{{ old('gur_card_1_desc', $settings['gur_card_1_desc'] ?? 'শীতের কুয়াশায়, গাছের ডগা থেকে রস নামানো হয় সূর্য ওঠার আগেই। ভোরের হিমেল হাওয়ায় সংগৃহীত এই কাঁচা রসই খাঁটি গুড়ের মূল রহস্য।') }}</textarea>
                                    </div>

                                    <div>
                                        <label class="admin-label text-xs" for="gur_card_1_video_url">Background Video URL (mp4 direct link)</label>
                                        <input id="gur_card_1_video_url" name="gur_card_1_video_url" type="url" value="{{ old('gur_card_1_video_url', $settings['gur_card_1_video_url'] ?? 'https://assets.mixkit.co/videos/preview/mixkit-morning-fog-in-a-forest-of-pines-42742-large.mp4') }}" class="field bg-white text-xs" placeholder="https://...">
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2 pt-1">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="admin-label text-xs !mb-0 font-semibold" for="gur_card_1_video">Custom Video File</label>
                                                <span class="rounded bg-mist px-2 py-0.5 text-[10px] font-bold text-muted">MP4 / WebM</span>
                                            </div>
                                            @include('admin.partials.dropzone', [
                                                'name' => 'gur_card_1_video',
                                                'id' => 'gur_card_1_video',
                                                'isVideo' => true,
                                                'current' => !empty($settings['gur_card_1_video']) && file_exists(public_path('storage/'.$settings['gur_card_1_video'])) ? asset('storage/'.$settings['gur_card_1_video']) : null,
                                                'hint' => 'Recommended: 1080×1920 (9:16 Vertical) or 720p · Max 100 MB',
                                                'accept' => 'video/mp4,video/webm',
                                                'maxHeight' => 'h-32',
                                                'removeName' => 'remove_gur_card_1_video',
                                            ])
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="admin-label text-xs !mb-0 font-semibold" for="gur_card_1_image">Poster / Fallback Image</label>
                                                <span class="rounded bg-emerald-50 text-emerald-800 border border-emerald-200/80 px-2 py-0.5 text-[10px] font-bold">
                                                    Recommended: 600 × 800 px (3:4)
                                                </span>
                                            </div>
                                            @include('admin.partials.dropzone', [
                                                'name' => 'gur_card_1_image',
                                                'id' => 'gur_card_1_image',
                                                'current' => !empty($settings['gur_card_1_image']) && (file_exists(public_path('storage/'.$settings['gur_card_1_image'])) || file_exists(public_path($settings['gur_card_1_image']))) ? (file_exists(public_path('storage/'.$settings['gur_card_1_image'])) ? asset('storage/'.$settings['gur_card_1_image']) : asset($settings['gur_card_1_image'])) : null,
                                                'hint' => 'Upload PNG, JPG, WEBP · Recommended: 600×800 px · Max 16 MB',
                                                'accept' => 'image/png,image/webp,image/jpeg,image/jpg',
                                                'maxHeight' => 'h-32',
                                                'removeName' => 'remove_gur_card_1_image',
                                            ])
                                        </div>
                                    </div>
                                </div>

                                {{-- CARD 2: Jhola Gur --}}
                                <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3.5 shadow-2xs">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                        <span class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-600 text-white text-xs font-bold">2</span>
                                            <span class="text-xs font-bold text-slate-900">Card 2: Showcase Product</span>
                                        </span>
                                        <span class="text-[10px] font-bold text-amber-800 uppercase bg-amber-50 px-2 py-0.5 rounded">Dynamic Product</span>
                                    </div>

                                    {{-- Product Selector --}}
                                    <div class="rounded-lg bg-amber-50/50 border border-amber-200/70 p-2.5 space-y-1">
                                        <label class="admin-label text-xs !mb-0 font-bold text-amber-950 flex items-center gap-1.5" for="gur_card_2_product_id">
                                            <span>📦 Connect Product (Auto-sync title, price, desc & image)</span>
                                        </label>
                                        <select id="gur_card_2_product_id" name="gur_card_2_product_id" class="field bg-white text-xs">
                                            <option value="">-- No Product Linked (Use Manual Defaults) --</option>
                                            @if (!empty($products))
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}" {{ ($settings['gur_card_2_product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                        {{ $p->name }} (৳{{ (int)$p->price }})
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <p class="text-[10px] text-amber-800/80">When a product is selected, title, price, description, link and image automatically sync from it. Use fields below only to override.</p>
                                    </div>

                                    {{-- Timeline Time (Manual) & Overrides --}}
                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="admin-label text-xs font-bold text-slate-800" for="gur_card_2_time">Timeline Time (Manual)</label>
                                            <input id="gur_card_2_time" name="gur_card_2_time" type="text" value="{{ old('gur_card_2_time', $settings['gur_card_2_time'] ?? 'সকাল ৮টা') }}" class="field text-xs bg-white" placeholder="সকাল ৮টা">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_2_title">Custom Title Override</label>
                                            <input id="gur_card_2_title" name="gur_card_2_title" type="text" value="{{ old('gur_card_2_title', $settings['gur_card_2_title'] ?? '') }}" class="field text-xs" placeholder="Leave blank to use product name">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_2_price">Custom Price Override</label>
                                            <input id="gur_card_2_price" name="gur_card_2_price" type="text" value="{{ old('gur_card_2_price', $settings['gur_card_2_price'] ?? '') }}" class="field text-xs" placeholder="Leave blank to use product price">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="admin-label text-xs" for="gur_card_2_desc">Custom Description / Tagline Override</label>
                                        <input id="gur_card_2_desc" name="gur_card_2_desc" type="text" value="{{ old('gur_card_2_desc', $settings['gur_card_2_desc'] ?? '') }}" class="field text-xs" placeholder="Leave blank to use product short description">
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_2_btn_text">Action Link Text Override</label>
                                            <input id="gur_card_2_btn_text" name="gur_card_2_btn_text" type="text" value="{{ old('gur_card_2_btn_text', $settings['gur_card_2_btn_text'] ?? '') }}" class="field text-xs" placeholder="Auto if blank">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_2_btn_url">Action Link URL Override</label>
                                            <input id="gur_card_2_btn_url" name="gur_card_2_btn_url" type="text" value="{{ old('gur_card_2_btn_url', $settings['gur_card_2_btn_url'] ?? '') }}" class="field text-xs" placeholder="Auto product URL if blank">
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="admin-label text-xs !mb-0 font-semibold" for="gur_card_2_image">Custom Product Image Override (Optional)</label>
                                            <span class="rounded bg-emerald-50 text-emerald-800 border border-emerald-200/80 px-2 py-0.5 text-[10px] font-bold">
                                                Recommended: 600 × 600 px (1:1 PNG Transparent)
                                            </span>
                                        </div>
                                        @include('admin.partials.dropzone', [
                                            'name' => 'gur_card_2_image',
                                            'id' => 'gur_card_2_image',
                                            'current' => !empty($settings['gur_card_2_image']) && (file_exists(public_path('storage/'.$settings['gur_card_2_image'])) || file_exists(public_path($settings['gur_card_2_image']))) ? (file_exists(public_path('storage/'.$settings['gur_card_2_image'])) ? asset('storage/'.$settings['gur_card_2_image']) : asset($settings['gur_card_2_image'])) : null,
                                            'hint' => 'Upload transparent PNG, WEBP or JPG · Recommended: 600×600 px · Max 16 MB',
                                            'accept' => 'image/png,image/webp,image/jpeg,image/jpg',
                                            'maxHeight' => 'h-32',
                                            'removeName' => 'remove_gur_card_2_image',
                                        ])
                                    </div>
                                </div>

                                {{-- CARD 3: Bij Gur --}}
                                <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3.5 shadow-2xs">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                        <span class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-700 text-white text-xs font-bold">3</span>
                                            <span class="text-xs font-bold text-slate-900">Card 3: Feature Highlight</span>
                                        </span>
                                        <span class="text-[10px] font-bold text-amber-800 uppercase bg-amber-50 px-2 py-0.5 rounded">Dynamic Product</span>
                                    </div>

                                    {{-- Product Selector --}}
                                    <div class="rounded-lg bg-amber-50/50 border border-amber-200/70 p-2.5 space-y-1">
                                        <label class="admin-label text-xs !mb-0 font-bold text-amber-950 flex items-center gap-1.5" for="gur_card_3_product_id">
                                            <span>📦 Connect Product (Auto-sync title, price, desc & image)</span>
                                        </label>
                                        <select id="gur_card_3_product_id" name="gur_card_3_product_id" class="field bg-white text-xs">
                                            <option value="">-- No Product Linked (Use Manual Defaults) --</option>
                                            @if (!empty($products))
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}" {{ ($settings['gur_card_3_product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                        {{ $p->name }} (৳{{ (int)$p->price }})
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <p class="text-[10px] text-amber-800/80">When a product is selected, title, price, description, link and image automatically sync from it. Use fields below only to override.</p>
                                    </div>

                                    {{-- Timeline Time (Manual) & Overrides --}}
                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="admin-label text-xs font-bold text-slate-800" for="gur_card_3_time">Timeline Time (Manual)</label>
                                            <input id="gur_card_3_time" name="gur_card_3_time" type="text" value="{{ old('gur_card_3_time', $settings['gur_card_3_time'] ?? 'সকাল ১০টা') }}" class="field text-xs bg-white" placeholder="সকাল ১০টা">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_3_title">Custom Title Override</label>
                                            <input id="gur_card_3_title" name="gur_card_3_title" type="text" value="{{ old('gur_card_3_title', $settings['gur_card_3_title'] ?? '') }}" class="field text-xs" placeholder="Leave blank to use product name">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_3_price">Custom Price Override</label>
                                            <input id="gur_card_3_price" name="gur_card_3_price" type="text" value="{{ old('gur_card_3_price', $settings['gur_card_3_price'] ?? '') }}" class="field text-xs" placeholder="Leave blank to use product price">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="admin-label text-xs" for="gur_card_3_desc">Custom Description / Tagline Override</label>
                                        <input id="gur_card_3_desc" name="gur_card_3_desc" type="text" value="{{ old('gur_card_3_desc', $settings['gur_card_3_desc'] ?? '') }}" class="field text-xs" placeholder="Leave blank to use product short description">
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_3_btn_text">Action Link Text Override</label>
                                            <input id="gur_card_3_btn_text" name="gur_card_3_btn_text" type="text" value="{{ old('gur_card_3_btn_text', $settings['gur_card_3_btn_text'] ?? '') }}" class="field text-xs" placeholder="Auto if blank">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_3_btn_url">Action Link URL Override</label>
                                            <input id="gur_card_3_btn_url" name="gur_card_3_btn_url" type="text" value="{{ old('gur_card_3_btn_url', $settings['gur_card_3_btn_url'] ?? '') }}" class="field text-xs" placeholder="Auto product URL if blank">
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="admin-label text-xs !mb-0 font-semibold" for="gur_card_3_image">Custom Product Image Override (Optional)</label>
                                            <span class="rounded bg-emerald-50 text-emerald-800 border border-emerald-200/80 px-2 py-0.5 text-[10px] font-bold">
                                                Recommended: 600 × 600 px (1:1 PNG Transparent)
                                            </span>
                                        </div>
                                        @include('admin.partials.dropzone', [
                                            'name' => 'gur_card_3_image',
                                            'id' => 'gur_card_3_image',
                                            'current' => !empty($settings['gur_card_3_image']) && (file_exists(public_path('storage/'.$settings['gur_card_3_image'])) || file_exists(public_path($settings['gur_card_3_image']))) ? (file_exists(public_path('storage/'.$settings['gur_card_3_image'])) ? asset('storage/'.$settings['gur_card_3_image']) : asset($settings['gur_card_3_image'])) : null,
                                            'hint' => 'Upload transparent PNG, WEBP or JPG · Recommended: 600×600 px · Max 16 MB',
                                            'accept' => 'image/png,image/webp,image/jpeg,image/jpg',
                                            'maxHeight' => 'h-32',
                                            'removeName' => 'remove_gur_card_3_image',
                                        ])
                                    </div>
                                </div>

                                {{-- CARD 4: Patali Gur --}}
                                <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3.5 shadow-2xs">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                        <span class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-800 text-white text-xs font-bold">4</span>
                                            <span class="text-xs font-bold text-slate-900">Card 4: Patali Gur (পাটালি)</span>
                                        </span>
                                        <span class="text-[10px] font-bold text-amber-800 uppercase bg-amber-50 px-2 py-0.5 rounded">Dynamic Product</span>
                                    </div>

                                    {{-- Product Selector --}}
                                    <div class="rounded-lg bg-amber-50/50 border border-amber-200/70 p-2.5 space-y-1">
                                        <label class="admin-label text-xs !mb-0 font-bold text-amber-950 flex items-center gap-1.5" for="gur_card_4_product_id">
                                            <span>📦 Connect Product (Auto-sync title, price, desc & image)</span>
                                        </label>
                                        <select id="gur_card_4_product_id" name="gur_card_4_product_id" class="field bg-white text-xs">
                                            <option value="">-- No Product Linked (Use Manual Defaults) --</option>
                                            @if (!empty($products))
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}" {{ ($settings['gur_card_4_product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                        {{ $p->name }} (৳{{ (int)$p->price }})
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <p class="text-[10px] text-amber-800/80">When a product is selected, title, price, description, link and image automatically sync from it. Use fields below only to override.</p>
                                    </div>

                                    {{-- Timeline Time (Manual) & Overrides --}}
                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="admin-label text-xs font-bold text-slate-800" for="gur_card_4_time">Timeline Time (Manual)</label>
                                            <input id="gur_card_4_time" name="gur_card_4_time" type="text" value="{{ old('gur_card_4_time', $settings['gur_card_4_time'] ?? 'দুপুর ১২টা') }}" class="field text-xs bg-white" placeholder="দুপুর ১২টা">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_4_title">Custom Title Override</label>
                                            <input id="gur_card_4_title" name="gur_card_4_title" type="text" value="{{ old('gur_card_4_title', $settings['gur_card_4_title'] ?? '') }}" class="field text-xs" placeholder="Leave blank to use product name">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_4_price">Custom Price Override</label>
                                            <input id="gur_card_4_price" name="gur_card_4_price" type="text" value="{{ old('gur_card_4_price', $settings['gur_card_4_price'] ?? '') }}" class="field text-xs" placeholder="Leave blank to use product price">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="admin-label text-xs" for="gur_card_4_desc">Custom Description / Tagline Override</label>
                                        <input id="gur_card_4_desc" name="gur_card_4_desc" type="text" value="{{ old('gur_card_4_desc', $settings['gur_card_4_desc'] ?? '') }}" class="field text-xs" placeholder="Leave blank to use product short description">
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_4_btn_text">Action Link Text Override</label>
                                            <input id="gur_card_4_btn_text" name="gur_card_4_btn_text" type="text" value="{{ old('gur_card_4_btn_text', $settings['gur_card_4_btn_text'] ?? '') }}" class="field text-xs" placeholder="Auto if blank">
                                        </div>
                                        <div>
                                            <label class="admin-label text-xs" for="gur_card_4_btn_url">Action Link URL Override</label>
                                            <input id="gur_card_4_btn_url" name="gur_card_4_btn_url" type="text" value="{{ old('gur_card_4_btn_url', $settings['gur_card_4_btn_url'] ?? '') }}" class="field text-xs" placeholder="Auto product URL if blank">
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="admin-label text-xs !mb-0 font-semibold" for="gur_card_4_image">Custom Product Image Override (Optional)</label>
                                            <span class="rounded bg-emerald-50 text-emerald-800 border border-emerald-200/80 px-2 py-0.5 text-[10px] font-bold">
                                                Recommended: 600 × 600 px (1:1 PNG Transparent)
                                            </span>
                                        </div>
                                        @include('admin.partials.dropzone', [
                                            'name' => 'gur_card_4_image',
                                            'id' => 'gur_card_4_image',
                                            'current' => !empty($settings['gur_card_4_image']) && (file_exists(public_path('storage/'.$settings['gur_card_4_image'])) || file_exists(public_path($settings['gur_card_4_image']))) ? (file_exists(public_path('storage/'.$settings['gur_card_4_image'])) ? asset('storage/'.$settings['gur_card_4_image']) : asset($settings['gur_card_4_image'])) : null,
                                            'hint' => 'Upload transparent PNG, WEBP or JPG · Recommended: 600×600 px · Max 16 MB',
                                            'accept' => 'image/png,image/webp,image/jpeg,image/jpg',
                                            'maxHeight' => 'h-32',
                                            'removeName' => 'remove_gur_card_4_image',
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="home-dynamic-sections-card" data-section-card="dynamic_sections">
                {{-- Dynamic Product Showcase Sections Builder --}}
            <div class="admin-card border-2 border-emerald-600/30 bg-white">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-emerald-50/50 via-white to-white">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-[#013F1E] text-white">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Dynamic Product Showcase Sections Builder</h3>
                            <span class="rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2 py-0.5">New</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Create homepage product sections. Use Custom Order to choose which products show, and in which order.</p>
                    </div>

                    <button
                        type="button"
                        id="btn-add-new-section"
                        class="admin-btn-primary !px-3.5 !py-2 text-xs font-bold inline-flex items-center gap-1.5 shadow-xs shrink-0 cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>+ Add New Section</span>
                    </button>
                </div>

                <div class="admin-card-body p-4 sm:p-5 space-y-4">
                    {{-- Hidden JSON storage input --}}
                    <input
                        type="hidden"
                        name="home_product_sections"
                        id="home_product_sections_input"
                        value="{{ old('home_product_sections', $settings['home_product_sections'] ?? '') }}"
                    >

                    {{-- Dynamic Sections Render Target --}}
                    <div id="dynamic-sections-container" class="space-y-4">
                        {{-- Injected and managed via JavaScript --}}
                    </div>
                </div>
            </div>
            </div>

            

            <div id="home-featured-card" data-section-card="featured">
                {{-- Featured Products Section --}}
            <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Trending / Featured Products Section</h3>
                        <p class="text-[11px] text-slate-500">Show/hide, rename and configure responsive grid columns & image ratio.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="home_show_featured" value="0">
                        <input type="checkbox" name="home_show_featured" value="1" {{ ($settings['home_show_featured'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-4">
                    <div>
                        <label class="admin-label" for="featured_section_title">Section Title</label>
                        <input id="featured_section_title" name="featured_section_title" type="text" value="{{ old('featured_section_title', $settings['featured_section_title'] ?? 'Trending Collection') }}" class="field">
                    </div>

                    {{-- Product Grid Columns & Image Ratio Settings --}}
                    <div class="rounded-xl border border-ink/10 bg-mist/30 p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-ink uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-sage" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                <span>Homepage Products Responsive Grid & Columns</span>
                            </span>
                            <span class="text-[11px] text-muted">Customize Mobile, Tablet & Desktop view</span>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                            <div>
                                <label class="admin-label text-xs" for="home_product_limit">
                                    Product Limit
                                </label>
                                <select id="home_product_limit" name="home_product_limit" class="field bg-white text-xs">
                                    <option value="4" {{ ($settings['home_product_limit'] ?? '8') === '4' ? 'selected' : '' }}>4 Products</option>
                                    <option value="8" {{ ($settings['home_product_limit'] ?? '8') === '8' ? 'selected' : '' }}>8 Products (Default)</option>
                                    <option value="12" {{ ($settings['home_product_limit'] ?? '8') === '12' ? 'selected' : '' }}>12 Products</option>
                                    <option value="16" {{ ($settings['home_product_limit'] ?? '8') === '16' ? 'selected' : '' }}>16 Products</option>
                                    <option value="20" {{ ($settings['home_product_limit'] ?? '8') === '20' ? 'selected' : '' }}>20 Products</option>
                                    <option value="24" {{ ($settings['home_product_limit'] ?? '8') === '24' ? 'selected' : '' }}>24 Products</option>
                                    <option value="32" {{ ($settings['home_product_limit'] ?? '8') === '32' ? 'selected' : '' }}>32 Products</option>
                                </select>
                            </div>

                            <div>
                                <label class="admin-label text-xs" for="product_cols_mobile">
                                    📱 Mobile Columns
                                </label>
                                <select id="product_cols_mobile" name="product_cols_mobile" class="field bg-white text-xs">
                                    <option value="1" {{ ($settings['product_cols_mobile'] ?? '2') === '1' ? 'selected' : '' }}>1 Column (Big Card)</option>
                                    <option value="2" {{ ($settings['product_cols_mobile'] ?? '2') === '2' ? 'selected' : '' }}>2 Columns (Standard)</option>
                                </select>
                            </div>

                            <div>
                                <label class="admin-label text-xs" for="product_cols_tablet">
                                    💻 Tablet Columns
                                </label>
                                <select id="product_cols_tablet" name="product_cols_tablet" class="field bg-white text-xs">
                                    <option value="2" {{ ($settings['product_cols_tablet'] ?? '3') === '2' ? 'selected' : '' }}>2 Columns</option>
                                    <option value="3" {{ ($settings['product_cols_tablet'] ?? '3') === '3' ? 'selected' : '' }}>3 Columns (Default)</option>
                                    <option value="4" {{ ($settings['product_cols_tablet'] ?? '3') === '4' ? 'selected' : '' }}>4 Columns</option>
                                </select>
                            </div>

                            <div>
                                <label class="admin-label text-xs" for="product_cols_desktop">
                                    🖥️ Desktop Columns
                                </label>
                                <select id="product_cols_desktop" name="product_cols_desktop" class="field bg-white text-xs">
                                    <option value="2" {{ ($settings['product_cols_desktop'] ?? '4') === '2' ? 'selected' : '' }}>2 Columns</option>
                                    <option value="3" {{ ($settings['product_cols_desktop'] ?? '4') === '3' ? 'selected' : '' }}>3 Columns</option>
                                    <option value="4" {{ ($settings['product_cols_desktop'] ?? '4') === '4' ? 'selected' : '' }}>4 Columns (Default)</option>
                                    <option value="5" {{ ($settings['product_cols_desktop'] ?? '4') === '5' ? 'selected' : '' }}>5 Columns</option>
                                    <option value="6" {{ ($settings['product_cols_desktop'] ?? '4') === '6' ? 'selected' : '' }}>6 Columns</option>
                                </select>
                            </div>

                            <div>
                                <label class="admin-label text-xs" for="product_card_aspect">
                                    🖼️ Image Ratio
                                </label>
                                <select id="product_card_aspect" name="product_card_aspect" class="field bg-white text-xs">
                                    <option value="3/4" {{ ($settings['product_card_aspect'] ?? '3/4') === '3/4' ? 'selected' : '' }}>3:4 (Portrait Fashion)</option>
                                    <option value="1/1" {{ ($settings['product_card_aspect'] ?? '3/4') === '1/1' ? 'selected' : '' }}>1:1 (Square Standard)</option>
                                    <option value="4/5" {{ ($settings['product_card_aspect'] ?? '3/4') === '4/5' ? 'selected' : '' }}>4:5 (Tall Portrait)</option>
                                    <option value="auto" {{ ($settings['product_card_aspect'] ?? '3/4') === 'auto' ? 'selected' : '' }}>Auto (Natural Ratio)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            

            <div id="home-reel-card" data-section-card="story_reel">
                {{-- Our Story & Video Reel Section --}}
            <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <span>Our Story & Video Reel</span>
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Featured Reel</span>
                        </h3>
                        <p class="text-[11px] text-slate-500">Configure our story narrative, 3 process steps, and upload video or YouTube link for the vertical reel card.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="home_show_story_section" value="0">
                        <input type="checkbox" name="home_show_story_section" value="1" {{ ($settings['home_show_story_section'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-5">
                    {{-- Section Narrative Header --}}
                    <div>
                        <label class="admin-label" for="story_section_title">Section Title</label>
                        <input id="story_section_title" name="story_section_title" type="text" value="{{ old('story_section_title', $settings['story_section_title'] ?? 'Tradition to Purity') }}" class="field" placeholder="Tradition to Purity">
                    </div>

                    <div>
                        <label class="admin-label" for="story_section_desc">Story Narrative / Description</label>
                        <textarea id="story_section_desc" name="story_section_desc" rows="2" class="field" placeholder="Starting from traditional harvesting to careful preparation...">{{ old('story_section_desc', $settings['story_section_desc'] ?? 'Starting from traditional harvesting to careful preparation, we preserve centuries-old heritage. 100% pure with no artificial colors or additives.') }}</textarea>
                    </div>

                    {{-- 3 Process Steps --}}
                    <div class="rounded-xl border border-ink/10 bg-mist/20 p-4 space-y-3">
                        <span class="text-xs font-bold text-ink uppercase tracking-wider flex items-center gap-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">123</span>
                            <span>3 Process Steps</span>
                        </span>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-2">
                                <span class="text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded">Step 1</span>
                                <div>
                                    <label class="admin-label text-[11px]" for="story_step_1_title">Title</label>
                                    <input id="story_step_1_title" name="story_step_1_title" type="text" value="{{ old('story_step_1_title', $settings['story_step_1_title'] ?? 'Fresh Harvesting') }}" class="field text-xs" placeholder="Fresh Harvesting">
                                </div>
                                <div>
                                    <label class="admin-label text-[11px]" for="story_step_1_desc">Description</label>
                                    <textarea id="story_step_1_desc" name="story_step_1_desc" rows="2" class="field text-xs" placeholder="Collected fresh daily before sunrise.">{{ old('story_step_1_desc', $settings['story_step_1_desc'] ?? 'Collected fresh daily before sunrise.') }}</textarea>
                                </div>
                            </div>

                            <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-2">
                                <span class="text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded">Step 2</span>
                                <div>
                                    <label class="admin-label text-[11px]" for="story_step_2_title">Title</label>
                                    <input id="story_step_2_title" name="story_step_2_title" type="text" value="{{ old('story_step_2_title', $settings['story_step_2_title'] ?? 'Traditional Cooking') }}" class="field text-xs" placeholder="Traditional Cooking">
                                </div>
                                <div>
                                    <label class="admin-label text-[11px]" for="story_step_2_desc">Description</label>
                                    <textarea id="story_step_2_desc" name="story_step_2_desc" rows="2" class="field text-xs" placeholder="Carefully cooked slowly over firewood.">{{ old('story_step_2_desc', $settings['story_step_2_desc'] ?? 'Carefully cooked slowly over firewood.') }}</textarea>
                                </div>
                            </div>

                            <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-2">
                                <span class="text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded">Step 3</span>
                                <div>
                                    <label class="admin-label text-[11px]" for="story_step_3_title">Title</label>
                                    <input id="story_step_3_title" name="story_step_3_title" type="text" value="{{ old('story_step_3_title', $settings['story_step_3_title'] ?? 'Pure Final Product') }}" class="field text-xs" placeholder="Pure Final Product">
                                </div>
                                <div>
                                    <label class="admin-label text-[11px]" for="story_step_3_desc">Description</label>
                                    <textarea id="story_step_3_desc" name="story_step_3_desc" rows="2" class="field text-xs" placeholder="Natural quality without chemical additives.">{{ old('story_step_3_desc', $settings['story_step_3_desc'] ?? 'Natural quality without chemical additives.') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 pt-1">
                            <div>
                                <label class="admin-label text-xs" for="story_btn_text">CTA Button Label</label>
                                <input id="story_btn_text" name="story_btn_text" type="text" value="{{ old('story_btn_text', $settings['story_btn_text'] ?? 'Learn More About Us') }}" class="field text-xs" placeholder="Learn More About Us">
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="story_btn_url">CTA Button URL</label>
                                <input id="story_btn_url" name="story_btn_url" type="text" value="{{ old('story_btn_url', $settings['story_btn_url'] ?? '') }}" class="field text-xs" placeholder="/shop">
                            </div>
                        </div>
                    </div>

                    {{-- Story Video Reel Media Box --}}
                    <div class="rounded-2xl border-2 border-emerald-600/30 bg-emerald-50/30 p-4 sm:p-5 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-emerald-600/15 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-700 text-white text-sm shadow-xs">🎬</span>
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-950">Story photo</h4>
                                    <p class="text-[11px] text-emerald-800/80">Upload one image. It is shown once beside the story. Video is optional.</p>
                                </div>
                            </div>
                            <span class="rounded bg-emerald-700 text-white text-[10px] font-bold px-2 py-0.5 w-fit">Shown once</span>
                        </div>

                        <div class="rounded-xl border border-ink/15 bg-white p-3.5 space-y-2 shadow-2xs">
                            <input type="hidden" name="auto_story_reel_image_data" id="auto_story_reel_image_data" value="">
                            <div class="flex items-center justify-between">
                                <label class="admin-label !mb-0 font-semibold" for="story_reel_image">Story image</label>
                                <span class="rounded bg-emerald-100 text-emerald-800 px-2 py-0.5 text-[10px] font-bold">One image · 4:5</span>
                            </div>
                            @include('admin.partials.dropzone', [
                                'name' => 'story_reel_image',
                                'id' => 'story_reel_image',
                                'accept' => 'image/*',
                                'isVideo' => false,
                                'current' => !empty($settings['story_reel_image']) && file_exists(public_path('storage/'.$settings['story_reel_image'])) ? asset('storage/'.$settings['story_reel_image']) : null,
                                'hint' => 'One photo is enough · about 1200 × 1500 px · JPG, PNG, WEBP (Max 8 MB)',
                                'maxHeight' => 'h-40',
                                'removeName' => 'remove_story_reel_image',
                            ])
                            <div id="auto-reel-thumb-badge" class="hidden mt-1.5 inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Video poster frame captured automatically</span>
                            </div>
                        </div>

                        <details class="rounded-xl border border-slate-200 bg-white p-3.5">
                            <summary class="cursor-pointer text-xs font-bold text-slate-700">Optional video</summary>
                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="admin-label" for="story_reel_video">Video file</label>
                                    @include('admin.partials.dropzone', [
                                        'name' => 'story_reel_video',
                                        'id' => 'story_reel_video',
                                        'accept' => 'video/mp4,video/webm,video/quicktime,video/x-matroska',
                                        'isVideo' => true,
                                        'current' => !empty($settings['story_reel_video']) && file_exists(public_path('storage/'.$settings['story_reel_video'])) ? asset('storage/'.$settings['story_reel_video']) : null,
                                        'hint' => 'Optional · MP4 or WebM (Max 100 MB). If set, this plays instead of the photo.',
                                        'maxHeight' => 'h-40',
                                        'removeName' => 'remove_story_reel_video',
                                    ])
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="story_reel_video_url">Or a YouTube / video link</label>
                                    <input
                                        id="story_reel_video_url"
                                        name="story_reel_video_url"
                                        type="url"
                                        value="{{ old('story_reel_video_url', $settings['story_reel_video_url'] ?? '') }}"
                                        class="field text-xs"
                                        placeholder="https://www.youtube.com/watch?v=..."
                                    >
                                </div>
                            </div>
                        </details>

                        {{-- Reel Card Text Overlays --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-3.5 space-y-3">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Video Reel Card Overlays
                            </span>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="admin-label text-xs" for="story_reel_badge">Top Badge</label>
                                    <input id="story_reel_badge" name="story_reel_badge" type="text" value="{{ old('story_reel_badge', $settings['story_reel_badge'] ?? 'Story Reel') }}" class="field text-xs" placeholder="Story Reel">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="story_reel_tag">Bottom Tag</label>
                                    <input id="story_reel_tag" name="story_reel_tag" type="text" value="{{ old('story_reel_tag', $settings['story_reel_tag'] ?? 'Behind The Scenes') }}" class="field text-xs" placeholder="Behind The Scenes">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="story_reel_title">Card Main Title</label>
                                    <input id="story_reel_title" name="story_reel_title" type="text" value="{{ old('story_reel_title', $settings['story_reel_title'] ?? 'Artisan Craft & Dedication') }}" class="field text-xs" placeholder="Artisan Craft & Dedication">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="story_reel_subtitle">Card Subtitle</label>
                                    <input id="story_reel_subtitle" name="story_reel_subtitle" type="text" value="{{ old('story_reel_subtitle', $settings['story_reel_subtitle'] ?? 'Centuries-old heritage crafted with natural care.') }}" class="field text-xs" placeholder="Centuries-old heritage crafted with natural care.">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            

            <div id="home-promises-card" data-section-card="promises">
                <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Trust & Commitment Cards (4 Values)</h3>
                        <p class="text-[11px] text-slate-500">Reinforce customer confidence with 4 customizable promise cards.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="home_show_promises" value="0">
                        <input type="checkbox" name="home_show_promises" value="1" {{ ($settings['home_show_promises'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-4">
                    <div>
                        <label class="admin-label" for="promises_title">Section Title</label>
                        <input id="promises_title" name="promises_title" type="text" value="{{ old('promises_title', $settings['promises_title'] ?? 'What Matters Most To Us') }}" class="field">
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 pt-1">
                        {{-- Card 1 --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5 space-y-2.5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded bg-emerald-100 text-[10px] font-bold text-emerald-800">01</span>
                                <span class="text-xs font-semibold text-slate-800">Card 1 (Fabric Quality)</span>
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="promise_1_title">Title</label>
                                <input id="promise_1_title" name="promise_1_title" type="text" value="{{ old('promise_1_title', $settings['promise_1_title'] ?? '100% Premium Fabric') }}" class="field bg-white">
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="promise_1_desc">Description</label>
                                <textarea id="promise_1_desc" name="promise_1_desc" rows="2" class="field bg-white">{{ old('promise_1_desc', $settings['promise_1_desc'] ?? 'Authentic fabrics with long-lasting color retention and supreme everyday comfort.') }}</textarea>
                            </div>
                        </div>

                        {{-- Card 2 --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5 space-y-2.5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded bg-sky-100 text-[10px] font-bold text-sky-800">02</span>
                                <span class="text-xs font-semibold text-slate-800">Card 2 (Tailoring & Fit)</span>
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="promise_2_title">Title</label>
                                <input id="promise_2_title" name="promise_2_title" type="text" value="{{ old('promise_2_title', $settings['promise_2_title'] ?? 'Precision Master Tailoring') }}" class="field bg-white">
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="promise_2_desc">Description</label>
                                <textarea id="promise_2_desc" name="promise_2_desc" rows="2" class="field bg-white">{{ old('promise_2_desc', $settings['promise_2_desc'] ?? 'Modern patterns and precise stitching tailored for a confident and sharp appearance.') }}</textarea>
                            </div>
                        </div>

                        {{-- Card 3 --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5 space-y-2.5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded bg-amber-100 text-[10px] font-bold text-amber-800">03</span>
                                <span class="text-xs font-semibold text-slate-800">Card 3 (Hassle-free Exchange)</span>
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="promise_3_title">Title</label>
                                <input id="promise_3_title" name="promise_3_title" type="text" value="{{ old('promise_3_title', $settings['promise_3_title'] ?? '7-Day Easy Exchange') }}" class="field bg-white">
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="promise_3_desc">Description</label>
                                <textarea id="promise_3_desc" name="promise_3_desc" rows="2" class="field bg-white">{{ old('promise_3_desc', $settings['promise_3_desc'] ?? 'Size or fitting issue? Exchange without hassle within 7 days of receiving your order.') }}</textarea>
                            </div>
                        </div>

                        {{-- Card 4 --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5 space-y-2.5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded bg-purple-100 text-[10px] font-bold text-purple-800">04</span>
                                <span class="text-xs font-semibold text-slate-800">Card 4 (Fast Delivery)</span>
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="promise_4_title">Title</label>
                                <input id="promise_4_title" name="promise_4_title" type="text" value="{{ old('promise_4_title', $settings['promise_4_title'] ?? 'Reliable Fast Delivery') }}" class="field bg-white">
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="promise_4_desc">Description</label>
                                <textarea id="promise_4_desc" name="promise_4_desc" rows="2" class="field bg-white">{{ old('promise_4_desc', $settings['promise_4_desc'] ?? 'Express doorstep delivery nationwide with verified courier tracking partners.') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            

            <div id="home-faq-card" data-section-card="faq">
                <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Frequently Asked Questions (FAQ)</h3>
                        <p class="text-[11px] text-slate-500">Interactive customer FAQ pairs displayed on the storefront.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="home_show_faq" value="0">
                        <input type="checkbox" name="home_show_faq" value="1" {{ ($settings['home_show_faq'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-3.5">
                    <div>
                        <label class="admin-label" for="faq_title">Section Title</label>
                        <input id="faq_title" name="faq_title" type="text" value="{{ old('faq_title', $settings['faq_title'] ?? 'Frequently Asked Questions') }}" class="field">
                    </div>

                    {{-- Customer Care & Chat Avatar Dropzone --}}
                    @php
                        $currentCustomAvatar = !empty($settings['chat_avatar']) && file_exists(public_path('storage/'.$settings['chat_avatar']))
                            ? asset('storage/'.$settings['chat_avatar'])
                            : null;
                        $currentFavicon = !empty($settings['site_favicon']) && file_exists(public_path('storage/'.$settings['site_favicon']))
                            ? asset('storage/'.$settings['site_favicon'])
                            : null;
                    @endphp
                    <div class="rounded-xl border border-ink/15 bg-slate-50/60 p-3.5 space-y-2 shadow-2xs">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 border-b border-ink/10 pb-2.5">
                            <div>
                                <label class="admin-label !mb-0 font-semibold text-xs flex items-center gap-1.5" for="chat_avatar">
                                    <span>💬 Support & Chat Avatar</span>
                                </label>
                                <p class="text-[11px] text-slate-500">Custom 1:1 square image for FAQ customer care thread &amp; AI chat. If left empty, your store Favicon is automatically used by default.</p>
                            </div>
                            <div class="shrink-0 flex items-center gap-1.5">
                                @if ($currentCustomAvatar)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 text-[10px] font-bold">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Custom Avatar Active
                                    </span>
                                @elseif ($currentFavicon)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 text-sky-800 border border-sky-300 px-2.5 py-0.5 text-[10px] font-bold">
                                        <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                        Default: Store Favicon
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-800 border border-amber-300 px-2.5 py-0.5 text-[10px] font-bold">
                                        Default: Fallback Icon
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-12 items-center pt-1">
                            <div class="sm:col-span-8">
                                @include('admin.partials.dropzone', [
                                    'name' => 'chat_avatar',
                                    'id' => 'chat_avatar',
                                    'current' => $currentCustomAvatar,
                                    'removeName' => 'remove_chat_avatar',
                                    'hint' => 'Upload 1:1 square image/icon (PNG, JPG, WEBP) · Leave empty to use Store Favicon',
                                    'maxHeight' => 'h-28',
                                ])
                            </div>
                            <div class="sm:col-span-4 flex flex-col items-center justify-center p-3 rounded-lg border border-slate-200 bg-white shadow-2xs space-y-1.5 text-center">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Live Avatar Preview</span>
                                <div class="relative flex h-12 w-12 items-center justify-center rounded-full overflow-hidden bg-white p-1 border-2 border-emerald-600 shadow-sm">
                                    <img src="{{ $currentCustomAvatar ?? $currentFavicon ?? asset('favicon.ico') }}" alt="Avatar Preview" class="h-full w-full rounded-full object-cover {{ empty($currentCustomAvatar) && empty($currentFavicon) ? 'hidden' : '' }}">
                                    <div class="flex h-full w-full items-center justify-center rounded-full bg-[#013F1E] text-white text-xs font-bold {{ !empty($currentCustomAvatar) || !empty($currentFavicon) ? 'hidden' : '' }}">
                                        💬
                                    </div>
                                    <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-700 leading-tight">
                                    {{ $currentCustomAvatar ? 'Custom Uploaded' : ($currentFavicon ? 'Favicon in Use' : 'Default Icon') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">WhatsApp Question Input Bar</span>
                                <span class="text-[10px] text-slate-500">Allow customers to send custom questions directly via WhatsApp</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="faq_show_whatsapp" value="0">
                                <input type="checkbox" name="faq_show_whatsapp" value="1" {{ ($settings['faq_show_whatsapp'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="admin-label text-xs" for="faq_whatsapp_prefix">WhatsApp Prefilled Message Prefix</label>
                                <input id="faq_whatsapp_prefix" name="faq_whatsapp_prefix" type="text" value="{{ old('faq_whatsapp_prefix', $settings['faq_whatsapp_prefix'] ?? 'আমি Foodness ওয়েবসাইট থেকে জানতে চাইছি:') }}" class="field text-xs bg-white" placeholder="আমি Foodness ওয়েবসাইট থেকে জানতে চাইছি:">
                            </div>
                            <div>
                                <label class="admin-label text-xs" for="faq_whatsapp_placeholder">Input Box Placeholder</label>
                                <input id="faq_whatsapp_placeholder" name="faq_whatsapp_placeholder" type="text" value="{{ old('faq_whatsapp_placeholder', $settings['faq_whatsapp_placeholder'] ?? 'অন্য কোনো প্রশ্ন আছে? হোয়াটসঅ্যাপে মেসেজ দিন...') }}" class="field text-xs bg-white" placeholder="অন্য কোনো প্রশ্ন আছে? হোয়াটসঅ্যাপে মেসেজ দিন...">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 pt-1">
                        @php
                            $defaultFaqs = [
                                1 => ['q' => 'How long does delivery take?', 'a' => 'Inside Dhaka within 1-2 days, and outside Dhaka within 2-3 business days. You will receive real-time courier tracking.'],
                                2 => ['q' => 'How do I pay for my order?', 'a' => 'All orders are 100% Cash on Delivery (COD). No advance payment needed. Pay in cash after receiving your package.'],
                                3 => ['q' => 'What if the size or fit does not match?', 'a' => 'We offer an easy 7-day exchange window. Contact our support team and we will exchange the size hassle-free.'],
                                4 => ['q' => 'Can I inspect the parcel upon delivery?', 'a' => 'Yes! You can check the quality and size in front of the courier agent before finalizing cash payment.'],
                                5 => ['q' => 'What fabric quality do you use?', 'a' => 'Foodness uses 100% combed cotton and breathable fabrics designed for long-lasting color and soft texture.'],
                                6 => ['q' => 'Can I place bulk or custom orders?', 'a' => 'Yes, for wholesale or corporate requirements, call our hotline directly or message us on WhatsApp.'],
                            ];
                        @endphp

                        @for ($i = 1; $i <= 6; $i++)
                            <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Question {{ $i }}</span>
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="faq_q{{ $i }}">Question {{ $i }} (Customer Query)</label>
                                    <input id="faq_q{{ $i }}" name="faq_q{{ $i }}" type="text" value="{{ old("faq_q{$i}", $settings["faq_q{$i}"] ?? $defaultFaqs[$i]['q']) }}" class="field bg-white">
                                </div>
                                <div>
                                    <label class="admin-label text-xs" for="faq_a{{ $i }}">Answer {{ $i }} (Response)</label>
                                    <textarea id="faq_a{{ $i }}" name="faq_a{{ $i }}" rows="2" class="field bg-white">{{ old("faq_a{$i}", $settings["faq_a{$i}"] ?? $defaultFaqs[$i]['a']) }}</textarea>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
            </div>

            

            </div>
        </div>

{{-- TAB 3: Footer & Bottom Bar --}}
        <div id="tab-footer" class="tab-content space-y-3 hidden">
            {{-- Footer Sections Sub-Navigation Jump Bar --}}
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100/90 rounded-xl px-2.5 border border-slate-200">
                <span class="text-slate-400 uppercase text-[10px] tracking-wider shrink-0 font-bold px-1">FOOTER:</span>
                <a href="#footer-brand-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🏢 Branding & Social</a>
                <a href="#footer-columns-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">📑 Columns & Info</a>
                <a href="#footer-partners-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🚚 Delivery Partners</a>
                <a href="#footer-copyright-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">©️ Copyright Note</a>
                <a href="#mobile-bottom-nav-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold shadow-2xs shrink-0 transition">📱 Mobile Bottom Dock</a>
            </div>

            {{-- Brand About, COD Badge & Social Links --}}
            <div class="admin-card" id="footer-brand-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-2.5 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Footer About & Social Media</h3>
                        <p class="text-[11px] text-slate-500">Configure footer branding, cash on delivery badge, and social media profile links.</p>
                    </div>
                </div>
                <div class="admin-card-body p-3.5 space-y-2.5">
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        <div>
                            <label class="admin-label text-xs" for="footer_about_text">About Store Description</label>
                            <textarea id="footer_about_text" name="footer_about_text" rows="2" class="field text-xs">{{ old('footer_about_text', $settings['footer_about_text'] ?? 'Foodness brings you premium quality modern fashion wear with 100% Cash on Delivery and complete shopping satisfaction.') }}</textarea>
                        </div>
                        <div>
                            <label class="admin-label text-xs" for="footer_cod_badge">Cash on Delivery Badge Text</label>
                            <input id="footer_cod_badge" name="footer_cod_badge" type="text" value="{{ old('footer_cod_badge', $settings['footer_cod_badge'] ?? '100% Cash on Delivery Nationwide') }}" class="field text-xs" placeholder="100% Cash on Delivery Nationwide">
                            <p class="text-[10px] text-slate-500 mt-1">Displayed in column 1 below the brand description with a green verified checkmark.</p>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-[11px] font-bold text-slate-800 uppercase tracking-wider block mb-1.5">Social Media Profiles</span>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="admin-label text-[10px]" for="social_facebook">Facebook Page URL</label>
                                <input id="social_facebook" name="social_facebook" type="url" value="{{ old('social_facebook', $settings['social_facebook'] ?? '') }}" placeholder="https://facebook.com/foodness" class="field text-xs !py-1">
                            </div>
                            <div>
                                <label class="admin-label text-[10px]" for="social_instagram">Instagram Profile URL</label>
                                <input id="social_instagram" name="social_instagram" type="url" value="{{ old('social_instagram', $settings['social_instagram'] ?? '') }}" placeholder="https://instagram.com/foodness" class="field text-xs !py-1">
                            </div>
                            <div>
                                <label class="admin-label text-[10px]" for="social_whatsapp">WhatsApp Direct Number</label>
                                <input id="social_whatsapp" name="social_whatsapp" type="text" value="{{ old('social_whatsapp', $settings['social_whatsapp'] ?? '') }}" placeholder="01700000000" class="field text-xs !py-1">
                            </div>
                            <div>
                                <label class="admin-label text-[10px]" for="social_youtube">YouTube Channel URL</label>
                                <input id="social_youtube" name="social_youtube" type="url" value="{{ old('social_youtube', $settings['social_youtube'] ?? '') }}" placeholder="https://youtube.com/@foodness" class="field text-xs !py-1">
                            </div>
                            <div>
                                <label class="admin-label text-[10px]" for="social_tiktok">TikTok Profile URL</label>
                                <input id="social_tiktok" name="social_tiktok" type="url" value="{{ old('social_tiktok', $settings['social_tiktok'] ?? '') }}" placeholder="https://tiktok.com/@foodness" class="field text-xs !py-1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Columns & Information --}}
            <div class="admin-card" id="footer-columns-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-2.5 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Footer Columns & Information Settings</h3>
                        <p class="text-[11px] text-slate-500">Customize column titles, category links, delivery notes, and payment info.</p>
                    </div>
                </div>
                <div class="admin-card-body p-3.5 space-y-2.5">
                    <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        {{-- Column 2 Settings --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5 space-y-2">
                            <span class="text-xs font-bold text-slate-800 block">Column 2 (Categories)</span>
                            <div>
                                <label class="admin-label text-[10px]" for="footer_col2_title">Column 2 Title</label>
                                <input id="footer_col2_title" name="footer_col2_title" type="text" value="{{ old('footer_col2_title', $settings['footer_col2_title'] ?? 'Popular Categories') }}" class="field text-xs !py-1" placeholder="Popular Categories">
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-xs text-slate-700">Show Category Links</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="footer_show_categories" value="0">
                                    <input type="checkbox" name="footer_show_categories" value="1" {{ ($settings['footer_show_categories'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>
                        </div>

                        {{-- Column 3 Settings --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5 space-y-2">
                            <span class="text-xs font-bold text-slate-800 block">Column 3 (Service & Delivery)</span>
                            <div>
                                <label class="admin-label text-[10px]" for="footer_col3_title">Column 3 Title</label>
                                <input id="footer_col3_title" name="footer_col3_title" type="text" value="{{ old('footer_col3_title', $settings['footer_col3_title'] ?? 'সহায়তা ও ডেলিভারি') }}" class="field text-xs !py-1" placeholder="সহায়তা ও ডেলিভারি">
                            </div>
                            <div>
                                <label class="admin-label text-[10px]" for="footer_delivery_info">Delivery Information Note</label>
                                <input id="footer_delivery_info" name="footer_delivery_info" type="text" value="{{ old('footer_delivery_info', $settings['footer_delivery_info'] ?? '1-2 days delivery in Dhaka, 2-3 days across Bangladesh.') }}" class="field text-xs !py-1" placeholder="1-2 days delivery in Dhaka, 2-3 days across Bangladesh.">
                            </div>
                            <div>
                                <label class="admin-label text-[10px]" for="footer_payment_info">Payment Methods Note</label>
                                <input id="footer_payment_info" name="footer_payment_info" type="text" value="{{ old('footer_payment_info', $settings['footer_payment_info'] ?? 'Cash on delivery, bKash & Nagad secure payments.') }}" class="field text-xs !py-1" placeholder="Cash on delivery, bKash & Nagad secure payments.">
                            </div>
                        </div>

                        {{-- Column 4 Settings --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5 space-y-2">
                            <span class="text-xs font-bold text-slate-800 block">Column 4 (Contact & Location)</span>
                            <div>
                                <label class="admin-label text-[10px]" for="footer_col4_title">Column 4 Title</label>
                                <input id="footer_col4_title" name="footer_col4_title" type="text" value="{{ old('footer_col4_title', $settings['footer_col4_title'] ?? 'Contact Us') }}" class="field text-xs !py-1" placeholder="Contact Us">
                            </div>
                            <p class="text-[10px] text-slate-500 leading-snug">
                                Phone, email, and showroom address in column 4 automatically sync from Header Settings (Phone: <strong>{{ $settings['store_phone'] ?? '01700000000' }}</strong>).
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Delivery Partners & Payment Badges --}}
            <div class="admin-card" id="footer-partners-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-2.5 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Delivery Partners & Payment Badges</h3>
                        <p class="text-[11px] text-slate-500">Courier and delivery tracking partners displayed above the copyright bar.</p>
                    </div>
                </div>
                <div class="admin-card-body p-3.5 space-y-2.5">
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        <div>
                            <label class="admin-label text-xs" for="footer_delivery_partners_title">Delivery Partners Title</label>
                            <input id="footer_delivery_partners_title" name="footer_delivery_partners_title" type="text" value="{{ old('footer_delivery_partners_title', $settings['footer_delivery_partners_title'] ?? 'Our Delivery Partners') }}" class="field text-xs !py-1" placeholder="Our Delivery Partners">
                        </div>
                        <div>
                            <label class="admin-label text-xs" for="footer_delivery_partners_text">Partner Names (comma-separated)</label>
                            <input id="footer_delivery_partners_text" name="footer_delivery_partners_text" type="text" value="{{ old('footer_delivery_partners_text', $settings['footer_delivery_partners_text'] ?? 'Steadfast, Pathao, RedX, eCourier, SA Paribahan, Sundarban') }}" class="field text-xs !py-1" placeholder="Steadfast, Pathao, RedX, eCourier">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Copyright & Bottom Note --}}
            <div class="admin-card" id="footer-copyright-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-2.5 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Copyright & Bottom Note</h3>
                        <p class="text-[11px] text-slate-500">Copyright brand name and bottom trust note.</p>
                    </div>
                </div>
                <div class="admin-card-body p-3.5 space-y-2.5">
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        <div>
                            <label class="admin-label text-xs" for="footer_copyright">Copyright Brand Name</label>
                            <input id="footer_copyright" name="footer_copyright" type="text" value="{{ old('footer_copyright', $settings['footer_copyright'] ?? 'Foodness') }}" class="field text-xs !py-1">
                        </div>
                        <div>
                            <label class="admin-label text-xs" for="footer_bottom_note">Footer Trust Note (Right Side)</label>
                            <input id="footer_bottom_note" name="footer_bottom_note" type="text" value="{{ old('footer_bottom_note', $settings['footer_bottom_note'] ?? '100% Cash on Delivery · Secure Online Shopping') }}" class="field text-xs !py-1" placeholder="100% Cash on Delivery · Secure Online Shopping">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mobile App-Style Bottom Navigation Bar (A-Z Customizer) --}}
            <div class="admin-card border-emerald-200 shadow-sm" id="mobile-bottom-nav-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-emerald-50/50 to-white">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-white text-sm shadow-xs">
                            📱
                        </span>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                                <span>Mobile App-Style Bottom Navigation Bar (A-Z Customizer)</span>
                                <span class="rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-1.5 py-0.5">Fixed Mobile Dock</span>
                            </h3>
                            <p class="text-[11px] text-slate-500">Fixed dock at the bottom of mobile screens. Fully customize colors, icons, actions (Cart, Search, Drawer, Link, WhatsApp, Call), and live cart counter.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="hidden" name="mobile_bottom_nav_enabled" value="0">
                        <input type="checkbox" name="mobile_bottom_nav_enabled" value="1" {{ ($settings['mobile_bottom_nav_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-semibold text-slate-800">Bar Enabled</span>
                    </label>
                </div>

                <div class="admin-card-body p-3.5 sm:p-4 space-y-3.5">
                    {{-- Legacy Inputs Hidden to Guarantee Backward Compatibility --}}
                    <input type="hidden" name="mobile_nav_home_label" id="legacy_mobile_nav_home_label" value="{{ old('mobile_nav_home_label', $settings['mobile_nav_home_label'] ?? 'Home') }}">
                    <input type="hidden" name="mobile_nav_shop_label" id="legacy_mobile_nav_shop_label" value="{{ old('mobile_nav_shop_label', $settings['mobile_nav_shop_label'] ?? 'Shop') }}">
                    <input type="hidden" name="mobile_nav_search_label" id="legacy_mobile_nav_search_label" value="{{ old('mobile_nav_search_label', $settings['mobile_nav_search_label'] ?? 'Search') }}">
                    <input type="hidden" name="mobile_nav_cart_label" id="legacy_mobile_nav_cart_label" value="{{ old('mobile_nav_cart_label', $settings['mobile_nav_cart_label'] ?? 'Cart') }}">

                    {{-- Styling & Appearance Controls --}}
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5 mb-1.5">
                            <span class="text-emerald-700 font-black">①</span> Styling & Visual Colors
                        </h4>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 bg-slate-50/80 p-2.5 rounded-lg border border-slate-200">
                            {{-- Show Labels Toggle --}}
                            <div class="flex items-center justify-between p-1.5 rounded-md bg-white border border-slate-200">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800 block leading-tight">Show Text Labels</span>
                                    <span class="text-[10px] text-slate-500">Titles below icons</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="mobile_bottom_nav_show_labels" value="0">
                                    <input type="checkbox" name="mobile_bottom_nav_show_labels" id="bottom_nav_show_labels_toggle" value="1" {{ ($settings['mobile_bottom_nav_show_labels'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>

                            {{-- Background Color --}}
                            <div>
                                <label class="admin-label text-[10px] !mb-0.5" for="mobile_bottom_nav_bg">Dock Background Color</label>
                                <div class="flex items-center gap-1.5">
                                    <input type="color" id="mobile_bottom_nav_bg_picker" value="{{ old('mobile_bottom_nav_bg', $settings['mobile_bottom_nav_bg'] ?? '#FFFFFF') }}" class="h-7 w-8 p-0 border border-slate-300 rounded cursor-pointer shrink-0">
                                    <input type="text" id="mobile_bottom_nav_bg" name="mobile_bottom_nav_bg" value="{{ old('mobile_bottom_nav_bg', $settings['mobile_bottom_nav_bg'] ?? '#FFFFFF') }}" class="field text-xs !py-1 uppercase" placeholder="#FFFFFF">
                                </div>
                            </div>

                            {{-- Active Item Color --}}
                            <div>
                                <label class="admin-label text-[10px] !mb-0.5" for="mobile_bottom_nav_active_color">Active Tab Color</label>
                                <div class="flex items-center gap-1.5">
                                    <input type="color" id="mobile_bottom_nav_active_color_picker" value="{{ old('mobile_bottom_nav_active_color', $settings['mobile_bottom_nav_active_color'] ?? '#013F1E') }}" class="h-7 w-8 p-0 border border-slate-300 rounded cursor-pointer shrink-0">
                                    <input type="text" id="mobile_bottom_nav_active_color" name="mobile_bottom_nav_active_color" value="{{ old('mobile_bottom_nav_active_color', $settings['mobile_bottom_nav_active_color'] ?? '#013F1E') }}" class="field text-xs !py-1 uppercase" placeholder="#013F1E">
                                </div>
                            </div>

                            {{-- Inactive Item Color --}}
                            <div>
                                <label class="admin-label text-[10px] !mb-0.5" for="mobile_bottom_nav_inactive_color">Inactive Tab Color</label>
                                <div class="flex items-center gap-1.5">
                                    <input type="color" id="mobile_bottom_nav_inactive_color_picker" value="{{ old('mobile_bottom_nav_inactive_color', $settings['mobile_bottom_nav_inactive_color'] ?? '#64748B') }}" class="h-7 w-8 p-0 border border-slate-300 rounded cursor-pointer shrink-0">
                                    <input type="text" id="mobile_bottom_nav_inactive_color" name="mobile_bottom_nav_inactive_color" value="{{ old('mobile_bottom_nav_inactive_color', $settings['mobile_bottom_nav_inactive_color'] ?? '#64748B') }}" class="field text-xs !py-1 uppercase" placeholder="#64748B">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Navigation Tabs Builder --}}
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-1.5 border-b border-slate-100 mb-2">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <span class="text-emerald-700 font-black">②</span> Bottom Navigation Tabs (Order & Actions)
                                </h4>
                                <p class="text-[11px] text-slate-500">Pick any action (Cart modal, Search modal, Drawer, Custom Link, WhatsApp), icon, and custom or dynamic badge.</p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button" id="btn_add_bottom_nav_tab" class="admin-btn-primary !px-2.5 !py-1 text-xs whitespace-nowrap inline-flex items-center gap-1 cursor-pointer">
                                    <span>+ Add Nav Tab</span>
                                </button>
                                <button type="button" id="btn_reset_bottom_nav_tabs" class="admin-btn-secondary !px-2 !py-1 text-xs text-slate-500 hover:text-slate-700 whitespace-nowrap cursor-pointer" title="Reset to standard 5-tab app layout">
                                    ↺ Reset (5 Tabs)
                                </button>
                            </div>
                        </div>

                        {{-- Compact Table Column Guide on Desktop --}}
                        <div class="hidden md:grid grid-cols-12 gap-1.5 px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-100/70 rounded-md mb-1.5">
                            <div class="col-span-1"># Order</div>
                            <div class="col-span-3 lg:col-span-2">Action / Trigger</div>
                            <div class="col-span-2 lg:col-span-2">Icon</div>
                            <div class="col-span-2 lg:col-span-2">Tab Label</div>
                            <div class="col-span-3 lg:col-span-3">Destination URL</div>
                            <div class="col-span-1 lg:col-span-2 text-right">Badge & Del</div>
                        </div>

                        <input type="hidden" name="mobile_bottom_nav_items" id="mobile_bottom_nav_items_input" value="{{ old('mobile_bottom_nav_items', $settings['mobile_bottom_nav_items'] ?? '') }}">

                        <div id="mobile-bottom-nav-container" class="space-y-1.5">
                            {{-- Dynamically rendered by JS --}}
                        </div>
                    </div>

                    {{-- Live Bottom Nav Preview --}}
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5 mb-1.5">
                            <span class="text-emerald-700 font-black">③</span> Live Mobile Dock Preview
                        </h4>
                        <div class="p-2 bg-slate-900 rounded-xl max-w-xs mx-auto shadow-sm border border-slate-800">
                            <div class="text-[9px] uppercase tracking-wider text-slate-400 font-bold text-center pb-1">Simulated Mobile Dock</div>
                            <div id="mobile_nav_live_preview" class="flex items-center justify-around h-11 rounded-lg px-2 shadow-inner transition-colors">
                                {{-- Preview items rendered dynamically by JS --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

{{-- TAB 4: Theme, Colors & Widgets --}}
        <div id="tab-theme" class="tab-content space-y-3 hidden">
            {{-- Theme Sections Sub-Navigation Jump Bar --}}
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100/90 rounded-xl px-2.5 border border-slate-200">
                <span class="text-slate-400 uppercase text-[10px] tracking-wider shrink-0 font-bold px-1">THEME:</span>
                <a href="#theme-palettes-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🎨 Color Palettes</a>
                <a href="#theme-custom-colors-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">🎛️ Custom Colors</a>
                <a href="#theme-scripts-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">💻 Custom CSS / Scripts</a>
                <a href="#theme-auth-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">👤 Customer Auth (Login & Register)</a>
                <a href="#theme-whatsapp-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">💬 WhatsApp Widget</a>
                <a href="#theme-call-card" class="section-jump-link rounded-lg px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs shrink-0 transition text-slate-700">📞 Call Widget</a>
            </div>

            <div class="admin-card" id="theme-palettes-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Curated Theme Palettes</h3>
                        <p class="text-[11px] text-slate-500">Click any preset to instantly apply professional brand color combinations.</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 border border-indigo-200">
                        1-Click Apply
                    </span>
                </div>
                <div class="admin-card-body p-4 sm:p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        {{-- Preset 1: Emerald Forest --}}
                        <button
                            type="button"
                            onclick="applyPalette('#0B733E', '#D91C2B', '#E6A11D', '#13251B')"
                            class="group relative flex flex-col items-center p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 hover:shadow-md transition text-center bg-white"
                        >
                            <div class="flex items-center gap-1 mb-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #0B733E;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #D91C2B;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #E6A11D;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #13251B;"></span>
                            </div>
                            <span class="text-xs font-bold text-slate-800">Emerald Forest</span>
                            <span class="text-[10px] text-slate-400">Classic Store</span>
                        </button>

                        {{-- Preset 2: Royal Sapphire Blue --}}
                        <button
                            type="button"
                            onclick="applyPalette('#1E40AF', '#F97316', '#F59E0B', '#0F172A')"
                            class="group relative flex flex-col items-center p-2.5 rounded-xl border border-slate-200 hover:border-blue-500 hover:shadow-md transition text-center bg-white"
                        >
                            <div class="flex items-center gap-1 mb-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #1E40AF;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #F97316;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #F59E0B;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #0F172A;"></span>
                            </div>
                            <span class="text-xs font-bold text-slate-800">Royal Sapphire</span>
                            <span class="text-[10px] text-slate-400">Modern Tech</span>
                        </button>

                        {{-- Preset 3: Ruby Crimson --}}
                        <button
                            type="button"
                            onclick="applyPalette('#BE123C', '#EA580C', '#F59E0B', '#18181B')"
                            class="group relative flex flex-col items-center p-2.5 rounded-xl border border-slate-200 hover:border-rose-500 hover:shadow-md transition text-center bg-white"
                        >
                            <div class="flex items-center gap-1 mb-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #BE123C;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #EA580C;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #F59E0B;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #18181B;"></span>
                            </div>
                            <span class="text-xs font-bold text-slate-800">Ruby Crimson</span>
                            <span class="text-[10px] text-slate-400">Fashion & Cosmetics</span>
                        </button>

                        {{-- Preset 4: Midnight Luxury --}}
                        <button
                            type="button"
                            onclick="applyPalette('#18181B', '#D97706', '#EAB308', '#09090B')"
                            class="group relative flex flex-col items-center p-2.5 rounded-xl border border-slate-200 hover:border-zinc-700 hover:shadow-md transition text-center bg-white"
                        >
                            <div class="flex items-center gap-1 mb-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #18181B;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #D97706;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #EAB308;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #09090B;"></span>
                            </div>
                            <span class="text-xs font-bold text-slate-800">Midnight Luxury</span>
                            <span class="text-[10px] text-slate-400">Premium Goods</span>
                        </button>

                        {{-- Preset 5: Amethyst Purple --}}
                        <button
                            type="button"
                            onclick="applyPalette('#6D28D9', '#DB2777', '#F59E0B', '#1E1B4B')"
                            class="group relative flex flex-col items-center p-2.5 rounded-xl border border-slate-200 hover:border-purple-500 hover:shadow-md transition text-center bg-white"
                        >
                            <div class="flex items-center gap-1 mb-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #6D28D9;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #DB2777;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #F59E0B;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #1E1B4B;"></span>
                            </div>
                            <span class="text-xs font-bold text-slate-800">Amethyst Violet</span>
                            <span class="text-[10px] text-slate-400">Boutique & Lifestyle</span>
                        </button>

                        {{-- Preset 6: Ocean Teal --}}
                        <button
                            type="button"
                            onclick="applyPalette('#0D9488', '#F43F5E', '#FBBF24', '#134E4A')"
                            class="group relative flex flex-col items-center p-2.5 rounded-xl border border-slate-200 hover:border-teal-500 hover:shadow-md transition text-center bg-white"
                        >
                            <div class="flex items-center gap-1 mb-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #0D9488;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #F43F5E;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #FBBF24;"></span>
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background-color: #134E4A;"></span>
                            </div>
                            <span class="text-xs font-bold text-slate-800">Ocean Teal</span>
                            <span class="text-[10px] text-slate-400">Fresh & Organic</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Custom Brand Colors --}}
            <div class="admin-card" id="theme-custom-colors-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Custom Color Controls</h3>
                        <p class="text-[11px] text-slate-500">Fine-tune individual brand colors or pick your exact hex codes.</p>
                    </div>
                    <span class="text-xs text-muted">Real-time Preview Below</span>
                </div>
                <div class="admin-card-body p-4 sm:p-5">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {{-- Homepage Background (Canvas) Color --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-1.5">
                            <label class="admin-label text-xs" for="theme_canvas_color">Website / Homepage Background Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="picker_canvas" value="{{ $settings['theme_canvas_color'] ?? '#F7EDDE' }}" class="h-8 w-10 cursor-pointer rounded border border-slate-300 p-0.5" oninput="syncColor('canvas', this.value)">
                                <input id="theme_canvas_color" name="theme_canvas_color" type="text" value="{{ old('theme_canvas_color', $settings['theme_canvas_color'] ?? '#F7EDDE') }}" class="field font-mono text-xs uppercase" oninput="syncColor('canvas', this.value, true)">
                            </div>
                            <p class="text-[10px] text-slate-400">Used for: Storefront main body canvas background</p>
                        </div>

                        {{-- Card / Box Background Color --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-1.5">
                            <label class="admin-label text-xs" for="theme_card_color">Card & Content Box Background</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="picker_card" value="{{ $settings['theme_card_color'] ?? '#FFFFFF' }}" class="h-8 w-10 cursor-pointer rounded border border-slate-300 p-0.5" oninput="syncColor('card', this.value)">
                                <input id="theme_card_color" name="theme_card_color" type="text" value="{{ old('theme_card_color', $settings['theme_card_color'] ?? '#FFFFFF') }}" class="field font-mono text-xs uppercase" oninput="syncColor('card', this.value, true)">
                            </div>
                            <p class="text-[10px] text-slate-400">Used for: Product cards, sections, content boxes</p>
                        </div>

                        {{-- Primary Color --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-1.5">
                            <label class="admin-label text-xs" for="theme_primary_color">Primary Brand Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="picker_primary" value="{{ $settings['theme_primary_color'] ?? '#0B733E' }}" class="h-8 w-10 cursor-pointer rounded border border-slate-300 p-0.5" oninput="syncColor('primary', this.value)">
                                <input id="theme_primary_color" name="theme_primary_color" type="text" value="{{ old('theme_primary_color', $settings['theme_primary_color'] ?? '#0B733E') }}" class="field font-mono text-xs uppercase" oninput="syncColor('primary', this.value, true)">
                            </div>
                            <p class="text-[10px] text-slate-400">Used for: Buttons, Header bar, Main badges</p>
                        </div>

                        {{-- Accent Red --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-1.5">
                            <label class="admin-label text-xs" for="theme_accent_color">Accent / Sale Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="picker_accent" value="{{ $settings['theme_accent_color'] ?? '#D91C2B' }}" class="h-8 w-10 cursor-pointer rounded border border-slate-300 p-0.5" oninput="syncColor('accent', this.value)">
                                <input id="theme_accent_color" name="theme_accent_color" type="text" value="{{ old('theme_accent_color', $settings['theme_accent_color'] ?? '#D91C2B') }}" class="field font-mono text-xs uppercase" oninput="syncColor('accent', this.value, true)">
                            </div>
                            <p class="text-[10px] text-slate-400">Used for: Sale discounts, Cart counter badge</p>
                        </div>

                        {{-- Gold --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-1.5">
                            <label class="admin-label text-xs" for="theme_gold_color">Golden Highlight</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="picker_gold" value="{{ $settings['theme_gold_color'] ?? '#E6A11D' }}" class="h-8 w-10 cursor-pointer rounded border border-slate-300 p-0.5" oninput="syncColor('gold', this.value)">
                                <input id="theme_gold_color" name="theme_gold_color" type="text" value="{{ old('theme_gold_color', $settings['theme_gold_color'] ?? '#E6A11D') }}" class="field font-mono text-xs uppercase" oninput="syncColor('gold', this.value, true)">
                            </div>
                            <p class="text-[10px] text-slate-400">Used for: Review stars, Special badges</p>
                        </div>

                        {{-- Dark Ink --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-1.5">
                            <label class="admin-label text-xs" for="theme_ink_color">Dark Ink Text</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="picker_ink" value="{{ $settings['theme_ink_color'] ?? '#13251B' }}" class="h-8 w-10 cursor-pointer rounded border border-slate-300 p-0.5" oninput="syncColor('ink', this.value)">
                                <input id="theme_ink_color" name="theme_ink_color" type="text" value="{{ old('theme_ink_color', $settings['theme_ink_color'] ?? '#13251B') }}" class="field font-mono text-xs uppercase" oninput="syncColor('ink', this.value, true)">
                            </div>
                            <p class="text-[10px] text-slate-400">Used for: Headings, Paragraphs, Dark icons</p>
                        </div>
                    </div>

                    {{-- Live Interactive Storefront Preview --}}
                    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Live Storefront Mini Preview</span>
                            <span class="text-[11px] text-emerald-600 font-semibold">Updates dynamically as you pick colors</span>
                        </div>

                        <div class="rounded-lg bg-white shadow-sm border border-slate-200 overflow-hidden text-xs">
                            {{-- Mini Announcement Bar --}}
                            <div id="preview-announcement" class="py-1 px-3 text-white text-[11px] font-medium flex items-center justify-between transition-colors" style="background-color: {{ $settings['theme_primary_color'] ?? '#0B733E' }};">
                                <span>🎉 Free delivery all over Bangladesh on orders over ৳1,500!</span>
                                <span id="preview-sale-pill" class="rounded-full px-2 py-0.2 text-[10px] font-bold text-white transition-colors" style="background-color: {{ $settings['theme_accent_color'] ?? '#D91C2B' }};">SALE</span>
                            </div>

                            {{-- Mini Header --}}
                            <div class="py-2.5 px-4 flex items-center justify-between border-b border-slate-100 bg-white">
                                <span class="font-extrabold text-sm tracking-tight" id="preview-logo-text" style="color: {{ $settings['theme_ink_color'] ?? '#13251B' }};">
                                    {{ $settings['store_name'] ?? 'YourBrand' }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500 text-[11px]">Shop</span>
                                    <span class="text-slate-500 text-[11px]">Categories</span>
                                    <button type="button" id="preview-cart-btn" class="rounded-full px-2.5 py-1 text-[11px] text-white font-bold transition-colors flex items-center gap-1" style="background-color: {{ $settings['theme_primary_color'] ?? '#0B733E' }};">
                                        <span>Cart</span>
                                        <span id="preview-cart-badge" class="rounded-full px-1 text-[9px] font-black text-white" style="background-color: {{ $settings['theme_accent_color'] ?? '#D91C2B' }};">3</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Mini Product Card Preview --}}
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50/50">
                                <div class="bg-white p-3 rounded-lg border border-slate-100 shadow-xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span id="preview-badge" class="px-2 py-0.5 rounded text-[10px] font-bold text-white uppercase" style="background-color: {{ $settings['theme_accent_color'] ?? '#D91C2B' }};">20% OFF</span>
                                        <div class="flex items-center gap-0.5 text-xs" id="preview-stars" style="color: {{ $settings['theme_gold_color'] ?? '#E6A11D' }};">
                                            ★★★★★
                                        </div>
                                    </div>
                                    <p class="font-bold text-sm" id="preview-title" style="color: {{ $settings['theme_ink_color'] ?? '#13251B' }};">Premium Cotton Panjabi</p>
                                    <p class="text-sm font-black" id="preview-price" style="color: {{ $settings['theme_primary_color'] ?? '#0B733E' }};">৳ 2,450</p>
                                    <button type="button" id="preview-btn" class="w-full py-2 rounded-md font-bold text-white text-xs transition-colors" style="background-color: {{ $settings['theme_primary_color'] ?? '#0B733E' }};">
                                        Order Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Custom CSS Code Editor --}}
            <div class="admin-card" id="theme-scripts-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Custom CSS & HTML Injection</h3>
                        <p class="text-[11px] text-slate-500">Inject custom CSS or tracking scripts directly into the storefront layout.</p>
                    </div>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-mono font-semibold text-slate-600">Pixel-Perfect</span>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-3.5">
                    <div>
                        <label class="admin-label" for="theme_custom_css">Custom CSS Styles (&lt;style&gt; tag in header)</label>
                        <textarea
                            id="theme_custom_css"
                            name="theme_custom_css"
                            rows="7"
                            placeholder="/* Custom CSS rules */&#10;.memo-card { border-radius: 20px; }"
                            class="w-full rounded-lg border border-slate-300 bg-slate-900 p-3 font-mono text-xs text-emerald-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >{{ old('theme_custom_css', $settings['theme_custom_css'] ?? '') }}</textarea>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="admin-label" for="theme_custom_header_html">Custom Header HTML / Meta (Inside &lt;head&gt;)</label>
                            <textarea id="theme_custom_header_html" name="theme_custom_header_html" rows="3" placeholder="<!-- Custom head scripts or tags -->" class="field font-mono text-xs">{{ old('theme_custom_header_html', $settings['theme_custom_header_html'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="admin-label" for="theme_custom_footer_html">Custom Footer HTML (Before &lt;/body&gt;)</label>
                            <textarea id="theme_custom_footer_html" name="theme_custom_footer_html" rows="3" placeholder="<!-- Custom footer scripts -->" class="field font-mono text-xs">{{ old('theme_custom_footer_html', $settings['theme_custom_footer_html'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card" id="theme-auth-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-2.5 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Customer Auth Pages (Login & Register)</h3>
                        <p class="text-[11px] text-slate-500">Customize the headings and descriptive text displayed on customer login and create account screens.</p>
                    </div>
                </div>
                <div class="admin-card-body p-3.5 space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-2.5 space-y-2">
                            <span class="text-xs font-bold text-slate-800 block">Registration (Create Account) Page</span>
                            <div>
                                <label class="admin-label text-[10px]" for="register_title">Heading Title</label>
                                <input id="register_title" name="register_title" type="text" value="{{ old('register_title', $settings['register_title'] ?? 'নতুন অ্যাকাউন্ট তৈরি করুন') }}" class="field text-xs !py-1 bg-white">
                            </div>
                            <div>
                                <label class="admin-label text-[10px]" for="register_subtitle">Subtitle / Notice Text</label>
                                <textarea id="register_subtitle" name="register_subtitle" rows="2" class="field text-xs bg-white">{{ old('register_subtitle', $settings['register_subtitle'] ?? 'অ্যাকাউন্ট তৈরি করে আপনার ক্যাশ অন ডেলিভারি (COD) অর্ডার সহজে ট্র্যাক করুন ও দ্রুত কেনাকাটা সম্পন্ন করুন।') }}</textarea>
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-2.5 space-y-2">
                            <span class="text-xs font-bold text-slate-800 block">Login Page</span>
                            <div>
                                <label class="admin-label text-[10px]" for="login_title">Heading Title</label>
                                <input id="login_title" name="login_title" type="text" value="{{ old('login_title', $settings['login_title'] ?? 'স্বাগতম') }}" class="field text-xs !py-1 bg-white">
                            </div>
                            <div>
                                <label class="admin-label text-[10px]" for="login_subtitle">Subtitle / Notice Text</label>
                                <textarea id="login_subtitle" name="login_subtitle" rows="2" class="field text-xs bg-white">{{ old('login_subtitle', $settings['login_subtitle'] ?? 'আপনার অর্ডার ট্র্যাক করতে এবং অ্যাকাউন্ট পরিচালনা করতে লগইন করুন।') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card" id="theme-whatsapp-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Sticky WhatsApp Chat Widget</h3>
                        <p class="text-[11px] text-slate-500">Floating WhatsApp button for quick customer conversation.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="floating_whatsapp_enabled" value="0">
                        <input type="checkbox" name="floating_whatsapp_enabled" value="1" {{ ($settings['floating_whatsapp_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-3.5">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="admin-label" for="floating_whatsapp_number">WhatsApp Number (e.g. 01700000000)</label>
                            <input id="floating_whatsapp_number" name="floating_whatsapp_number" type="text" value="{{ old('floating_whatsapp_number', $settings['floating_whatsapp_number'] ?? '01700000000') }}" class="field">
                        </div>
                        <div>
                            <label class="admin-label" for="floating_whatsapp_message">Default Prefilled Message</label>
                            <input id="floating_whatsapp_message" name="floating_whatsapp_message" type="text" value="{{ old('floating_whatsapp_message', $settings['floating_whatsapp_message'] ?? 'Hello Foodness, I would like to inquire about a product.') }}" class="field">
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card" id="theme-call-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Direct Call Widget</h3>
                        <p class="text-[11px] text-slate-500">One-tap telephone call button for mobile shoppers.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="floating_call_enabled" value="0">
                        <input type="checkbox" name="floating_call_enabled" value="1" {{ ($settings['floating_call_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Enabled</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5">
                    <div>
                        <label class="admin-label" for="floating_call_number">Hotline Telephone Number</label>
                        <input id="floating_call_number" name="floating_call_number" type="text" value="{{ old('floating_call_number', $settings['floating_call_number'] ?? '01700000000') }}" class="field">
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-checkout" class="tab-content space-y-3 hidden">
            <div class="admin-card" id="checkout-terms-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Terms agreement</h3>
                        <p class="text-[11px] text-slate-500">অর্ডার মোডালের নিচে শর্তাবলি চেকবক্স। বন্ধ করলে লেখাটি লুকিয়ে যায়। টিক না দিলেও অর্ডার করা যাবে।</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="checkout_terms_enabled" value="0">
                        <input type="checkbox" name="checkout_terms_enabled" value="1" {{ (string) old('checkout_terms_enabled', $settings['checkout_terms_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Show</span>
                    </label>
                </div>
            </div>
            @php
                $checkoutVoices = \App\Support\CheckoutVoices::options();
                $selectedCheckoutVoice = \App\Support\CheckoutVoices::resolve(old('checkout_voice_id', $settings['checkout_voice_id'] ?? 'nabanita'));
                $checkoutVoiceOn = (string) old('checkout_voice_enabled', $settings['checkout_voice_enabled'] ?? '1') === '1';
                $checkoutVoiceClips = [];
                foreach (array_keys($checkoutVoices) as $checkoutVoiceKey) {
                    $checkoutVoiceClips[$checkoutVoiceKey] = \App\Support\CheckoutVoices::clips($checkoutVoiceKey);
                }
            @endphp
            <div class="admin-card" id="checkout-voice-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Checkout voice guide</h3>
                        <p class="text-[11px] text-slate-500">নাম, মোবাইল আর ঠিকানায় ক্লিক করলে বাংলায় বলে। চেকআউট পেজ এবং অর্ডার মোডাল — দুটোতেই একসাথে কাজ করে।</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="checkout_voice_enabled" value="0">
                        <input type="checkbox" name="checkout_voice_enabled" value="1" {{ $checkoutVoiceOn ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Voice on</span>
                    </label>
                </div>
                <div class="admin-card-body p-4 sm:p-5 space-y-3">
                    <div class="grid gap-3">
                        @foreach (\App\Support\CheckoutVoices::fields() as $fieldKey => $field)
                            <div>
                                <label class="admin-label" for="{{ $field['setting'] }}">{{ $field['label'] }} — যা বলবে</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        id="{{ $field['setting'] }}"
                                        name="{{ $field['setting'] }}"
                                        type="text"
                                        maxlength="180"
                                        value="{{ old($field['setting'], $settings[$field['setting']] ?? '') ?: $field['default'] }}"
                                        placeholder="{{ $field['default'] }}"
                                        class="field"
                                    >
                                    <button type="button" class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[11px] font-bold text-slate-700 hover:bg-slate-50" data-voice-field-preview="{{ $fieldKey }}">শুনুন</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-slate-500">লেখা বদলালে Save চাপুন। নতুন বাক্য রেকর্ড হতে কয়েক সেকেন্ড লাগে। তারপর শুনুন চাপলে নতুন কথা শোনা যাবে।</p>
                    <p class="text-[11px] text-slate-500">একটা ভয়েস বেছে Save করুন। শুনে দেখতে Preview চাপুন।</p>
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        @foreach ($checkoutVoices as $voiceId => $voice)
                            @php $sample = \App\Support\CheckoutVoices::clips($voiceId)['name']; @endphp
                            <label class="flex items-start gap-3 rounded-xl border p-3 cursor-pointer {{ $selectedCheckoutVoice === $voiceId ? 'border-emerald-600 bg-emerald-50/60' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                                <input type="radio" name="checkout_voice_id" value="{{ $voiceId }}" class="mt-1" {{ $selectedCheckoutVoice === $voiceId ? 'checked' : '' }}>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-bold text-slate-900">{{ $voice['label'] }}</span>
                                    <span class="block text-[11px] font-semibold text-emerald-800">{{ $voice['detail'] }}</span>
                                    <span class="mt-1 block text-[11px] leading-relaxed text-slate-500">{{ $voice['note'] }}</span>
                                </span>
                                <button type="button" class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-50" data-voice-preview="{{ $sample }}">Preview</button>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <script>
                (() => {
                    const clipMap = @json($checkoutVoiceClips);
                    const play = (src) => {
                        if (!src) return;
                        if (window.__czVoicePreview) {
                            window.__czVoicePreview.pause();
                        }
                        const audio = new Audio(src);
                        window.__czVoicePreview = audio;
                        audio.play().catch(() => {});
                    };
                    const selectedVoice = () => document.querySelector('input[name="checkout_voice_id"]:checked')?.value || 'nabanita';

                    document.querySelectorAll('[data-voice-preview]').forEach((button) => {
                        button.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            play(button.getAttribute('data-voice-preview'));
                        });
                    });

                    document.querySelectorAll('[data-voice-field-preview]').forEach((button) => {
                        button.addEventListener('click', (event) => {
                            event.preventDefault();
                            const field = button.getAttribute('data-voice-field-preview');
                            play(clipMap[selectedVoice()]?.[field] || '');
                        });
                    });
                })();
            </script>
        </div>

    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        const activeTabInput = document.getElementById('active_tab_input');

        function switchTab(rawTabId) {
            const tabAliases = {
                'branding': 'header',
                'header': 'header',
                'hero': 'home',
                'sections': 'home',
                'stories': 'home',
                'categories': 'home',
                'gur_showcase': 'home',
                'featured': 'home',
                'story_reel': 'home',
                'promises': 'home',
                'faq': 'home',
                'home': 'home',
                'footer': 'footer',
                'checkout': 'checkout',
                'colors': 'theme',
                'widgets': 'theme',
                'theme': 'theme',
            };
            const targetTab = tabAliases[rawTabId] || 'header';

            tabContents.forEach(content => content.classList.add('hidden'));

            tabBtns.forEach(btn => {
                btn.classList.remove('bg-sage', 'text-white', 'border-sage', 'shadow-xs');
                btn.classList.add('bg-white', 'text-ink', 'border-ink/15', 'hover:bg-mist/40');
                const badge = btn.querySelector('.tab-count-badge');
                if (badge) {
                    badge.classList.remove('bg-white/20', 'text-white');
                    badge.classList.add('bg-slate-100', 'text-slate-600');
                }
            });

            const targetContent = document.getElementById('tab-' + targetTab);
            const targetBtn = document.querySelector(`[data-tab-target="${targetTab}"]`);

            if (targetContent) {
                targetContent.classList.remove('hidden');
            }
            if (targetContent && targetBtn) {
                targetBtn.classList.remove('bg-white', 'text-ink', 'border-ink/15', 'hover:bg-mist/40');
                targetBtn.classList.add('bg-sage', 'text-white', 'border-sage', 'shadow-xs');
                const activeBadge = targetBtn.querySelector('.tab-count-badge');
                if (activeBadge) {
                    activeBadge.classList.remove('bg-slate-100', 'text-slate-600');
                    activeBadge.classList.add('bg-white/20', 'text-white');
                }
            }
            if (activeTabInput) {
                activeTabInput.value = targetTab;
            }

            // If an anchor was requested in home, expand and scroll smoothly to it
            const anchorMap = {
                'hero': 'home-hero-card',
                'sections': 'home-dynamic-sections-card',
                'stories': 'home-stories-card',
                'categories': 'home-categories-card',
                'gur_showcase': 'home-gur-showcase-card',
                'featured': 'home-featured-card',
                'story_reel': 'home-reel-card',
                'promises': 'home-promises-card',
                'faq': 'home-faq-card',
            };
            if (anchorMap[rawTabId]) {
                const targetCard = document.getElementById(anchorMap[rawTabId]);
                if (targetCard) {
                    const card = targetCard.classList.contains('admin-card') ? targetCard : targetCard.querySelector('.admin-card');
                    if (card) {
                        const body = card.querySelector('.admin-card-body');
                        const ch = card.querySelector('.card-chevron');
                        if (body) body.classList.remove('hidden');
                        if (ch) ch.classList.add('rotate-180');
                        setTimeout(() => {
                            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 120);
                    }
                }
            }
        }

        tabBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const target = this.getAttribute('data-tab-target');
                switchTab(target);
            });
        });

        // Initialize Accordion for all admin cards across all tabs
        function initAccordions() {
            const cards = document.querySelectorAll('.admin-card');

            cards.forEach(card => {
                const header = card.querySelector('.admin-card-header');
                const body = card.querySelector('.admin-card-body');
                if (!header || !body) return;

                header.classList.add('cursor-pointer', 'select-none', 'transition-colors', 'hover:bg-slate-50/70');

                // If not already injected, add chevron toggle
                if (!header.querySelector('.card-collapse-toggle')) {
                    const toggleBtn = document.createElement('button');
                    toggleBtn.type = 'button';
                    toggleBtn.className = 'card-collapse-toggle p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition shrink-0 ml-auto';
                    toggleBtn.setAttribute('aria-label', 'Toggle Section');
                    toggleBtn.innerHTML = `
                        <svg class="h-4 w-4 transform transition-transform duration-200 card-chevron rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    `;

                    // Check if last child is an action group or label
                    const lastEl = header.lastElementChild;
                    if (lastEl && (lastEl.tagName === 'LABEL' || lastEl.classList.contains('cursor-pointer') || lastEl.querySelector('input, select, button'))) {
                        const rightGroup = document.createElement('div');
                        rightGroup.className = 'flex items-center gap-2 shrink-0 ml-auto';
                        header.insertBefore(rightGroup, lastEl);
                        rightGroup.appendChild(lastEl);
                        rightGroup.appendChild(toggleBtn);
                    } else {
                        header.appendChild(toggleBtn);
                    }
                }

                // Click header to toggle
                header.addEventListener('click', function (e) {
                    // Do not toggle if clicking directly on an input, select, textarea, label, link, or normal action button
                    if (e.target.closest('input, select, textarea, label, a, .btn-action, .admin-btn-primary, .admin-btn-secondary') && !e.target.closest('.card-collapse-toggle')) {
                        return;
                    }

                    const isHidden = body.classList.toggle('hidden');
                    const chevron = header.querySelector('.card-chevron');
                    if (chevron) {
                        if (isHidden) {
                            chevron.classList.remove('rotate-180');
                        } else {
                            chevron.classList.add('rotate-180');
                        }
                    }
                });
            });

            // Collapse All button
            const btnCollapseAll = document.getElementById('btn-collapse-all');
            if (btnCollapseAll) {
                btnCollapseAll.addEventListener('click', function () {
                    const activeTab = document.querySelector('.tab-content:not(.hidden)');
                    const targetCards = (activeTab || document).querySelectorAll('.admin-card');
                    targetCards.forEach(c => {
                        const b = c.querySelector('.admin-card-body');
                        const ch = c.querySelector('.card-chevron');
                        if (b) b.classList.add('hidden');
                        if (ch) ch.classList.remove('rotate-180');
                    });
                });
            }

            // Expand All button
            const btnExpandAll = document.getElementById('btn-expand-all');
            if (btnExpandAll) {
                btnExpandAll.addEventListener('click', function () {
                    const activeTab = document.querySelector('.tab-content:not(.hidden)');
                    const targetCards = (activeTab || document).querySelectorAll('.admin-card');
                    targetCards.forEach(c => {
                        const b = c.querySelector('.admin-card-body');
                        const ch = c.querySelector('.card-chevron');
                        if (b) b.classList.remove('hidden');
                        if (ch) ch.classList.add('rotate-180');
                    });
                });
            }

            // Sub-nav Jump Links & Hash link handler
            document.addEventListener('click', function (e) {
                const link = e.target.closest('.section-jump-link, a[href^="#"]');
                if (!link) return;
                const href = link.getAttribute('href');
                if (!href || !href.startsWith('#') || href === '#') return;

                const targetId = href.substring(1);
                const targetEl = document.getElementById(targetId);
                if (!targetEl) return;

                // Find card
                const card = targetEl.classList.contains('admin-card') ? targetEl : targetEl.querySelector('.admin-card');
                if (!card) return;

                e.preventDefault();

                // Switch tab if not currently visible
                const parentTab = card.closest('.tab-content');
                if (parentTab && parentTab.classList.contains('hidden')) {
                    const targetTab = parentTab.id.replace('tab-', '');
                    switchTab(targetTab);
                }

                // Expand card
                const body = card.querySelector('.admin-card-body');
                const ch = card.querySelector('.card-chevron');
                if (body && body.classList.contains('hidden')) {
                    body.classList.remove('hidden');
                    if (ch) ch.classList.add('rotate-180');
                }

                // Smooth scroll & subtle ring highlight
                setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    card.classList.add('ring-2', 'ring-emerald-500/70', 'transition-all', 'duration-300');
                    setTimeout(() => {
                        card.classList.remove('ring-2', 'ring-emerald-500/70');
                    }, 1500);
                }, 80);
            });

            // If page loaded with URL hash
            if (window.location.hash) {
                const hashId = window.location.hash.substring(1);
                const targetEl = document.getElementById(hashId);
                if (targetEl) {
                    const card = targetEl.classList.contains('admin-card') ? targetEl : targetEl.querySelector('.admin-card');
                    if (card) {
                        const parentTab = card.closest('.tab-content');
                        if (parentTab) {
                            switchTab(parentTab.id.replace('tab-', ''));
                        }
                        const b = card.querySelector('.admin-card-body');
                        const ch = card.querySelector('.card-chevron');
                        if (b) b.classList.remove('hidden');
                        if (ch) ch.classList.add('rotate-180');
                        setTimeout(() => {
                            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 150);
                    }
                }
            }
        }

        initAccordions();

        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab') || '{{ request('tab', 'branding') }}';
        switchTab(queryTab);
        updateMiniPreview();
    });

    function applyPalette(primary, accent, gold, ink) {
        document.getElementById('picker_primary').value = primary;
        document.getElementById('theme_primary_color').value = primary;

        document.getElementById('picker_accent').value = accent;
        document.getElementById('theme_accent_color').value = accent;

        document.getElementById('picker_gold').value = gold;
        document.getElementById('theme_gold_color').value = gold;

        document.getElementById('picker_ink').value = ink;
        document.getElementById('theme_ink_color').value = ink;

        updateMiniPreview();
    }

    function syncColor(type, val, fromText = false) {
        if (fromText) {
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                document.getElementById('picker_' + type).value = val;
            }
        } else {
            document.getElementById('theme_' + type + '_color').value = val;
        }
        updateMiniPreview();
    }

    function updateMiniPreview() {
        const primary = document.getElementById('theme_primary_color')?.value || '#0B733E';
        const accent = document.getElementById('theme_accent_color')?.value || '#D91C2B';
        const gold = document.getElementById('theme_gold_color')?.value || '#E6A11D';
        const ink = document.getElementById('theme_ink_color')?.value || '#13251B';
        const canvas = document.getElementById('theme_canvas_color')?.value || '#F7EDDE';
        const card = document.getElementById('theme_card_color')?.value || '#FFFFFF';

        const elPreviewBox = document.querySelector('#theme-custom-colors-card .admin-card-body .rounded-lg.bg-white');
        if (elPreviewBox) elPreviewBox.style.backgroundColor = card;

        const elAnnounce = document.getElementById('preview-announcement');
        if (elAnnounce) elAnnounce.style.backgroundColor = primary;

        const elCartBtn = document.getElementById('preview-cart-btn');
        if (elCartBtn) elCartBtn.style.backgroundColor = primary;

        const elBtn = document.getElementById('preview-btn');
        if (elBtn) elBtn.style.backgroundColor = primary;

        const elPrice = document.getElementById('preview-price');
        if (elPrice) elPrice.style.color = primary;

        const elSale = document.getElementById('preview-sale-pill');
        if (elSale) elSale.style.backgroundColor = accent;

        const elBadge = document.getElementById('preview-badge');
        if (elBadge) elBadge.style.backgroundColor = accent;

        const elCartBadge = document.getElementById('preview-cart-badge');
        if (elCartBadge) elCartBadge.style.backgroundColor = accent;

        const elStars = document.getElementById('preview-stars');
        if (elStars) elStars.style.color = gold;

        const elLogo = document.getElementById('preview-logo-text');
        if (elLogo) elLogo.style.color = ink;

        const elTitle = document.getElementById('preview-title');
        if (elTitle) elTitle.style.color = ink;

        // Sync colors with Live Placement Mockups
        const elLiveAnnounce = document.getElementById('live-preview-announcement-bar');
        if (elLiveAnnounce) elLiveAnnounce.style.backgroundColor = primary;
    }

    function filterPlacements(filter) {
        const cards = document.querySelectorAll('.placement-card');
        const buttons = document.querySelectorAll('.placement-btn');

        buttons.forEach(btn => {
            if (btn.getAttribute('data-placement-btn') === filter) {
                btn.classList.add('active', 'bg-slate-900', 'text-white');
                btn.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-200');
            } else {
                btn.classList.remove('active', 'bg-slate-900', 'text-white');
                btn.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-200');
            }
        });

        cards.forEach(card => {
            const type = card.getAttribute('data-placement-type');
            if (filter === 'all' || type === filter) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    const initialLogoUrl = @json(!empty($settings['store_logo']) && file_exists(public_path('storage/'.$settings['store_logo'])) ? asset('storage/'.$settings['store_logo']) : null);
    const initialFaviconUrl = @json(!empty($settings['site_favicon']) && file_exists(public_path('storage/'.$settings['site_favicon'])) ? asset('storage/'.$settings['site_favicon']) : null);

    function updateLogoPreviews(url) {
        const desktopImg = document.getElementById('live-preview-logo-desktop');
        const desktopFallback = document.getElementById('live-preview-logo-desktop-fallback');
        const mobileImg = document.getElementById('live-preview-logo-mobile');
        const mobileFallback = document.getElementById('live-preview-logo-mobile-fallback');
        const invoiceImg = document.getElementById('live-preview-logo-invoice');
        const invoiceFallback = document.getElementById('live-preview-logo-invoice-fallback');

        if (url) {
            if (desktopImg) { desktopImg.src = url; desktopImg.classList.remove('hidden'); }
            if (desktopFallback) desktopFallback.classList.add('hidden');

            if (mobileImg) { mobileImg.src = url; mobileImg.classList.remove('hidden'); }
            if (mobileFallback) mobileFallback.classList.add('hidden');

            if (invoiceImg) { invoiceImg.src = url; invoiceImg.classList.remove('hidden'); }
            if (invoiceFallback) invoiceFallback.classList.add('hidden');
        } else {
            if (desktopImg) { desktopImg.src = ''; desktopImg.classList.add('hidden'); }
            if (desktopFallback) desktopFallback.classList.remove('hidden');

            if (mobileImg) { mobileImg.src = ''; mobileImg.classList.add('hidden'); }
            if (mobileFallback) mobileFallback.classList.remove('hidden');

            if (invoiceImg) { invoiceImg.src = ''; invoiceImg.classList.add('hidden'); }
            if (invoiceFallback) invoiceFallback.classList.remove('hidden');
        }
    }

    function updateFaviconPreviews(url) {
        const tabFaviconImg = document.getElementById('live-preview-favicon-tab');
        const tabFaviconFallback = document.getElementById('live-preview-favicon-tab-fallback');
        const urlFaviconImg = document.getElementById('live-preview-favicon-url');
        const urlFaviconFallback = document.getElementById('live-preview-favicon-url-fallback');

        if (url) {
            if (tabFaviconImg) { tabFaviconImg.src = url; tabFaviconImg.classList.remove('hidden'); }
            if (tabFaviconFallback) tabFaviconFallback.classList.add('hidden');

            if (urlFaviconImg) { urlFaviconImg.src = url; urlFaviconImg.classList.remove('hidden'); }
            if (urlFaviconFallback) urlFaviconFallback.classList.add('hidden');
        } else {
            if (tabFaviconImg) { tabFaviconImg.src = ''; tabFaviconImg.classList.add('hidden'); }
            if (tabFaviconFallback) tabFaviconFallback.classList.remove('hidden');

            if (urlFaviconImg) { urlFaviconImg.src = ''; urlFaviconImg.classList.add('hidden'); }
            if (urlFaviconFallback) urlFaviconFallback.classList.remove('hidden');
        }
    }

    function updateStoreTextPreviews() {
        const nameInput = document.getElementById('store_name');
        const taglineInput = document.getElementById('store_tagline');

        const name = nameInput && nameInput.value.trim() ? nameInput.value.trim() : 'Foodness';
        const tagline = taglineInput && taglineInput.value.trim() ? taglineInput.value.trim() : 'Premium Fashion & Cash on Delivery';

        const tabTitle = document.getElementById('live-preview-tab-title');
        if (tabTitle) tabTitle.textContent = `${name} | ${tagline}`;

        const desktopText = document.getElementById('preview-logo-desktop-text');
        if (desktopText) desktopText.textContent = name;

        const mobileText = document.getElementById('preview-logo-mobile-text');
        if (mobileText) mobileText.textContent = name;

        const invoiceText = document.getElementById('preview-logo-invoice-text');
        if (invoiceText) invoiceText.textContent = name;

        const invoiceTagline = document.getElementById('preview-invoice-tagline');
        if (invoiceTagline) invoiceTagline.textContent = tagline;

        const tabFallback = document.getElementById('live-preview-favicon-tab-fallback');
        if (tabFallback) tabFallback.textContent = name.charAt(0).toUpperCase();

        const urlFallback = document.getElementById('live-preview-favicon-url-fallback');
        if (urlFallback) urlFallback.textContent = name.charAt(0).toUpperCase();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const logoInput = document.getElementById('store_logo');
        if (logoInput) {
            logoInput.addEventListener('change', function () {
                const file = logoInput.files && logoInput.files[0];
                if (file) {
                    updateLogoPreviews(URL.createObjectURL(file));
                } else {
                    const removeInput = document.getElementById('dropzone_store_logo_remove');
                    if (removeInput && removeInput.value === '1') {
                        updateLogoPreviews(null);
                    } else {
                        updateLogoPreviews(initialLogoUrl);
                    }
                }
            });
        }

        const faviconInput = document.getElementById('site_favicon');
        if (faviconInput) {
            faviconInput.addEventListener('change', function () {
                const file = faviconInput.files && faviconInput.files[0];
                if (file) {
                    updateFaviconPreviews(URL.createObjectURL(file));
                } else {
                    const removeInput = document.getElementById('dropzone_site_favicon_remove');
                    if (removeInput && removeInput.value === '1') {
                        updateFaviconPreviews(null);
                    } else {
                        updateFaviconPreviews(initialFaviconUrl);
                    }
                }
            });
        }

        const storeNameInput = document.getElementById('store_name');
        if (storeNameInput) {
            storeNameInput.addEventListener('input', updateStoreTextPreviews);
        }
        const storeTaglineInput = document.getElementById('store_tagline');
        if (storeTaglineInput) {
            storeTaglineInput.addEventListener('input', updateStoreTextPreviews);
        }
    });

    function previewUploadImage(input, previewImgId, placeholderId) {
        if (!input.files || !input.files[0]) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const previewImg = document.getElementById(previewImgId);
            const placeholder = document.getElementById(placeholderId);

            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
            }
            if (placeholder) {
                placeholder.classList.add('hidden');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }

    // Auto-capture video frame for Story Reel Cover if no cover image uploaded
    const storyReelVideoInput = document.getElementById('story_reel_video');
    const storyReelImageInput = document.getElementById('story_reel_image');
    const autoReelDataInput = document.getElementById('auto_story_reel_image_data');
    const autoReelBadge = document.getElementById('auto-reel-thumb-badge');

    if (storyReelVideoInput) {
        storyReelVideoInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !file.type.startsWith('video/')) return;

            const video = document.createElement('video');
            video.preload = 'metadata';
            video.muted = true;
            video.playsInline = true;
            const objUrl = URL.createObjectURL(file);
            video.src = objUrl;

            video.onloadedmetadata = function () {
                let seekTime = 1.0;
                if (video.duration && video.duration > 0) {
                    seekTime = Math.min(1.0, video.duration / 3);
                }
                video.currentTime = seekTime;
            };

            video.onseeked = function () {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 540;
                    canvas.height = 960;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                    if (autoReelDataInput) {
                        autoReelDataInput.value = dataUrl;
                    }

                    if (storyReelImageInput && (!storyReelImageInput.files || storyReelImageInput.files.length === 0)) {
                        const dropzone = document.getElementById('dropzone_story_reel_image');
                        if (dropzone) {
                            const placeholder = dropzone.querySelector('[data-dropzone-placeholder]');
                            const previewWrap = dropzone.querySelector('[data-dropzone-preview-wrap]');
                            const img = dropzone.querySelector('[data-dropzone-img]') || previewWrap?.querySelector('img');

                            if (placeholder) placeholder.classList.add('hidden');
                            if (previewWrap) previewWrap.classList.remove('hidden');
                            if (img) {
                                img.src = dataUrl;
                                img.classList.remove('hidden');
                            }
                        }
                        if (autoReelBadge) autoReelBadge.classList.remove('hidden');
                    }
                } catch (e) {
                } finally {
                    URL.revokeObjectURL(objUrl);
                }
            };
        });
    }

    if (storyReelImageInput) {
        storyReelImageInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                if (autoReelBadge) autoReelBadge.classList.add('hidden');
                if (autoReelDataInput) autoReelDataInput.value = '';
            }
        });
    }

    // ==========================================
    
    // ==========================================
    // Interactive Header Navigation Menu Builder
    // ==========================================
    (function () {
        const menuInput = document.getElementById('header_menu_links_input');
        const menuContainer = document.getElementById('header-menu-links-container');
        const btnAddMenuLink = document.getElementById('btn_add_menu_link');
        const btnQuickAddCat = document.getElementById('btn_quick_add_cat');
        const quickAddCatSelect = document.getElementById('quick_add_cat_select');

        if (!menuInput || !menuContainer) return;

        let links = [];

        try {
            if (menuInput.value && menuInput.value.trim() !== '') {
                const parsed = JSON.parse(menuInput.value);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    links = parsed;
                }
            }
        } catch (e) {
            console.error('Failed to parse header_menu_links JSON:', e);
        }

        if (links.length === 0) {
            links = [
                { label: 'All Products', url: '/shop', new_tab: false },
                { label: 'Featured Category', url: '/shop', new_tab: false },
                { label: 'Special Collection', url: '/shop', new_tab: false },
                { label: 'Our Story', url: '#home-reel-card', new_tab: false }
            ];
        }

        function syncInput() {
            menuInput.value = JSON.stringify(links);
        }

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function render() {
            menuContainer.innerHTML = '';

            if (links.length === 0) {
                menuContainer.innerHTML = `
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-slate-500">
                        <p class="text-xs font-semibold text-slate-700">No header menu links added yet.</p>
                        <p class="text-[11px] text-slate-500 mt-1">Use "+ Custom Link" or the Category dropdown above to add menu items.</p>
                    </div>
                `;
                syncInput();
                return;
            }

            links.forEach((item, idx) => {
                const row = document.createElement('div');
                row.className = 'grid grid-cols-1 md:grid-cols-12 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white p-1.5 sm:px-2 shadow-2xs hover:border-slate-300 transition';
                row.innerHTML = `
                    <div class="col-span-1 flex items-center gap-1 shrink-0">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-700">
                            ${idx + 1}
                        </span>
                        <div class="flex flex-col">
                            <button type="button" class="btn-menu-up p-0.5 rounded hover:bg-slate-100 text-slate-500 hover:text-slate-800 text-[10px] leading-none transition cursor-pointer" data-idx="${idx}" title="Move Up" ${idx === 0 ? 'disabled style="opacity:0.25;cursor:not-allowed;"' : ''}>
                                ▲
                            </button>
                            <button type="button" class="btn-menu-down p-0.5 rounded hover:bg-slate-100 text-slate-500 hover:text-slate-800 text-[10px] leading-none transition cursor-pointer" data-idx="${idx}" title="Move Down" ${idx === links.length - 1 ? 'disabled style="opacity:0.25;cursor:not-allowed;"' : ''}>
                                ▼
                            </button>
                        </div>
                    </div>

                    <div class="col-span-4">
                        <input type="text" class="field text-xs !py-1 !px-2 h-7.5 w-full menu-label-input" data-idx="${idx}" value="${escapeHtml(item.label)}" placeholder="Menu Label">
                    </div>

                    <div class="col-span-5">
                        <input type="text" class="field text-xs !py-1 !px-2 h-7.5 w-full menu-url-input" data-idx="${idx}" value="${escapeHtml(item.url)}" placeholder="Destination URL (e.g. /shop)">
                    </div>

                    <div class="col-span-2 flex items-center gap-2 justify-end">
                        <label class="inline-flex items-center gap-1 text-[11px] text-slate-600 cursor-pointer select-none shrink-0">
                            <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-0 menu-tab-toggle" data-idx="${idx}" ${item.new_tab ? 'checked' : ''}>
                            <span class="text-[10px] font-medium text-slate-600">New Tab</span>
                        </label>
                        <button type="button" class="btn-menu-delete h-7 w-7 rounded-md border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold transition flex items-center justify-center shrink-0 cursor-pointer" data-idx="${idx}" title="Delete Link">
                            ✕
                        </button>
                    </div>
                `;
                menuContainer.appendChild(row);
            });

            syncInput();
        }

        menuContainer.addEventListener('input', function (e) {
            const labelInput = e.target.closest('.menu-label-input');
            if (labelInput) {
                const idx = parseInt(labelInput.getAttribute('data-idx'), 10);
                if (links[idx]) {
                    links[idx].label = labelInput.value;
                    syncInput();
                }
                return;
            }

            const urlInput = e.target.closest('.menu-url-input');
            if (urlInput) {
                const idx = parseInt(urlInput.getAttribute('data-idx'), 10);
                if (links[idx]) {
                    links[idx].url = urlInput.value;
                    syncInput();
                }
                return;
            }
        });

        menuContainer.addEventListener('change', function (e) {
            const tabToggle = e.target.closest('.menu-tab-toggle');
            if (tabToggle) {
                const idx = parseInt(tabToggle.getAttribute('data-idx'), 10);
                if (links[idx]) {
                    links[idx].new_tab = tabToggle.checked;
                    syncInput();
                }
            }
        });

        menuContainer.addEventListener('click', function (e) {
            const delBtn = e.target.closest('.btn-menu-delete');
            if (delBtn) {
                const idx = parseInt(delBtn.getAttribute('data-idx'), 10);
                links.splice(idx, 1);
                render();
                return;
            }

            const upBtn = e.target.closest('.btn-menu-up');
            if (upBtn) {
                const idx = parseInt(upBtn.getAttribute('data-idx'), 10);
                if (idx > 0) {
                    const temp = links[idx];
                    links[idx] = links[idx - 1];
                    links[idx - 1] = temp;
                    render();
                }
                return;
            }

            const downBtn = e.target.closest('.btn-menu-down');
            if (downBtn) {
                const idx = parseInt(downBtn.getAttribute('data-idx'), 10);
                if (idx < links.length - 1) {
                    const temp = links[idx];
                    links[idx] = links[idx + 1];
                    links[idx + 1] = temp;
                    render();
                }
                return;
            }
        });

        if (btnAddMenuLink) {
            btnAddMenuLink.addEventListener('click', function () {
                links.push({
                    label: 'New Link',
                    url: '/shop',
                    new_tab: false
                });
                render();
            });
        }

        if (btnQuickAddCat && quickAddCatSelect) {
            btnQuickAddCat.addEventListener('click', function () {
                const opt = quickAddCatSelect.options[quickAddCatSelect.selectedIndex];
                if (!opt || !opt.value) {
                    alert('Please select a category from the dropdown first.');
                    return;
                }
                const name = opt.getAttribute('data-name') || opt.textContent.trim();
                const url = opt.value;

                links.push({
                    label: name,
                    url: url,
                    new_tab: false
                });
                render();
                quickAddCatSelect.value = '';
            });
        }

        const customForm = document.getElementById('customization-form');
        if (customForm) {
            customForm.addEventListener('submit', function () {
                syncInput();
            });
        }

        render();
    })();

    // ==========================================
    // Interactive Mobile 3-Dash Drawer Links Manager
    // ==========================================
    (function () {
        const inputEl = document.getElementById('mobile_drawer_menu_links_input');
        const containerEl = document.getElementById('mobile-drawer-links-container');
        const btnAdd = document.getElementById('btn_add_drawer_link');
        const btnQuickAddCat = document.getElementById('btn_quick_add_drawer_cat');
        const quickAddCatSelect = document.getElementById('quick_add_drawer_cat_select');
        const btnReset = document.getElementById('btn_reset_drawer_links');

        if (!inputEl || !containerEl) return;

        const defaultLinks = [
            { label: 'হোম', url: '/', icon: 'home', badge: '', new_tab: false },
            { label: 'সকল পণ্য', url: '/shop', icon: 'shop', badge: 'সব', new_tab: false },
            { label: 'আমাদের গল্প', url: '#amader-golpo', icon: 'book', badge: '', new_tab: false },
            { label: 'প্রশ্নোত্তর (FAQ)', url: '#faq', icon: 'help', badge: '', new_tab: false }
        ];

        let links = [];

        try {
            if (inputEl.value && inputEl.value.trim() !== '') {
                const parsed = JSON.parse(inputEl.value);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    links = parsed;
                }
            }
        } catch (e) {
            console.error('Failed to parse mobile_drawer_menu_links JSON:', e);
        }

        if (links.length === 0) {
            links = JSON.parse(JSON.stringify(defaultLinks));
        }

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function syncInput() {
            inputEl.value = JSON.stringify(links);
        }

        const iconOptions = [
            { val: 'home', label: '🏠 Home' },
            { val: 'shop', label: '🛍️ Shop / Store' },
            { val: 'grid', label: '🗂️ Categories' },
            { val: 'search', label: '🔍 Search' },
            { val: 'cart', label: '🛒 Cart / Bag' },
            { val: 'menu', label: '☰ Menu' },
            { val: 'user', label: '👤 Account' },
            { val: 'whatsapp', label: '💬 WhatsApp' },
            { val: 'phone', label: '📞 Phone Call' },
            { val: 'heart', label: '❤️ Wishlist' },
            { val: 'tag', label: '🏷️ Deals / Offers' },
            { val: 'truck', label: '🚚 Track Order' },
            { val: 'book', label: '📖 Story / Blog' },
            { val: 'help', label: '❓ FAQ / Help' },
            { val: 'link', label: '🔗 Direct Link' }
        ];

        function render() {
            containerEl.innerHTML = '';

            if (links.length === 0) {
                containerEl.innerHTML = `
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-slate-500">
                        <p class="text-xs font-semibold text-slate-700">No drawer links added yet.</p>
                        <p class="text-[11px] text-slate-500 mt-1">Use "+ Custom Link" or the Category dropdown to add menu shortcuts.</p>
                    </div>
                `;
                syncInput();
                return;
            }

            links.forEach((item, idx) => {
                const row = document.createElement('div');
                row.className = 'grid grid-cols-1 md:grid-cols-12 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white p-1.5 sm:px-2 shadow-2xs hover:border-slate-300 transition';
                
                let iconSelectHtml = `<select class="field text-xs !py-1 !px-1.5 h-7.5 w-full bg-slate-50/70 border-slate-200 drawer-icon-select" data-idx="${idx}">`;
                iconOptions.forEach(opt => {
                    const sel = (item.icon === opt.val) ? 'selected' : '';
                    iconSelectHtml += `<option value="${opt.val}" ${sel}>${opt.label}</option>`;
                });
                iconSelectHtml += `</select>`;

                row.innerHTML = `
                    <div class="col-span-1 flex items-center gap-1 shrink-0">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[10px] font-bold text-emerald-800 border border-emerald-200">
                            ${idx + 1}
                        </span>
                        <div class="flex flex-col">
                            <button type="button" class="btn-drawer-up p-0.5 rounded hover:bg-slate-100 text-slate-500 hover:text-slate-800 text-[10px] leading-none transition cursor-pointer" data-idx="${idx}" title="Move Up" ${idx === 0 ? 'disabled style="opacity:0.25;cursor:not-allowed;"' : ''}>
                                ▲
                            </button>
                            <button type="button" class="btn-drawer-down p-0.5 rounded hover:bg-slate-100 text-slate-500 hover:text-slate-800 text-[10px] leading-none transition cursor-pointer" data-idx="${idx}" title="Move Down" ${idx === links.length - 1 ? 'disabled style="opacity:0.25;cursor:not-allowed;"' : ''}>
                                ▼
                            </button>
                        </div>
                    </div>

                    <div class="col-span-2">
                        ${iconSelectHtml}
                    </div>

                    <div class="col-span-3">
                        <input type="text" class="field text-xs !py-1 !px-2 h-7.5 w-full drawer-label-input" data-idx="${idx}" value="${escapeHtml(item.label)}" placeholder="Link Title">
                    </div>

                    <div class="col-span-3">
                        <input type="text" class="field text-xs !py-1 !px-2 h-7.5 w-full drawer-url-input" data-idx="${idx}" value="${escapeHtml(item.url)}" placeholder="Target URL (e.g. /shop)">
                    </div>

                    <div class="col-span-1">
                        <input type="text" class="field text-xs !py-1 !px-1.5 h-7.5 w-full drawer-badge-input" data-idx="${idx}" value="${escapeHtml(item.badge || '')}" placeholder="Tag">
                    </div>

                    <div class="col-span-2 flex items-center gap-1.5 justify-end">
                        <label class="inline-flex items-center gap-1 text-[11px] text-slate-600 cursor-pointer select-none shrink-0">
                            <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-0 drawer-tab-toggle" data-idx="${idx}" ${item.new_tab ? 'checked' : ''}>
                            <span class="text-[10px] font-medium text-slate-600">New Tab</span>
                        </label>
                        <button type="button" class="btn-drawer-delete h-7 w-7 rounded-md border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold transition flex items-center justify-center shrink-0 cursor-pointer" data-idx="${idx}" title="Delete Link">
                            ✕
                        </button>
                    </div>
                `;
                containerEl.appendChild(row);
            });

            syncInput();
        }

        containerEl.addEventListener('input', function (e) {
            const labelInput = e.target.closest('.drawer-label-input');
            if (labelInput) {
                const idx = parseInt(labelInput.getAttribute('data-idx'), 10);
                if (links[idx]) {
                    links[idx].label = labelInput.value;
                    syncInput();
                }
                return;
            }

            const urlInput = e.target.closest('.drawer-url-input');
            if (urlInput) {
                const idx = parseInt(urlInput.getAttribute('data-idx'), 10);
                if (links[idx]) {
                    links[idx].url = urlInput.value;
                    syncInput();
                }
                return;
            }

            const badgeInput = e.target.closest('.drawer-badge-input');
            if (badgeInput) {
                const idx = parseInt(badgeInput.getAttribute('data-idx'), 10);
                if (links[idx]) {
                    links[idx].badge = badgeInput.value;
                    syncInput();
                }
                return;
            }
        });

        containerEl.addEventListener('change', function (e) {
            const iconSelect = e.target.closest('.drawer-icon-select');
            if (iconSelect) {
                const idx = parseInt(iconSelect.getAttribute('data-idx'), 10);
                if (links[idx]) {
                    links[idx].icon = iconSelect.value;
                    syncInput();
                }
                return;
            }

            const tabToggle = e.target.closest('.drawer-tab-toggle');
            if (tabToggle) {
                const idx = parseInt(tabToggle.getAttribute('data-idx'), 10);
                if (links[idx]) {
                    links[idx].new_tab = tabToggle.checked;
                    syncInput();
                }
                return;
            }
        });

        containerEl.addEventListener('click', function (e) {
            const delBtn = e.target.closest('.btn-drawer-delete');
            if (delBtn) {
                const idx = parseInt(delBtn.getAttribute('data-idx'), 10);
                links.splice(idx, 1);
                render();
                return;
            }

            const upBtn = e.target.closest('.btn-drawer-up');
            if (upBtn) {
                const idx = parseInt(upBtn.getAttribute('data-idx'), 10);
                if (idx > 0) {
                    const temp = links[idx];
                    links[idx] = links[idx - 1];
                    links[idx - 1] = temp;
                    render();
                }
                return;
            }

            const downBtn = e.target.closest('.btn-drawer-down');
            if (downBtn) {
                const idx = parseInt(downBtn.getAttribute('data-idx'), 10);
                if (idx < links.length - 1) {
                    const temp = links[idx];
                    links[idx] = links[idx + 1];
                    links[idx + 1] = temp;
                    render();
                }
                return;
            }
        });

        if (btnAdd) {
            btnAdd.addEventListener('click', function () {
                links.push({
                    label: 'New Link',
                    url: '/shop',
                    icon: 'link',
                    badge: '',
                    new_tab: false
                });
                render();
            });
        }

        if (btnQuickAddCat && quickAddCatSelect) {
            btnQuickAddCat.addEventListener('click', function () {
                const opt = quickAddCatSelect.options[quickAddCatSelect.selectedIndex];
                if (!opt || !opt.value) {
                    alert('Please select a category from the dropdown first.');
                    return;
                }
                const name = opt.getAttribute('data-name') || opt.textContent.trim();
                const url = opt.value;

                links.push({
                    label: name,
                    url: url,
                    icon: 'grid',
                    badge: '',
                    new_tab: false
                });
                render();
                quickAddCatSelect.value = '';
            });
        }

        if (btnReset) {
            btnReset.addEventListener('click', function () {
                if (confirm('Reset drawer navigation links to standard defaults?')) {
                    links = JSON.parse(JSON.stringify(defaultLinks));
                    render();
                }
            });
        }

        const customForm = document.getElementById('customization-form');
        if (customForm) {
            customForm.addEventListener('submit', function () {
                syncInput();
            });
        }

        render();
    })();

    // ==========================================
    // Interactive Mobile Bottom Navigation Bar Manager
    // ==========================================
    (function () {
        const inputEl = document.getElementById('mobile_bottom_nav_items_input');
        const containerEl = document.getElementById('mobile-bottom-nav-container');
        const previewEl = document.getElementById('mobile_nav_live_preview');
        const btnAdd = document.getElementById('btn_add_bottom_nav_tab');
        const btnReset = document.getElementById('btn_reset_bottom_nav_tabs');

        const bgInput = document.getElementById('mobile_bottom_nav_bg');
        const bgPicker = document.getElementById('mobile_bottom_nav_bg_picker');
        const activeColorInput = document.getElementById('mobile_bottom_nav_active_color');
        const activeColorPicker = document.getElementById('mobile_bottom_nav_active_color_picker');
        const inactiveColorInput = document.getElementById('mobile_bottom_nav_inactive_color');
        const inactiveColorPicker = document.getElementById('mobile_bottom_nav_inactive_color_picker');
        const showLabelsToggle = document.getElementById('bottom_nav_show_labels_toggle');

        const legacyHome = document.getElementById('legacy_mobile_nav_home_label');
        const legacyShop = document.getElementById('legacy_mobile_nav_shop_label');
        const legacySearch = document.getElementById('legacy_mobile_nav_search_label');
        const legacyCart = document.getElementById('legacy_mobile_nav_cart_label');

        if (!inputEl || !containerEl) return;

        const defaultTabs = [
            { label: 'হোম', url: '/', action: 'url', icon: 'home', badge: 'none', badge_text: '' },
            { label: 'শপ', url: '/shop', action: 'url', icon: 'shop', badge: 'none', badge_text: '' },
            { label: 'সার্চ', url: '', action: 'search', icon: 'search', badge: 'none', badge_text: '' },
            { label: 'ব্যাগ', url: '/cart', action: 'cart', icon: 'cart', badge: 'cart_count', badge_text: '' },
            { label: 'মেনু', url: '', action: 'drawer', icon: 'menu', badge: 'none', badge_text: '' }
        ];

        let tabs = [];

        try {
            if (inputEl.value && inputEl.value.trim() !== '') {
                const parsed = JSON.parse(inputEl.value);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    tabs = parsed;
                }
            }
        } catch (e) {
            console.error('Failed to parse mobile_bottom_nav_items JSON:', e);
        }

        if (tabs.length === 0) {
            tabs = JSON.parse(JSON.stringify(defaultTabs));
        }

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function syncInput() {
            inputEl.value = JSON.stringify(tabs);

            // Sync legacy fields for tests and backwards compatibility
            if (legacyHome && tabs[0]) legacyHome.value = tabs[0].label || 'Home';
            if (legacyShop && tabs[1]) legacyShop.value = tabs[1].label || 'Shop';
            if (legacySearch && tabs[2]) legacySearch.value = tabs[2].label || 'Search';
            if (legacyCart && tabs[3]) legacyCart.value = tabs[3].label || 'Cart';

            renderLivePreview();
        }

        const iconOptions = [
            { val: 'home', label: '🏠 Home' },
            { val: 'shop', label: '🛍️ Shop' },
            { val: 'grid', label: '🗂️ Categories' },
            { val: 'search', label: '🔍 Search' },
            { val: 'cart', label: '🛒 Cart' },
            { val: 'menu', label: '☰ Drawer Menu' },
            { val: 'user', label: '👤 Account' },
            { val: 'whatsapp', label: '💬 WhatsApp' },
            { val: 'phone', label: '📞 Phone Call' },
            { val: 'heart', label: '❤️ Wishlist' },
            { val: 'tag', label: '🏷️ Offers' },
            { val: 'truck', label: '🚚 Orders' },
            { val: 'book', label: '📖 Story' },
            { val: 'help', label: '❓ Help' }
        ];

        const actionOptions = [
            { val: 'url', label: '🔗 Direct Link / URL' },
            { val: 'search', label: '🔍 Open Search Modal' },
            { val: 'cart', label: '🛒 Open Cart Modal' },
            { val: 'drawer', label: '☰ Open 3-Dash Menu' },
            { val: 'whatsapp', label: '💬 Open WhatsApp' },
            { val: 'call', label: '📞 Direct Phone Call' }
        ];

        const badgeOptions = [
            { val: 'none', label: 'No Badge' },
            { val: 'cart_count', label: '🔴 Live Cart Count' },
            { val: 'custom', label: '🏷️ Custom Text Tag' }
        ];

        function render() {
            containerEl.innerHTML = '';

            if (tabs.length === 0) {
                containerEl.innerHTML = `
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-slate-500">
                        <p class="text-xs font-semibold text-slate-700">No bottom navigation tabs defined.</p>
                        <p class="text-[11px] text-slate-500 mt-1">Click "+ Add Nav Tab" or "Reset to Default" to build your mobile dock.</p>
                    </div>
                `;
                syncInput();
                return;
            }

            tabs.forEach((item, idx) => {
                const row = document.createElement('div');
                row.className = 'grid grid-cols-1 md:grid-cols-12 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white p-1.5 sm:px-2 shadow-2xs hover:border-slate-300 transition';

                // Action select
                let actionSelectHtml = `<select class="field text-xs !py-1 !px-2 h-7.5 w-full bg-slate-50/70 border-slate-200 bnav-action-select" data-idx="${idx}">`;
                actionOptions.forEach(opt => {
                    const sel = (item.action === opt.val) ? 'selected' : '';
                    actionSelectHtml += `<option value="${opt.val}" ${sel}>${opt.label}</option>`;
                });
                actionSelectHtml += `</select>`;

                // Icon select
                let iconSelectHtml = `<select class="field text-xs !py-1 !px-2 h-7.5 w-full bg-slate-50/70 border-slate-200 bnav-icon-select" data-idx="${idx}">`;
                iconOptions.forEach(opt => {
                    const sel = (item.icon === opt.val) ? 'selected' : '';
                    iconSelectHtml += `<option value="${opt.val}" ${sel}>${opt.label}</option>`;
                });
                iconSelectHtml += `</select>`;

                const isUrlAction = (item.action === 'url' || item.action === 'call' || !item.action);
                const isCustomBadge = (item.badge === 'custom');

                // Badge select
                let badgeSelectHtml = `<select class="field text-xs !py-1 !px-1.5 h-7.5 ${isCustomBadge ? 'w-20' : 'w-24'} bg-slate-50/70 border-slate-200 bnav-badge-select shrink-0" data-idx="${idx}">`;
                badgeOptions.forEach(opt => {
                    const sel = (item.badge === opt.val) ? 'selected' : '';
                    badgeSelectHtml += `<option value="${opt.val}" ${sel}>${opt.label}</option>`;
                });
                badgeSelectHtml += `</select>`;

                row.innerHTML = `
                    <div class="col-span-1 flex items-center gap-1 shrink-0">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[10px] font-bold text-emerald-800 border border-emerald-200">
                            ${idx + 1}
                        </span>
                        <div class="flex flex-col">
                            <button type="button" class="btn-bnav-up p-0.5 rounded hover:bg-slate-100 text-slate-500 hover:text-slate-800 text-[10px] leading-none transition cursor-pointer" data-idx="${idx}" title="Move Up" ${idx === 0 ? 'disabled style="opacity:0.25;cursor:not-allowed;"' : ''}>
                                ▲
                            </button>
                            <button type="button" class="btn-bnav-down p-0.5 rounded hover:bg-slate-100 text-slate-500 hover:text-slate-800 text-[10px] leading-none transition cursor-pointer" data-idx="${idx}" title="Move Down" ${idx === tabs.length - 1 ? 'disabled style="opacity:0.25;cursor:not-allowed;"' : ''}>
                                ▼
                            </button>
                        </div>
                    </div>

                    <div class="col-span-3 lg:col-span-2">
                        ${actionSelectHtml}
                    </div>

                    <div class="col-span-2 lg:col-span-2">
                        ${iconSelectHtml}
                    </div>

                    <div class="col-span-2 lg:col-span-2">
                        <input type="text" class="field text-xs !py-1 !px-2 h-7.5 w-full bnav-label-input" data-idx="${idx}" value="${escapeHtml(item.label)}" placeholder="Tab Label">
                    </div>

                    <div class="col-span-3 lg:col-span-3 ${isUrlAction ? '' : 'opacity-40 pointer-events-none'}">
                        <input type="text" class="field text-xs !py-1 !px-2 h-7.5 w-full bnav-url-input" data-idx="${idx}" value="${escapeHtml(item.url || '')}" placeholder="Destination URL" ${isUrlAction ? '' : 'disabled'}>
                    </div>

                    <div class="col-span-1 lg:col-span-2 flex items-center gap-1 justify-end">
                        ${badgeSelectHtml}
                        <div class="w-14 ${isCustomBadge ? '' : 'hidden'} bnav-badgetext-wrap shrink-0">
                            <input type="text" class="field text-xs !py-1 !px-1.5 h-7.5 w-full bnav-badgetext-input" data-idx="${idx}" value="${escapeHtml(item.badge_text || '')}" placeholder="Tag">
                        </div>
                        <button type="button" class="btn-bnav-delete h-7 w-7 rounded-md border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold transition flex items-center justify-center cursor-pointer shrink-0" data-idx="${idx}" title="Delete Tab">
                            ✕
                        </button>
                    </div>
                `;

                containerEl.appendChild(row);
            });

            syncInput();
        }

        function renderLivePreview() {
            if (!previewEl) return;
            const bg = bgInput ? bgInput.value : '#FFFFFF';
            const activeColor = activeColorInput ? activeColorInput.value : '#013F1E';
            const inactiveColor = inactiveColorInput ? inactiveColorInput.value : '#64748B';
            const showLabels = showLabelsToggle ? showLabelsToggle.checked : true;

            previewEl.style.backgroundColor = bg;
            previewEl.innerHTML = '';

            tabs.forEach((t, i) => {
                const isFirst = (i === 0);
                const col = isFirst ? activeColor : inactiveColor;
                const tabDiv = document.createElement('div');
                tabDiv.className = 'flex flex-1 flex-col items-center justify-center gap-0.5 text-center relative py-1';
                tabDiv.style.color = col;

                let badgeHtml = '';
                if (t.badge === 'cart_count') {
                    badgeHtml = `<span class="absolute -top-1 -right-2 bg-red-600 text-white rounded-full text-[8px] font-bold px-1 min-w-[12px] leading-tight">2</span>`;
                } else if (t.badge === 'custom' && t.badge_text) {
                    badgeHtml = `<span class="absolute -top-1 -right-2 bg-amber-500 text-white rounded-full text-[7px] font-bold px-1 leading-tight">${escapeHtml(t.badge_text)}</span>`;
                }

                const iconEmojiMap = {
                    home: '🏠', shop: '🛍️', grid: '🗂️', search: '🔍', cart: '🛒',
                    menu: '☰', user: '👤', whatsapp: '💬', phone: '📞', heart: '❤️',
                    tag: '🏷️', truck: '🚚', book: '📖', help: '❓'
                };
                const emoji = iconEmojiMap[t.icon] || '🔗';

                tabDiv.innerHTML = `
                    <div class="relative text-sm">
                        <span>${emoji}</span>
                        ${badgeHtml}
                    </div>
                    ${showLabels && t.label ? `<span class="text-[9px] font-medium leading-none truncate max-w-[50px]">${escapeHtml(t.label)}</span>` : ''}
                `;
                previewEl.appendChild(tabDiv);
            });
        }

        // Color sync helpers
        function syncColors() {
            if (bgPicker && bgInput) {
                bgPicker.addEventListener('input', () => { bgInput.value = bgPicker.value; renderLivePreview(); });
                bgInput.addEventListener('input', () => { if (/^#[0-9A-Fa-f]{6}$/.test(bgInput.value)) { bgPicker.value = bgInput.value; renderLivePreview(); } });
            }
            if (activeColorPicker && activeColorInput) {
                activeColorPicker.addEventListener('input', () => { activeColorInput.value = activeColorPicker.value; renderLivePreview(); });
                activeColorInput.addEventListener('input', () => { if (/^#[0-9A-Fa-f]{6}$/.test(activeColorInput.value)) { activeColorPicker.value = activeColorInput.value; renderLivePreview(); } });
            }
            if (inactiveColorPicker && inactiveColorInput) {
                inactiveColorPicker.addEventListener('input', () => { inactiveColorInput.value = inactiveColorPicker.value; renderLivePreview(); });
                inactiveColorInput.addEventListener('input', () => { if (/^#[0-9A-Fa-f]{6}$/.test(inactiveColorInput.value)) { inactiveColorPicker.value = inactiveColorInput.value; renderLivePreview(); } });
            }
            if (showLabelsToggle) {
                showLabelsToggle.addEventListener('change', () => { renderLivePreview(); });
            }
        }
        syncColors();

        containerEl.addEventListener('input', function (e) {
            const labelInput = e.target.closest('.bnav-label-input');
            if (labelInput) {
                const idx = parseInt(labelInput.getAttribute('data-idx'), 10);
                if (tabs[idx]) {
                    tabs[idx].label = labelInput.value;
                    syncInput();
                }
                return;
            }

            const urlInput = e.target.closest('.bnav-url-input');
            if (urlInput) {
                const idx = parseInt(urlInput.getAttribute('data-idx'), 10);
                if (tabs[idx]) {
                    tabs[idx].url = urlInput.value;
                    syncInput();
                }
                return;
            }

            const badgeTextInput = e.target.closest('.bnav-badgetext-input');
            if (badgeTextInput) {
                const idx = parseInt(badgeTextInput.getAttribute('data-idx'), 10);
                if (tabs[idx]) {
                    tabs[idx].badge_text = badgeTextInput.value;
                    syncInput();
                }
                return;
            }
        });

        containerEl.addEventListener('change', function (e) {
            const actionSelect = e.target.closest('.bnav-action-select');
            if (actionSelect) {
                const idx = parseInt(actionSelect.getAttribute('data-idx'), 10);
                if (tabs[idx]) {
                    tabs[idx].action = actionSelect.value;
                    if (actionSelect.value === 'cart') tabs[idx].icon = 'cart';
                    else if (actionSelect.value === 'search') tabs[idx].icon = 'search';
                    else if (actionSelect.value === 'drawer') tabs[idx].icon = 'menu';
                    else if (actionSelect.value === 'whatsapp') tabs[idx].icon = 'whatsapp';
                    else if (actionSelect.value === 'call') tabs[idx].icon = 'phone';
                    render();
                }
                return;
            }

            const iconSelect = e.target.closest('.bnav-icon-select');
            if (iconSelect) {
                const idx = parseInt(iconSelect.getAttribute('data-idx'), 10);
                if (tabs[idx]) {
                    tabs[idx].icon = iconSelect.value;
                    syncInput();
                }
                return;
            }

            const badgeSelect = e.target.closest('.bnav-badge-select');
            if (badgeSelect) {
                const idx = parseInt(badgeSelect.getAttribute('data-idx'), 10);
                if (tabs[idx]) {
                    tabs[idx].badge = badgeSelect.value;
                    render();
                }
                return;
            }
        });

        containerEl.addEventListener('click', function (e) {
            const delBtn = e.target.closest('.btn-bnav-delete');
            if (delBtn) {
                const idx = parseInt(delBtn.getAttribute('data-idx'), 10);
                tabs.splice(idx, 1);
                render();
                return;
            }

            const upBtn = e.target.closest('.btn-bnav-up');
            if (upBtn) {
                const idx = parseInt(upBtn.getAttribute('data-idx'), 10);
                if (idx > 0) {
                    const temp = tabs[idx];
                    tabs[idx] = tabs[idx - 1];
                    tabs[idx - 1] = temp;
                    render();
                }
                return;
            }

            const downBtn = e.target.closest('.btn-bnav-down');
            if (downBtn) {
                const idx = parseInt(downBtn.getAttribute('data-idx'), 10);
                if (idx < tabs.length - 1) {
                    const temp = tabs[idx];
                    tabs[idx] = tabs[idx + 1];
                    tabs[idx + 1] = temp;
                    render();
                }
                return;
            }
        });

        if (btnAdd) {
            btnAdd.addEventListener('click', function () {
                tabs.push({
                    label: 'New Tab',
                    url: '/shop',
                    action: 'url',
                    icon: 'shop',
                    badge: 'none',
                    badge_text: ''
                });
                render();
            });
        }

        if (btnReset) {
            btnReset.addEventListener('click', function () {
                if (confirm('Reset bottom navigation bar to standard 5-tab default layout?')) {
                    tabs = JSON.parse(JSON.stringify(defaultTabs));
                    render();
                }
            });
        }

        const customForm = document.getElementById('customization-form');
        if (customForm) {
            customForm.addEventListener('submit', function () {
                syncInput();
            });
        }

        render();
    })();

    // Dynamic Product Showcase Sections Builder
    // ==========================================
    (function () {
        const availableCategories = @json($categories ?? []);
        const availableSubcategories = @json($subcategories ?? []);
        const availableProducts = @json(($products ?? collect())->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values());
        const inputEl = document.getElementById('home_product_sections_input');
        const containerEl = document.getElementById('dynamic-sections-container');
        const addSectionBtn = document.getElementById('btn-add-new-section');

        if (!containerEl || !inputEl) return;

        let sections = [];

        try {
            if (inputEl.value && inputEl.value.trim() !== '') {
                const parsed = JSON.parse(inputEl.value);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    sections = parsed;
                }
            }
        } catch (e) {
            console.error('Failed to parse home_product_sections JSON:', e);
        }

        if (sections.length === 0) {
            const mango = availableCategories.find(c => c.slug === 'fresh-mango' || c.slug === 'mango');
            const gur = availableCategories.find(c => c.slug === 'khejur-gur' || c.slug === 'gur');
            const homemade = availableCategories.find(c => c.slug === 'homemade');

            const defaultTabs = [
                { id: 'tab_all', label: 'All', type: 'all', target_id: '' }
            ];
            if (mango) defaultTabs.push({ id: 'tab_' + mango.id, label: mango.name, type: 'category', target_id: String(mango.id) });
            if (gur) defaultTabs.push({ id: 'tab_' + gur.id, label: gur.name, type: 'category', target_id: String(gur.id) });
            if (homemade) defaultTabs.push({ id: 'tab_' + homemade.id, label: homemade.name, type: 'category', target_id: String(homemade.id) });

            sections.push({
                id: 'sec_' + Date.now(),
                is_active: true,
                tag: 'Handpicked Collection',
                title: 'Popular This Week',
                view_all_text: 'View All Products',
                view_all_url: '/shop',
                product_limit: 8,
                product_source: 'all',
                tabs: defaultTabs
            });
        }

        sections.forEach(function (sec) {
            sec.product_ids = (Array.isArray(sec.product_ids) ? sec.product_ids : [])
                .map(function (id) { return parseInt(id, 10); })
                .filter(function (id) { return id > 0; });
        });

        function syncInput() {
            inputEl.value = JSON.stringify(sections);
        }

        function productName(id) {
            const found = availableProducts.find(function (p) { return String(p.id) === String(id); });
            return found ? found.name : ('Product #' + id);
        }

        function productOptionsHtml(chosenIds, query) {
            const chosen = {};
            (chosenIds || []).forEach(function (id) { chosen[String(id)] = true; });
            const q = String(query || '').trim().toLowerCase();
            return availableProducts.filter(function (p) {
                if (chosen[String(p.id)]) return false;
                if (!q) return true;
                return String(p.name || '').toLowerCase().indexOf(q) !== -1;
            }).map(function (p) {
                return '<option value="' + p.id + '">' + escapeHtml(p.name) + '</option>';
            }).join('');
        }

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function render() {
            containerEl.innerHTML = '';

            if (sections.length === 0) {
                containerEl.innerHTML = `
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">
                        <p class="text-sm font-semibold text-slate-700">No dynamic product sections created yet.</p>
                        <p class="text-xs text-slate-500 mt-1">Click "+ Add New Section" above to create product showcases on your homepage.</p>
                    </div>
                `;
                syncInput();
                return;
            }

            sections.forEach((sec, sIdx) => {
                const card = document.createElement('div');
                card.className = 'rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:p-5 space-y-4 shadow-2xs transition hover:border-[#013F1E]/30';

                const topBar = document.createElement('div');
                topBar.className = 'flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 pb-3';
                topBar.innerHTML = `
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#013F1E] text-white text-xs font-bold shadow-2xs">
                            ${sIdx + 1}
                        </span>
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900">${escapeHtml(sec.title || 'Untitled Section')}</h4>
                            <span class="text-[10px] text-slate-500">${(sec.tabs || []).length} tabs configured</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="relative inline-flex items-center cursor-pointer mr-2">
                            <input type="checkbox" class="sr-only peer sec-active-toggle" data-sec-idx="${sIdx}" ${sec.is_active ? 'checked' : ''}>
                            <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                            <span class="ml-1.5 text-xs font-medium text-slate-700">${sec.is_active ? 'Active' : 'Inactive'}</span>
                        </label>

                        <button type="button" class="btn-move-up p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs transition cursor-pointer" data-sec-idx="${sIdx}" title="Move Up" ${sIdx === 0 ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''}>
                            ▲
                        </button>
                        <button type="button" class="btn-move-down p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs transition cursor-pointer" data-sec-idx="${sIdx}" title="Move Down" ${sIdx === sections.length - 1 ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''}>
                            ▼
                        </button>
                        <button type="button" class="btn-delete-sec p-1.5 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold transition cursor-pointer" data-sec-idx="${sIdx}" title="Delete Section">
                            ✕ Delete
                        </button>
                    </div>
                `;
                card.appendChild(topBar);

                const fieldsGrid = document.createElement('div');
                fieldsGrid.className = 'grid gap-3 sm:grid-cols-2 lg:grid-cols-3 text-xs';
                fieldsGrid.innerHTML = `
                    <div>
                        <label class="admin-label text-xs">Section Title</label>
                        <input type="text" class="field text-xs sec-input" data-sec-idx="${sIdx}" data-field="title" value="${escapeHtml(sec.title || '')}" placeholder="e.g. Trending This Week">
                    </div>
                    <div>
                        <label class="admin-label text-xs">View All Button Label</label>
                        <input type="text" class="field text-xs sec-input" data-sec-idx="${sIdx}" data-field="view_all_text" value="${escapeHtml(sec.view_all_text || '')}" placeholder="e.g. View All Products">
                    </div>
                    <div>
                        <label class="admin-label text-xs">Button Destination URL</label>
                        <input type="text" class="field text-xs sec-input" data-sec-idx="${sIdx}" data-field="view_all_url" value="${escapeHtml(sec.view_all_url || '')}" placeholder="e.g. /shop">
                    </div>
                    <div>
                        <label class="admin-label text-xs">Product Limit</label>
                        <select class="field text-xs sec-input bg-white" data-sec-idx="${sIdx}" data-field="product_limit">
                            <option value="4" ${sec.product_limit == 4 ? 'selected' : ''}>4 Products</option>
                            <option value="8" ${sec.product_limit == 8 ? 'selected' : ''}>8 Products (Default)</option>
                            <option value="12" ${sec.product_limit == 12 ? 'selected' : ''}>12 Products</option>
                            <option value="16" ${sec.product_limit == 16 ? 'selected' : ''}>16 Products</option>
                            <option value="20" ${sec.product_limit == 20 ? 'selected' : ''}>20 Products</option>
                            <option value="24" ${sec.product_limit == 24 ? 'selected' : ''}>24 Products</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label text-xs font-semibold text-slate-800">Product Source</label>
                        <select class="field text-xs sec-input bg-white sec-source-select" data-sec-idx="${sIdx}" data-field="product_source">
                            <option value="manual" ${sec.product_source === 'manual' ? 'selected' : ''}>Custom Order</option>
                            <option value="category" ${sec.product_source === 'category' ? 'selected' : ''}>Category Based</option>
                            <option value="best_selling" ${sec.product_source === 'best_selling' ? 'selected' : ''}>Best Selling</option>
                            <option value="featured" ${sec.product_source === 'featured' ? 'selected' : ''}>Featured Products</option>
                            <option value="all" ${(sec.product_source || 'all') === 'all' ? 'selected' : ''}>All Products</option>
                        </select>
                    </div>
                    <div class="sec-cat-wrap-${sIdx} sm:col-span-2 lg:col-span-3 rounded-xl bg-emerald-50/70 border border-emerald-200/80 p-3" style="${sec.product_source === 'category' ? '' : 'display:none;'}">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex-1">
                                <label class="admin-label text-xs font-bold text-emerald-900 !mb-1 flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>Select Target Category</span>
                                </label>
                                <p class="text-[11px] text-emerald-700">Only products from this selected category will be displayed in this section.</p>
                            </div>
                            <div class="w-full sm:w-64">
                                <select class="field text-xs sec-input bg-white border-emerald-300 font-semibold text-emerald-950 sec-target-cat" data-sec-idx="${sIdx}" data-field="source_category_id">
                                    <option value="">-- Select Category --</option>
                                    ${availableCategories.map(c => `<option value="${c.id}" ${String(sec.source_category_id || '') === String(c.id) ? 'selected' : ''} data-name="${escapeHtml(c.name)}" data-slug="${escapeHtml(c.slug)}">${escapeHtml(c.name)}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="sec-manual-box sm:col-span-2 lg:col-span-3 rounded-xl bg-white border border-slate-200 p-3 space-y-3" style="${sec.product_source === 'manual' ? '' : 'display:none;'}">
                        <div>
                            <p class="text-xs font-bold text-slate-800">Product order</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Add products, then move them up or down. The top one shows first. Product Limit still decides how many are visible. Use an “All” tab if you want this exact order on the homepage.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" class="field text-xs prod-search flex-1" data-sec-idx="${sIdx}" placeholder="Search product name">
                            <select class="field text-xs bg-white prod-pick sm:w-72" data-sec-idx="${sIdx}">
                                <option value="">-- Select product --</option>
                                ${productOptionsHtml(sec.product_ids || [])}
                            </select>
                            <button type="button" class="btn-add-product admin-btn-primary !px-3 !py-2 text-xs font-semibold shrink-0 cursor-pointer" data-sec-idx="${sIdx}">+ Add</button>
                        </div>
                        <div class="space-y-1.5">
                            ${(sec.product_ids || []).length ? (sec.product_ids || []).map((pid, pIdx) => `
                                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#013F1E] text-white text-[10px] font-bold">${pIdx + 1}</span>
                                    <span class="flex-1 text-xs font-semibold text-slate-800 truncate">${escapeHtml(productName(pid))}</span>
                                    <button type="button" class="btn-prod-up px-1.5 py-1 rounded border border-slate-200 bg-white text-[11px] cursor-pointer" data-sec-idx="${sIdx}" data-prod-idx="${pIdx}" ${pIdx === 0 ? 'disabled style="opacity:.4;cursor:not-allowed;"' : ''}>▲</button>
                                    <button type="button" class="btn-prod-down px-1.5 py-1 rounded border border-slate-200 bg-white text-[11px] cursor-pointer" data-sec-idx="${sIdx}" data-prod-idx="${pIdx}" ${pIdx === (sec.product_ids.length - 1) ? 'disabled style="opacity:.4;cursor:not-allowed;"' : ''}>▼</button>
                                    <button type="button" class="btn-prod-remove px-1.5 py-1 rounded border border-red-200 bg-red-50 text-red-600 text-[11px] font-bold cursor-pointer" data-sec-idx="${sIdx}" data-prod-idx="${pIdx}">✕</button>
                                </div>
                            `).join('') : '<p class="text-xs text-slate-400 italic">No products picked yet. This section stays empty until you add some.</p>'}
                        </div>
                    </div>
                `;
                card.appendChild(fieldsGrid);

                const tabsBox = document.createElement('div');
                tabsBox.className = 'rounded-xl border border-slate-200/80 bg-white p-3 sm:p-4 space-y-3';

                const tabsListHtml = (sec.tabs || []).map((tab, tIdx) => {
                    let typeBadge = '<span class="rounded bg-slate-200 text-slate-700 px-1.5 py-0.5 text-[9px] font-bold uppercase">ALL</span>';
                    if (tab.type === 'category') {
                        typeBadge = '<span class="rounded bg-emerald-100 text-emerald-800 px-1.5 py-0.5 text-[9px] font-bold uppercase">CATEGORY</span>';
                    } else if (tab.type === 'subcategory') {
                        typeBadge = '<span class="rounded bg-blue-100 text-blue-800 px-1.5 py-0.5 text-[9px] font-bold uppercase">SUBCATEGORY</span>';
                    }

                    return `
                        <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow-2xs">
                            ${typeBadge}
                            <span>${escapeHtml(tab.label)}</span>
                            <button type="button" class="btn-remove-tab text-slate-400 hover:text-red-600 transition ml-1" data-sec-idx="${sIdx}" data-tab-idx="${tIdx}" title="Remove Tab">&times;</button>
                        </div>
                    `;
                }).join('');

                const isCatSource = (sec.product_source === 'category');
                const filteredSubcats = (isCatSource && sec.source_category_id)
                    ? availableSubcategories.filter(s => String(s.category_id) === String(sec.source_category_id))
                    : availableSubcategories;

                const catOptions = availableCategories.map(c => `<option value="${c.id}" data-name="${escapeHtml(c.name)}">${escapeHtml(c.name)}</option>`).join('');
                const subcatOptions = filteredSubcats.map(s => {
                    const parentName = (!isCatSource && s.category) ? `(${s.category.name}) ` : '';
                    return `<option value="${s.id}" data-name="${escapeHtml(s.name)}">${parentName}${escapeHtml(s.name)}</option>`;
                }).join('');

                tabsBox.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-[#013F1E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span>Filter Tabs (Category & Subcategory)</span>
                        </span>
                        <span class="text-[11px] text-slate-500">Visitors can click these tabs on the storefront to filter products</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 min-h-[32px]">
                        ${tabsListHtml || '<span class="text-xs text-slate-400 italic">No tabs added yet. Use the form below to add filter tabs.</span>'}
                    </div>

                    <div class="pt-2 border-t border-slate-200/80">
                        <div class="flex flex-wrap items-end gap-2 text-xs">
                            <div class="w-32">
                                <label class="admin-label text-[11px] !mb-1">Tab Type</label>
                                <select class="field text-xs bg-white tab-type-select" data-sec-idx="${sIdx}">
                                    ${isCatSource ? `
                                        <option value="all">All Products (All)</option>
                                        <option value="subcategory">Subcategory</option>
                                    ` : `
                                        <option value="category">Category</option>
                                        <option value="subcategory">Subcategory</option>
                                        <option value="all">All Products (All)</option>
                                    `}
                                </select>
                            </div>

                            <div class="w-48 tab-target-container" data-sec-idx="${sIdx}" style="${isCatSource ? 'display:none;' : ''}">
                                <label class="admin-label text-[11px] !mb-1 tab-target-label">Target Category</label>
                                <select class="field text-xs bg-white tab-target-select" data-sec-idx="${sIdx}">
                                    <option value="">-- Select --</option>
                                    ${catOptions}
                                </select>
                            </div>

                            <div class="flex-1 min-w-[140px]">
                                <label class="admin-label text-[11px] !mb-1">Tab Label</label>
                                <input type="text" class="field text-xs tab-label-input" data-sec-idx="${sIdx}" value="${isCatSource ? 'All' : ''}" placeholder="Label shown on tab">
                            </div>

                            <button type="button" class="btn-add-tab admin-btn-primary !px-3 !py-2 text-xs font-semibold inline-flex items-center gap-1 shrink-0 cursor-pointer" data-sec-idx="${sIdx}">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <span>+ Add Tab</span>
                            </button>
                        </div>
                    </div>
                `;
                card.appendChild(tabsBox);

                containerEl.appendChild(card);
            });

            syncInput();
        }

        containerEl.addEventListener('input', function (e) {
            const search = e.target.closest('.prod-search');
            if (search) {
                const sIdx = parseInt(search.getAttribute('data-sec-idx'), 10);
                const box = search.closest('.sec-manual-box');
                const pick = box ? box.querySelector('.prod-pick') : null;
                if (pick && sections[sIdx]) {
                    pick.innerHTML = '<option value="">-- Select product --</option>' + productOptionsHtml(sections[sIdx].product_ids || [], search.value);
                }
                return;
            }

            const secInput = e.target.closest('.sec-input');
            if (secInput) {
                const sIdx = parseInt(secInput.getAttribute('data-sec-idx'), 10);
                const field = secInput.getAttribute('data-field');
                if (sections[sIdx]) {
                    sections[sIdx][field] = secInput.value;
                    syncInput();
                }
            }
        });

        containerEl.addEventListener('change', function (e) {
            const toggle = e.target.closest('.sec-active-toggle');
            if (toggle) {
                const sIdx = parseInt(toggle.getAttribute('data-sec-idx'), 10);
                if (sections[sIdx]) {
                    sections[sIdx].is_active = toggle.checked;
                    syncInput();
                    render();
                }
                return;
            }

            const sourceSelect = e.target.closest('.sec-source-select');
            if (sourceSelect) {
                const sIdx = parseInt(sourceSelect.getAttribute('data-sec-idx'), 10);
                const val = sourceSelect.value;
                if (sections[sIdx]) {
                    sections[sIdx].product_source = val;
                    if (val === 'category' && !sections[sIdx].source_category_id && availableCategories.length > 0) {
                        const firstCat = availableCategories[0];
                        sections[sIdx].source_category_id = String(firstCat.id);
                        if (!sections[sIdx].title || sections[sIdx].title === 'Popular This Week' || sections[sIdx].title === 'Best Selling Collection') {
                            sections[sIdx].title = firstCat.name + ' Collection';
                        }
                        if (!sections[sIdx].view_all_text || sections[sIdx].view_all_text === 'View All Products' || sections[sIdx].view_all_text === 'View All') {
                            sections[sIdx].view_all_text = 'View All ' + firstCat.name;
                        }
                        if (!sections[sIdx].view_all_url || sections[sIdx].view_all_url === '/shop') {
                            sections[sIdx].view_all_url = '/shop?category=' + (firstCat.slug || '');
                        }
                    }
                    syncInput();
                    render();
                }
                return;
            }

            const targetCatSelect = e.target.closest('.sec-target-cat');
            if (targetCatSelect) {
                const sIdx = parseInt(targetCatSelect.getAttribute('data-sec-idx'), 10);
                const catId = targetCatSelect.value;
                if (sections[sIdx]) {
                    sections[sIdx].source_category_id = catId;
                    const selectedOpt = targetCatSelect.options[targetCatSelect.selectedIndex];
                    if (selectedOpt && catId) {
                        const catName = selectedOpt.getAttribute('data-name');
                        const catSlug = selectedOpt.getAttribute('data-slug');
                        if (!sections[sIdx].title || sections[sIdx].title === 'Popular This Week' || sections[sIdx].title === 'Best Selling Collection') {
                            sections[sIdx].title = catName + ' Collection';
                        }
                        if (!sections[sIdx].view_all_text || sections[sIdx].view_all_text === 'View All Products' || sections[sIdx].view_all_text === 'View All') {
                            sections[sIdx].view_all_text = 'View All ' + catName;
                        }
                        if (!sections[sIdx].view_all_url || sections[sIdx].view_all_url === '/shop') {
                            sections[sIdx].view_all_url = '/shop?category=' + catSlug;
                        }
                    }
                    syncInput();
                    render();
                }
                return;
            }

            const typeSelect = e.target.closest('.tab-type-select');
            if (typeSelect) {
                const sIdx = parseInt(typeSelect.getAttribute('data-sec-idx'), 10);
                const card = typeSelect.closest('.rounded-2xl');
                const targetContainer = card.querySelector('.tab-target-container');
                const targetLabel = card.querySelector('.tab-target-label');
                const targetSelect = card.querySelector('.tab-target-select');
                const labelInput = card.querySelector('.tab-label-input');

                const isSecCatSource = (sections[sIdx] && sections[sIdx].product_source === 'category');
                const relevantSubcats = (isSecCatSource && sections[sIdx].source_category_id)
                    ? availableSubcategories.filter(s => String(s.category_id) === String(sections[sIdx].source_category_id))
                    : availableSubcategories;

                const val = typeSelect.value;
                if (val === 'all') {
                    targetContainer.style.display = 'none';
                    labelInput.value = isSecCatSource ? 'All' : 'All';
                } else if (val === 'category') {
                    targetContainer.style.display = 'block';
                    targetLabel.textContent = 'Target Category';
                    targetSelect.innerHTML = '<option value="">-- Select --</option>' + availableCategories.map(c => `<option value="${c.id}" data-name="${escapeHtml(c.name)}">${escapeHtml(c.name)}</option>`).join('');
                    labelInput.value = '';
                } else if (val === 'subcategory') {
                    targetContainer.style.display = 'block';
                    targetLabel.textContent = 'Target Subcategory';
                    targetSelect.innerHTML = '<option value="">-- Select --</option>' + relevantSubcats.map(s => {
                        const parentName = (!isSecCatSource && s.category) ? `(${s.category.name}) ` : '';
                        return `<option value="${s.id}" data-name="${escapeHtml(s.name)}">${parentName}${escapeHtml(s.name)}</option>`;
                    }).join('');
                    labelInput.value = '';
                }
                return;
            }

            const targetSelect = e.target.closest('.tab-target-select');
            if (targetSelect) {
                const card = targetSelect.closest('.rounded-2xl');
                const labelInput = card.querySelector('.tab-label-input');
                const selectedOpt = targetSelect.options[targetSelect.selectedIndex];
                if (selectedOpt && selectedOpt.getAttribute('data-name')) {
                    labelInput.value = selectedOpt.getAttribute('data-name');
                }
            }
        });

        containerEl.addEventListener('click', function (e) {
            const delBtn = e.target.closest('.btn-delete-sec');
            if (delBtn) {
                const sIdx = parseInt(delBtn.getAttribute('data-sec-idx'), 10);
                if (confirm('Are you sure you want to delete this product section?')) {
                    sections.splice(sIdx, 1);
                    render();
                }
                return;
            }

            const upBtn = e.target.closest('.btn-move-up');
            if (upBtn) {
                const sIdx = parseInt(upBtn.getAttribute('data-sec-idx'), 10);
                if (sIdx > 0) {
                    const temp = sections[sIdx];
                    sections[sIdx] = sections[sIdx - 1];
                    sections[sIdx - 1] = temp;
                    render();
                }
                return;
            }

            const downBtn = e.target.closest('.btn-move-down');
            if (downBtn) {
                const sIdx = parseInt(downBtn.getAttribute('data-sec-idx'), 10);
                if (sIdx < sections.length - 1) {
                    const temp = sections[sIdx];
                    sections[sIdx] = sections[sIdx + 1];
                    sections[sIdx + 1] = temp;
                    render();
                }
                return;
            }

            const remTabBtn = e.target.closest('.btn-remove-tab');
            if (remTabBtn) {
                const sIdx = parseInt(remTabBtn.getAttribute('data-sec-idx'), 10);
                const tIdx = parseInt(remTabBtn.getAttribute('data-tab-idx'), 10);
                if (sections[sIdx] && sections[sIdx].tabs) {
                    sections[sIdx].tabs.splice(tIdx, 1);
                    render();
                }
                return;
            }

            const addProdBtn = e.target.closest('.btn-add-product');
            if (addProdBtn) {
                const sIdx = parseInt(addProdBtn.getAttribute('data-sec-idx'), 10);
                const box = addProdBtn.closest('.sec-manual-box');
                const pick = box ? box.querySelector('.prod-pick') : null;
                const pid = pick ? parseInt(pick.value, 10) : 0;
                if (!sections[sIdx] || !pid) {
                    return;
                }
                if (!Array.isArray(sections[sIdx].product_ids)) {
                    sections[sIdx].product_ids = [];
                }
                if (sections[sIdx].product_ids.indexOf(pid) === -1 && sections[sIdx].product_ids.length < 36) {
                    sections[sIdx].product_ids.push(pid);
                    render();
                }
                return;
            }

            const prodUp = e.target.closest('.btn-prod-up');
            if (prodUp) {
                const sIdx = parseInt(prodUp.getAttribute('data-sec-idx'), 10);
                const pIdx = parseInt(prodUp.getAttribute('data-prod-idx'), 10);
                const ids = sections[sIdx] && sections[sIdx].product_ids;
                if (ids && pIdx > 0) {
                    const temp = ids[pIdx];
                    ids[pIdx] = ids[pIdx - 1];
                    ids[pIdx - 1] = temp;
                    render();
                }
                return;
            }

            const prodDown = e.target.closest('.btn-prod-down');
            if (prodDown) {
                const sIdx = parseInt(prodDown.getAttribute('data-sec-idx'), 10);
                const pIdx = parseInt(prodDown.getAttribute('data-prod-idx'), 10);
                const ids = sections[sIdx] && sections[sIdx].product_ids;
                if (ids && pIdx < ids.length - 1) {
                    const temp = ids[pIdx];
                    ids[pIdx] = ids[pIdx + 1];
                    ids[pIdx + 1] = temp;
                    render();
                }
                return;
            }

            const prodRemove = e.target.closest('.btn-prod-remove');
            if (prodRemove) {
                const sIdx = parseInt(prodRemove.getAttribute('data-sec-idx'), 10);
                const pIdx = parseInt(prodRemove.getAttribute('data-prod-idx'), 10);
                if (sections[sIdx] && Array.isArray(sections[sIdx].product_ids)) {
                    sections[sIdx].product_ids.splice(pIdx, 1);
                    render();
                }
                return;
            }

            const addTabBtn = e.target.closest('.btn-add-tab');
            if (addTabBtn) {
                const sIdx = parseInt(addTabBtn.getAttribute('data-sec-idx'), 10);
                const card = addTabBtn.closest('.rounded-2xl');
                const typeSelect = card.querySelector('.tab-type-select');
                const targetSelect = card.querySelector('.tab-target-select');
                const labelInput = card.querySelector('.tab-label-input');

                const type = typeSelect ? typeSelect.value : 'all';
                let targetId = targetSelect ? targetSelect.value : '';
                let label = labelInput ? labelInput.value.trim() : '';

                if (type !== 'all' && !targetId) {
                    alert('Please select a category or subcategory.');
                    return;
                }

                if (!label) {
                    label = type === 'all' ? 'All' : 'Tab';
                }

                if (!sections[sIdx].tabs) {
                    sections[sIdx].tabs = [];
                }

                sections[sIdx].tabs.push({
                    id: 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                    label: label,
                    type: type,
                    target_id: String(targetId || '')
                });

                render();
                return;
            }
        });

        if (addSectionBtn) {
            addSectionBtn.addEventListener('click', function () {
                const firstCat = availableCategories.length > 0 ? availableCategories[0] : null;
                sections.push({
                    id: 'sec_' + Date.now(),
                    is_active: true,
                    tag: 'Curated Products',
                    title: firstCat ? (firstCat.name + ' Collection') : 'Best Selling Collection',
                    view_all_text: firstCat ? ('View All ' + firstCat.name) : 'View All Products',
                    view_all_url: firstCat ? ('/shop?category=' + (firstCat.slug || '')) : '/shop',
                    product_limit: 8,
                    product_source: 'category',
                    source_category_id: firstCat ? String(firstCat.id) : '',
                    tabs: [
                        { id: 'tab_all_' + Date.now(), label: 'All', type: 'all', target_id: '' }
                    ]
                });
                render();
            });
        }

        const customForm = document.getElementById('customization-form');
        if (customForm) {
            customForm.addEventListener('submit', function () {
                syncInput();
            });
        }

        render();
    })();

    // --- Homepage Sections Drag & Drop Reorder Manager ---
    (function () {
        const reorderList = document.getElementById('home-sections-reorder-list');
        const orderInput = document.getElementById('home_sections_order_input');
        const cardsContainer = document.getElementById('home-sections-cards-container');
        if (!reorderList || !orderInput) return;

        function syncSectionsOrder() {
            const items = reorderList.querySelectorAll('.section-reorder-item');
            const newOrder = [];

            items.forEach((item, index) => {
                const key = item.getAttribute('data-section-key');
                if (key) newOrder.push(key);

                // Update number badge
                const badge = item.querySelector('.sec-pos-badge');
                if (badge) badge.textContent = index + 1;
            });

            orderInput.value = JSON.stringify(newOrder);

            // Reorder the DOM cards in container to match exactly
            if (cardsContainer) {
                newOrder.forEach(key => {
                    const card = cardsContainer.querySelector(`[data-section-card="${key}"]`);
                    if (card) {
                        cardsContainer.appendChild(card);
                    }
                });
            }
        }

        let draggedItem = null;

        reorderList.addEventListener('dragstart', function (e) {
            const item = e.target.closest('.section-reorder-item');
            if (!item) return;
            draggedItem = item;
            e.dataTransfer.effectAllowed = 'move';
            item.classList.add('opacity-40', 'border-emerald-500', 'bg-emerald-50/50');
        });

        reorderList.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const target = e.target.closest('.section-reorder-item');
            if (target && target !== draggedItem) {
                const rect = target.getBoundingClientRect();
                const isAfter = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
                if (isAfter) {
                    target.after(draggedItem);
                } else {
                    target.before(draggedItem);
                }
            }
        });

        reorderList.addEventListener('dragend', function (e) {
            const item = e.target.closest('.section-reorder-item');
            if (item) {
                item.classList.remove('opacity-40', 'border-emerald-500', 'bg-emerald-50/50');
            }
            draggedItem = null;
            syncSectionsOrder();
        });

        // Up and Down button click handlers
        reorderList.addEventListener('click', function (e) {
            const upBtn = e.target.closest('.btn-sec-up');
            const downBtn = e.target.closest('.btn-sec-down');

            if (upBtn) {
                const item = upBtn.closest('.section-reorder-item');
                if (item && item.previousElementSibling) {
                    item.parentNode.insertBefore(item, item.previousElementSibling);
                    syncSectionsOrder();
                }
            } else if (downBtn) {
                const item = downBtn.closest('.section-reorder-item');
                if (item && item.nextElementSibling) {
                    item.parentNode.insertBefore(item.nextElementSibling, item);
                    syncSectionsOrder();
                }
            }
        });

        // Reset default order button
        const resetBtn = document.getElementById('btn-reset-sections-order');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                const defaultKeys = ['hero', 'stories', 'categories', 'dynamic_sections', 'promo_banner', 'featured', 'story_reel', 'promises', 'faq'];
                defaultKeys.forEach(k => {
                    const item = reorderList.querySelector(`[data-section-key="${k}"]`);
                    if (item) reorderList.appendChild(item);
                });
                syncSectionsOrder();
            });
        }

        // Live update status badge when toggling checkboxes in section cards
        const toggleMap = {
            'home_show_stories': 'stories',
            'home_show_categories': 'categories',
            'home_show_featured': 'featured',
            'home_show_story_section': 'story_reel',
            'home_show_promises': 'promises',
            'home_show_faq': 'faq',
            'home_show_hero': 'hero',
            'home_show_promo_banner': 'promo_banner',
        };

        Object.entries(toggleMap).forEach(([inputName, secKey]) => {
            const checkbox = document.querySelector(`input[type="checkbox"][name="${inputName}"]`);
            if (checkbox) {
                checkbox.addEventListener('change', function () {
                    const badge = document.querySelector(`[data-sec-status="${secKey}"]`);
                    if (badge) {
                        if (this.checked) {
                            badge.textContent = 'Active';
                            badge.className = 'sec-status-badge text-[10px] sm:text-[11px] px-2 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800';
                        } else {
                            badge.textContent = 'Hidden';
                            badge.className = 'sec-status-badge text-[10px] sm:text-[11px] px-2 py-0.5 rounded-full font-bold bg-slate-100 text-slate-500';
                        }
                    }
                });
            }
        });

        // Initial sync on load to align DOM cards with saved order
        syncSectionsOrder();
    })();

    // Hero Banner Image Slider Manager
    (function () {
        const inputEl = document.getElementById('hero_slides_input');
        const listEl = document.getElementById('hero-slides-list');
        const emptyEl = document.getElementById('hero-slides-empty');
        const counterEl = document.getElementById('hero-slides-counter');
        const dropzoneBox = document.getElementById('hero-slide-dropzone-box');
        const fileInput = document.getElementById('hero-slide-file-input');
        const progressEl = document.getElementById('hero-upload-progress');

        if (!inputEl || !listEl) return;

        let slides = [];
        try {
            slides = JSON.parse(inputEl.value || '[]');
            if (!Array.isArray(slides)) slides = [];
        } catch (e) {
            slides = [];
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getImageUrl(imgPath) {
            if (!imgPath) return '';
            if (imgPath.startsWith('http://') || imgPath.startsWith('https://') || imgPath.startsWith('/')) {
                return imgPath;
            }
            return '/storage/' + imgPath.replace(/^storage\//, '');
        }

        function syncInput() {
            inputEl.value = JSON.stringify(slides);
            if (counterEl) {
                const activeCount = slides.filter(s => s.is_active !== false).length;
                counterEl.textContent = `${slides.length} Slide${slides.length === 1 ? '' : 's'} (${activeCount} Active)`;
            }
            if (emptyEl) {
                if (slides.length === 0) {
                    emptyEl.classList.remove('hidden');
                } else {
                    emptyEl.classList.add('hidden');
                }
            }
        }

        function renderSlides() {
            listEl.innerHTML = '';

            slides.forEach((slide, idx) => {
                const card = document.createElement('div');
                card.className = 'slide-item rounded-xl border border-slate-200 bg-white p-3 sm:p-4 shadow-2xs hover:shadow-xs transition';
                card.setAttribute('data-slide-index', idx);

                const isActive = slide.is_active !== false;
                const imgUrl = getImageUrl(slide.image);

                card.innerHTML = `
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                        <div class="flex items-center gap-2 self-stretch sm:self-auto shrink-0 justify-between sm:justify-start">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                                #${idx + 1}
                            </span>
                            <button type="button" class="btn-slide-replace relative w-28 h-16 sm:w-36 sm:h-20 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 shrink-0 shadow-2xs cursor-pointer" data-index="${idx}" title="Replace image">
                                <img src="${escapeHtml(imgUrl)}" alt="Slide ${idx + 1}" class="w-full h-full object-cover pointer-events-none">
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 flex-1 w-full">
                            <div>
                                <label class="admin-label text-[11px] !mb-1">Title / Alt Text (Optional)</label>
                                <input type="text" class="field text-xs slide-field-title" data-index="${idx}" value="${escapeHtml(slide.title || '')}" placeholder="e.g. Summer Special Promotion">
                            </div>
                            <div>
                                <label class="admin-label text-[11px] !mb-1">Destination URL (Optional)</label>
                                <input type="text" class="field text-xs slide-field-link" data-index="${idx}" value="${escapeHtml(slide.link_url || '')}" placeholder="e.g. /shop or /products/item-slug">
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 self-end sm:self-center shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 w-full sm:w-auto justify-between sm:justify-start">
                            <label class="relative inline-flex items-center cursor-pointer mr-1" title="Toggle active status">
                                <input type="checkbox" class="sr-only peer slide-active-toggle" data-index="${idx}" ${isActive ? 'checked' : ''}>
                                <div class="w-8 h-4.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>

                            <button type="button" class="btn-slide-replace inline-flex items-center px-2 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-semibold transition cursor-pointer" data-index="${idx}" title="Replace image from media">Replace</button>

                            <button type="button" class="btn-slide-up p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed" ${idx === 0 ? 'disabled' : ''} title="Move Up">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                </svg>
                            </button>

                            <button type="button" class="btn-slide-down p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed" ${idx === slides.length - 1 ? 'disabled' : ''} title="Move Down">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <button type="button" class="btn-slide-delete p-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 text-rose-600 transition cursor-pointer" data-index="${idx}" title="Delete Slide">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                `;

                listEl.appendChild(card);
            });

            syncInput();
        }

        // Delegated events for title, link, active toggle, reorder, delete
        listEl.addEventListener('input', function (e) {
            const titleInput = e.target.closest('.slide-field-title');
            const linkInput = e.target.closest('.slide-field-link');

            if (titleInput) {
                const idx = parseInt(titleInput.getAttribute('data-index'), 10);
                if (slides[idx]) {
                    slides[idx].title = titleInput.value;
                    syncInput();
                }
            } else if (linkInput) {
                const idx = parseInt(linkInput.getAttribute('data-index'), 10);
                if (slides[idx]) {
                    slides[idx].link_url = linkInput.value;
                    syncInput();
                }
            }
        });

        listEl.addEventListener('change', function (e) {
            const activeToggle = e.target.closest('.slide-active-toggle');
            if (activeToggle) {
                const idx = parseInt(activeToggle.getAttribute('data-index'), 10);
                if (slides[idx]) {
                    slides[idx].is_active = activeToggle.checked;
                    syncInput();
                }
            }
        });

        listEl.addEventListener('click', function (e) {
            const replaceBtn = e.target.closest('.btn-slide-replace');
            const upBtn = e.target.closest('.btn-slide-up');
            const downBtn = e.target.closest('.btn-slide-down');
            const delBtn = e.target.closest('.btn-slide-delete');

            if (replaceBtn) {
                const idx = parseInt(replaceBtn.getAttribute('data-index'), 10);
                openHeroMediaPicker(function (item) {
                    const path = mediaImagePath(item);
                    if (!path || !slides[idx]) return;
                    slides[idx].image = path;
                    renderSlides();
                }, false);
            } else if (upBtn) {
                const card = upBtn.closest('.slide-item');
                const idx = parseInt(card.getAttribute('data-slide-index'), 10);
                if (idx > 0) {
                    const temp = slides[idx];
                    slides[idx] = slides[idx - 1];
                    slides[idx - 1] = temp;
                    renderSlides();
                }
            } else if (downBtn) {
                const card = downBtn.closest('.slide-item');
                const idx = parseInt(card.getAttribute('data-slide-index'), 10);
                if (idx < slides.length - 1) {
                    const temp = slides[idx];
                    slides[idx] = slides[idx + 1];
                    slides[idx + 1] = temp;
                    renderSlides();
                }
            } else if (delBtn) {
                const idx = parseInt(delBtn.getAttribute('data-index'), 10);
                if (confirm('Are you sure you want to remove this slide?')) {
                    slides.splice(idx, 1);
                    renderSlides();
                }
            }
        });

        function mediaImagePath(item) {
            if (!item) return '';
            if (item.path) {
                return String(item.path).replace(/^\/+/, '').replace(/^storage\//, '');
            }
            return item.url || '';
        }

        function addSlidesFromMedia(items) {
            const list = Array.isArray(items) ? items : [items];
            list.forEach(function (item) {
                if (!item || item.is_image === false) return;
                const path = mediaImagePath(item);
                if (!path) return;
                const rawName = item.name || 'Banner';
                slides.push({
                    id: 'slide_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                    image: path,
                    title: rawName.replace(/\.[^/.]+$/, ''),
                    link_url: '/shop',
                    is_active: true
                });
            });
            renderSlides();
        }

        function openHeroMediaPicker(onSelect, multiple) {
            if (typeof window.openMediaPicker !== 'function') {
                if (fileInput) fileInput.click();
                return;
            }
            window.openMediaPicker({
                multiple: Boolean(multiple),
                initialTab: 'library',
                filter: 'image',
                title: multiple ? 'Add banner slides' : 'Replace banner image',
                buttonText: multiple ? 'Add slides' : 'Replace image',
                onSelect: onSelect
            });
        }

        // Dropzone & File Upload Handling
        if (dropzoneBox && fileInput) {
            dropzoneBox.addEventListener('click', function () {
                openHeroMediaPicker(addSlidesFromMedia, true);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzoneBox.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzoneBox.classList.add('border-indigo-500', 'bg-indigo-50/20');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzoneBox.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzoneBox.classList.remove('border-indigo-500', 'bg-indigo-50/20');
                });
            });

            dropzoneBox.addEventListener('drop', function (e) {
                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    handleFilesUpload(files);
                }
            });

            fileInput.addEventListener('click', function (e) {
                e.stopPropagation();
            });

            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files.length > 0) {
                    handleFilesUpload(fileInput.files);
                }
            });
        }

        async function handleFilesUpload(fileList) {
            if (progressEl) progressEl.classList.remove('hidden');

            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                if (!file.type.startsWith('image/')) continue;

                const formData = new FormData();
                formData.append('image', file);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch('{{ route('admin.customization.upload-hero-slide') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                        }
                    });

                    const res = await response.json();
                    if (response.ok && res.ok && res.path) {
                        slides.push({
                            id: 'slide_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                            image: res.path,
                            title: file.name.replace(/\.[^/.]+$/, ''),
                            link_url: '/shop',
                            is_active: true
                        });
                        renderSlides();
                    } else {
                        alert(res.message || 'Failed to upload slide image.');
                    }
                } catch (err) {
                    console.error('Upload Error:', err);
                    alert('Network error while uploading slide.');
                }
            }

            if (fileInput) fileInput.value = '';
            if (progressEl) progressEl.classList.add('hidden');
        }

        renderSlides();
    })();
</script>
@endpush
@endsection