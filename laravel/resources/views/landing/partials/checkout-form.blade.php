@php
    $themeColor = $landingPage->checkout_color ?: '#044120';
    $customer = $user ?? auth('web')->user();
    $defaultShipping = collect($shippingOptions)->firstWhere('is_default', true) ?? ($shippingOptions[0] ?? null);
    $defaultShippingAmount = (float) ($defaultShipping['amount'] ?? 0);
    $isMultipleMode = $landingPage->isMultipleChoice();
    $showQuantity = (bool) ($landingPage->show_quantity ?? true);
    $assigned = $assignedProducts ?? collect();
    $sslCommerzActive = !empty($sslCommerzEnabled);
    $manualPaymentActive = !empty($manualPaymentEnabled);
    $firstAssigned = $assigned->firstWhere('is_default', true) ?? $assigned->first();
    $defaultProd = $firstAssigned?->product ?? $product;
    $defaultUnitPrice = $firstAssigned && method_exists($firstAssigned, 'effectivePrice')
        ? (float) $firstAssigned->effectivePrice()
        : (float) $effectivePrice;

    $checkoutTitle = $landingPage->checkout_heading ?: 'অর্ডার কনফার্ম করতে নিচের তথ্যগুলো পূরণ করুন';
    $checkoutSubtitle = $landingPage->checkout_subheading ?: 'সব তথ্য ঠিক থাকলে অর্ডার কনফার্ম বাটনে ক্লিক করুন';
    $productLabel = $landingPage->product_selection_label ?: ($isMultipleMode ? 'পণ্য নির্বাচন করুন (একের অধিক পণ্য বেছে নিতে পারেন):' : 'পণ্য নির্বাচন করুন:');
    $variantLabel = $landingPage->variant_label ?: 'পরিমাণ / সাইজ / কালার নির্বাচন করুন:';
@endphp

<style>
    .landing-theme-border { border-color: {{ $themeColor }} !important; }
    .landing-theme-bg { background-color: {{ $themeColor }} !important; }
    .landing-theme-text { color: {{ $themeColor }} !important; }
    .landing-card-active {
        border-color: {{ $themeColor }} !important;
        background-color: {{ $themeColor }}0a !important;
    }
    .landing-pill-active {
        border-color: {{ $themeColor }} !important;
        background-color: {{ $themeColor }}14 !important;
        color: {{ $themeColor }} !important;
    }
    #checkout-section .shipping-option-card:has(.shipping-radio:checked) {
        border-color: {{ $themeColor }} !important;
        background-color: {{ $themeColor }}0a !important;
    }

    /* One clear Bangla font for the whole checkout (text + digits) */
    #checkout-section,
    #checkout-section * {
        font-family: 'Noto Sans Bengali', system-ui, sans-serif !important;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }
    #checkout-section h2,
    #checkout-section h3,
    #checkout-section h4 {
        letter-spacing: -0.01em;
        line-height: 1.4;
        font-weight: 700;
    }
    #checkout-section label {
        letter-spacing: 0.01em;
        line-height: 1.5;
        font-weight: 600;
    }
    #checkout-section input,
    #checkout-section textarea,
    #checkout-section select {
        font-size: 15px;
        line-height: 1.55;
        letter-spacing: 0.01em;
        font-weight: 500;
    }
    #checkout-section .shipping-amount-badge,
    #checkout-section .card-display-price,
    #checkout-section .variant-price,
    #checkout-section .variant-pill,
    #checkout-section .input-item-qty,
    #checkout-section #summary-total,
    #checkout-section #summary-subtotal,
    #checkout-section #summary-delivery,
    #checkout-section #order-row-item-subtotal,
    #checkout-section #mobile-sticky-price {
        font-weight: 700 !important;
        letter-spacing: 0.03em;
    }
    #checkout-section .card-display-price {
        font-size: 15px !important;
        font-weight: 800 !important;
    }
    #checkout-section .variant-pill {
        font-size: 12.5px !important;
        line-height: 1.45 !important;
        font-weight: 600 !important;
    }
    #checkout-section .variant-pill .variant-price {
        font-weight: 800 !important;
    }
    #checkout-section .input-item-qty {
        font-size: 13px !important;
        font-weight: 700 !important;
    }
    #checkout-section .multi-product-card h4,
    #checkout-section .single-package-card h4 {
        font-weight: 800 !important;
        letter-spacing: 0.01em;
    }

    /* Product picker — memo-pack layout (theme color based) */
    #checkout-section {
        --lp-ink: #1f2937;
        --lp-sub: #6b7280;
        --lp-line: color-mix(in srgb, {{ $themeColor }} 18%, #e8e4de);
        --lp-paper: #fff;
        --lp-checked: color-mix(in srgb, {{ $themeColor }} 9%, #fff8f0);
        --lp-chili: #dc2626;
        --lp-theme: {{ $themeColor }};
    }
    #checkout-section .lp-prod-pick {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 4px;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.3;
        color: var(--lp-ink);
    }
    #checkout-section .lp-prod-pick::before {
        content: "";
        flex: none;
        width: 3px;
        height: 16px;
        border-radius: 2px;
        background: var(--lp-theme);
    }
    #checkout-section .lp-prod-pick span {
        padding: 0 4px;
        border-radius: 3px;
        background: linear-gradient(transparent 12%, color-mix(in srgb, {{ $themeColor }} 22%, #ffe066) 12%, color-mix(in srgb, {{ $themeColor }} 22%, #ffe066) 88%, transparent 88%);
    }
    #checkout-section .lp-prod-note {
        margin: 0 0 8px;
        font-size: 11px;
        color: var(--lp-sub);
    }
    #checkout-section .lp-prod-list {
        position: relative;
        display: grid !important;
        grid-template-columns: 1fr;
        gap: 8px !important;
        border-radius: 0;
        background: transparent;
        overflow: visible;
        box-shadow: none;
    }
    @media (min-width: 768px) {
        #checkout-section .lp-prod-list {
            grid-template-columns: 1fr 1fr;
            gap: 8px !important;
        }
    }
    #checkout-section .lp-prod-row {
        position: relative;
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 8px;
        min-height: 0;
        padding: 8px 10px !important;
        border: 0 !important;
        border-radius: 10px !important;
        background: #fff !important;
        box-shadow: inset 0 0 0 1px var(--lp-line);
        transition: background-color .22s ease;
    }
    #checkout-section .lp-prod-row + .lp-prod-row {
        border-top: 0 !important;
    }
    #checkout-section .lp-prod-row.landing-card-active,
    #checkout-section .lp-prod-row[data-checked="true"] {
        background: var(--lp-checked) !important;
    }
    #checkout-section .lp-prod-label {
        display: grid;
        grid-template-columns: 48px 1fr;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        min-width: 0;
        position: relative;
    }
    #checkout-section .lp-prod-ph {
        position: relative;
        width: 48px;
        height: 48px;
        flex-shrink: 0;
    }
    #checkout-section .lp-prod-ph img {
        display: block;
        width: 100% !important;
        height: 100% !important;
        border-radius: 8px;
        object-fit: cover;
        background: #f3f4f6;
        filter: saturate(.82);
        transition: filter .2s ease, transform .16s ease;
    }
    #checkout-section .lp-prod-row.landing-card-active .lp-prod-ph img,
    #checkout-section .lp-prod-row[data-checked="true"] .lp-prod-ph img {
        filter: none;
        box-shadow: inset 0 0 0 1.5px var(--lp-theme);
    }
    #checkout-section .lp-prod-dot {
        position: absolute;
        z-index: 1;
        right: -5px;
        top: -5px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        box-shadow: inset 0 0 0 1.2px color-mix(in srgb, {{ $themeColor }} 40%, #94a3b8), 0 1px 2px rgba(0,0,0,.12);
        display: grid;
        place-items: center;
    }
    #checkout-section .lp-prod-dot svg {
        width: 10px;
        height: 10px;
        color: #fff;
        opacity: 0;
        transform: scale(.4);
        transition: opacity .18s ease, transform .22s ease;
    }
    #checkout-section .lp-prod-row.landing-card-active .lp-prod-dot,
    #checkout-section .lp-prod-row[data-checked="true"] .lp-prod-dot {
        background: var(--lp-theme);
        box-shadow: none;
    }
    #checkout-section .lp-prod-row.landing-card-active .lp-prod-dot svg,
    #checkout-section .lp-prod-row[data-checked="true"] .lp-prod-dot svg {
        opacity: 1;
        transform: none;
    }
    #checkout-section .lp-prod-lbl {
        min-width: 0;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.25;
        color: var(--lp-ink);
    }
    #checkout-section .lp-prod-lbl b {
        display: block;
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #checkout-section .lp-prod-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
        margin-top: 1px;
    }
    #checkout-section .lp-prod-sz {
        font-size: 11px;
        font-weight: 600;
        color: var(--lp-sub);
        white-space: nowrap;
    }
    #checkout-section .lp-prod-off {
        display: inline-block;
        padding: 1px 7px;
        border-radius: 999px;
        background: var(--lp-chili);
        color: #fff;
        font-size: 10px;
        font-weight: 800;
        font-style: normal;
        line-height: 1.4;
    }
    #checkout-section .lp-prod-end {
        position: relative;
        z-index: 1;
        display: grid;
        justify-items: end;
        gap: 4px;
    }
    #checkout-section .lp-prod-pr {
        font-size: 15px;
        font-weight: 900;
        line-height: 1.15;
        color: var(--lp-ink);
        font-variant-numeric: tabular-nums;
        transition: color .2s ease;
        white-space: nowrap;
    }
    #checkout-section .lp-prod-row.landing-card-active .lp-prod-pr,
    #checkout-section .lp-prod-row[data-checked="true"] .lp-prod-pr {
        color: var(--lp-theme);
    }
    #checkout-section .lp-prod-pr .card-display-price {
        font-size: inherit !important;
        font-weight: inherit !important;
        color: inherit !important;
    }
    #checkout-section .lp-prod-pr s,
    #checkout-section .lp-prod-pr .card-compare-price {
        display: inline-block;
        margin-left: 5px;
        font-size: 11px;
        font-weight: 700;
        color: var(--lp-sub);
        text-decoration: line-through;
        text-decoration-color: var(--lp-chili);
        text-decoration-thickness: 1.2px;
    }
    #checkout-section .lp-prod-qty {
        display: none;
        pointer-events: auto;
    }
    #checkout-section .lp-prod-row.landing-card-active .lp-prod-qty,
    #checkout-section .lp-prod-row[data-checked="true"] .lp-prod-qty {
        display: inline-flex;
        animation: lp-qty-in .26s ease;
    }
    #checkout-section .lp-qty-pill {
        display: inline-flex;
        align-items: center;
        border: 0;
        border-radius: 999px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,.08), 0 3px 8px -5px rgba(0,0,0,.25);
    }
    #checkout-section .lp-qty-pill .btn-qty-minus,
    #checkout-section .lp-qty-pill .btn-qty-plus {
        min-width: 28px;
        min-height: 28px;
        border: 0;
        background: transparent;
        color: var(--lp-theme);
        font-size: 15px;
        font-weight: 700;
        line-height: 1;
        padding: 0;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    #checkout-section .lp-qty-pill .btn-qty-minus:hover,
    #checkout-section .lp-qty-pill .btn-qty-plus:hover {
        background: color-mix(in srgb, {{ $themeColor }} 10%, transparent);
    }
    #checkout-section .lp-qty-pill .input-item-qty {
        min-width: 18px;
        width: 22px;
        text-align: center;
        border: 0;
        background: transparent;
        font-size: 13px !important;
        font-weight: 700 !important;
        color: var(--lp-ink);
        padding: 0;
        -moz-appearance: textfield;
    }
    #checkout-section .lp-qty-pill .input-item-qty::-webkit-outer-spin-button,
    #checkout-section .lp-qty-pill .input-item-qty::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    @keyframes lp-qty-in {
        from { opacity: 0; transform: translateY(-3px) scale(.96); }
        to { opacity: 1; transform: none; }
    }
    @media (max-width: 480px) {
        #checkout-section .lp-prod-ph { width: 44px; height: 44px; }
        #checkout-section .lp-prod-label { grid-template-columns: 44px 1fr; gap: 7px; }
        #checkout-section .lp-prod-lbl { font-size: 12.5px; }
        #checkout-section .lp-prod-pr { font-size: 14px; }
        #checkout-section .lp-prod-pick { font-size: 14px; }
        #checkout-section .lp-prod-row { padding: 7px 8px !important; }
    }
    #checkout-section .order-item-row,
    #checkout-section .order-item-name,
    #checkout-section .order-item-qty,
    #checkout-section .order-item-price,
    #checkout-section #order-row-item-name,
    #checkout-section #order-row-item-subtotal {
        font-family: 'Noto Sans Bengali', system-ui, sans-serif !important;
        letter-spacing: 0.02em;
        -webkit-font-smoothing: antialiased;
    }
    #checkout-section .order-item-name,
    #checkout-section #order-row-item-name {
        font-size: 13px;
        font-weight: 600 !important;
        line-height: 1.45;
    }
    #checkout-section .order-item-price,
    #checkout-section #order-row-item-subtotal {
        font-size: 14px;
        font-weight: 800 !important;
        letter-spacing: 0.03em;
    }
</style>

<section id="checkout-section" class="py-6 sm:py-12 px-2 sm:px-6 max-w-5xl xl:max-w-6xl mx-auto scroll-mt-14">
    <div class="bg-white rounded-2xl md:rounded-3xl shadow-xl overflow-hidden border border-slate-200/80">

        <div class="px-4 py-6 sm:px-8 sm:py-8 text-center text-white relative flex flex-col items-center justify-center" style="background-color: {{ $themeColor }} !important;">
            <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-white flex items-center justify-center shadow-md mb-2 sm:mb-3 shrink-0">
                <svg class="w-6 h-6 sm:w-8 sm:h-8" style="color: {{ $themeColor }};" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>

            @if ($checkoutSubtitle)
                <p class="text-xs sm:text-base text-white/95 font-medium leading-relaxed tracking-normal">
                    {{ $checkoutSubtitle }}
                </p>
            @endif
            <h2 class="text-xl sm:text-3xl md:text-4xl font-black tracking-tight mt-1 text-white">
                {{ $checkoutTitle }}
            </h2>
        </div>

        <form id="landing-order-form" action="{{ route('landing.checkout', $landingPage->slug) }}" method="POST" class="p-4 sm:p-8 lg:p-10 space-y-7">
            @csrf

            <div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                <input type="text" name="hp_email" tabindex="-1" autocomplete="off" value="">
                <input type="text" name="website_url" tabindex="-1" autocomplete="off" value="">
            </div>

            <input type="hidden" name="_submission_time" value="{{ encrypt(time()) }}">

            <div id="checkout-error-box" class="hidden rounded-xl bg-red-50 border border-red-200 p-3 text-xs sm:text-sm text-red-700">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span id="checkout-error-message"></span>
                </div>
            </div>

            <div class="space-y-2.5">
                <p class="lp-prod-pick"><span>{{ $productLabel ?: 'পণ্য ও পরিমাণ বেছে নিন' }}</span></p>
                @if ($isMultipleMode)
                    <p class="lp-prod-note">একাধিক বেছে নিতে পারেন</p>
                @endif

                @if ($isMultipleMode)
                    <div class="lp-prod-list" id="multi-product-list" role="group">
                        @foreach ($assigned as $index => $item)
                            @php
                                $prod = $item->product ?? $item;
                                $pImg = $prod->primaryImagePath() ? asset('storage/' . $prod->primaryImagePath()) : asset('images/placeholder.png');
                                $basePrice = method_exists($item, 'effectivePrice') ? $item->effectivePrice() : (float) $prod->price;
                                $isDefault = (bool) ($item->is_default ?? ($index === 0));
                                $hasVariants = $prod->variants->isNotEmpty();
                                $initialVariant = $hasVariants ? $prod->variants->first() : null;
                                $initialPrice = $initialVariant && (float) $initialVariant->price > 0 ? (float) $initialVariant->price : $basePrice;
                                $initialCompare = $initialVariant && (float) $initialVariant->compare_at_price > 0
                                    ? (float) $initialVariant->compare_at_price
                                    : ($prod->compare_at_price ? (float) $prod->compare_at_price : 0);
                                $unitLabel = \App\Support\Money::formatUnit($prod->unit ?? null);
                                $discountPct = ($initialCompare > $initialPrice && $initialCompare > 0)
                                    ? (int) round((($initialCompare - $initialPrice) / $initialCompare) * 100)
                                    : 0;
                            @endphp
                            <div class="multi-product-card lp-prod-row {{ $isDefault ? 'landing-card-active' : '' }}" data-checked="{{ $isDefault ? 'true' : 'false' }}" data-product-id="{{ $prod->id }}" data-price="{{ $initialPrice }}" data-compare="{{ $initialCompare ?: '' }}" data-name="{{ $prod->name }}">
                                <label class="lp-prod-label">
                                    <input type="checkbox" name="selected_products[]" value="{{ $prod->id }}" class="product-checkbox sr-only" {{ $isDefault ? 'checked' : '' }}>
                                    <span class="lp-prod-ph" aria-hidden="true">
                                        <img src="{{ $pImg }}" alt="{{ $prod->name }}" width="68" height="68" loading="lazy" decoding="async">
                                        <span class="lp-prod-dot">
                                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.25-3.25a1 1 0 011.414-1.414L8.5 11.586l6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </span>
                                    </span>
                                    <span class="lp-prod-lbl">
                                        <b>{{ $prod->name }}</b>
                                        <span class="lp-prod-meta">
                                            @if ($unitLabel)<span class="lp-prod-sz">{{ $unitLabel }}</span>@endif
                                            <em class="lp-prod-off {{ $discountPct > 0 ? '' : 'hidden' }}" data-discount-badge>{{ $discountPct > 0 ? \App\Support\Money::toBengali($discountPct) . '% ছাড়' : '' }}</em>
                                        </span>
                                    </span>
                                </label>
                                <div class="lp-prod-end">
                                    <span class="lp-prod-pr">
                                        <span class="card-display-price">{{ \App\Support\Money::formatBn($initialPrice) }}</span>
                                        <s class="card-compare-price {{ ($initialCompare > $initialPrice) ? '' : 'hidden' }}">{{ $initialCompare > $initialPrice ? \App\Support\Money::formatBn($initialCompare) : '' }}</s>
                                    </span>
                                    @if ($showQuantity)
                                        <div class="lp-prod-qty">
                                            <div class="lp-qty-pill" role="group" aria-label="{{ $prod->name }} পরিমাণ">
                                                <button type="button" class="btn-qty-minus" aria-label="কমান">−</button>
                                                <input type="number" name="quantities[{{ $prod->id }}]" value="1" min="1" max="50" class="input-item-qty" readonly>
                                                <button type="button" class="btn-qty-plus" aria-label="বাড়ান">+</button>
                                            </div>
                                        </div>
                                    @else
                                        <input type="hidden" name="quantities[{{ $prod->id }}]" value="1" class="input-item-qty">
                                    @endif
                                </div>
                                @if ($hasVariants)
                                    <div class="variant-selector-box col-span-full mt-1 pt-3 border-t border-slate-100 {{ $isDefault ? '' : 'opacity-60 pointer-events-none' }}" style="grid-column: 1 / -1;">
                                        <div class="flex flex-wrap items-center gap-3">
                                            @foreach ($prod->variants as $vIdx => $v)
                                                @php
                                                    $vPrice = (float) ($v->price > 0 ? $v->price : $basePrice);
                                                    $varDel = $landingPage->variantDeliveryConfig($v);
                                                    $isVarFree = ($varDel['type'] === 'free') || ($landingPage->delivery_charge_type === 'free');
                                                    $isSelected = ($isDefault && $vIdx === 0);
                                                @endphp
                                                <div class="relative inline-block">
                                                    @if ($isVarFree)
                                                        <span class="absolute -top-3.5 right-0 z-10 whitespace-nowrap px-2 py-0.5 rounded-full text-[9.5px] font-black leading-tight shadow-sm uppercase select-none tracking-tight pointer-events-none" style="background-color: #ecfdf5 !important; color: #047857 !important; border: 1.5px solid #10b981;">ফ্রি ডেলিভারি</span>
                                                    @endif
                                                    <label class="variant-pill relative inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs cursor-pointer transition shadow-2xs select-none {{ $isSelected ? 'landing-pill-active font-bold' : 'border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 font-medium text-slate-700' }}" data-price="{{ $vPrice }}" data-compare="{{ $v->compare_at_price ?: '' }}" data-delivery-type="{{ $varDel['type'] }}" data-delivery-inside="{{ $varDel['inside'] ?? '' }}" data-delivery-outside="{{ $varDel['outside'] ?? '' }}" style="{{ $isSelected ? 'border-color: ' . $themeColor . ' !important; background-color: ' . $themeColor . '18 !important; color: ' . $themeColor . ' !important;' : 'border-color: #e2e8f0 !important; background-color: #ffffff !important; color: #475569 !important;' }}">
                                                        <input type="radio" name="variants[{{ $prod->id }}]" value="{{ $v->id }}" class="variant-radio sr-only" data-price="{{ $vPrice }}" data-compare="{{ $v->compare_at_price ?: '' }}" data-delivery-type="{{ $varDel['type'] }}" data-delivery-inside="{{ $varDel['inside'] ?? '' }}" data-delivery-outside="{{ $varDel['outside'] ?? '' }}" {{ $isSelected ? 'checked' : '' }} {{ $isDefault ? '' : 'disabled' }}>
                                                        <span class="variant-dot w-2 h-2 rounded-full shrink-0" style="background-color: {{ $isSelected ? $themeColor : '#94a3b8' }};"></span>
                                                        <span class="truncate">{{ $v->label() ?? 'Variant ' . ($vIdx + 1) }}</span>
                                                        <span class="variant-price font-bold ml-0.5 shrink-0" style="color: {{ $isSelected ? $themeColor : '#64748b' }};">{{ \App\Support\Money::formatBn($vPrice) }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($assigned->count() > 1)
                    <div class="lp-prod-list" id="single-package-list" role="group">
                        @foreach ($assigned as $index => $item)
                            @php
                                $prod = $item->product ?? $item;
                                $pImg = $prod->primaryImagePath() ? asset('storage/' . $prod->primaryImagePath()) : asset('images/placeholder.png');
                                $basePrice = method_exists($item, 'effectivePrice') ? $item->effectivePrice() : (float) $prod->price;
                                $isDefault = (bool) ($item->is_default ?? ($index === 0));
                                $hasVariants = $prod->variants->isNotEmpty();
                                $initialVariant = $hasVariants ? $prod->variants->first() : null;
                                $initialPrice = $initialVariant && (float) $initialVariant->price > 0 ? (float) $initialVariant->price : $basePrice;
                                $initialCompare = $initialVariant && (float) $initialVariant->compare_at_price > 0
                                    ? (float) $initialVariant->compare_at_price
                                    : ($prod->compare_at_price ? (float) $prod->compare_at_price : 0);
                                $unitLabel = \App\Support\Money::formatUnit($prod->unit ?? null);
                                $discountPct = ($initialCompare > $initialPrice && $initialCompare > 0)
                                    ? (int) round((($initialCompare - $initialPrice) / $initialCompare) * 100)
                                    : 0;
                            @endphp
                            <div class="single-package-card lp-prod-row cursor-pointer {{ $isDefault ? 'landing-card-active' : '' }}" data-checked="{{ $isDefault ? 'true' : 'false' }}" data-product-id="{{ $prod->id }}" data-price="{{ $initialPrice }}" data-compare="{{ $initialCompare ?: '' }}" data-name="{{ $prod->name }}">
                                <label class="lp-prod-label">
                                    <input type="radio" name="product_id" value="{{ $prod->id }}" class="package-radio sr-only" {{ $isDefault ? 'checked' : '' }}>
                                    <span class="lp-prod-ph" aria-hidden="true">
                                        <img src="{{ $pImg }}" alt="{{ $prod->name }}" width="68" height="68" loading="lazy" decoding="async">
                                        <span class="lp-prod-dot">
                                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.25-3.25a1 1 0 011.414-1.414L8.5 11.586l6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </span>
                                    </span>
                                    <span class="lp-prod-lbl">
                                        <b>{{ $prod->name }}</b>
                                        <span class="lp-prod-meta">
                                            @if ($unitLabel)<span class="lp-prod-sz">{{ $unitLabel }}</span>@endif
                                            <em class="lp-prod-off {{ $discountPct > 0 ? '' : 'hidden' }}" data-discount-badge>{{ $discountPct > 0 ? \App\Support\Money::toBengali($discountPct) . '% ছাড়' : '' }}</em>
                                        </span>
                                    </span>
                                </label>
                                <div class="lp-prod-end">
                                    <span class="lp-prod-pr">
                                        <span class="card-display-price">{{ \App\Support\Money::formatBn($initialPrice) }}</span>
                                        <s class="card-compare-price {{ ($initialCompare > $initialPrice) ? '' : 'hidden' }}">{{ $initialCompare > $initialPrice ? \App\Support\Money::formatBn($initialCompare) : '' }}</s>
                                    </span>
                                </div>
                                @if ($hasVariants)
                                    <div class="variant-selector-box col-span-full mt-1 pt-3 border-t border-slate-100 {{ $isDefault ? '' : 'opacity-60 pointer-events-none' }}" style="grid-column: 1 / -1;">
                                        <div class="flex flex-wrap items-center gap-3">
                                            @foreach ($prod->variants as $vIdx => $v)
                                                @php
                                                    $vPrice = (float) ($v->price > 0 ? $v->price : $basePrice);
                                                    $varDel = $landingPage->variantDeliveryConfig($v);
                                                    $isVarFree = ($varDel['type'] === 'free') || ($landingPage->delivery_charge_type === 'free');
                                                    $isSelected = ($isDefault && $vIdx === 0);
                                                @endphp
                                                <div class="relative inline-block">
                                                    @if ($isVarFree)
                                                        <span class="absolute -top-3.5 right-0 z-10 whitespace-nowrap px-2 py-0.5 rounded-full text-[9.5px] font-black leading-tight shadow-sm uppercase select-none tracking-tight pointer-events-none" style="background-color: #ecfdf5 !important; color: #047857 !important; border: 1.5px solid #10b981;">ফ্রি ডেলিভারি</span>
                                                    @endif
                                                    <label class="variant-pill relative inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs cursor-pointer transition shadow-2xs select-none {{ $isSelected ? 'landing-pill-active font-bold' : 'border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 font-medium text-slate-700' }}" data-price="{{ $vPrice }}" data-compare="{{ $v->compare_at_price ?: '' }}" data-delivery-type="{{ $varDel['type'] }}" data-delivery-inside="{{ $varDel['inside'] ?? '' }}" data-delivery-outside="{{ $varDel['outside'] ?? '' }}" style="{{ $isSelected ? 'border-color: ' . $themeColor . ' !important; background-color: ' . $themeColor . '18 !important; color: ' . $themeColor . ' !important;' : 'border-color: #e2e8f0 !important; background-color: #ffffff !important; color: #475569 !important;' }}">
                                                        <input type="radio" name="variant_id" value="{{ $v->id }}" class="variant-radio sr-only" data-product-id="{{ $prod->id }}" data-price="{{ $vPrice }}" data-compare="{{ $v->compare_at_price ?: '' }}" data-delivery-type="{{ $varDel['type'] }}" data-delivery-inside="{{ $varDel['inside'] ?? '' }}" data-delivery-outside="{{ $varDel['outside'] ?? '' }}" {{ $isSelected ? 'checked' : '' }} {{ $isDefault ? '' : 'disabled' }}>
                                                        <span class="variant-dot w-2 h-2 rounded-full shrink-0" style="background-color: {{ $isSelected ? $themeColor : '#94a3b8' }};"></span>
                                                        <span class="truncate">{{ $v->label() ?? 'Variant ' . ($vIdx + 1) }}</span>
                                                        <span class="variant-price font-bold ml-0.5 shrink-0" style="color: {{ $isSelected ? $themeColor : '#64748b' }};">{{ \App\Support\Money::formatBn($vPrice) }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    @php
                        $prod = $defaultProd;
                        $pImg = $prod->primaryImagePath() ? asset('storage/' . $prod->primaryImagePath()) : asset('images/placeholder.png');
                        $basePrice = $defaultUnitPrice;
                        $hasVariants = $prod->variants->isNotEmpty();
                        $initialVariant = $hasVariants ? $prod->variants->first() : null;
                        $initialPrice = $initialVariant && (float) $initialVariant->price > 0 ? (float) $initialVariant->price : $basePrice;
                    @endphp
                    <input type="hidden" name="product_id" value="{{ $prod->id }}">
                    <div id="single-product-main-card" class="bg-slate-50/70 border border-slate-200 rounded-xl p-3 flex items-center gap-3" data-price="{{ $initialPrice }}" data-product-id="{{ $prod->id }}" data-name="{{ $prod->name }}">
                        <img src="{{ $pImg }}" alt="{{ $prod->name }}" class="w-14 h-14 object-cover rounded-lg border border-slate-200 bg-white shrink-0">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 text-sm leading-snug truncate">{{ $prod->name }}</h3>
                            <div class="mt-1 flex items-center justify-between">
                                <span class="card-display-price text-base font-black text-slate-900">{{ \App\Support\Money::formatBn($initialPrice) }}</span>
                            </div>
                        </div>
                    </div>

                    @if ($hasVariants)
                        <div class="variant-selector-box mt-3 p-3.5 rounded-xl border border-slate-200 bg-white">
                            <label class="block text-xs font-bold text-slate-700 mb-2">{{ $variantLabel }}</label>
                            <div class="flex flex-wrap items-center gap-3">
                                @foreach ($prod->variants as $vIdx => $v)
                                    @php
                                        $vPrice = (float) ($v->price > 0 ? $v->price : $basePrice);
                                        $varDel = $landingPage->variantDeliveryConfig($v);
                                        $isVarFree = ($varDel['type'] === 'free') || ($landingPage->delivery_charge_type === 'free');
                                        $isSelected = ($vIdx === 0);
                                    @endphp
                                    <div class="relative inline-block">
                                        @if ($isVarFree)
                                            <span class="absolute -top-3.5 right-0 z-10 whitespace-nowrap px-2 py-0.5 rounded-full text-[9.5px] font-black leading-tight shadow-sm uppercase select-none tracking-tight pointer-events-none" style="background-color: #ecfdf5 !important; color: #047857 !important; border: 1.5px solid #10b981;">ফ্রি ডেলিভারি</span>
                                        @endif
                                        <label class="variant-pill relative inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs cursor-pointer transition shadow-2xs select-none {{ $isSelected ? 'landing-pill-active font-bold' : 'border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 font-medium text-slate-700' }}" data-price="{{ $vPrice }}" data-compare="{{ $v->compare_at_price ?: '' }}" data-delivery-type="{{ $varDel['type'] }}" data-delivery-inside="{{ $varDel['inside'] ?? '' }}" data-delivery-outside="{{ $varDel['outside'] ?? '' }}" style="{{ $isSelected ? 'border-color: ' . $themeColor . ' !important; background-color: ' . $themeColor . '18 !important; color: ' . $themeColor . ' !important;' : 'border-color: #e2e8f0 !important; background-color: #ffffff !important; color: #475569 !important;' }}">
                                            <input type="radio" name="variant_id" value="{{ $v->id }}" class="variant-radio sr-only" data-price="{{ $vPrice }}" data-compare="{{ $v->compare_at_price ?: '' }}" data-delivery-type="{{ $varDel['type'] }}" data-delivery-inside="{{ $varDel['inside'] ?? '' }}" data-delivery-outside="{{ $varDel['outside'] ?? '' }}" {{ $isSelected ? 'checked' : '' }}>
                                            <span class="variant-dot w-2 h-2 rounded-full shrink-0" style="background-color: {{ $isSelected ? $themeColor : '#94a3b8' }};"></span>
                                            <span class="truncate">{{ $v->label() ?? 'Variant ' . ($vIdx + 1) }}</span>
                                            <span class="variant-price font-bold ml-0.5 shrink-0" style="color: {{ $isSelected ? $themeColor : '#64748b' }};">{{ \App\Support\Money::formatBn($vPrice) }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 pt-2">
                <div class="lg:col-span-7 space-y-4 sm:space-y-5">
                    <h3 class="text-base sm:text-lg font-bold border-b border-slate-100 pb-1.5" style="color: {{ $themeColor }};">
                        {{ $landingPage->checkoutField('billing_title', 'Billing details') }}
                    </h3>

                    @php
                        $resolvedFields = $landingPage->resolvedCheckoutFields();
                    @endphp

                    @foreach ($resolvedFields as $fld)
                        @if (empty($fld['enabled'])) @continue @endif

                        @if ($fld['key'] === 'customer_name')
                            <div>
                                <label for="customer_name" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    {{ $fld['label'] ?: 'আপনার নাম' }} @if(!empty($fld['required']))<span class="text-red-500">*</span>@endif
                                </label>
                                <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $customer?->name ?? '') }}" {{ !empty($fld['required']) ? 'required' : '' }} autocomplete="name" placeholder="{{ $fld['placeholder'] ?: 'যেমন: মো: আরিফুল ইসলাম' }}" data-bn-voice="name" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-1 outline-hidden transition bg-slate-50/30" style="--tw-ring-color: {{ $themeColor }};">
                            </div>
                        @elseif ($fld['key'] === 'customer_phone')
                            <div>
                                <label for="customer_phone" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    {{ $fld['label'] ?: 'আপনার ফোন নাম্বার' }} @if(!empty($fld['required']))<span class="text-red-500">*</span>@endif
                                </label>
                                <input type="tel" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $customer?->phone ?? '') }}" {{ !empty($fld['required']) ? 'required' : '' }} autocomplete="tel" placeholder="{{ $fld['placeholder'] ?: 'যেমন: 017XXXXXXXX' }}" data-bn-voice="phone" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-1 outline-hidden transition bg-slate-50/30" style="--tw-ring-color: {{ $themeColor }};">
                            </div>
                        @elseif ($fld['key'] === 'customer_address')
                            <div>
                                <label for="customer_address" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    {{ $fld['label'] ?: 'আপনার সম্পূর্ণ ঠিকানা' }} @if(!empty($fld['required']))<span class="text-red-500">*</span>@endif
                                </label>
                                <textarea id="customer_address" name="customer_address" {{ !empty($fld['required']) ? 'required' : '' }} rows="2" autocomplete="street-address" placeholder="{{ $fld['placeholder'] ?: 'বাড়ি/রোড নং, এলাকা, থানা ও জেলার নাম লিখুন...' }}" data-bn-voice="address" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-1 outline-hidden transition bg-slate-50/30 leading-relaxed" style="--tw-ring-color: {{ $themeColor }};">{{ old('customer_address', $customer?->address ?? '') }}</textarea>
                            </div>
                        @elseif ($fld['key'] === 'shipping_option')
                            <div class="space-y-2 pt-1">
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700">
                                    {{ $fld['label'] ?: 'ডেলিভারি এলাকা নির্বাচন করুন' }} @if(!empty($fld['required']))<span class="text-red-500">*</span>@endif
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="shipping-options-container">
                                    @foreach ($shippingOptions as $option)
                                        <label class="shipping-option-card relative flex items-center justify-between p-3 rounded-xl border border-slate-300 bg-white cursor-pointer hover:border-slate-400 transition shadow-xs">
                                            <div class="flex items-center gap-2.5">
                                                <input type="radio" name="shipping_option" value="{{ $option['key'] }}" data-amount="{{ $option['amount'] }}" data-original-amount="{{ $option['amount'] }}" data-original-formatted="{{ $option['formatted_amount'] }}" data-name="{{ $option['name'] }}" class="shipping-radio w-4 h-4 cursor-pointer" style="accent-color: {{ $themeColor }};" {{ (old('shipping_option') === $option['key'] || (!old('shipping_option') && $option['is_default'])) ? 'checked' : '' }}>
                                                <span class="text-xs sm:text-sm font-bold text-slate-800">{{ $option['name'] }}</span>
                                            </div>
                                            <span class="shipping-amount-badge inline-flex items-center justify-center min-w-[58px] text-xs sm:text-sm font-black px-2.5 py-1 rounded-md text-white shadow-2xs" data-original-formatted="{{ $option['formatted_amount'] }}" style="color: #ffffff !important; background-color: {{ $themeColor }} !important; font-family: 'Noto Sans Bengali', system-ui, sans-serif !important; font-weight: 700 !important; letter-spacing: 0.03em; line-height: 1.25 !important; display: inline-flex !important; visibility: visible !important; opacity: 1 !important;">{{ $option['formatted_amount'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($fld['key'] === 'order_notes')
                            <div>
                                <input type="text" id="order_notes" name="notes" value="{{ old('notes', '') }}" placeholder="{{ $fld['placeholder'] ?: 'অতিরিক্ত কোনো মন্তব্য বা স্পেশাল নোট (ঐচ্ছিক)' }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-1 outline-hidden transition bg-slate-50/50">
                            </div>
                        @else
                            <div>
                                <label for="field_{{ $fld['key'] }}" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    {{ $fld['label'] }} @if(!empty($fld['required']))<span class="text-red-500">*</span>@endif
                                </label>
                                @if (($fld['type'] ?? 'text') === 'textarea')
                                    <textarea id="field_{{ $fld['key'] }}" name="custom_fields[{{ $fld['key'] }}]" {{ !empty($fld['required']) ? 'required' : '' }} rows="2" placeholder="{{ $fld['placeholder'] }}" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-1 outline-hidden transition bg-slate-50/30 leading-relaxed" style="--tw-ring-color: {{ $themeColor }};">{{ old('custom_fields.' . $fld['key']) }}</textarea>
                                @else
                                    <input type="{{ $fld['type'] ?? 'text' }}" id="field_{{ $fld['key'] }}" name="custom_fields[{{ $fld['key'] }}]" value="{{ old('custom_fields.' . $fld['key']) }}" {{ !empty($fld['required']) ? 'required' : '' }} placeholder="{{ $fld['placeholder'] }}" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-1 outline-hidden transition bg-slate-50/30" style="--tw-ring-color: {{ $themeColor }};">
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="lg:col-span-5 space-y-4 sm:space-y-5">
                    <h3 class="text-base sm:text-lg font-bold border-b border-slate-100 pb-1.5" style="color: {{ $themeColor }};">
                        {{ $landingPage->checkoutField('order_title', 'Your order') }}
                    </h3>

                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white text-xs sm:text-sm shadow-xs">
                        <div class="bg-slate-50/80 px-4 py-3 flex items-center justify-between font-bold text-slate-700 border-b border-slate-200">
                            <span>{{ $landingPage->checkoutField('table_product', 'Product') }}</span>
                            <span>{{ $landingPage->checkoutField('table_subtotal', 'Subtotal') }}</span>
                        </div>

                        <div id="checkout-order-items-list" class="p-3.5 divide-y divide-slate-100">
                            <div class="py-2.5 flex items-center justify-between text-slate-700">
                                <span class="font-medium truncate pr-2" id="order-row-item-name">{{ $defaultProd->name }}</span>
                                <span class="font-bold text-slate-900 shrink-0" id="order-row-item-subtotal">৳০</span>
                            </div>
                        </div>

                        <div class="bg-slate-50/50 p-4 space-y-2.5 border-t border-slate-200">
                            <div class="flex items-center justify-between text-slate-600">
                                <span class="font-medium">{{ $landingPage->checkoutField('table_subtotal', 'Subtotal') }}</span>
                                <span id="summary-subtotal" class="font-bold text-slate-800">৳০</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span class="font-medium">{{ $landingPage->checkoutField('table_shipment', 'Shipment') }}</span>
                                <span id="summary-delivery" class="font-bold text-slate-800">৳০</span>
                            </div>
                            <div class="border-t border-slate-200 pt-2.5 flex items-center justify-between text-sm sm:text-base font-black text-slate-900">
                                <span>{{ $landingPage->checkoutField('table_total', 'Total') }}</span>
                                <span id="summary-total" class="text-lg sm:text-xl font-black" style="color: {{ $themeColor }} !important;">৳০</span>
                            </div>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-xs" id="payment-method-container">
                        @php
                            $gatewaysList = $availableGateways ?? [];
                            if (empty($gatewaysList)) {
                                $gatewaysList = [
                                    [
                                        'id' => 'cod',
                                        'title' => 'Cash On Delivery',
                                        'subtitle' => 'Pay in cash when you receive the parcel at your doorstep.',
                                    ]
                                ];
                            }
                            $isMultiGateway = count($gatewaysList) > 1;
                        @endphp

                        @if ($isMultiGateway)
                            <div class="p-3.5 space-y-2 bg-white">
                                @foreach ($gatewaysList as $gwIdx => $gw)
                                    <label class="payment-method-pill flex items-center gap-2.5 p-2 rounded-xl border border-slate-200 cursor-pointer transition hover:bg-slate-50 {{ $gwIdx === 0 ? 'bg-emerald-50/30 border-emerald-300' : '' }}">
                                        <input type="radio" name="payment_method" value="{{ $gw['id'] }}" data-description="{{ $gw['subtitle'] ?? '' }}" class="payment-radio w-4 h-4" style="accent-color: {{ $themeColor }};" {{ $gwIdx === 0 ? 'checked' : '' }}>
                                        <span class="text-xs sm:text-sm font-bold text-slate-800">{{ $gw['title'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            @php $singleGw = $gatewaysList[0]; @endphp
                            <input type="hidden" name="payment_method" value="{{ $singleGw['id'] }}">
                            <div class="px-4 py-3 font-bold text-xs sm:text-sm text-slate-800 flex items-center gap-2 bg-white">
                                <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $themeColor }};"></span>
                                <span>{{ $singleGw['title'] }}</span>
                            </div>
                        @endif

                        <div id="payment-method-instruction" class="p-3.5 text-xs text-slate-600 leading-relaxed bg-slate-50/60 border-t border-slate-100 whitespace-pre-line hidden"></div>
                    </div>

                    <button type="submit" id="submit-order-btn" class="w-full py-4 px-6 text-white text-base sm:text-lg font-black rounded-xl transition transform hover:-translate-y-0.5 active:scale-95 flex items-center justify-center gap-2" style="background-color: {{ $themeColor }} !important; box-shadow: 0 10px 24px -4px {{ $themeColor }}55 !important;">
                        <span id="btn-spinner" class="hidden animate-spin">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        <span id="btn-text">{{ $landingPage->checkout_button_text ?: 'অর্ডার কনফার্ম করুন' }}</span>
                        <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>

                    <div class="pt-1 flex flex-wrap items-center justify-center gap-3 text-[11px] font-semibold text-slate-500">
                        <div class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" style="color: {{ $themeColor }};" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>ক্যাশ অন ডেলিভারি</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" style="color: {{ $themeColor }};" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>পণ্য চেক করে গ্রহণ</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Live Calculation, Storage Persistence & Tracking Script -->
<script>
(function() {
    const isMultipleMode = {{ $isMultipleMode ? 'true' : 'false' }};
    const hasMultiplePackages = {{ (!$isMultipleMode && $assigned->count() > 1) ? 'true' : 'false' }};
    const defaultShippingAmount = {{ $defaultShippingAmount }};
    const form = document.getElementById('landing-order-form');
    const submitBtn = document.getElementById('submit-order-btn');
    const btnSpinner = document.getElementById('btn-spinner');
    const btnText = document.getElementById('btn-text');
    const errorBox = document.getElementById('checkout-error-box');
    const errorMessage = document.getElementById('checkout-error-message');

    const summarySubtotal = document.getElementById('summary-subtotal');
    const summaryDelivery = document.getElementById('summary-delivery');
    const summaryTotal = document.getElementById('summary-total');
    const shippingRadios = document.querySelectorAll('.shipping-radio');

    // Customer profile inputs
    const nameInput = document.getElementById('customer_name');
    const phoneInput = document.getElementById('customer_phone');
    const addressInput = document.getElementById('customer_address');
    const notesInput = document.getElementById('order_notes');

    // Single mode quantity inputs
    const qtyInput = document.getElementById('order-quantity');
    const decrementBtn = document.getElementById('qty-decrement');
    const incrementBtn = document.getElementById('qty-increment');

    const STORAGE_KEY = 'foodness_saved_customer_info';
    const LANDING_STATE_KEY = 'foodness_landing_state_{{ $landingPage->id }}';

    function toBn(num) {
        const bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return String(Math.round(num)).replace(/\d/g, d => bn[d]);
    }

    // Resolve current delivery fee
    function getDeliveryFee() {
        const checkedRadio = form.querySelector('.shipping-radio:checked');
        if (checkedRadio) {
            return parseFloat(checkedRadio.dataset.amount) || 0;
        }
        return defaultShippingAmount;
    }

    // Card shows unit price (qty only affects order summary)
    function updateCardDisplayPrice(card) {
        if (!card) return;
        const unitPrice = parseFloat(card.dataset.price) || 0;
        const comparePrice = card.dataset.compare ? parseFloat(card.dataset.compare) : null;

        const displayPriceEl = card.querySelector('.card-display-price');
        if (displayPriceEl) {
            displayPriceEl.textContent = '৳' + toBn(unitPrice);
        }

        const compareEl = card.querySelector('.card-compare-price');
        if (compareEl) {
            if (comparePrice && comparePrice > unitPrice) {
                compareEl.textContent = '৳' + toBn(comparePrice);
                compareEl.classList.remove('hidden');
            } else {
                compareEl.classList.add('hidden');
            }
        }

        const discountEl = card.querySelector('[data-discount-badge]');
        if (discountEl) {
            if (comparePrice && comparePrice > unitPrice) {
                const pct = Math.round(((comparePrice - unitPrice) / comparePrice) * 100);
                discountEl.textContent = toBn(pct) + '% ছাড়';
                discountEl.classList.remove('hidden');
            } else {
                discountEl.textContent = '';
                discountEl.classList.add('hidden');
            }
        }
    }

    function updateCurrentSingleCardPrice() {
        let targetCard = null;
        if (hasMultiplePackages) {
            const checkedRadio = form.querySelector('.package-radio:checked');
            targetCard = checkedRadio ? checkedRadio.closest('.single-package-card') : null;
        } else {
            targetCard = document.getElementById('single-product-main-card');
        }
        if (targetCard) {
            updateCardDisplayPrice(targetCard);
        }
    }

    // Apply variant-specific delivery charge configuration
    function applyVariantDelivery(variantRadio) {
        const delType = variantRadio ? (variantRadio.dataset.deliveryType || 'default') : 'default';
        const delInside = variantRadio && variantRadio.dataset.deliveryInside !== '' ? parseFloat(variantRadio.dataset.deliveryInside) : null;
        const delOutside = variantRadio && variantRadio.dataset.deliveryOutside !== '' ? parseFloat(variantRadio.dataset.deliveryOutside) : null;

        const shippingCards = form.querySelectorAll('.shipping-option-card');
        shippingCards.forEach(card => {
            const radio = card.querySelector('.shipping-radio');
            const badge = card.querySelector('.shipping-amount-badge');
            if (!radio || !badge) return;

            if (!radio.dataset.originalAmount) {
                radio.dataset.originalAmount = radio.dataset.amount;
            }
            if (!radio.dataset.originalFormatted) {
                radio.dataset.originalFormatted = radio.getAttribute('data-original-formatted') || badge.getAttribute('data-original-formatted') || badge.textContent.trim() || ('৳' + toBn(radio.dataset.originalAmount));
            }

            if (delType === 'free') {
                radio.dataset.amount = 0;
                badge.textContent = 'ফ্রি';
            } else if (delType === 'custom' && (delInside !== null || delOutside !== null)) {
                const optName = (radio.dataset.name || radio.value || '').toLowerCase();
                const isInside = optName.includes('inside') || optName.includes('ভেতরে') || optName.includes('dhaka');
                const customRate = isInside ? (delInside !== null ? delInside : 70) : (delOutside !== null ? delOutside : 130);
                radio.dataset.amount = customRate;
                badge.textContent = customRate === 0 ? 'ফ্রি' : '৳' + toBn(customRate);
            } else {
                radio.dataset.amount = radio.dataset.originalAmount;
                badge.textContent = radio.dataset.originalFormatted || ('৳' + toBn(radio.dataset.originalAmount));
            }
        });
    }

    // Calculate Grand Total across all modes & sync "Your order" table
    function calculateTotal() {
        let subtotal = 0;
        const orderItemsList = document.getElementById('checkout-order-items-list');
        const rowsHtml = [];

        if (isMultipleMode) {
            const checkedCards = form.querySelectorAll('.product-checkbox:checked');
            checkedCards.forEach(cb => {
                const card = cb.closest('.multi-product-card');
                const p = parseFloat(card.dataset.price) || 0;
                const q = Math.max(1, parseInt(card.querySelector('.input-item-qty')?.value) || 1);
                const lineTotal = p * q;
                subtotal += lineTotal;

                const name = card.dataset.name || 'পণ্য';
                const checkedVar = card.querySelector('.variant-radio:checked');
                const varLabel = checkedVar ? checkedVar.closest('.variant-pill')?.querySelector('.truncate')?.textContent?.trim() : '';
                const itemTitle = varLabel ? `${name} (${varLabel})` : name;

                rowsHtml.push(`
                    <div class="order-item-row py-2.5 flex items-center justify-between gap-3 text-slate-800">
                        <span class="order-item-name font-semibold truncate pr-1">${itemTitle} <span class="order-item-qty text-slate-500 font-medium">× ${toBn(q)}</span></span>
                        <span class="order-item-price font-bold text-slate-900 shrink-0">৳${toBn(lineTotal)}</span>
                    </div>
                `);
            });
        } else if (hasMultiplePackages) {
            const checkedRadio = form.querySelector('.package-radio:checked');
            const card = checkedRadio ? checkedRadio.closest('.single-package-card') : null;
            const p = card ? (parseFloat(card.dataset.price) || 0) : 0;
            const q = Math.max(1, parseInt(qtyInput ? qtyInput.value : 1) || 1);
            const lineTotal = p * q;
            subtotal = lineTotal;

            const name = card?.dataset.name || '{{ $defaultProd->name }}';
            const checkedVar = card ? card.querySelector('.variant-radio:checked') : null;
            const varLabel = checkedVar ? checkedVar.closest('.variant-pill')?.querySelector('.truncate')?.textContent?.trim() : '';
            const itemTitle = varLabel ? `${name} (${varLabel})` : name;

            rowsHtml.push(`
                <div class="order-item-row py-2.5 flex items-center justify-between gap-3 text-slate-800">
                    <span class="order-item-name font-semibold truncate pr-1">${itemTitle} <span class="order-item-qty text-slate-500 font-medium">× ${toBn(q)}</span></span>
                    <span class="order-item-price font-bold text-slate-900 shrink-0">৳${toBn(lineTotal)}</span>
                </div>
            `);
        } else {
            const mainCard = document.getElementById('single-product-main-card');
            const p = mainCard ? (parseFloat(mainCard.dataset.price) || 0) : 0;
            const q = Math.max(1, parseInt(qtyInput ? qtyInput.value : 1) || 1);
            const lineTotal = p * q;
            subtotal = lineTotal;

            const name = '{{ $defaultProd->name }}';
            const checkedVar = form.querySelector('.variant-radio:checked');
            const varLabel = checkedVar ? checkedVar.closest('.variant-pill')?.querySelector('.truncate')?.textContent?.trim() : '';
            const itemTitle = varLabel ? `${name} (${varLabel})` : name;

            rowsHtml.push(`
                <div class="order-item-row py-2.5 flex items-center justify-between gap-3 text-slate-800">
                    <span class="order-item-name font-semibold truncate pr-1">${itemTitle} <span class="order-item-qty text-slate-500 font-medium">× ${toBn(q)}</span></span>
                    <span class="order-item-price font-bold text-slate-900 shrink-0">৳${toBn(lineTotal)}</span>
                </div>
            `);
        }

        if (orderItemsList) {
            if (rowsHtml.length > 0) {
                orderItemsList.innerHTML = rowsHtml.join('');
            } else {
                orderItemsList.innerHTML = `
                    <div class="py-2 text-slate-400 text-center italic">
                        কোনো পণ্য নির্বাচিত নেই
                    </div>
                `;
            }
        }

        const deliveryFee = getDeliveryFee();
        const total = subtotal + deliveryFee;

        if (summarySubtotal) summarySubtotal.textContent = '৳' + toBn(subtotal);
        if (summaryDelivery) summaryDelivery.textContent = deliveryFee === 0 ? 'ফ্রি' : '৳' + toBn(deliveryFee);
        if (summaryTotal) summaryTotal.textContent = '৳' + toBn(total);

        const mobileStickyPrice = document.getElementById('mobile-sticky-price');
        if (mobileStickyPrice) {
            mobileStickyPrice.textContent = '৳' + toBn(total);
        }

        return { subtotal, deliveryFee, total };
    }

    // VARIANT PILL SELECTION & DYNAMIC PRICE UPDATE
    function updateVariantSelection(variantRadio) {
        const vPrice = parseFloat(variantRadio.dataset.price) || 0;
        const vCompare = variantRadio.dataset.compare ? parseFloat(variantRadio.dataset.compare) : null;
        const card = variantRadio.closest('.multi-product-card') 
            || variantRadio.closest('.single-package-card') 
            || document.getElementById('single-product-main-card')
            || (hasMultiplePackages ? form.querySelector('.single-package-card[data-product-id="' + form.querySelector('.package-radio:checked')?.value + '"]') : null);

        // Update active pill styling
        const pill = variantRadio.closest('.variant-pill');
        const selectorBox = variantRadio.closest('.variant-selector-box');
        if (selectorBox && pill) {
            selectorBox.querySelectorAll('.variant-pill').forEach(p => {
                p.classList.remove('landing-pill-active', 'font-bold', 'shadow-xs');
                p.classList.add('border-slate-200', 'bg-white', 'font-medium', 'text-slate-600');
                p.style.setProperty('border-color', '#e2e8f0', 'important');
                p.style.setProperty('background-color', '#ffffff', 'important');
                p.style.setProperty('color', '#475569', 'important');
                const dot = p.querySelector('.variant-dot');
                if (dot) dot.style.backgroundColor = '#94a3b8';
                const vPr = p.querySelector('.variant-price');
                if (vPr) vPr.style.color = '#64748b';
            });
            pill.classList.remove('border-slate-200', 'bg-white', 'font-medium', 'text-slate-600');
            pill.classList.add('landing-pill-active', 'font-bold', 'shadow-xs');
            pill.style.setProperty('border-color', '{{ $themeColor }}', 'important');
            pill.style.setProperty('background-color', '{{ $themeColor }}18', 'important');
            pill.style.setProperty('color', '{{ $themeColor }}', 'important');
            const dot = pill.querySelector('.variant-dot');
            if (dot) dot.style.backgroundColor = '{{ $themeColor }}';
            const vPr = pill.querySelector('.variant-price');
            if (vPr) vPr.style.color = '{{ $themeColor }}';
        }

        // Update card's displayed unit price and compare-at price
        if (card) {
            card.dataset.price = vPrice;
            if (vCompare) {
                card.dataset.compare = vCompare;
            } else {
                delete card.dataset.compare;
            }
            updateCardDisplayPrice(card);
        }

        applyVariantDelivery(variantRadio);
        calculateTotal();
        saveFormData();
        triggerAddToCart();
    }

    form.querySelectorAll('.variant-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            updateVariantSelection(this);
        });
    });

    // Multi-mode quantity steppers & checkbox card highlighting
    if (isMultipleMode) {
        form.querySelectorAll('.multi-product-card').forEach(card => {
            const cb = card.querySelector('.product-checkbox');
            const minusBtn = card.querySelector('.btn-qty-minus');
            const plusBtn = card.querySelector('.btn-qty-plus');
            const itemQty = card.querySelector('.input-item-qty');

            function syncCardState() {
                const varBox = card.querySelector('.variant-selector-box');
                const varRadios = card.querySelectorAll('.variant-radio');
                if (cb.checked) {
                    card.classList.add('landing-card-active');
                    card.setAttribute('data-checked', 'true');
                    if (varBox) {
                        varBox.classList.remove('opacity-60', 'pointer-events-none');
                    }
                    varRadios.forEach(r => r.disabled = false);
                    const checkedVar = card.querySelector('.variant-radio:checked') || card.querySelector('.variant-radio');
                    if (checkedVar && !card.querySelector('.variant-radio:checked')) {
                        checkedVar.checked = true;
                        updateVariantSelection(checkedVar);
                    }
                } else {
                    card.classList.remove('landing-card-active');
                    card.setAttribute('data-checked', 'false');
                    if (varBox) {
                        varBox.classList.add('opacity-60', 'pointer-events-none');
                    }
                    varRadios.forEach(r => r.disabled = true);
                }
            }

            cb.addEventListener('change', () => {
                syncCardState();
                saveFormData();
                calculateTotal();
                triggerAddToCart();
            });

            // Qty / variants stay interactive; label handles selection
            card.addEventListener('click', (e) => {
                if (e.target.closest('.lp-prod-qty') || e.target.closest('.variant-pill')) {
                    e.stopPropagation();
                }
            });

            if (minusBtn && plusBtn && itemQty) {
                minusBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    let v = parseInt(itemQty.value) || 1;
                    if (v > 1) {
                        itemQty.value = v - 1;
                        updateCardDisplayPrice(card);
                        saveFormData();
                        calculateTotal();
                    }
                });

                plusBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    let v = parseInt(itemQty.value) || 1;
                    if (v < 50) {
                        itemQty.value = v + 1;
                        if (!cb.checked) {
                            cb.checked = true;
                            syncCardState();
                        }
                        updateCardDisplayPrice(card);
                        saveFormData();
                        calculateTotal();
                    }
                });

                itemQty.addEventListener('input', () => {
                    updateCardDisplayPrice(card);
                    saveFormData();
                    calculateTotal();
                });
            }
        });
    }

    // Single mode package selection & variant container switching
    if (hasMultiplePackages) {
        form.querySelectorAll('.package-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                const selectedPid = this.value;

                form.querySelectorAll('.single-package-card').forEach(card => {
                    const isTarget = (card.dataset.productId === selectedPid);
                    const varBox = card.querySelector('.variant-selector-box');
                    const varRadios = card.querySelectorAll('.variant-radio');

                    if (isTarget) {
                        card.classList.add('landing-card-active');
                        card.setAttribute('data-checked', 'true');

                        if (varBox) {
                            varBox.classList.remove('opacity-60', 'pointer-events-none');
                        }
                        varRadios.forEach(r => r.disabled = false);

                        const checkedVar = card.querySelector('.variant-radio:checked') || card.querySelector('.variant-radio');
                        if (checkedVar) {
                            checkedVar.checked = true;
                            updateVariantSelection(checkedVar);
                        }
                    } else {
                        card.classList.remove('landing-card-active');
                        card.setAttribute('data-checked', 'false');

                        if (varBox) {
                            varBox.classList.add('opacity-60', 'pointer-events-none');
                        }
                        varRadios.forEach(r => r.disabled = true);
                    }
                });

                updateCurrentSingleCardPrice();
                saveFormData();
                calculateTotal();
                triggerAddToCart();
            });
        });

        // Click anywhere on card (outside pill) selects package radio
        form.querySelectorAll('.single-package-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.variant-pill') || e.target.closest('input[type="radio"]')) return;
                const r = this.querySelector('.package-radio');
                if (r && !r.checked) {
                    r.checked = true;
                    r.dispatchEvent(new Event('change'));
                }
            });
        });
    }

    // Single mode quantity steppers
    if (decrementBtn && incrementBtn && qtyInput) {
        decrementBtn.addEventListener('click', function() {
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) {
                qtyInput.value = val - 1;
                updateCurrentSingleCardPrice();
                saveFormData();
                calculateTotal();
            }
        });

        incrementBtn.addEventListener('click', function() {
            let val = parseInt(qtyInput.value) || 1;
            if (val < 50) {
                qtyInput.value = val + 1;
                updateCurrentSingleCardPrice();
                saveFormData();
                calculateTotal();
            }
        });

        qtyInput.addEventListener('input', function() {
            updateCurrentSingleCardPrice();
            saveFormData();
            calculateTotal();
        });
    }

    // Initialize initial card prices & variant delivery settings
    if (isMultipleMode) {
        form.querySelectorAll('.multi-product-card').forEach(card => updateCardDisplayPrice(card));
    } else {
        updateCurrentSingleCardPrice();
    }

    const initialActiveVar = form.querySelector('.variant-radio:checked');
    if (initialActiveVar) {
        applyVariantDelivery(initialActiveVar);
    }

    // Shipping change
    shippingRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            saveFormData();
            calculateTotal();
        });
    });

    // Payment Method selection dynamic description
    const paymentRadios = form.querySelectorAll('.payment-radio');
    const paymentDescEl = document.getElementById('payment-method-instruction');
    paymentRadios.forEach(pr => {
        pr.addEventListener('change', function() {
            form.querySelectorAll('.payment-method-pill').forEach(pill => {
                pill.classList.remove('bg-emerald-50/30', 'border-emerald-300');
            });
            const parentPill = this.closest('.payment-method-pill');
            if (parentPill) {
                parentPill.classList.add('bg-emerald-50/30', 'border-emerald-300');
            }
            if (paymentDescEl) {
                if (this.dataset.description && this.dataset.description.trim() !== '') {
                    paymentDescEl.textContent = this.dataset.description;
                    paymentDescEl.classList.remove('hidden');
                } else {
                    paymentDescEl.textContent = '';
                    paymentDescEl.classList.add('hidden');
                }
            }
        });
    });

    // Auto-save form data
    function saveFormData() {
        try {
            const customerInfo = {
                name: nameInput ? nameInput.value.trim() : '',
                phone: phoneInput ? phoneInput.value.trim() : '',
                address: addressInput ? addressInput.value.trim() : '',
            };
            if (customerInfo.name || customerInfo.phone || customerInfo.address) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(customerInfo));
            }

            const landingDraft = {
                notes: notesInput ? notesInput.value.trim() : '',
                shipping_option: form.querySelector('input[name="shipping_option"]:checked')?.value || null,
            };

            if (isMultipleMode) {
                landingDraft.items = [];
                form.querySelectorAll('.product-checkbox:checked').forEach(cb => {
                    const card = cb.closest('.multi-product-card');
                    landingDraft.items.push({
                        product_id: cb.value,
                        quantity: card.querySelector('.input-item-qty')?.value || 1,
                        variant_id: card.querySelector('.variant-radio:checked')?.value || null
                    });
                });
            } else {
                landingDraft.quantity = qtyInput ? (parseInt(qtyInput.value) || 1) : 1;
                landingDraft.product_id = form.querySelector('.package-radio:checked')?.value || null;
                landingDraft.variant_id = form.querySelector('input[name="variant_id"]:checked')?.value || null;
            }

            localStorage.setItem(LANDING_STATE_KEY, JSON.stringify(landingDraft));
        } catch (e) {}
    }

    // Auto-restore form data
    function restoreFormData() {
        try {
            const savedRaw = localStorage.getItem(STORAGE_KEY);
            if (savedRaw) {
                const saved = JSON.parse(savedRaw);
                if (saved && typeof saved === 'object') {
                    if (nameInput && !nameInput.value.trim() && saved.name) nameInput.value = saved.name;
                    if (phoneInput && !phoneInput.value.trim() && saved.phone) phoneInput.value = saved.phone;
                    if (addressInput && !addressInput.value.trim() && saved.address) addressInput.value = saved.address;
                }
            }

            const landingRaw = localStorage.getItem(LANDING_STATE_KEY);
            if (landingRaw) {
                const draft = JSON.parse(landingRaw);
                if (draft && typeof draft === 'object') {
                    if (draft.notes && notesInput && !notesInput.value.trim()) notesInput.value = draft.notes;
                    if (draft.shipping_option) {
                        const targetShipping = form.querySelector(`input[name="shipping_option"][value="${draft.shipping_option}"]`);
                        if (targetShipping) targetShipping.checked = true;
                    }

                    if (isMultipleMode && Array.isArray(draft.items) && draft.items.length > 0) {
                        form.querySelectorAll('.product-checkbox').forEach(cb => {
                            cb.checked = false;
                            const card = cb.closest('.multi-product-card');
                            if (card) {
                                card.classList.remove('landing-card-active');
                                card.setAttribute('data-checked', 'false');
                            }
                        });

                        draft.items.forEach(it => {
                            const cb = form.querySelector(`.product-checkbox[value="${it.product_id}"]`);
                            if (cb) {
                                cb.checked = true;
                                const card = cb.closest('.multi-product-card');
                                if (card) {
                                    card.classList.add('landing-card-active');
                                    card.setAttribute('data-checked', 'true');
                                    const varBox = card.querySelector('.variant-selector-box');
                                    if (varBox) varBox.classList.remove('opacity-60', 'pointer-events-none');
                                    card.querySelectorAll('.variant-radio').forEach(r => r.disabled = false);
                                    const itemQty = card.querySelector('.input-item-qty');
                                    if (itemQty && it.quantity) itemQty.value = it.quantity;
                                    if (it.variant_id) {
                                        const vRadio = card.querySelector(`.variant-radio[value="${it.variant_id}"]`);
                                        if (vRadio) {
                                            vRadio.checked = true;
                                            updateVariantSelection(vRadio);
                                        }
                                    }
                                }
                            }
                        });
                    } else if (!isMultipleMode) {
                        if (draft.quantity && qtyInput) qtyInput.value = Math.max(1, parseInt(draft.quantity) || 1);
                        if (draft.product_id && hasMultiplePackages) {
                            const pRadio = form.querySelector(`.package-radio[value="${draft.product_id}"]`);
                            if (pRadio) {
                                pRadio.checked = true;
                                pRadio.dispatchEvent(new Event('change'));
                            }
                        }
                        if (draft.variant_id) {
                            const vRadio = form.querySelector(`input[name="variant_id"][value="${draft.variant_id}"]`);
                            if (vRadio) {
                                vRadio.checked = true;
                                updateVariantSelection(vRadio);
                            }
                        }
                    }
                }
            }
        } catch (e) {}
    }

    // Attach profile field listeners
    [nameInput, phoneInput, addressInput, notesInput].forEach(field => {
        if (field) {
            field.addEventListener('input', saveFormData);
            field.addEventListener('change', saveFormData);
        }
    });

    restoreFormData();
    calculateTotal();

    // Helper: Collect selected items for event tracking
    function getSelectedItemsForTracking() {
        const items = [];
        if (isMultipleMode) {
            form.querySelectorAll('.product-checkbox:checked').forEach(cb => {
                const card = cb.closest('.multi-product-card');
                const pid = cb.value;
                const name = card.dataset.name || 'Product';
                const price = parseFloat(card.dataset.price) || 0;
                const qty = Math.max(1, parseInt(card.querySelector('.input-item-qty')?.value) || 1);
                items.push({ id: pid, name: name, price: price, quantity: qty });
            });
        } else if (hasMultiplePackages) {
            const checkedRadio = form.querySelector('.package-radio:checked');
            const card = checkedRadio ? checkedRadio.closest('.single-package-card') : null;
            const pid = checkedRadio ? checkedRadio.value : @json((string) $defaultProd->id);
            const name = card ? (card.dataset.name || @json($defaultProd->name)) : @json($defaultProd->name);
            const price = card ? (parseFloat(card.dataset.price) || 0) : 0;
            const qty = Math.max(1, parseInt(qtyInput ? qtyInput.value : 1) || 1);
            items.push({ id: pid, name: name, price: price, quantity: qty });
        } else {
            const mainCard = document.getElementById('single-product-main-card');
            const price = mainCard ? (parseFloat(mainCard.dataset.price) || 0) : 0;
            const qty = Math.max(1, parseInt(qtyInput ? qtyInput.value : 1) || 1);
            items.push({
                id: @json((string) $defaultProd->id),
                name: @json($defaultProd->name),
                price: price,
                quantity: qty
            });
        }
        return items;
    }

    // 1. Tracking: AddToCart Event
    let addToCartTracked = false;
    function triggerAddToCart() {
        if (addToCartTracked) return;
        addToCartTracked = true;

        const items = getSelectedItemsForTracking();
        if (items.length === 0) return;

        const subtotalVal = items.reduce((sum, it) => sum + (it.price * it.quantity), 0);
        const contentIds = items.map(it => String(it.id));

        // Meta Pixel AddToCart
        if (typeof fbq === 'function') {
            fbq('track', 'AddToCart', {
                content_ids: contentIds,
                content_name: items.map(it => it.name).join(', '),
                content_type: 'product',
                value: subtotalVal,
                currency: 'BDT'
            });
        }

        // TikTok Pixel AddToCart
        if (typeof ttq === 'object' && typeof ttq.track === 'function') {
            ttq.track('AddToCart', {
                contents: items.map(it => ({
                    content_id: String(it.id),
                    content_type: 'product',
                    content_name: it.name,
                    quantity: it.quantity,
                    price: it.price
                })),
                value: subtotalVal,
                currency: 'BDT'
            });
        }

        // Google dataLayer add_to_cart
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            event: 'add_to_cart',
            ecommerce: {
                currency: 'BDT',
                value: subtotalVal,
                items: items.map(it => ({
                    item_id: String(it.id),
                    item_name: it.name,
                    price: it.price,
                    quantity: it.quantity
                }))
            }
        });
    }

    // 2. Tracking: InitiateCheckout Event
    let initiateCheckoutTracked = false;
    function triggerInitiateCheckout() {
        triggerAddToCart();

        if (initiateCheckoutTracked) return;
        initiateCheckoutTracked = true;

        const items = getSelectedItemsForTracking();
        if (items.length === 0) return;

        const subtotalVal = items.reduce((sum, it) => sum + (it.price * it.quantity), 0);
        const totalQty = items.reduce((sum, it) => sum + it.quantity, 0);
        const contentIds = items.map(it => String(it.id));

        // Meta Pixel InitiateCheckout
        if (typeof fbq === 'function') {
            fbq('track', 'InitiateCheckout', {
                content_ids: contentIds,
                content_name: items.map(it => it.name).join(', '),
                content_type: 'product',
                value: subtotalVal,
                currency: 'BDT',
                num_items: totalQty
            });
        }

        // TikTok Pixel InitiateCheckout
        if (typeof ttq === 'object' && typeof ttq.track === 'function') {
            ttq.track('InitiateCheckout', {
                contents: items.map(it => ({
                    content_id: String(it.id),
                    content_type: 'product',
                    content_name: it.name,
                    quantity: it.quantity,
                    price: it.price
                })),
                value: subtotalVal,
                currency: 'BDT'
            });
        }

        // Google dataLayer begin_checkout
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            event: 'begin_checkout',
            ecommerce: {
                currency: 'BDT',
                value: subtotalVal,
                items: items.map(it => ({
                    item_id: String(it.id),
                    item_name: it.name,
                    price: it.price,
                    quantity: it.quantity
                }))
            }
        });
    }

    // CTA button click triggers AddToCart
    document.querySelectorAll('a[href="#checkout-section"]').forEach(anchor => {
        anchor.addEventListener('click', triggerAddToCart, { once: true });
    });

    // Form input focus triggers InitiateCheckout
    [nameInput, phoneInput, addressInput].forEach(field => {
        if (field) {
            field.addEventListener('focus', triggerInitiateCheckout, { once: true });
        }
    });

    // 3. Live Abandoned Lead (Incomplete Order) capture when phone has 11 digits
    let lastTrackedPhone = '';
    function checkAndTrackIncompleteOrder() {
        if (!phoneInput) return;
        let digits = phoneInput.value.replace(/\D/g, '');
        if (digits.startsWith('8801') && digits.length === 13) digits = digits.slice(2);
        if (digits.length === 11 && digits.startsWith('01') && digits !== lastTrackedPhone) {
            lastTrackedPhone = digits;
            const progressData = new FormData();
            progressData.append('_token', '{{ csrf_token() }}');
            progressData.append('customer_name', nameInput ? nameInput.value.trim() : '');
            progressData.append('customer_phone', digits);
            progressData.append('customer_address', addressInput ? addressInput.value.trim() : '');

            if (isMultipleMode) {
                let idx = 0;
                form.querySelectorAll('.product-checkbox:checked').forEach(cb => {
                    const card = cb.closest('.multi-product-card');
                    const pid = cb.value;
                    const qty = card.querySelector('.input-item-qty')?.value || 1;
                    const variantRadio = card.querySelector('.variant-radio:checked');
                    progressData.append(`items[${idx}][product_id]`, pid);
                    progressData.append(`items[${idx}][quantity]`, qty);
                    if (variantRadio) progressData.append(`items[${idx}][variant_id]`, variantRadio.value);
                    idx++;
                });
            } else {
                const selectedPid = form.querySelector('.package-radio:checked')?.value || @json($defaultProd->id);
                const variantId = form.querySelector('input[name="variant_id"]:checked')?.value;
                progressData.append('product_id', selectedPid);
                progressData.append('quantity', qtyInput ? qtyInput.value : 1);
                if (variantId) progressData.append('variant_id', variantId);
            }

            fetch('{{ route("landing.progress", $landingPage->slug) }}', {
                method: 'POST',
                body: progressData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).catch(() => {});
        }
    }
    if (phoneInput) {
        phoneInput.addEventListener('input', checkAndTrackIncompleteOrder);
        phoneInput.addEventListener('blur', checkAndTrackIncompleteOrder);
    }

    // AJAX Form Submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        triggerInitiateCheckout();
        errorBox.classList.add('hidden');

        if (isMultipleMode) {
            const checkedCount = form.querySelectorAll('.product-checkbox:checked').length;
            if (checkedCount === 0) {
                errorMessage.textContent = 'অনুগ্রহ করে কমপক্ষে একটি পণ্য সিলেক্ট করুন।';
                errorBox.classList.remove('hidden');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        }

        submitBtn.disabled = true;
        btnSpinner.classList.remove('hidden');
        btnText.textContent = 'অর্ডার প্রসেসিং হচ্ছে...';

        const formData = new FormData(form);

        if (isMultipleMode) {
            let idx = 0;
            form.querySelectorAll('.product-checkbox:checked').forEach(cb => {
                const card = cb.closest('.multi-product-card');
                const pid = cb.value;
                const qty = card.querySelector('.input-item-qty')?.value || 1;
                const variantRadio = card.querySelector('.variant-radio:checked');
                formData.append(`items[${idx}][product_id]`, pid);
                formData.append(`items[${idx}][quantity]`, qty);
                if (variantRadio) formData.append(`items[${idx}][variant_id]`, variantRadio.value);
                idx++;
            });
        }

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const data = await response.json();
            if (response.ok && data.redirect) {
                try {
                    localStorage.removeItem(LANDING_STATE_KEY);
                } catch (e) {}
                window.location.href = data.redirect;
            } else {
                throw new Error(data.message || 'অর্ডার করতে সমস্যা হয়েছে, অনুগ্রহ করে তথ্য চেক করুন।');
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            btnSpinner.classList.add('hidden');
            btnText.textContent = '{{ $landingPage->checkout_button_text ?: "অর্ডার কনফার্ম করুন" }}';
            errorMessage.textContent = err.message;
            errorBox.classList.remove('hidden');
            errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
})();
</script>
@include('partials.bn-field-voice')
