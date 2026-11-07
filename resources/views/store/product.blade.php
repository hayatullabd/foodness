@extends('layouts.store')

@php
    use App\Support\Money;
    $isTesting = app()->environment('testing');
    $galleryImages = $product->allGalleryImages();
    $primaryImage = $galleryImages->firstWhere('is_primary', true) ?? $galleryImages->first();
    $galleryVideos = $product->videos;
    
    $mainImageUrl = null;
    if ($primaryImage && ($isTesting || file_exists(public_path('storage/'.$primaryImage['path'])))) {
        $mainImageUrl = $primaryImage['url'];
    } elseif ($primaryImage) {
        $mainImageUrl = $primaryImage['url'];
    } elseif ($product->primaryImagePath()) {
        $mainImageUrl = asset('storage/'.$product->primaryImagePath());
    } elseif ($product->images->first()?->image_path) {
        $mainImageUrl = asset('storage/'.$product->images->first()->image_path);
    } else {
        $mainImageUrl = \App\Support\PlaceholderImage::url();
    }

    $hasVariants = $product->variants->isNotEmpty();
    $variationAttributes = $hasVariants ? $product->variationAttributes() : [];

    $colorCodes = \App\Models\AttributeValue::query()
        ->whereNotNull('color_code')
        ->pluck('color_code', 'name')
        ->all();

    $variantsJson = $hasVariants
        ? $product->variants->map(function ($v) use ($isTesting, $product) {
            if (! $v->relationLoaded('product')) {
                $v->setRelation('product', $product);
            }
            $imgPath = $v->primaryImagePath();
            return [
                'id' => $v->id,
                'name' => $v->label(),
                'attributes' => $v->attributeMap(),
                'color_id' => $v->color_id,
                'color_name' => $v->color?->name,
                'size_id' => $v->size_id,
                'size_name' => $v->size?->name ?? $v->size_name,
                'price' => (float) $v->effectivePrice(),
                'price_formatted' => number_format((float) $v->effectivePrice(), 0, '.', ',').'৳',
                'compare_at_price' => $v->effectiveCompareAtPrice() ? (float) $v->effectiveCompareAtPrice() : null,
                'compare_at_price_formatted' => $v->effectiveCompareAtPrice() ? number_format((float) $v->effectiveCompareAtPrice(), 0, '.', ',').'৳' : null,
                'stock' => $v->is_unlimited_stock ? 99999 : (int) $v->stock,
                'is_unlimited_stock' => (bool) $v->is_unlimited_stock,
                'sku' => $v->sku,
                'delivery_charge_type' => $v->delivery_charge_type ?? 'default',
                'custom_delivery_inside' => $v->custom_delivery_inside !== null ? (float) $v->custom_delivery_inside : null,
                'custom_delivery_outside' => $v->custom_delivery_outside !== null ? (float) $v->custom_delivery_outside : null,
                'image_url' => ($imgPath && ($isTesting || file_exists(public_path('storage/'.$imgPath)))) ? asset('storage/'.$imgPath) : null,
            ];
        })->values()
        : collect();

    $totalStock = $product->totalStock();

    // Store Phone & WhatsApp settings
    $storePhone = $store['store_phone'] ?? '01700000000';
    $whatsAppNumber = ! empty($store['product_whatsapp_number'])
        ? $store['product_whatsapp_number']
        : (! empty($store['floating_whatsapp_number']) ? $store['floating_whatsapp_number'] : $storePhone);
    $whatsAppCleanNumber = preg_replace('/[^0-9]/', '', $whatsAppNumber);
    if (! str_starts_with($whatsAppCleanNumber, '880') && str_starts_with($whatsAppCleanNumber, '01')) {
        $whatsAppCleanNumber = '88' . $whatsAppCleanNumber;
    }
    $whatsAppMsgTemplate = $store['product_whatsapp_message'] ?? 'হ্যালো, আমি এই পণ্যটি অর্ডার করতে চাই: {product_name} (মূল্য: {price})। প্রোডাক্ট লিংক: {url}';

    $callNumber = ! empty($store['product_call_number']) ? $store['product_call_number'] : $storePhone;
    $callCleanNumber = preg_replace('/[^0-9+]/', '', $callNumber);

    $showRelated = ($store['product_show_related'] ?? '1') === '1';
    $hasDiscount = $product->compare_at_price && (float) $product->compare_at_price > (float) $product->price;

    $ppFlag = function (string $key, bool $fallback) use ($store): bool {
        if (! array_key_exists($key, $store) || $store[$key] === null || $store[$key] === '') {
            return $fallback;
        }

        return (string) $store[$key] === '1';
    };
    $ppText = function (string $key, string $fallback) use ($store): string {
        $value = $store[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }

        return $value;
    };
    $ppHex = function (string $key, string $fallback) use ($ppText): string {
        $value = $ppText($key, $fallback);

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) ? $value : $fallback;
    };
    $ppDark = function (string $hex): bool {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return (($r * 299) + ($g * 587) + ($b * 114)) / 1000 < 150;
    };

    $showCart = $ppFlag('product_add_to_cart_enabled', true);
    $showBuyNow = $ppFlag('product_buy_now_enabled', true);
    $showWhatsapp = $ppFlag('product_whatsapp_btn_enabled', true);
    $showCall = $ppFlag('product_call_btn_enabled', true);
    $showZoom = $ppFlag('product_show_zoom', true);
    $showArrows = $ppFlag('product_show_gallery_arrows', false) && ($galleryImages->count() + $galleryVideos->count()) > 1;
    $showShort = $ppFlag('product_show_short_description', true);
    $showSku = $ppFlag('product_show_sku', true);
    $showStock = $ppFlag('product_show_stock', false);
    $showCategory = $ppFlag('product_show_category', true);
    $showDeliveryBadge = $ppFlag('product_show_delivery_badge', false);
    $showPriceBox = $ppFlag('product_show_price_box', false);
    $showTrust = $ppFlag('product_show_trust_badges', true);

    $cartText = $ppText('product_add_to_cart_text', 'কার্টে যোগ করুন');
    $buyText = $ppText('product_buy_now_text', 'ক্যাশ অন ডেলিভারিতে অর্ডার করুন');
    $whatsappText = $ppText('product_whatsapp_btn_text', 'WhatsApp');
    $callText = $ppText('product_call_btn_text', 'কল করুন');
    $descTabTitle = $ppText('product_desc_tab_title', 'পণ্যের বিবরণ');
    $deliveryTabTitle = $ppText('product_delivery_payment_tab_title', 'ডেলিভারি ও রিটার্ন');
    $relatedTitle = $ppText('product_related_title', 'সম্পর্কিত পণ্য');
    $deliveryText = $ppText('product_delivery_info_text', $store['footer_delivery_info'] ?? 'ঢাকার মধ্যে ১–২ এবং ঢাকার বাইরে ৩–৫ কর্মদিবসে ডেলিভারি। পণ্য ক্ষতিগ্রস্ত অবস্থায় পৌঁছালে ২৪ ঘণ্টার মধ্যে ছবিসহ জানান।');
    $paymentText = $ppText('product_payment_info_text', '');
    $cartBg = $ppHex('product_add_to_cart_bg', '#164f38');
    $buyBg = $ppHex('product_buy_now_bg', '#f6e8b7');
    $whatsappBg = $ppHex('product_whatsapp_btn_bg', '#1d6245');
    $callBg = $ppHex('product_call_btn_bg', '#164f38');
    $priceColor = $ppHex('product_price_color', '#164f38');
    $priceBoxBg = $ppHex('product_price_box_bg', '#f2f8f4');
    $thumbBorder = $ppHex('product_thumb_border_color', '#164f38');
    $cartColor = $ppDark($cartBg) ? '#ffffff' : '#103d2d';
    $buyColor = $ppDark($buyBg) ? '#ffffff' : '#103d2d';
    $callColor = $ppDark($callBg) ? '#ffffff' : '#103d2d';
    $trustBadges = $showTrust ? array_values(array_filter([
        ['icon' => $ppText('product_trust_badge_1_icon', '✓'), 'title' => $ppText('product_trust_badge_1_title', '১০০% প্রাকৃতিক'), 'desc' => $ppText('product_trust_badge_1_desc', 'কোনো কৃত্রিম রং নেই')],
        ['icon' => $ppText('product_trust_badge_2_icon', '✓'), 'title' => $ppText('product_trust_badge_2_title', 'স্বাস্থ্যসম্মত'), 'desc' => $ppText('product_trust_badge_2_desc', 'পরিচ্ছন্ন পরিবেশে প্রস্তুত')],
        ['icon' => $ppText('product_trust_badge_3_icon', '✓'), 'title' => $ppText('product_trust_badge_3_title', 'দেশি উপকরণ'), 'desc' => $ppText('product_trust_badge_3_desc', 'বিশ্বস্ত উৎস থেকে সংগৃহীত')],
    ], fn (array $badge): bool => $badge['title'] !== '' || $badge['desc'] !== '')) : [];
@endphp

@section('title', $product->name.' — '.($store['store_name'] ?? config('app.name', 'Store')))

@section('content')
    <style>
        .fn-store .detail-thumbs button.is-active { border-color: {{ $thumbBorder }}; }
        .fn-store .product-detail .purchase-row { grid-template-columns: auto auto minmax(0, 1fr); gap: 10px; align-items: stretch; }
        @media (min-width: 901px) {
            .fn-store .product-detail { grid-template-columns: minmax(280px, 0.78fr) minmax(0, 1.22fr); gap: 36px; }
        }
        .fn-store .product-detail .quantity { height: 46px; width: auto; min-width: 112px; padding: 0 2px; border: 1px solid #e4e4e6; border-radius: 10px; background: #f6f6f7; box-shadow: none; }
        .fn-store .product-detail .quantity button { width: 34px; height: 38px; flex-basis: 34px; border-radius: 8px; background: transparent; color: #3a3a3a; box-shadow: none; font-size: 20px; }
        .fn-store .product-detail .quantity button:hover { background: #fff; color: #111; }
        .fn-store .product-detail .quantity input { width: 26px; flex: 0 0 26px; color: #111; font-size: 16px; }
        .fn-store .product-detail .purchase-row .detail-cart { height: 46px; min-height: 46px; width: auto; padding: 0 22px; border-radius: 10px; background: #111 !important; color: #fff !important; box-shadow: none; justify-content: center; white-space: nowrap; }
        .fn-store .product-detail .purchase-row .buy-now { height: 46px; min-height: 46px; border-radius: 10px; padding-inline: 14px; background: #f0b400 !important; color: #1c1400 !important; border-color: #f0b400 !important; font-size: 15px; font-weight: 700; white-space: nowrap; }
        .fn-store .detail-arrow { position: absolute; z-index: 3; top: 50%; display: grid; place-items: center; width: 36px; height: 36px; border: 0; border-radius: 999px; background: rgba(255,255,255,.92); color: #103d2d; font-size: 22px; line-height: 1; transform: translateY(-50%); box-shadow: 0 8px 18px rgba(16,61,45,.16); }
        .fn-store .detail-arrow--prev { left: 12px; }
        .fn-store .detail-arrow--next { right: 12px; }
        .fn-store .detail-stock { display: inline-flex; align-items: center; min-height: 26px; padding: 0 10px; border-radius: 999px; background: #e7f1e9; color: #164f38; font-size: 12px; font-weight: 700; }
        .fn-store .detail-stock.is-out { background: #fde8ea; color: #820008; }
        .fn-store .detail-delivery-note { margin: -8px 0 18px; color: #66746c; font-size: 13px; font-weight: 600; }
        .fn-store .pp-copy--desk { display: none; }
        .fn-store .pp-copy--mob { display: inline; }
        @media (max-width: 700px) {
            .fn-store .product-detail .purchase-row { grid-template-columns: auto minmax(0, 1fr); gap: 10px; }
            .fn-store .product-detail .purchase-row .buy-now { grid-column: 1 / -1; }
            .fn-store .product-detail .quantity { min-width: 108px; border-radius: 8px; background: #fff; }
            .fn-store .product-detail .purchase-row .detail-cart,
            .fn-store .product-detail .purchase-row .buy-now { border-radius: 8px; }
        }
    </style>
    <div class="fn-container breadcrumb">
        <a href="{{ route('home') }}">হোম</a>
        <span>›</span>
        @if ($product->category)
            <a href="{{ route('shop.category', $product->category) }}">{{ $product->category->name }}</a>
            <span>›</span>
        @else
            <a href="{{ route('shop.index') }}">পণ্যসমূহ</a>
            <span>›</span>
        @endif
        <strong>{{ $product->name }}</strong>
    </div>

    <section class="fn-container product-detail">
        <div class="detail-gallery" data-product-gallery>
            <div class="detail-main-image" data-gallery-main>
                @include('store.partials.product-badges', ['product' => $product])
                <img
                    src="{{ $mainImageUrl }}"
                    alt="{{ $product->name }}"
                    id="product-main-image-element"
                    data-gallery-main-media
                >
                @if ($showZoom)
                    <button type="button" id="btn-gallery-zoom" class="detail-zoom" aria-label="Zoom image">
                        <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2M10.5 7.5v6m3-3h-6M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
                    </button>
                @endif
                @if ($showArrows)
                    <button type="button" class="detail-arrow detail-arrow--prev" data-gallery-prev aria-label="আগের ছবি">‹</button>
                    <button type="button" class="detail-arrow detail-arrow--next" data-gallery-next aria-label="পরের ছবি">›</button>
                @endif
            </div>
            @if ($galleryImages->count() > 1 || $galleryVideos->isNotEmpty())
                <div class="detail-thumbs" id="product-thumbnails-track">
                    @foreach ($galleryImages as $index => $image)
                        <button
                            type="button"
                            class="{{ $loop->first ? 'is-active' : '' }}"
                            data-gallery-thumb
                            data-thumb-index="{{ $index }}"
                            data-media-type="image"
                            data-media-src="{{ $image['url'] }}"
                            aria-label="Thumbnail {{ $loop->iteration }}"
                        >
                            <img src="{{ $image['url'] }}" alt="{{ $product->name }}">
                        </button>
                    @endforeach
                    @foreach ($galleryVideos as $video)
                        <button
                            type="button"
                            data-gallery-thumb
                            data-thumb-index="{{ $galleryImages->count() + $loop->index }}"
                            data-media-type="{{ $video->isYoutube() ? 'youtube' : 'video' }}"
                            data-media-src="{{ $video->isYoutube() ? $video->embedUrl() : asset('storage/'.$video->path) }}"
                            aria-label="Video thumbnail {{ $loop->iteration }}"
                        >
                            @if ($video->isYoutube())
                                <img src="{{ $video->thumbnailUrl() }}" alt="">
                            @else
                                <video src="{{ asset('storage/'.$video->path) }}" muted preload="metadata"></video>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="detail-content">
            @if ($showCategory && $product->category)
                <span class="detail-category">{{ $product->category->name }}</span>
            @endif
            <h1>{{ $product->name }}</h1>
            @if ($showShort && filled($product->short_description))
                <p class="detail-summary">{{ $product->short_description }}</p>
            @endif

            <div class="detail-price" @if ($showPriceBox) style="background: {{ $priceBoxBg }}; padding: 12px 16px; border-radius: 12px;" @endif>
                <del class="{{ $hasDiscount ? '' : 'hidden' }}" data-product-compare-price>
                    @if ($hasDiscount)
                        {{ \App\Support\Money::formatBn($product->compare_at_price) }}
                    @endif
                </del>
                <strong data-product-price style="color: {{ $priceColor }}">{{ \App\Support\Money::formatBn($product->price) }}</strong>
                @if ($showStock)
                    <span class="detail-stock {{ $totalStock > 0 ? '' : 'is-out' }}" data-product-stock>{{ $totalStock > 0 ? 'স্টকে আছে' : 'স্টক শেষ' }}</span>
                @endif
            </div>
            @if ($showDeliveryBadge)
                <p class="detail-delivery-note">{{ \Illuminate\Support\Str::of($deliveryText)->before("\n")->trim() }}</p>
            @endif

            @if ($hasVariants && ! empty($variationAttributes))
                <div data-variant-picker>
                    @foreach ($variationAttributes as $attrName => $attrValues)
                        <div class="detail-option" data-attribute-row="{{ $attrName }}">
                            <strong>{{ $attrName }}</strong>
                            <div>
                                @foreach ($attrValues as $valIndex => $valName)
                                    <button
                                        type="button"
                                        class="variant-attr-btn option {{ $valIndex === 0 ? 'active' : '' }}"
                                        data-attr="{{ $attrName }}"
                                        data-val="{{ $valName }}"
                                    >{{ $valName }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('cart.store') }}" data-cart-form id="single-product-form">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                @if ($hasVariants)
                    <input type="hidden" name="variant_id" id="selected_variant_id" value="" data-variant-id-input>
                @endif
                <div class="purchase-row">
                    <div class="quantity">
                        <button type="button" id="btn-qty-decrement" aria-label="Decrease quantity">&minus;</button>
                        <input
                            id="qty"
                            type="number"
                            name="qty"
                            value="1"
                            min="1"
                            max="{{ max(1, $totalStock) }}"
                            aria-label="পরিমাণ"
                            data-qty-input
                            {{ $totalStock <= 0 ? 'disabled' : '' }}
                        >
                        <button type="button" id="btn-qty-increment" aria-label="Increase quantity">+</button>
                    </div>
                    @if ($showCart)
                        <button type="submit" name="add_to_bag" value="1" data-add-to-bag class="btn btn--primary detail-cart" {{ $totalStock <= 0 ? 'disabled' : '' }}>
                            Add to cart
                        </button>
                    @endif
                    @if ($showBuyNow)
                        <button type="submit" name="buy_now" value="1" data-buy-now class="buy-now" style="background: {{ $buyBg }}; color: {{ $buyColor }}; border-color: {{ $buyBg }};" {{ $totalStock <= 0 ? 'disabled' : '' }}>
                            {{ $buyText }}
                        </button>
                    @endif
                </div>
            </form>

            @if ($showSku || ($showCategory && $product->category))
                <div class="detail-meta">
                    @if ($showSku)
                        <span><b>SKU:</b> <span data-product-sku>{{ $product->sku ?? 'N/A' }}</span></span>
                    @endif
                    @if ($showCategory && $product->category)
                        <span><b>ক্যাটাগরি:</b> <a href="{{ route('shop.category', $product->category) }}">{{ $product->category->name }}</a></span>
                    @endif
                </div>
            @endif

            @if ($showWhatsapp)
                <div class="whatsapp-row">
                    <span>কোনো প্রশ্ন ছিলো? লাইভ চ্যাট করুন</span>
                    <a
                        id="btn-whatsapp-order"
                        href="https://wa.me/{{ $whatsAppCleanNumber }}?text={{ urlencode(str_replace(['{product_name}', '{price}', '{url}'], [$product->name, number_format((float) $product->price, 0).'৳', request()->url()], $whatsAppMsgTemplate)) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        style="background: {{ $whatsappBg }};"
                    >{{ $whatsappText }}</a>
                </div>
            @endif
        </div>
    </section>

    <section class="fn-container product-info">
        <div class="tabs" role="tablist">
            <button class="tab active" type="button" data-tab="description">{{ $descTabTitle }}</button>
            <button class="tab" type="button" data-tab="delivery">{{ $deliveryTabTitle }}</button>
        </div>
        <div class="tab-panel active" id="description">
            <h2>প্রকৃতির স্বাদ, যত্নে প্রস্তুত</h2>
            @if (filled($product->description))
                <div class="prose">{!! \App\Support\HtmlSanitizer::clean($product->description) !!}</div>
            @else
                <p>বাছাই করা মানসম্মত ফল থেকে স্বাস্থ্যসম্মত পরিবেশে তৈরি আমাদের পণ্য। এতে কোনো কৃত্রিম রং, ক্ষতিকর প্রিজারভেটিভ বা অপ্রয়োজনীয় উপাদান ব্যবহার করা হয়নি।</p>
            @endif
            @if ($trustBadges !== [])
                <div class="info-grid">
                    @foreach ($trustBadges as $badge)
                        <article>
                            <span>{{ $badge['icon'] }}</span>
                            <div>
                                @if ($badge['title'] !== '')
                                    <strong>{{ $badge['title'] }}</strong>
                                @endif
                                @if ($badge['desc'] !== '')
                                    <small>{{ $badge['desc'] }}</small>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="tab-panel" id="delivery">
            <h2>{{ $deliveryTabTitle }}</h2>
            <p>{!! nl2br(e($deliveryText)) !!}</p>
            @if (trim($paymentText) !== '')
                <p>{!! nl2br(e($paymentText)) !!}</p>
            @endif
        </div>
    </section>

    @if ($showCall)
        <section class="fn-container">
            <div class="callout">
                <h3>যেকোনো প্রয়োজনে কিংবা সরাসরি ফোনে অর্ডার করতে কল করুন</h3>
                <div class="phone-pill">
                    <span>{{ $callCleanNumber ?: ($store['store_phone'] ?? '') }}</span>
                    <a href="tel:{{ $callCleanNumber ?: ($store['store_phone'] ?? '') }}" style="background: {{ $callBg }}; color: {{ $callColor }};">{{ $callText }}</a>
                </div>
            </div>
        </section>
    @endif

    @if ($showRelated && $relatedProducts->isNotEmpty())
        <section class="section related-section">
            <div class="fn-container">
                <div class="section-heading section-heading--row">
                    <div><h2>{{ $relatedTitle }}</h2></div>
                    <a href="{{ route('shop.index') }}">সব পণ্য <span>→</span></a>
                </div>
                <div class="product-grid">
                    @foreach ($relatedProducts as $related)
                        @include('store.partials.product-card', ['product' => $related])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <div id="gallery-lightbox-modal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <button type="button" id="lightbox-close-btn" class="absolute top-4 right-4 h-10 w-10 rounded-full bg-white/20 text-white hover:bg-white/40 flex items-center justify-center text-xl font-bold" aria-label="Close image modal">&times;</button>
        <div class="max-w-4xl max-h-[85vh] p-2 flex items-center justify-center">
            <img id="lightbox-modal-img" src="" alt="{{ $product->name }}" class="max-w-full max-h-[80vh] object-contain rounded-xl shadow-2xl">
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-product-gallery]');
            const main = root?.querySelector('[data-gallery-main]');
            const thumbs = Array.from(root?.querySelectorAll('[data-gallery-thumb]') || []);
            const productName = @json($product->name);
            const productPrice = {{ (float) $product->price }};
            const productUrl = @json(url()->current());
            const toBn = (value) => String(value).replace(/\d/g, (digit) => '০১২৩৪৫৬৭৮৯'[digit]);
            const formatPrice = (value) => '৳' + toBn(Math.round(value).toLocaleString('en-US'));

            document.querySelectorAll('.product-info .tab').forEach((tab) => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('.product-info .tab, .product-info .tab-panel').forEach((item) => item.classList.remove('active'));
                    tab.classList.add('active');
                    document.getElementById(tab.dataset.tab)?.classList.add('active');
                });
            });

            // Lightbox Modal
            const lightboxModal = document.getElementById('gallery-lightbox-modal');
            const lightboxImg = document.getElementById('lightbox-modal-img');
            const lightboxClose = document.getElementById('lightbox-close-btn');

            function openLightbox(src) {
                if (! lightboxModal || ! lightboxImg) return;
                lightboxImg.src = src;
                lightboxModal.classList.remove('hidden');
            }

            function closeLightbox() {
                if (! lightboxModal) return;
                lightboxModal.classList.add('hidden');
            }

            const zoomEnabled = @json($showZoom);
            if (zoomEnabled) {
                document.getElementById('btn-gallery-zoom')?.addEventListener('click', () => {
                    const curImg = document.getElementById('product-main-image-element');
                    if (curImg && curImg.src) openLightbox(curImg.src);
                });

                document.getElementById('product-main-image-element')?.addEventListener('click', (e) => {
                    openLightbox(e.target.src);
                });
            }

            lightboxClose?.addEventListener('click', closeLightbox);
            lightboxModal?.addEventListener('click', (e) => {
                if (e.target === lightboxModal) closeLightbox();
            });

            // Thumbnails click
            const showThumb = (thumb) => {
                const src = thumb.getAttribute('data-media-src');
                const mainImg = document.getElementById('product-main-image-element');
                if (mainImg && src && thumb.getAttribute('data-media-type') !== 'youtube' && thumb.getAttribute('data-media-type') !== 'video') {
                    mainImg.src = src;
                }
                thumbs.forEach((item) => item.classList.remove('is-active'));
                thumb.classList.add('is-active');
            };

            thumbs.forEach((thumb) => {
                thumb.addEventListener('click', () => showThumb(thumb));
            });

            const stepGallery = (direction) => {
                if (thumbs.length < 2) return;
                const current = Math.max(0, thumbs.findIndex((thumb) => thumb.classList.contains('is-active')));
                const next = (current + direction + thumbs.length) % thumbs.length;
                showThumb(thumbs[next]);
            };
            document.querySelector('[data-gallery-prev]')?.addEventListener('click', () => stepGallery(-1));
            document.querySelector('[data-gallery-next]')?.addEventListener('click', () => stepGallery(1));

            // Quantity buttons & Price updates
            const qtyInput = document.querySelector('[data-qty-input]');
            const btnDec = document.getElementById('btn-qty-decrement');
            const btnInc = document.getElementById('btn-qty-increment');
            const priceEl = document.querySelector('[data-product-price]');
            const comparePriceEl = document.querySelector('[data-product-compare-price]');

            const updatePriceByQty = () => {
                const currentVar = typeof findVariant === 'function' ? findVariant() : null;
                const basePrice = currentVar ? currentVar.price : productPrice;
                const baseCompare = currentVar ? (currentVar.compare_at_price || 0) : {{ (float) ($product->compare_at_price ?? 0) }};
                const qty = parseInt(qtyInput?.value, 10) || 1;

                if (priceEl) {
                    priceEl.textContent = formatPrice(basePrice * qty);
                }
                if (comparePriceEl) {
                    if (baseCompare > basePrice) {
                        comparePriceEl.textContent = formatPrice(baseCompare * qty);
                        comparePriceEl.classList.remove('hidden');
                    } else {
                        comparePriceEl.textContent = '';
                        comparePriceEl.classList.add('hidden');
                    }
                }
                updateWhatsAppLink();
            };

            btnDec?.addEventListener('click', () => {
                if (! qtyInput || qtyInput.disabled) return;
                const val = parseInt(qtyInput.value, 10) || 1;
                if (val > 1) {
                    qtyInput.value = val - 1;
                    updatePriceByQty();
                }
            });

            btnInc?.addEventListener('click', () => {
                if (! qtyInput || qtyInput.disabled) return;
                const val = parseInt(qtyInput.value, 10) || 1;
                const max = parseInt(qtyInput.max, 10) || 999;
                if (val < max) {
                    qtyInput.value = val + 1;
                    updatePriceByQty();
                }
            });

            qtyInput?.addEventListener('input', () => {
                updatePriceByQty();
            });

            // WhatsApp link generator
            const waBtn = document.getElementById('btn-whatsapp-order');
            const waNumber = @json($whatsAppCleanNumber);
            const waRawTemplate = @json($whatsAppMsgTemplate);

            function updateWhatsAppLink() {
                if (! waBtn || ! waNumber) return;
                const currentVar = typeof findVariant === 'function' ? findVariant() : null;
                const qty = parseInt(qtyInput?.value, 10) || 1;
                const currentPrice = currentVar ? currentVar.price : productPrice;
                const currentSku = currentVar ? (currentVar.sku || @json($product->sku)) : @json($product->sku);
                const varName = currentVar ? currentVar.name : '';

                let message = waRawTemplate
                    .replace('{product_name}', productName + (varName ? ' (' + varName + ')' : ''))
                    .replace('{price}', (currentPrice * qty) + '৳')
                    .replace('{sku}', currentSku || '')
                    .replace('{url}', productUrl);

                message += `\nপরিমাণ: ${qty} টি`;
                waBtn.href = `https://wa.me/${waNumber}?text=${encodeURIComponent(message)}`;
            }

            // Variants Logic
            const variants = @json($variantsJson);
            const attrButtons = document.querySelectorAll('.variant-attr-btn');
            const variantInput = document.querySelector('[data-variant-id-input]');
            const skuEl = document.querySelector('[data-product-sku]');
            const addToBagBtn = document.querySelector('[data-add-to-bag]');
            const buyNowBtn = document.querySelector('[data-buy-now]');

            const currentSelection = {};

            const updateActiveButtons = () => {
                attrButtons.forEach((btn) => {
                    const attr = btn.getAttribute('data-attr');
                    const val = btn.getAttribute('data-val');
                    const active = currentSelection[attr] === val;

                    if (active) {
                        btn.className = 'variant-attr-btn option active';
                    } else {
                        btn.className = 'variant-attr-btn option';
                    }
                });
            };

            function findVariant() {
                if (! Array.isArray(variants) || variants.length === 0) return null;
                const match = variants.find((v) => {
                    const vAttrs = v.attributes || {};
                    return Object.keys(currentSelection).every((key) => {
                        return String(vAttrs[key] || '') === String(currentSelection[key]);
                    });
                });
                return match || variants[0];
            }

            const syncSelection = () => {
                const current = findVariant();
                if (! current) return;

                if (variantInput) {
                    variantInput.value = current.id;
                }

                updatePriceByQty();

                if (skuEl && current.sku) {
                    skuEl.textContent = current.sku;
                }

                const stockEl = document.querySelector('[data-product-stock]');
                if (stockEl) {
                    const inStock = current.stock > 0;
                    stockEl.textContent = inStock ? 'স্টকে আছে' : 'স্টক শেষ';
                    stockEl.classList.toggle('is-out', ! inStock);
                }

                if (qtyInput) {
                    qtyInput.max = Math.max(1, current.stock);
                    qtyInput.disabled = current.stock <= 0;
                    if (parseInt(qtyInput.value, 10) > current.stock) {
                        qtyInput.value = Math.max(1, current.stock);
                    }
                }

                if (addToBagBtn) {
                    addToBagBtn.disabled = current.stock <= 0;
                }

                if (buyNowBtn) {
                    buyNowBtn.disabled = current.stock <= 0;
                }

                if (current.image_url) {
                    const mainImg = document.getElementById('product-main-image-element');
                    if (mainImg) mainImg.src = current.image_url;
                }
            };

            // Set initial selected variant
            attrButtons.forEach((btn) => {
                const attr = btn.getAttribute('data-attr');
                const val = btn.getAttribute('data-val');
                if (! currentSelection[attr]) {
                    currentSelection[attr] = val;
                }
            });
            updateActiveButtons();
            syncSelection();

            // Variant button click
            attrButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const attr = btn.getAttribute('data-attr');
                    const val = btn.getAttribute('data-val');
                    currentSelection[attr] = val;
                    updateActiveButtons();
                    syncSelection();
                });
            });

            // Add to Bag Interception
            addToBagBtn?.addEventListener('click', async (e) => {
                e.preventDefault();
                const current = typeof findVariant === 'function' ? findVariant() : null;
                const variantId = current ? current.id : (variantInput?.value || null);
                const qty = parseInt(qtyInput?.value || '1', 10) || 1;

                addToBagBtn.disabled = true;
                const origHtml = addToBagBtn.innerHTML;
                addToBagBtn.innerHTML = '<span>যুক্ত হচ্ছে...</span>';

                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch(@json(route('cart.store.modal')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            product_id: @json($product->id),
                            variant_id: variantId,
                            qty: qty,
                            buy_now: 0,
                        }),
                    });

                    const data = await res.json();
                    if (data.ok) {
                        if (window.StoreModal && typeof window.StoreModal.updateBadges === 'function') {
                            window.StoreModal.updateBadges(data.cart_count, data.subtotal);
                        } else {
                            document.querySelectorAll('[data-cart-count-badge], [data-cart-count-badge-mobile]').forEach(b => {
                                b.textContent = data.cart_count;
                                b.classList.toggle('hidden', data.cart_count <= 0);
                            });
                        }

                        if (typeof window.triggerAddToCartTracking === 'function' && data.tracked_event) {
                            window.triggerAddToCartTracking(data.tracked_event, {
                                id: @json((string) $product->id),
                                name: @json($product->name),
                                category: @json($product->category?->name ?? ''),
                            });
                        }

                        window.showToast(data.message || 'পণ্যটি ব্যাগে যোগ হয়েছে।', 'success');
                        addToBagBtn.innerHTML = '<span>✓ ব্যাগে যোগ হয়েছে</span>';
                        setTimeout(() => {
                            addToBagBtn.innerHTML = origHtml;
                            addToBagBtn.disabled = false;
                        }, 1800);
                    } else {
                        window.showToast(data.message || 'পণ্যটি ব্যাগে যোগ করা সম্ভব হয়নি।', 'error');
                        addToBagBtn.disabled = false;
                        addToBagBtn.innerHTML = origHtml;
                    }
                } catch (err) {
                    addToBagBtn.disabled = false;
                    addToBagBtn.innerHTML = origHtml;
                    document.getElementById('single-product-form')?.submit();
                }
            });

            // Cash on Delivery / Buy Now Interception
            buyNowBtn?.addEventListener('click', async (e) => {
                e.preventDefault();
                const current = typeof findVariant === 'function' ? findVariant() : null;
                const variantId = current ? current.id : (variantInput?.value || null);
                const qty = parseInt(qtyInput?.value || '1', 10) || 1;

                buyNowBtn.disabled = true;
                const origHtml = buyNowBtn.innerHTML;
                buyNowBtn.innerHTML = '<span>অর্ডার প্রক্রিয়া হচ্ছে...</span>';

                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch(@json(route('cart.store.modal')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            product_id: @json($product->id),
                            variant_id: variantId,
                            qty: qty,
                            buy_now: 1,
                        }),
                    });

                    const data = await res.json();
                    if (data.ok) {
                        if (typeof window.triggerAddToCartTracking === 'function' && data.tracked_event) {
                            window.triggerAddToCartTracking(data.tracked_event, {
                                id: @json((string) $product->id),
                                name: @json($product->name),
                                category: @json($product->category?->name ?? ''),
                            });
                        }
                        if (window.StoreModal) {
                            if (typeof window.StoreModal.updateBadges === 'function') {
                                window.StoreModal.updateBadges(data.cart_count, data.subtotal);
                            }
                            window.StoreModal.openCheckoutModal(data.checkout_data || null);
                        } else {
                            window.location.href = @json(route('checkout.create'));
                        }
                    } else {
                        window.showToast(data.message || 'অর্ডার সম্পন্ন করা সম্ভব হয়নি।', 'error');
                    }
                } catch (err) {
                    document.getElementById('single-product-form')?.submit();
                } finally {
                    buyNowBtn.disabled = false;
                    buyNowBtn.innerHTML = origHtml;
                }
            });
        })();
    </script>

    {{-- Preserved Tracking Scripts --}}
    <script>
        (() => {
            const productData = {
                id: @json((string) $product->id),
                name: @json($product->name),
                category: @json($product->category?->name ?? ''),
                price: {{ (float) $product->price }},
                currency: 'BDT'
            };

            const eventId = @json($trackedEvent['event_id'] ?? ('view_'.\Illuminate\Support\Str::uuid()->toString()));

            window.__tracked_events = window.__tracked_events || {};
            if (window.__tracked_events[eventId]) {
                return;
            }
            window.__tracked_events[eventId] = true;

            // Meta Pixel ViewContent
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

            // TikTok Pixel ViewContent
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

            // Google dataLayer view_item
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
@endpush
