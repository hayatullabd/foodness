<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $defaultTitle = $seo['site_title'] ?? $store['store_name'] ?? config('app.name', 'Store');
        $storeLogo = ! empty($store['store_logo']) && file_exists(public_path('storage/'.$store['store_logo']))
            ? asset('storage/'.$store['store_logo'])
            : null;
        $storeFavicon = ! empty($store['site_favicon']) && (\Illuminate\Support\Facades\Storage::disk('public')->exists($store['site_favicon']) || file_exists(public_path('storage/'.$store['site_favicon'])))
            ? asset('storage/'.$store['site_favicon'])
            : (file_exists(public_path('favicon.ico')) ? asset('favicon.ico') : null);
        $customChatAvatar = ! empty($store['chat_avatar']) && (\Illuminate\Support\Facades\Storage::disk('public')->exists($store['chat_avatar']) || file_exists(public_path('storage/'.$store['chat_avatar'])))
            ? asset('storage/'.$store['chat_avatar'])
            : null;
        $chatAvatar = $customChatAvatar ?? $storeFavicon;
        $categoriesList = ! empty($storeCategories) ? $storeCategories : \App\Models\Category::query()->active()->orderBy('sort_order')->get();
    @endphp
    <title>@yield('title', $defaultTitle)</title>
    @if (! empty($seo['meta_description']))
        <meta name="description" content="{{ $seo['meta_description'] }}">
    @endif
    @if (! empty($seo['meta_keywords']))
        <meta name="keywords" content="{{ $seo['meta_keywords'] }}">
    @endif
    @if (! empty($seo['google_site_verification']))
        <meta name="google-site-verification" content="{{ $seo['google_site_verification'] }}">
    @endif
    @hasSection('meta')
        @yield('meta')
    @else
        @if (! empty($seo['site_title']))
            <meta property="og:title" content="{{ $seo['site_title'] }}">
        @endif
        @if (! empty($seo['meta_description']))
            <meta property="og:description" content="{{ $seo['meta_description'] }}">
        @endif
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
    @endif
    @fonts
    @if (! empty($store['site_favicon']) && file_exists(public_path('storage/'.$store['site_favicon'])))
        <link rel="icon" href="{{ asset('storage/'.$store['site_favicon']) }}?v={{ filemtime(public_path('storage/'.$store['site_favicon'])) }}">
        <link rel="shortcut icon" href="{{ asset('storage/'.$store['site_favicon']) }}?v={{ filemtime(public_path('storage/'.$store['site_favicon'])) }}">
        <link rel="apple-touch-icon" href="{{ asset('storage/'.$store['site_favicon']) }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Galada&family=Manrope:wght@700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&family=Noto+Serif+Bengali:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --color-canvas: {{ $store['theme_canvas_color'] ?? '#F7EDDE' }};
            --color-card: {{ $store['theme_card_color'] ?? '#ffffff' }};
            --color-primary: {{ $store['theme_primary_color'] ?? '#1C4F38' }};
            --color-style-green: {{ $store['theme_primary_color'] ?? '#1C4F38' }};
            --color-sage: {{ $store['theme_primary_color'] ?? '#1C4F38' }};
            --color-accent: {{ $store['theme_accent_color'] ?? '#A5000B' }};
            --color-amar-red: {{ $store['theme_accent_color'] ?? '#A5000B' }};
            --color-gold: {{ $store['theme_gold_color'] ?? '#D9822B' }};
            --color-ink: {{ $store['theme_ink_color'] ?? '#0d2600' }};
            --color-primary-dark: #163f2d;
            --color-primary-light: #e8f2ed;
            --color-accent-dark: #820008;
            --color-accent-light: #fde8ea;
        }
        .store-auth {
            --color-accent: #1c4f38;
            --color-accent-deep: #163f2d;
            --color-accent-dark: #163f2d;
        }
        {!! $store['theme_custom_css'] ?? '' !!}
    </style>
    {!! $store['theme_custom_header_html'] ?? '' !!}
    @include('store.partials.tracking-head')
    @include('store.partials.anti-inspect')
</head>
<body class="fn-store min-h-screen antialiased">
    @include('store.partials.tracking-body')
    @include('store.partials.toast-notifications')

    @php
        $rawHeaderLinks = $store['header_menu_links'] ?? null;
        $headerLinks = ! empty($rawHeaderLinks) ? json_decode((string) $rawHeaderLinks, true) : null;
        if (empty($headerLinks) || ! is_array($headerLinks)) {
            $headerLinks = [
                ['label' => 'হোম', 'url' => route('home')],
            ];
            foreach (array_slice($categoriesList, 0, 4) as $cat) {
                $catName = is_array($cat) ? ($cat['name'] ?? '') : ($cat->name ?? '');
                $catSlug = is_array($cat) ? ($cat['slug'] ?? '') : ($cat->slug ?? '');
                if ($catSlug !== '') {
                    $headerLinks[] = ['label' => $catName, 'url' => route('shop.category', $catSlug)];
                }
            }
            $headerLinks[] = ['label' => 'আমাদের গল্প', 'url' => route('home').'#story'];
            $headerLinks[] = ['label' => 'যোগাযোগ', 'url' => route('contact.index')];
        }
        $logoHeight = ! empty($store['header_logo_height']) ? (int) $store['header_logo_height'] : 48;
        $announceText = trim(strip_tags($store['top_announcement_point_1'] ?? 'দেশজ স্বাদ, সরাসরি আপনার ঘরে'));
        $storePhone = $store['store_phone'] ?? '';
        $brandName = $store['store_name'] ?? config('app.name', 'Foodness');
        $brandTagline = $store['store_tagline'] ?? 'বিশুদ্ধ স্বাদের প্রতিশ্রুতি';
        $cartAmount = 0;
        try {
            $cartAmount = (float) app(\App\Support\Cart::class)->subtotal();
        } catch (\Throwable) {
            $cartAmount = 0;
        }
        $cartPillLabel = number_format($cartAmount, 0).'৳';
        $searchPlaceholder = trim((string) ($store['header_search_text'] ?? ''));
        if ($searchPlaceholder === '' || in_array(strtolower($searchPlaceholder), ['search products...', 'type to start searching...'], true)) {
            $searchPlaceholder = 'পণ্য খুঁজুন...';
        }
        $goldNav = [];
        foreach ($categoriesList as $cat) {
            $catName = is_array($cat) ? ($cat['name'] ?? '') : ($cat->name ?? '');
            $catSlug = is_array($cat) ? ($cat['slug'] ?? '') : ($cat->slug ?? '');
            if ($catSlug !== '') {
                $goldNav[] = ['label' => $catName, 'url' => route('shop.category', $catSlug)];
            }
        }
        if ($goldNav === []) {
            $goldNav = $headerLinks;
        }
    @endphp

    @if (($store['top_announcement_enabled'] ?? '1') === '1')
        <div class="announcement">
            <div class="fn-container announcement__inner">
                <span>{{ $announceText !== '' ? $announceText : 'দেশজ স্বাদ, সরাসরি আপনার ঘরে' }}</span>
                <div>
                    @if ($storePhone !== '')
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $storePhone) }}">হেল্পলাইন: {{ $storePhone }}</a>
                        <span class="announcement__divider"></span>
                    @endif
                    <a href="{{ route('order-track.index') }}">অর্ডার ট্র্যাক করুন</a>
                </div>
            </div>
        </div>
    @endif

    <header class="site-header sb-header {{ ($store['header_sticky'] ?? '1') === '1' ? 'sticky top-0' : '' }}">
        <div class="sb-top">
            <div class="fn-container sb-top__inner">
                <button class="sb-menu" type="button" data-drawer-open aria-label="মেনু খুলুন">
                    <svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>

                <a class="sb-logo" href="{{ route('home') }}" aria-label="{{ $brandName }} হোম">
                    @if ($storeLogo)
                        <img src="{{ $storeLogo }}" alt="{{ $brandName }}" style="height: {{ min($logoHeight, 52) }}px;">
                    @else
                        <span class="sb-logo__mark" aria-hidden="true">
                            <svg viewBox="0 0 48 48"><path d="M24 6c-6 8-14 12-14 22a14 14 0 0 0 28 0C38 18 30 14 24 6Z"/><path d="M24 18c2 4 6 6 6 11a6 6 0 0 1-12 0c0-5 4-7 6-11Z"/></svg>
                        </span>
                        <span class="sb-logo__name">{{ $brandName }}</span>
                    @endif
                </a>

                @if (($store['header_search_enabled'] ?? '1') === '1')
                    <div class="sb-search-wrap">
                        <form class="sb-search" role="search" action="{{ route('shop.index') }}" method="get">
                            <input type="search" name="q" id="header-search-input" placeholder="{{ $searchPlaceholder }}" aria-label="পণ্য খুঁজুন" autocomplete="off" aria-autocomplete="list" aria-controls="sb-search-panel">
                            <button type="submit" aria-label="খুঁজুন">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            </button>
                        </form>
                        <div class="sb-search__panel" id="sb-search-panel" hidden>
                            <p class="sb-search__title" id="sb-search-title">জনপ্রিয় পণ্যসমূহ</p>
                            <div id="sb-search-results"></div>
                            <p class="sb-search__empty" id="sb-search-empty" hidden>কোনো পণ্য পাওয়া যায়নি</p>
                        </div>
                    </div>
                @endif

                <div class="sb-tools">
                    @auth
                        <div class="header-account sb-account">
                            <a href="{{ route('account.index') }}" aria-label="অ্যাকাউন্ট">
                                <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0m12-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
                            </a>
                            <div class="header-account__menu">
                                <div class="header-account__email">{{ auth()->user()->email ?? auth()->user()->phone ?? 'লগইন আছেন' }}</div>
                                <a href="{{ route('account.index') }}">ড্যাশবোর্ড ও অর্ডার</a>
                                <a href="{{ route('account.profile') }}">আমার প্রোফাইল</a>
                                @if (auth()->user()->role === 'admin' || auth()->user()->can('access-admin'))
                                    <a href="{{ route('admin.dashboard') }}">অ্যাডমিন প্যানেল</a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="is-danger">লগআউট</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a class="sb-account" href="{{ route('login') }}" aria-label="অ্যাকাউন্ট">
                            <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0m12-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
                        </a>
                    @endauth

                    <a class="sb-cart" href="{{ route('cart.index') }}" data-open-cart-modal aria-label="শপিং কার্ট">
                        <span class="sb-cart__total" data-cart-subtotal>{{ $cartPillLabel }}</span>
                        <span class="sb-cart__icon">
                            <svg viewBox="0 0 24 24"><path d="M6 7h15l-1.4 8.2a2 2 0 0 1-2 1.6H9.2a2 2 0 0 1-2-1.6L5.2 4H3"/><path d="M9 20.5h.01M17 20.5h.01"/></svg>
                            <span class="sb-cart__count {{ $cartCount > 0 ? '' : 'hidden' }}" data-cart-count-badge>{{ $cartCount }}</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <nav class="main-nav sb-nav" aria-label="প্রধান নেভিগেশন">
            <div class="fn-container sb-nav__inner">
                @foreach ($goldNav as $hLink)
                    @php
                        $navUrl = $hLink['url'] ?? '#';
                        $navActive = $navUrl === url()->current() || $navUrl === request()->url();
                    @endphp
                    <a href="{{ $navUrl }}" @class(['is-active' => $navActive]) @if(!empty($hLink['new_tab'])) target="_blank" rel="noopener noreferrer" @endif>{{ $hLink['label'] ?? '' }}</a>
                @endforeach
            </div>
        </nav>
    </header>

    {{-- Off-Canvas Slide Drawer for Mobile (Customizable from A to Z) --}}
    @if (($store['mobile_drawer_enabled'] ?? '1') === '1')
        @php
            $drawerShowLogo = ($store['mobile_drawer_show_logo'] ?? '1') === '1';
            $drawerHeaderTitle = $store['mobile_drawer_header_title'] ?? null;
            $drawerShowSearch = ($store['mobile_drawer_show_search'] ?? '1') === '1';
            $drawerSearchPlaceholder = $store['mobile_drawer_search_placeholder'] ?? 'Search products or collections...';
            $drawerShowCategories = ($store['mobile_drawer_show_categories'] ?? '1') === '1';
            $drawerCategoriesTitle = $store['mobile_drawer_categories_title'] ?? 'ক্যাটাগরি ও কালেকশন';
            $drawerShowAccount = ($store['mobile_drawer_show_account'] ?? '1') === '1';
            $drawerAccountTitle = $store['mobile_drawer_account_title'] ?? 'মাই অ্যাকাউন্ট';
            $drawerShowPromo = ($store['mobile_drawer_show_promo'] ?? '0') === '1';
            $drawerPromoBadge = $store['mobile_drawer_promo_badge'] ?? 'স্পেশাল অফার';
            $drawerPromoText = $store['mobile_drawer_promo_text'] ?? 'আজকের অর্ডারে বিশেষ ছাড়!';
            $drawerPromoUrl = $store['mobile_drawer_promo_url'] ?? route('shop.index');

            $rawDrawerLinks = $store['mobile_drawer_menu_links'] ?? null;
            $drawerLinks = [];
            if (!empty($rawDrawerLinks)) {
                $decoded = is_array($rawDrawerLinks) ? $rawDrawerLinks : json_decode($rawDrawerLinks, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $drawerLinks = $decoded;
                }
            }
            if (empty($drawerLinks)) {
                $drawerLinks = !empty($headerLinks) ? $headerLinks : [
                    ['label' => 'All Products', 'url' => route('shop.index'), 'badge' => '', 'icon' => 'shop'],
                    ['label' => 'Our Story', 'url' => '#amader-golpo', 'badge' => '', 'icon' => 'book'],
                ];
            }
        @endphp
        <div
            data-store-drawer
            aria-hidden="true"
            class="pointer-events-none fixed inset-0 z-50 flex"
        >
            {{-- Backdrop --}}
            <div
                data-drawer-backdrop
                onclick="closeStoreDrawer()"
                class="pointer-events-none fixed inset-0 bg-black/60 backdrop-blur-xs opacity-0 transition-opacity duration-300"
            ></div>

            {{-- Drawer Panel --}}
            <div
                data-drawer-content
                class="relative flex w-full max-w-xs -translate-x-full flex-col overflow-hidden bg-[var(--color-card,#fff)] shadow-[0_20px_50px_rgba(13,38,0,.18)] transition-transform duration-300 ease-out"
            >
                {{-- Drawer Header --}}
                <div class="relative flex items-center justify-between border-b border-primary/10 px-5 py-4" style="background: linear-gradient(180deg, color-mix(in srgb, var(--color-primary) 8%, #fff) 0%, #fff 100%);">
                    <span class="absolute inset-x-0 top-0 h-0.5 bg-primary"></span>
                    <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0">
                        @if ($drawerShowLogo && $storeLogo)
                            <img src="{{ $storeLogo }}" alt="{{ $store['store_name'] ?? 'Foodness' }}" class="h-9 w-auto max-w-[150px] object-contain">
                        @elseif (!empty($drawerHeaderTitle))
                            <span class="font-serif text-xl font-black tracking-tight text-primary truncate">
                                {{ $drawerHeaderTitle }}
                            </span>
                        @else
                            <span class="font-serif text-2xl font-black tracking-tight text-primary">
                                {{ $store['store_name'] ?? 'Foodness' }}
                            </span>
                        @endif
                    </a>
                    <button
                        type="button"
                        class="rounded-full p-2 text-ink/50 hover:bg-primary/10 hover:text-primary transition cursor-pointer shrink-0"
                        data-drawer-close
                        onclick="closeStoreDrawer()"
                        aria-label="Close menu"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Drawer Mobile Search --}}
                @if ($drawerShowSearch)
                    <div class="px-4 pt-4 pb-3 border-b border-primary/8 bg-white">
                        <button
                            type="button"
                            data-search-open
                            data-drawer-close
                            class="w-full flex items-center justify-between rounded-2xl border border-primary/15 bg-primary/[0.04] py-2.5 px-3.5 text-xs text-ink/55 hover:border-primary/35 hover:bg-white transition text-left cursor-pointer shadow-[inset_0_1px_0_rgba(255,255,255,.7)]"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-primary shrink-0">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </span>
                                <span class="truncate">{{ $drawerSearchPlaceholder }}</span>
                            </div>
                            <span class="shrink-0 rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white">Live</span>
                        </button>
                    </div>
                @endif

                {{-- Drawer Nav Links --}}
                <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-1 bg-white">
                    <p class="px-3 pt-1 pb-2 text-[10px] font-extrabold uppercase tracking-[0.14em] text-primary/80">মেনু ও কালেকশন</p>
                    @foreach ($drawerLinks as $dLink)
                        @php
                            $dIcon = $dLink['icon'] ?? 'link';
                            $dBadge = $dLink['badge'] ?? '';
                        @endphp
                        <a
                            href="{{ $dLink['url'] ?? '#' }}"
                            @if(!empty($dLink['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
                            class="flex items-center justify-between rounded-2xl px-3 py-2.5 text-sm font-bold text-ink transition hover:bg-primary/[0.07] hover:text-primary group"
                            data-drawer-close
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                @if (!empty($dIcon) && $dIcon !== 'none')
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-primary/8 text-primary group-hover:bg-primary group-hover:text-white transition shrink-0">
                                        @include('store.partials.nav-icon', ['name' => $dIcon, 'class' => 'h-4 w-4'])
                                    </span>
                                @endif
                                <span class="truncate">{{ $dLink['label'] ?? '' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                @if (!empty($dBadge))
                                    <span class="rounded-md bg-accent/10 text-accent text-[10px] font-extrabold px-1.5 py-0.5 uppercase tracking-wide">
                                        {{ $dBadge }}
                                    </span>
                                @endif
                                <svg class="h-4 w-4 text-ink/25 group-hover:text-primary/70 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </a>
                    @endforeach

                    @if ($drawerShowCategories && !empty($categoriesList) && count($categoriesList) > 0)
                        <div class="pt-4 mt-3 border-t border-primary/10">
                            <p class="px-3 pb-2 text-[10px] font-extrabold uppercase tracking-[0.14em] text-primary/80">{{ $drawerCategoriesTitle }}</p>
                            @foreach ($categoriesList as $cat)
                                @php
                                    $catName = is_array($cat) ? ($cat['name'] ?? '') : ($cat->name ?? '');
                                    $catSlug = is_array($cat) ? ($cat['slug'] ?? '') : ($cat->slug ?? '');
                                @endphp
                                @if ($catSlug)
                                    <a
                                        href="{{ route('shop.category', $catSlug) }}"
                                        class="flex items-center justify-between rounded-2xl px-3 py-2.5 text-sm font-semibold text-ink/85 transition hover:bg-primary/[0.07] hover:text-primary group"
                                        data-drawer-close
                                    >
                                        <span class="flex items-center gap-2.5 min-w-0">
                                            <span class="h-1.5 w-1.5 rounded-full bg-primary/35 group-hover:bg-primary shrink-0"></span>
                                            <span class="truncate">{{ $catName }}</span>
                                        </span>
                                        <svg class="h-3.5 w-3.5 text-ink/25 group-hover:text-primary/70 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    {{-- Promotional Highlight Mini Card inside Drawer --}}
                    @if ($drawerShowPromo)
                        <div class="my-3 rounded-2xl p-3.5 text-white shadow-sm" style="background: linear-gradient(145deg, var(--color-primary) 0%, var(--color-primary-dark, #128200) 100%);">
                            <span class="inline-block rounded-md bg-white/15 text-white px-2 py-0.5 text-[9px] font-black uppercase tracking-wider mb-1.5 backdrop-blur-sm">
                                {{ $drawerPromoBadge }}
                            </span>
                            <p class="text-xs font-semibold leading-snug text-white/95">{{ $drawerPromoText }}</p>
                            <a href="{{ $drawerPromoUrl }}" class="mt-2.5 inline-flex items-center gap-1 text-[11px] font-bold text-white/90 hover:text-white underline underline-offset-2" data-drawer-close>
                                <span>অর্ডার করুন</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    @endif

                    @if ($drawerShowAccount)
                        <div class="pt-4 mt-2 border-t border-primary/10">
                            <p class="px-3 pb-2 text-[10px] font-extrabold uppercase tracking-[0.14em] text-primary/80">{{ $drawerAccountTitle }}</p>
                            @auth
                                <a
                                    href="{{ route('account.index') }}"
                                    class="flex items-center gap-2.5 rounded-2xl px-3 py-2.5 text-sm font-bold text-ink transition hover:bg-primary/[0.07] hover:text-primary"
                                    data-drawer-close
                                >
                                    <span>ড্যাশবোর্ড ও অর্ডার হিস্ট্রি</span>
                                </a>
                                <a
                                    href="{{ route('account.profile') }}"
                                    class="flex items-center gap-2.5 rounded-2xl px-3 py-2.5 text-sm font-bold text-ink transition hover:bg-primary/[0.07] hover:text-primary"
                                    data-drawer-close
                                >
                                    <span>আমার প্রোফাইল</span>
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="px-3 pt-1">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-accent hover:underline cursor-pointer">
                                        সাইন আউট
                                    </button>
                                </form>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="flex items-center gap-2.5 rounded-2xl px-3 py-2.5 text-sm font-bold text-ink transition hover:bg-primary/[0.07] hover:text-primary"
                                    data-drawer-close
                                >
                                    <span>লগইন</span>
                                </a>
                                <a
                                    href="{{ route('register') }}"
                                    class="flex items-center justify-center gap-2 rounded-2xl px-3 py-2.5 mt-1 text-sm font-bold text-white bg-primary hover:bg-primary-dark transition shadow-sm"
                                    data-drawer-close
                                >
                                    <span>নতুন অ্যাকাউন্ট তৈরি করুন</span>
                                </a>
                            @endauth
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Alert Messages --}}
    @if (session('success') || session('error') || $errors->any())
        <div class="store-container pt-4">
            @if (session('success'))
                <div role="alert" class="flex items-center justify-between rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" data-dismiss-alert class="text-emerald-600 hover:text-emerald-900">&times;</button>
                </div>
            @endif
            @if (session('error'))
                <div role="alert" class="mt-2 flex items-center justify-between rounded-2xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" data-dismiss-alert class="text-rose-600 hover:text-rose-900">&times;</button>
                </div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mt-2 rounded-2xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 font-bold">
                            <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Please review the following:</span>
                        </div>
                        <button type="button" data-dismiss-alert class="text-rose-600 hover:text-rose-900">&times;</button>
                    </div>
                    <ul class="list-disc pl-9 mt-1.5 space-y-0.5 text-xs">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- Main Content --}}
    <main class="pb-20 md:pb-12">
        @yield('content')
    </main>

    <footer class="footer" id="footer">
        <div class="fn-container footer-grid">
            <div class="footer-about">
                <a class="brand brand--light" href="{{ route('home') }}">
                    @if ($storeLogo)
                        <img class="brand-logo" src="{{ $storeLogo }}" alt="{{ $store['store_name'] ?? config('app.name', 'Store') }}">
                    @else
                        <span class="brand__mark" aria-hidden="true">
                            <svg viewBox="0 0 54 54"><path d="M44.7 8.6C29.4 8.7 16 17 12.4 31.3c5.8-7.2 13.3-11.8 23.2-14.2-9.3 4.7-16.4 10.6-21.2 18.1 3.1 6.6 8.8 10 15.9 10 12.4 0 19.3-13.5 14.4-36.6Z"/><path d="M10.6 18.1c-4 5.3-4.8 11.2-2.4 17.7 2.4-7.5 6.8-13.7 13.2-18.5-4.8-.9-8.4-.6-10.8.8Z"/></svg>
                        </span>
                        <span><strong>{{ $store['store_name'] ?? 'FOODNESS' }}</strong><small>{{ $store['store_tagline'] ?? 'বিশুদ্ধ স্বাদের প্রতিশ্রুতি' }}</small></span>
                    @endif
                </a>
                <p>{{ $store['footer_about_text'] ?? 'দেশের সেরা বাগান ও বিশ্বস্ত উৎপাদকদের কাছ থেকে সংগ্রহ করা খাঁটি খাবার পৌঁছে দিই আপনার ঘরে।' }}</p>
                @if (!empty($store['social_facebook']) || !empty($store['social_instagram']) || !empty($store['social_youtube']) || !empty($store['social_whatsapp']))
                    <div class="social-links">
                        @if (!empty($store['social_facebook']))
                            <a href="{{ $store['social_facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook">f</a>
                        @endif
                        @if (!empty($store['social_instagram']))
                            <a href="{{ $store['social_instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram">◎</a>
                        @endif
                        @if (!empty($store['social_youtube']))
                            <a href="{{ $store['social_youtube'] }}" target="_blank" rel="noopener" aria-label="Youtube">▶</a>
                        @endif
                        @if (!empty($store['social_whatsapp']))
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $store['social_whatsapp']) }}" target="_blank" rel="noopener" aria-label="WhatsApp">w</a>
                        @endif
                    </div>
                @endif
            </div>
            <div>
                <h3>প্রয়োজনীয় লিংক</h3>
                <a href="{{ route('home') }}#story">আমাদের সম্পর্কে</a>
                <a href="{{ route('page.shipping') }}">ডেলিভারি নীতিমালা</a>
                <a href="{{ route('page.refund-return') }}">রিটার্ন ও রিফান্ড</a>
                <a href="{{ route('page.privacy') }}">গোপনীয়তা নীতি</a>
                <a href="{{ route('page.terms') }}">শর্তাবলি</a>
                <a href="{{ route('faq.index') }}">সাধারণ প্রশ্নোত্তর</a>
                <a href="{{ route('contact.index') }}">যোগাযোগ করুন</a>
            </div>
            <div>
                <h3>{{ $store['footer_col2_title'] ?? 'জনপ্রিয় ক্যাটাগরি' }}</h3>
                <a href="{{ route('shop.index') }}">সব পণ্য</a>
                @foreach ($categoriesList as $cat)
                    @php
                        $catName = is_array($cat) ? ($cat['name'] ?? '') : ($cat->name ?? '');
                        $catSlug = is_array($cat) ? ($cat['slug'] ?? '') : ($cat->slug ?? '');
                    @endphp
                    @if ($catSlug !== '')
                        <a href="{{ route('shop.category', $catSlug) }}">{{ $catName }}</a>
                    @endif
                @endforeach
            </div>
            <div>
                <h3>{{ $store['footer_col4_title'] ?? 'যোগাযোগ' }}</h3>
                @if (! empty($store['store_address']))
                    <p>{{ $store['store_address'] }}</p>
                @endif
                @if (! empty($store['store_phone']))
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $store['store_phone']) }}">{{ $store['store_phone'] }}</a>
                @endif
                @if (! empty($store['store_email']))
                    <a href="mailto:{{ $store['store_email'] }}">{{ $store['store_email'] }}</a>
                @endif
            </div>
        </div>
        <div class="fn-container footer-bottom">
            <span>&copy; {{ date('Y') }} {{ $store['footer_copyright'] ?? ($store['store_name'] ?? 'Foodness') }}. সর্বস্বত্ব সংরক্ষিত।</span>
            <span>{{ $store['footer_bottom_note'] ?? 'স্বাদে খাঁটি, সম্পর্কে আপন' }}</span>
        </div>
    </footer>

    @php
        $rawWa = preg_replace('/[^0-9]/', '', $store['floating_whatsapp_number'] ?? ($store['store_phone'] ?? '01700000000'));
        $waNum = str_starts_with($rawWa, '0') ? '88'.$rawWa : ($rawWa ?: '8801700000000');
        $waMsg = urlencode($store['floating_whatsapp_message'] ?? ('Hello '.($store['store_name'] ?? 'Foodness').', I would like to know more about a product.'));
        $rawCall = preg_replace('/[^0-9+]/', '', $store['floating_call_number'] ?? ($store['store_phone'] ?? '01700000000'));
    @endphp

    {{-- Mobile Bottom Navigation Bar (Customizable from A to Z) --}}
    @if (($store['mobile_bottom_nav_enabled'] ?? '1') === '1')
        @php
            $bottomNavShowLabels = ($store['mobile_bottom_nav_show_labels'] ?? '1') === '1';
            $bottomNavBg = $store['mobile_bottom_nav_bg'] ?? '#FFFFFF';
            $bottomNavActiveColor = $store['mobile_bottom_nav_active_color'] ?? '#1C4F38';
            $bottomNavInactiveColor = $store['mobile_bottom_nav_inactive_color'] ?? '#6B7280';

            $rawBottomItems = $store['mobile_bottom_nav_items'] ?? null;
            $bottomNavItems = [];
            if (!empty($rawBottomItems)) {
                $decoded = is_array($rawBottomItems) ? $rawBottomItems : json_decode($rawBottomItems, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $bottomNavItems = $decoded;
                }
            }
            if (empty($bottomNavItems)) {
                $bottomNavItems = [
                    ['id' => '1', 'label' => $store['mobile_nav_home_label'] ?? 'হোম', 'url' => route('home'), 'action' => 'url', 'icon' => 'home', 'badge' => 'none'],
                    ['id' => '2', 'label' => $store['mobile_nav_shop_label'] ?? 'শপ', 'url' => route('shop.index'), 'action' => 'url', 'icon' => 'shop', 'badge' => 'none'],
                    ['id' => '3', 'label' => $store['mobile_nav_search_label'] ?? 'সার্চ', 'url' => '', 'action' => 'search', 'icon' => 'search', 'badge' => 'none'],
                    ['id' => '4', 'label' => $store['mobile_nav_cart_label'] ?? 'ব্যাগ', 'url' => route('cart.index'), 'action' => 'cart', 'icon' => 'cart', 'badge' => 'cart_count'],
                    ['id' => '5', 'label' => 'মেনু', 'url' => '', 'action' => 'drawer', 'icon' => 'menu', 'badge' => 'none'],
                ];
            }
        @endphp
        <nav
            id="mobile-bottom-nav"
            style="background-color: {{ $bottomNavBg }};"
            class="fixed bottom-0 inset-x-0 z-40 flex h-16 items-center justify-around border-t border-primary/10 px-2 backdrop-blur-md md:hidden shadow-lg select-none"
        >
            @foreach ($bottomNavItems as $navItem)
                @php
                    $nAction = $navItem['action'] ?? 'url';
                    $nIcon = $navItem['icon'] ?? 'home';
                    $nLabel = $navItem['label'] ?? '';
                    $nUrl = $navItem['url'] ?? '#';
                    $nBadge = $navItem['badge'] ?? 'none';
                    $nBadgeText = $navItem['badge_text'] ?? '';
                    $nNewTab = !empty($navItem['new_tab']);

                    $isActive = false;
                    if ($nAction === 'url' && !empty($nUrl) && $nUrl !== '#') {
                        $parsedPath = parse_url($nUrl, PHP_URL_PATH) ?? $nUrl;
                        $isActive = request()->is(trim($parsedPath, '/')) || (request()->path() === '/' && ($nUrl === '/' || $nUrl === route('home')));
                    }
                @endphp

                @if ($nAction === 'search')
                    <button
                        type="button"
                        data-search-open
                        class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center cursor-pointer transition hover:scale-105"
                        style="color: {{ $bottomNavInactiveColor }};"
                        aria-label="{{ $nLabel }}"
                    >
                        <div class="relative">
                            @include('store.partials.nav-icon', ['name' => $nIcon, 'class' => 'h-5 w-5'])
                        </div>
                        @if ($bottomNavShowLabels && !empty($nLabel))
                            <span class="text-[10px] leading-tight font-medium">{{ $nLabel }}</span>
                        @endif
                    </button>
                @elseif ($nAction === 'cart')
                    <a
                        href="{{ route('cart.index') }}"
                        data-open-cart-modal
                        class="relative flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center cursor-pointer transition hover:scale-105"
                        style="color: {{ request()->routeIs('cart.*') ? $bottomNavActiveColor : $bottomNavInactiveColor }}; font-weight: {{ request()->routeIs('cart.*') ? '800' : '500' }};"
                        aria-label="{{ $nLabel }}"
                    >
                        <div class="relative">
                            @include('store.partials.nav-icon', ['name' => $nIcon, 'class' => 'h-5 w-5'])
                            <span class="absolute -top-1.5 -right-2.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-accent px-1 text-[9px] font-bold text-white {{ $cartCount > 0 ? '' : 'hidden' }}" data-cart-count-badge-mobile>
                                {{ $cartCount }}
                            </span>
                        </div>
                        @if ($bottomNavShowLabels && !empty($nLabel))
                            <span class="text-[10px] leading-tight">{{ $nLabel }}</span>
                        @endif
                    </a>
                @elseif ($nAction === 'drawer')
                    <button
                        type="button"
                        data-drawer-open
                        onclick="openStoreDrawer()"
                        class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center cursor-pointer transition hover:scale-105"
                        style="color: {{ $bottomNavInactiveColor }};"
                        aria-label="{{ $nLabel }}"
                    >
                        <div class="relative">
                            @include('store.partials.nav-icon', ['name' => $nIcon, 'class' => 'h-5 w-5'])
                        </div>
                        @if ($bottomNavShowLabels && !empty($nLabel))
                            <span class="text-[10px] leading-tight font-medium">{{ $nLabel }}</span>
                        @endif
                    </button>
                @elseif ($nAction === 'whatsapp')
                    <a
                        href="https://wa.me/{{ $waNum }}?text={{ $waMsg }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center cursor-pointer transition hover:scale-105 text-emerald-600"
                        aria-label="{{ $nLabel }}"
                    >
                        <div class="relative">
                            @include('store.partials.nav-icon', ['name' => 'whatsapp', 'class' => 'h-5 w-5'])
                        </div>
                        @if ($bottomNavShowLabels && !empty($nLabel))
                            <span class="text-[10px] leading-tight font-medium">{{ $nLabel }}</span>
                        @endif
                    </a>
                @elseif ($nAction === 'call')
                    <a
                        href="tel:{{ $store['store_phone'] ?? '01700000000' }}"
                        class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center cursor-pointer transition hover:scale-105"
                        style="color: {{ $bottomNavInactiveColor }};"
                        aria-label="{{ $nLabel }}"
                    >
                        <div class="relative">
                            @include('store.partials.nav-icon', ['name' => 'phone', 'class' => 'h-5 w-5'])
                        </div>
                        @if ($bottomNavShowLabels && !empty($nLabel))
                            <span class="text-[10px] leading-tight font-medium">{{ $nLabel }}</span>
                        @endif
                    </a>
                @else
                    <a
                        href="{{ $nUrl }}"
                        @if($nNewTab) target="_blank" rel="noopener noreferrer" @endif
                        class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center cursor-pointer transition hover:scale-105"
                        style="color: {{ $isActive ? $bottomNavActiveColor : $bottomNavInactiveColor }}; font-weight: {{ $isActive ? '800' : '500' }};"
                        aria-label="{{ $nLabel }}"
                    >
                        <div class="relative">
                            @include('store.partials.nav-icon', ['name' => $nIcon, 'class' => 'h-5 w-5'])
                            @if ($nBadge === 'cart_count')
                                <span class="absolute -top-1.5 -right-2.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-accent px-1 text-[9px] font-bold text-white {{ $cartCount > 0 ? '' : 'hidden' }}" data-cart-count-badge-mobile>
                                    {{ $cartCount }}
                                </span>
                            @elseif ($nBadge === 'custom' && !empty($nBadgeText))
                                <span class="absolute -top-1.5 -right-2.5 flex h-3.5 min-w-[14px] items-center justify-center rounded-full bg-accent px-1 text-[8px] font-extrabold text-white">
                                    {{ $nBadgeText }}
                                </span>
                            @endif
                        </div>
                        @if ($bottomNavShowLabels && !empty($nLabel))
                            <span class="text-[10px] leading-tight">{{ $nLabel }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>
    @endif

    {{-- Floating WhatsApp, Call, and Animated Scroll-To-Top Widget (Grouped on Right) --}}
    <div class="fixed bottom-20 md:bottom-6 right-4 sm:right-6 z-40 flex flex-col items-end gap-2.5">
        {{-- Modern Animated Scroll-To-Top Button with Circular Progress Indicator (WhatsApp/Call এর উপরে) --}}
        <button
            id="scroll-to-top"
            type="button"
            onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-primary text-white shadow-xl border border-white/20 opacity-0 pointer-events-none translate-y-3 transition-all duration-300 hover:scale-110 hover:bg-primary-dark active:scale-95 group cursor-pointer relative"
            aria-label="Scroll to top"
            title="উপরে যান"
        >
            {{-- Circular Scroll Progress Ring --}}
            <svg class="absolute inset-0 h-full w-full -rotate-90 pointer-events-none p-0.5" viewBox="0 0 48 48">
                <circle
                    class="text-white/10"
                    stroke-width="3"
                    stroke="currentColor"
                    fill="transparent"
                    r="21"
                    cx="24"
                    cy="24"
                />
                <circle
                    id="scroll-progress-circle"
                    class="text-gold transition-all duration-150"
                    stroke-width="3"
                    stroke-dasharray="131.95"
                    stroke-dashoffset="131.95"
                    stroke-linecap="round"
                    stroke="currentColor"
                    fill="transparent"
                    r="21"
                    cx="24"
                    cy="24"
                />
            </svg>

            {{-- Bouncing Up Arrow Icon --}}
            <svg class="h-5 w-5 text-white transition-transform duration-300 group-hover:-translate-y-1 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
            </svg>
        </button>

        @if (($store['floating_call_enabled'] ?? '1') === '1')
            <a
                href="tel:{{ $rawCall }}"
                class="flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-primary text-white shadow-xl hover:scale-110 hover:bg-primary-dark transition duration-200"
                title="Call Us Directly"
                aria-label="Call Store"
            >
                <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </a>
        @endif

        @if (($store['floating_whatsapp_enabled'] ?? '1') === '1')
            <a
                href="https://wa.me/{{ $waNum }}?text={{ $waMsg }}"
                target="_blank"
                rel="noopener noreferrer"
                class="group flex items-center gap-2 rounded-full bg-[#25D366] text-white p-2.5 sm:px-4 sm:py-2.5 shadow-2xl hover:scale-105 hover:bg-[#20ba59] transition duration-200"
                title="Chat on WhatsApp"
                aria-label="WhatsApp Chat"
            >
                <svg class="h-5 w-5 sm:h-6 sm:w-6 fill-current shrink-0" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
                <span class="hidden sm:inline text-xs font-bold">WhatsApp Chat</span>
            </a>
        @endif
    </div>

    {{-- Live Search & Recommendation Modal --}}
    @include('store.partials.live-search-modal')

    {{-- AI Shopping Assistant Widget --}}
    @include('store.partials.ai-chat-widget')

    {{-- Instant Variation & Checkout Modals --}}
    @include('store.partials.variation-modal')
    @include('store.partials.checkout-modal')
    @include('partials.bn-field-voice')
    @include('store.partials.modal-buy-flow-scripts')

    {{-- Hardware & Device Fingerprinting --}}
    @include('store.partials.device-fingerprint')
    {!! $store['theme_custom_footer_html'] ?? '' !!}
    <script>
        document.querySelector('[data-nav-toggle]')?.addEventListener('click', () => {
            const menu = document.querySelector('.main-nav');
            const toggle = document.querySelector('[data-nav-toggle]');
            if (! menu || ! toggle) return;
            const isOpen = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
        function openStoreDrawer() {
            const drawer = document.querySelector('[data-store-drawer]');
            if (!drawer) return;
            drawer.classList.remove('pointer-events-none');
            drawer.setAttribute('aria-hidden', 'false');
            const backdrop = drawer.querySelector('[data-drawer-backdrop]');
            if (backdrop) {
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                backdrop.classList.add('opacity-100');
            }
            const panel = drawer.querySelector('[data-drawer-content]');
            if (panel) {
                panel.classList.remove('-translate-x-full');
                panel.classList.add('translate-x-0');
            }
            document.body.style.overflow = 'hidden';
        }
        function closeStoreDrawer() {
            const drawer = document.querySelector('[data-store-drawer]');
            if (!drawer) return;
            drawer.classList.add('pointer-events-none');
            drawer.setAttribute('aria-hidden', 'true');
            const backdrop = drawer.querySelector('[data-drawer-backdrop]');
            if (backdrop) {
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0', 'pointer-events-none');
            }
            const panel = drawer.querySelector('[data-drawer-content]');
            if (panel) {
                panel.classList.remove('translate-x-0');
                panel.classList.add('-translate-x-full');
            }
            document.body.style.overflow = '';
        }
    </script>
    <script>
        // Modern Scroll-To-Top Button with Dynamic Circular Scroll Percentage Progress
        (function() {
            const btn = document.getElementById('scroll-to-top');
            const progressCircle = document.getElementById('scroll-progress-circle');
            if (!btn || !progressCircle) return;

            const circumference = 2 * Math.PI * 21; // ~131.95

            function updateScrollProgress() {
                const scrollTop = window.scrollY || document.documentElement.scrollTop;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;

                if (scrollTop > 250) {
                    btn.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
                    btn.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
                } else {
                    btn.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                    btn.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
                }

                if (docHeight > 0) {
                    const scrollPercent = Math.min(Math.max(scrollTop / docHeight, 0), 1);
                    const offset = circumference - (scrollPercent * circumference);
                    progressCircle.style.strokeDashoffset = offset;
                }
            }

            window.addEventListener('scroll', updateScrollProgress, { passive: true });
            updateScrollProgress();
        })();
    </script>
    <script>
        // Storefront Failsafe: Release hanging browser spinners caused by dead external assets / iframes
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (document.readyState !== 'complete' && typeof window.stop === 'function') {
                    try { window.stop(); } catch (_) {}
                }
            }, 4000);
        });
    </script>
    @stack('scripts')
    @include('partials.password-toggle')
</body>
</html>
