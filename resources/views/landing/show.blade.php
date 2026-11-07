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
        $activeThemeColor = $landingPage->checkout_color ?: '#044120';
    @endphp

    <style>
        /* Hide unstyled HTML until Tailwind utilities are actually applied. */
        html:not(.landing-css-ready) body { visibility: hidden; }
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
      Self-hosted Tailwind Play CDN (v3). Landing HTML/CSS is admin-authored and often includes
      an unlayered `* { padding:0; margin:0; }` reset. Tailwind v4 browser utilities live in
      @layer and lose to that reset, which breaks checkout spacing. v3 Play CDN utilities win.
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
    <script src="{{ asset($landingTwAsset) }}?v={{ $landingTwVersion }}" onload="window.__landingTwLoaded = true" onerror="window.markLandingCssReady()"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $brandPrimaryColor }}',
                        accent: '{{ $brandAccentColor }}',
                    }
                }
            }
        };
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Bengali:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'IBM Plex Sans Bengali', 'Noto Sans Bengali', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        /* Custom User CSS (scoped advice: target .lp-* / .hero-* classes, not #checkout-section) */
        {!! $landingPage->custom_css ?? '' !!}

        /* System safeguard: keep built-in checkout usable if custom CSS uses global resets */
        #checkout-section,
        #checkout-section *,
        #checkout-section *::before,
        #checkout-section *::after {
            box-sizing: border-box;
        }
        #checkout-section img {
            max-width: 100%;
        }
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
