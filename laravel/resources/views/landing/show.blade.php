<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $landingPage->title }} — {{ config('app.name', 'Foodness') }}</title>
    <meta name="description" content="{{ $product?->short_description ?? $landingPage->title }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $landingPage->title }}">
    <meta property="og:description" content="{{ $product?->short_description ?? $landingPage->title }}">
    @if ($product && $product->primaryImagePath())
        <meta property="og:image" content="{{ asset('storage/' . $product->primaryImagePath()) }}">
    @endif
    <meta property="og:type" content="product">
    <meta property="og:url" content="{{ url()->current() }}">

    @php
        $brandPrimaryColor = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'theme_primary_color')->value('value') ?: '#044120';
        $brandAccentColor = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'theme_accent_color')->value('value') ?: '#A5000B';
        $activeThemeColor = $landingPage->checkout_color ?: $brandPrimaryColor ?: '#044120';
        // Landing kits usually style buttons/backgrounds with bg-primary — prefer checkout color.
        $landingPrimaryColor = $activeThemeColor ?: $brandPrimaryColor;
    @endphp

    <style>
        /* Hide unstyled HTML until Tailwind utilities are actually applied. */
        html:not(.landing-css-ready) body { visibility: hidden; }

        /* Color tokens for pasted HTML/CSS (var(--primary), bg-primary fallbacks). */
        :root {
            --primary: {{ $landingPrimaryColor }};
            --accent: {{ $brandAccentColor }};
            --color-primary: {{ $landingPrimaryColor }};
            --color-accent: {{ $brandAccentColor }};
            --lp-theme: {{ $activeThemeColor }};
            --lp-primary: {{ $landingPrimaryColor }};
            --lp-accent: {{ $brandAccentColor }};
        }
        .bg-primary { background-color: {{ $landingPrimaryColor }} !important; }
        .text-primary { color: {{ $landingPrimaryColor }} !important; }
        .border-primary { border-color: {{ $landingPrimaryColor }} !important; }
        .bg-accent { background-color: {{ $brandAccentColor }} !important; }
        .text-accent { color: {{ $brandAccentColor }} !important; }
        .border-accent { border-color: {{ $brandAccentColor }} !important; }
        .hover\:bg-primary:hover { background-color: {{ $landingPrimaryColor }} !important; }
        .hover\:bg-accent:hover { background-color: {{ $brandAccentColor }} !important; }
    </style>
    <script>
        window.__landingCssReady = false;
        window.markLandingCssReady = function () {
            if (window.__landingCssReady) return;
            window.__landingCssReady = true;
            document.documentElement.classList.add('landing-css-ready');
        };
        window.isLandingTailwindReady = function () {
            try {
                var probe = document.createElement('div');
                probe.className = 'hidden';
                probe.setAttribute('aria-hidden', 'true');
                probe.style.cssText = 'position:absolute;left:-99999px;top:0;pointer-events:none;';
                (document.body || document.documentElement).appendChild(probe);
                var ready = window.getComputedStyle(probe).display === 'none';
                probe.remove();
                return ready;
            } catch (e) {
                return false;
            }
        };
        window.waitForLandingCss = function () {
            var tries = 0;
            var maxTries = 160; // ~4s at 25ms
            var tick = function () {
                if (window.isLandingTailwindReady() || tries >= maxTries) {
                    window.markLandingCssReady();
                    return;
                }
                tries += 1;
                setTimeout(tick, 25);
            };
            tick();
        };
        // Hard fallback so the page never stays blank if the browser build fails.
        setTimeout(window.markLandingCssReady, 6000);
    </script>
    {{--
      Self-hosted Tailwind Play CDN (v3). Config MUST be set before the script loads
      or custom colors (bg-primary / bg-accent) never generate.
      Landing HTML often includes an unlayered `*` reset; v3 utilities beat that reset.
    --}}
    @php
        $landingTwPath = public_path('vendor/tailwindcss/play-cdn.js');
        if (! is_file($landingTwPath)) {
            $landingTwPath = public_path('vendor/tailwindcss/browser.js');
        }
        $landingTwVersion = is_file($landingTwPath) ? (string) filemtime($landingTwPath) : '1';
        $landingTwAsset = str_ends_with($landingTwPath, 'play-cdn.js')
            ? 'vendor/tailwindcss/play-cdn.js'
            : 'vendor/tailwindcss/browser.js';
    @endphp
    <script>
        tailwind = window.tailwind || {};
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: @json($landingPrimaryColor),
                        accent: @json($brandAccentColor),
                        theme: @json($activeThemeColor),
                    }
                }
            }
        };
    </script>
    <script src="{{ asset($landingTwAsset) }}?v={{ $landingTwVersion }}" onload="window.__landingTwLoaded = true" onerror="window.markLandingCssReady()"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800;900&family=Noto+Serif+Bengali:wght@600;700;800&display=swap" rel="stylesheet">

    @php
        $lpCssRaw = $scopedCustomCss ?? \App\Support\LandingHtml::scopeCss((string) ($landingPage->custom_css ?? ''));
        $lpCssImports = '';
        $lpCssBody = $lpCssRaw;
        if (is_string($lpCssRaw) && $lpCssRaw !== '') {
            // @import must come first. Match full url("...") so semicolons inside font URLs are kept.
            $importPattern = '/@import\s+(?:url\(\s*[\'"][^\'"]+[\'"]\s*\)|[\'"][^\'"]+[\'"])[^;]*;/i';
            if (preg_match_all($importPattern, $lpCssRaw, $importMatches)) {
                $lpCssImports = implode("\n", $importMatches[0]);
                $lpCssBody = trim(preg_replace($importPattern, '', $lpCssRaw) ?? $lpCssRaw);
            }
        }
    @endphp
    @if ($lpCssImports !== '')
        <style>{!! $lpCssImports !!}</style>
    @endif
    <style>
        body {
            font-family: 'Noto Sans Bengali', system-ui, -apple-system, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        /* Custom User CSS — universal `*` resets are scoped to .lp-content (not checkout) */
        {!! $lpCssBody !!}

        /* System safeguard: keep built-in checkout usable if custom CSS uses global resets */
        #checkout-section,
        #checkout-section *,
        #checkout-section *::before,
        #checkout-section *::after {
            box-sizing: border-box;
        }
        #checkout-section img {
            max-width: 100%;
            height: auto;
        }
        /*
          Unlayered spacing shield for #checkout-section.
          Needed when only Tailwind v4 (browser.js) is present — its @layer utilities
          lose to admin `* { margin:0; padding:0 }` resets.
        */
        #checkout-section {
            padding: 1.5rem 0.5rem !important;
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: 72rem !important;
            width: 100% !important;
        }
        @media (min-width: 640px) {
            #checkout-section {
                padding: 3rem 1.5rem !important;
            }
        }
        #checkout-section .bg-white.rounded-2xl,
        #checkout-section .bg-white.rounded-3xl,
        #checkout-section > .bg-white {
            padding: 0 !important;
        }
        #checkout-section form.p-4,
        #checkout-section form {
            padding: 1rem !important;
        }
        @media (min-width: 640px) {
            #checkout-section form.p-4,
            #checkout-section form {
                padding: 2rem !important;
            }
        }
        @media (min-width: 1024px) {
            #checkout-section form {
                padding: 2.5rem !important;
            }
        }
        #checkout-section .space-y-7 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 1.75rem !important;
        }
        #checkout-section .gap-2 { gap: 0.5rem !important; }
        #checkout-section .gap-3 { gap: 0.75rem !important; }
        #checkout-section .gap-4 { gap: 1rem !important; }
        #checkout-section .p-3 { padding: 0.75rem !important; }
        #checkout-section .p-4 { padding: 1rem !important; }
        #checkout-section .px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
        #checkout-section .py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
        #checkout-section .py-2\.5 { padding-top: 0.625rem !important; padding-bottom: 0.625rem !important; }
        #checkout-section .py-3 { padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }
        #checkout-section .mb-2 { margin-bottom: 0.5rem !important; }
        #checkout-section .mb-3 { margin-bottom: 0.75rem !important; }
        #checkout-section .mt-1 { margin-top: 0.25rem !important; }
        #checkout-section .mt-2 { margin-top: 0.5rem !important; }
        #checkout-section .mt-3 { margin-top: 0.75rem !important; }
        #checkout-section .mt-4 { margin-top: 1rem !important; }
    </style>

    @php
        $effectivePrice = (float) $landingPage->effectivePrice();
        $customPixelId = $landingPage->fb_pixel_id ? trim($landingPage->fb_pixel_id) : null;
        $resolvedTracking = $tracking ?? [];
        if ($customPixelId) {
            $resolvedTracking['facebook'] = [
                'enabled' => true,
                'pixel_id' => $customPixelId,
            ];
        }
    @endphp

    {{-- Unified Store Tracking Header (Google Tag Manager, GA4, Meta Pixel, TikTok Pixel) --}}
    @include('store.partials.tracking-head', ['tracking' => $resolvedTracking])

    {{-- If custom landing pixel differs from global store pixel, initialize custom pixel too --}}
    @if ($customPixelId && !empty($tracking['facebook']['enabled']) && filled($tracking['facebook']['pixel_id'] ?? null) && $customPixelId !== $tracking['facebook']['pixel_id'])
        <script>
            if (typeof fbq === 'function') {
                fbq('init', @js($customPixelId));
                fbq('track', 'PageView');
            }
        </script>
        <noscript>
            <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $customPixelId }}&ev=PageView&noscript=1" alt=""/>
        </noscript>
    @endif

    {{-- Product ViewContent Event for Sales Funnel / Landing Page --}}
    @php
        $categoryName = 'General';
        if ($product) {
            if ($product->relationLoaded('category') && $product->category) {
                $categoryName = $product->category->name;
            } elseif ($product->category_id) {
                $categoryName = \App\Models\Category::query()->whereKey($product->category_id)->value('name') ?? 'General';
            }
        }
    @endphp
    <script>
        (function() {
            const productData = {
                id: @json((string) ($product?->id ?? '')),
                name: @json($product?->name ?? $landingPage->title),
                price: {{ $effectivePrice }},
                category: @json($categoryName),
                currency: 'BDT'
            };
            const eventId = @json('view_landing_' . $landingPage->id . '_' . ($product?->id ?? 0));

            // 1. Meta Pixel ViewContent
            if (typeof fbq === 'function') {
                fbq('track', 'ViewContent', {
                    content_name: productData.name,
                    content_category: productData.category,
                    content_ids: [productData.id],
                    content_type: 'product',
                    value: productData.price,
                    currency: productData.currency
                }, {
                    eventID: eventId
                });
            }

            // 2. TikTok Pixel ViewContent
            if (typeof ttq === 'object' && typeof ttq.track === 'function') {
                ttq.track('ViewContent', {
                    content_id: productData.id,
                    content_type: 'product',
                    content_name: productData.name,
                    quantity: 1,
                    price: productData.price,
                    value: productData.price,
                    currency: productData.currency
                }, {
                    event_id: eventId
                });
            }

            // 3. Google dataLayer view_item
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                event: 'view_item',
                event_id: eventId,
                ecommerce: {
                    currency: productData.currency,
                    value: productData.price,
                    items: [{
                        item_id: productData.id,
                        item_name: productData.name,
                        item_category: productData.category,
                        price: productData.price,
                        quantity: 1
                    }]
                }
            });
        })();
    </script>
    @include('store.partials.anti-inspect')
</head>
<body class="bg-slate-50 text-slate-800 antialiased pb-20 md:pb-6">
    {{-- Unified Store Tracking Body (GTM noscript) --}}
    @include('store.partials.tracking-body', ['tracking' => $resolvedTracking])

    <!-- Main Landing Page Content (Custom HTML + Checkout Form) -->
    <main>
        {!! $finalHtml !!}
    </main>

    <!-- Sticky Bottom Bar for Mobile -->
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 p-3 flex items-center justify-between md:hidden shadow-lg">
        <div>
            <span class="text-[11px] text-slate-500 block leading-tight">অফার মূল্য</span>
            <span id="mobile-sticky-price" class="text-lg font-black leading-tight" style="color: {{ $activeThemeColor }} !important;">
                {{ \App\Support\Money::formatBn($landingPage->effectivePrice()) }}
            </span>
        </div>
        <a href="#checkout-section" class="px-6 py-2.5 text-white font-bold text-sm rounded-xl shadow-md flex items-center gap-1.5 transition active:scale-95" style="background-color: {{ $activeThemeColor }} !important;">
            <span>🛒 অর্ডার করুন</span>
        </a>
    </div>

    <!-- Custom User JS (errors here should not stop the rest of the page) -->
    @if ($landingPage->custom_js)
        <script>
            try {
                {!! $landingPage->custom_js !!}
            } catch (err) {
                console.error('Landing custom JS error:', err);
            }
        </script>
    @endif

    {{-- Universal Safeguard for Broken Image URLs & Hanging Network Requests --}}
    <script>
        (function() {
            const transparentPixel = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>';
            const protectImage = function(img) {
                if (!img || img.dataset.safeguarded) return;
                img.dataset.safeguarded = 'true';
                if (!img.hasAttribute('loading')) {
                    img.setAttribute('loading', 'lazy');
                }
                img.addEventListener('error', function() {
                    this.onerror = null;
                    this.src = transparentPixel;
                    this.style.opacity = '0';
                    this.style.pointerEvents = 'none';
                }, { once: true });
            };

            document.querySelectorAll('img').forEach(protectImage);

            if (window.MutationObserver) {
                new MutationObserver(function(mutations) {
                    for (let i = 0; i < mutations.length; i++) {
                        const added = mutations[i].addedNodes;
                        for (let j = 0; j < added.length; j++) {
                            const node = added[j];
                            if (node.nodeType === 1) {
                                if (node.tagName === 'IMG') protectImage(node);
                                else if (node.querySelectorAll) node.querySelectorAll('img').forEach(protectImage);
                            }
                        }
                    }
                }).observe(document.body, { childList: true, subtree: true });
            }

        })();
    </script>
    <script>
        // Wait until Tailwind utilities are actually computed — rAF alone races the CDN.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', window.waitForLandingCss);
        } else {
            window.waitForLandingCss();
        }
    </script>
</body>
</html>
