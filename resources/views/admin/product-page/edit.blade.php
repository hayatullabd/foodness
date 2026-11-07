@extends('layouts.admin')

@section('title', 'Product Page Customizer')
@section('heading', 'Product Page Customizer')
@section('subheading', 'Customize the layout, colors, WhatsApp & Call buttons, price box, and features of your single product pages.')

@php
    $storePhone = $settings['store_phone'] ?? '01700000000';
    $themePrimary = $settings['theme_primary_color'] ?? '#013F1E';
    $sampleSlug = $sampleProduct?->slug ?? '';
@endphp

@section('actions')
    <div class="flex items-center gap-2">
        @if ($sampleSlug)
            <a
                href="{{ route('product.show', $sampleSlug) }}"
                target="_blank"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition"
            >
                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 7.25v11.5A2.25 2.25 0 005.25 21h11.5A2.25 2.25 0 0019 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                <span>View Live Product</span>
            </a>
        @endif

        <form method="POST" action="{{ route('admin.product-page.reset') }}" onsubmit="return confirm('Reset all product page customizations to default recommended settings?');">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50/60 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition cursor-pointer"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>Reset Defaults</span>
            </button>
        </form>

        <button
            type="submit"
            form="product-page-form"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-800 transition cursor-pointer"
        >
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span>Save Settings</span>
        </button>
    </div>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-semibold text-emerald-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        {{-- Settings Form Column --}}
        <div class="lg:col-span-7 space-y-4">
            <form id="product-page-form" method="POST" action="{{ route('admin.product-page.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Card 1: 4 Main Action Buttons (CTA Grid) --}}
                <div class="admin-card">
                    <div class="admin-card-header bg-slate-50/70">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 text-xs font-bold">1</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">4 Main Action Buttons (2x2 Grid)</h3>
                        </div>
                        <span class="text-[11px] text-slate-500 font-medium">Add to Cart, Buy Now, WhatsApp & Call</span>
                    </div>
                    <div class="admin-card-body space-y-4 divide-y divide-slate-100">
                        {{-- Row 1: Add to Cart & Buy Now --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pb-4">
                            {{-- Add to Cart --}}
                            <div class="space-y-2 p-3 rounded-xl border border-slate-200/80 bg-slate-50/40">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <span>🛒 Add to Cart Button</span>
                                    </label>
                                    <input type="hidden" name="product_add_to_cart_enabled" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="product_add_to_cart_enabled" value="1" class="sr-only peer" {{ ($settings['product_add_to_cart_enabled'] ?? $defaults['product_add_to_cart_enabled']) === '1' ? 'checked' : '' }}>
                                        <div class="w-8 h-4 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Button Text</label>
                                    <input
                                        type="text"
                                        name="product_add_to_cart_text"
                                        value="{{ old('product_add_to_cart_text', $settings['product_add_to_cart_text'] ?? $defaults['product_add_to_cart_text']) }}"
                                        class="w-full text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                        id="input-cart-text"
                                    >
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Button Color</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value="{{ old('product_add_to_cart_bg', $settings['product_add_to_cart_bg'] ?? $defaults['product_add_to_cart_bg']) }}"
                                            class="h-7 w-8 rounded border border-slate-300 cursor-pointer p-0.5 bg-white"
                                            oninput="document.getElementById('product_add_to_cart_bg_hex').value = this.value;"
                                        >
                                        <input
                                            type="text"
                                            id="product_add_to_cart_bg_hex"
                                            name="product_add_to_cart_bg"
                                            value="{{ old('product_add_to_cart_bg', $settings['product_add_to_cart_bg'] ?? $defaults['product_add_to_cart_bg']) }}"
                                            class="flex-1 text-xs font-mono rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                        >
                                    </div>
                                </div>
                            </div>

                            {{-- Buy Now --}}
                            <div class="space-y-2 p-3 rounded-xl border border-slate-200/80 bg-slate-50/40">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <span>⚡ Buy Now Button</span>
                                    </label>
                                    <input type="hidden" name="product_buy_now_enabled" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="product_buy_now_enabled" value="1" class="sr-only peer" {{ ($settings['product_buy_now_enabled'] ?? $defaults['product_buy_now_enabled']) === '1' ? 'checked' : '' }}>
                                        <div class="w-8 h-4 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Button Text</label>
                                    <input
                                        type="text"
                                        name="product_buy_now_text"
                                        value="{{ old('product_buy_now_text', $settings['product_buy_now_text'] ?? $defaults['product_buy_now_text']) }}"
                                        class="w-full text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                        id="input-buy-text"
                                    >
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Button Color</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value="{{ old('product_buy_now_bg', $settings['product_buy_now_bg'] ?? $defaults['product_buy_now_bg']) }}"
                                            class="h-7 w-8 rounded border border-slate-300 cursor-pointer p-0.5 bg-white"
                                            oninput="document.getElementById('product_buy_now_bg_hex').value = this.value;"
                                        >
                                        <input
                                            type="text"
                                            id="product_buy_now_bg_hex"
                                            name="product_buy_now_bg"
                                            value="{{ old('product_buy_now_bg', $settings['product_buy_now_bg'] ?? $defaults['product_buy_now_bg']) }}"
                                            class="flex-1 text-xs font-mono rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: WhatsApp & Call For Order --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4">
                            {{-- WhatsApp Order Button --}}
                            <div class="space-y-2 p-3 rounded-xl border border-emerald-200/90 bg-emerald-50/20">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                        <span>💬 Order on WhatsApp</span>
                                    </label>
                                    <input type="hidden" name="product_whatsapp_btn_enabled" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="product_whatsapp_btn_enabled" value="1" class="sr-only peer" {{ ($settings['product_whatsapp_btn_enabled'] ?? $defaults['product_whatsapp_btn_enabled']) === '1' ? 'checked' : '' }}>
                                        <div class="w-8 h-4 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">WhatsApp Number (e.g. 017XXXXXXXX)</label>
                                    <input
                                        type="text"
                                        name="product_whatsapp_number"
                                        value="{{ old('product_whatsapp_number', $settings['product_whatsapp_number'] ?? $settings['floating_whatsapp_number'] ?? $storePhone) }}"
                                        placeholder="{{ $settings['floating_whatsapp_number'] ?? $storePhone }}"
                                        class="w-full text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Button Text</label>
                                    <input
                                        type="text"
                                        name="product_whatsapp_btn_text"
                                        value="{{ old('product_whatsapp_btn_text', $settings['product_whatsapp_btn_text'] ?? $defaults['product_whatsapp_btn_text']) }}"
                                        class="w-full text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                        id="input-wa-text"
                                    >
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Button Color</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value="{{ old('product_whatsapp_btn_bg', $settings['product_whatsapp_btn_bg'] ?? $defaults['product_whatsapp_btn_bg']) }}"
                                            class="h-7 w-8 rounded border border-slate-300 cursor-pointer p-0.5 bg-white"
                                            oninput="document.getElementById('product_whatsapp_btn_bg_hex').value = this.value;"
                                        >
                                        <input
                                            type="text"
                                            id="product_whatsapp_btn_bg_hex"
                                            name="product_whatsapp_btn_bg"
                                            value="{{ old('product_whatsapp_btn_bg', $settings['product_whatsapp_btn_bg'] ?? $defaults['product_whatsapp_btn_bg']) }}"
                                            class="flex-1 text-xs font-mono rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                        >
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Message Template</label>
                                    <textarea
                                        name="product_whatsapp_message"
                                        rows="2"
                                        class="w-full text-[11px] rounded-lg border border-slate-300 p-2 focus:border-emerald-600 focus:outline-none"
                                        placeholder="Hello, I want to order {product_name}..."
                                    >{{ old('product_whatsapp_message', $settings['product_whatsapp_message'] ?? $defaults['product_whatsapp_message']) }}</textarea>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Available tokens: <code>{product_name}</code>, <code>{price}</code>, <code>{sku}</code>, <code>{url}</code></p>
                                </div>
                            </div>

                            {{-- Call For Order Button --}}
                            <div class="space-y-2 p-3 rounded-xl border border-slate-300/80 bg-slate-50/40">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <span>📞 Call For Order</span>
                                    </label>
                                    <input type="hidden" name="product_call_btn_enabled" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="product_call_btn_enabled" value="1" class="sr-only peer" {{ ($settings['product_call_btn_enabled'] ?? $defaults['product_call_btn_enabled']) === '1' ? 'checked' : '' }}>
                                        <div class="w-8 h-4 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Call Phone Number</label>
                                    <input
                                        type="text"
                                        name="product_call_number"
                                        value="{{ old('product_call_number', $settings['product_call_number'] ?? $storePhone) }}"
                                        placeholder="{{ $storePhone }}"
                                        class="w-full text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Button Text</label>
                                    <input
                                        type="text"
                                        name="product_call_btn_text"
                                        value="{{ old('product_call_btn_text', $settings['product_call_btn_text'] ?? $defaults['product_call_btn_text']) }}"
                                        class="w-full text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                        id="input-call-text"
                                    >
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Button Color</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value="{{ old('product_call_btn_bg', $settings['product_call_btn_bg'] ?? $defaults['product_call_btn_bg']) }}"
                                            class="h-7 w-8 rounded border border-slate-300 cursor-pointer p-0.5 bg-white"
                                            oninput="document.getElementById('product_call_btn_bg_hex').value = this.value;"
                                        >
                                        <input
                                            type="text"
                                            id="product_call_btn_bg_hex"
                                            name="product_call_btn_bg"
                                            value="{{ old('product_call_btn_bg', $settings['product_call_btn_bg'] ?? $defaults['product_call_btn_bg']) }}"
                                            class="flex-1 text-xs font-mono rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Image Gallery, Zoom & Discount Badge --}}
                <div class="admin-card">
                    <div class="admin-card-header bg-slate-50/70">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 text-xs font-bold">2</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Gallery, Zoom & Discount Badge</h3>
                        </div>
                    </div>
                    <div class="admin-card-body space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {{-- Discount Badge Toggle --}}
                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/40 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-800">🏷️ Discount Badge</span>
                                    <input type="hidden" name="product_show_discount_badge" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="product_show_discount_badge" value="1" class="sr-only peer" {{ ($settings['product_show_discount_badge'] ?? $defaults['product_show_discount_badge']) === '1' ? 'checked' : '' }}>
                                        <div class="w-8 h-4 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-500 block mb-1">Badge Format</label>
                                        <input
                                            type="text"
                                            name="product_discount_badge_text"
                                            value="{{ old('product_discount_badge_text', $settings['product_discount_badge_text'] ?? $defaults['product_discount_badge_text']) }}"
                                            placeholder="{discount}% Off!"
                                            class="w-full text-xs rounded-lg border border-slate-300 px-2 py-1"
                                        >
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-500 block mb-1">Badge Color</label>
                                        <input
                                            type="text"
                                            name="product_discount_badge_bg"
                                            value="{{ old('product_discount_badge_bg', $settings['product_discount_badge_bg'] ?? $defaults['product_discount_badge_bg']) }}"
                                            placeholder="#F15A24"
                                            class="w-full text-xs font-mono rounded-lg border border-slate-300 px-2 py-1"
                                        >
                                    </div>
                                </div>
                            </div>

                            {{-- Image Controls --}}
                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/40 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-700">🔍 Image Zoom / Lightbox</span>
                                    <input type="hidden" name="product_show_zoom" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="product_show_zoom" value="1" class="sr-only peer" {{ ($settings['product_show_zoom'] ?? $defaults['product_show_zoom']) === '1' ? 'checked' : '' }}>
                                        <div class="w-8 h-4 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between border-t border-slate-200/60 pt-2">
                                    <span class="text-xs font-semibold text-slate-700">↔ Carousel Arrows (&lt; and &gt;)</span>
                                    <input type="hidden" name="product_show_gallery_arrows" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="product_show_gallery_arrows" value="1" class="sr-only peer" {{ ($settings['product_show_gallery_arrows'] ?? $defaults['product_show_gallery_arrows']) === '1' ? 'checked' : '' }}>
                                        <div class="w-8 h-4 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between border-t border-slate-200/60 pt-2">
                                    <span class="text-xs font-semibold text-slate-700">Active Thumb Border</span>
                                    <input
                                        type="text"
                                        name="product_thumb_border_color"
                                        value="{{ old('product_thumb_border_color', $settings['product_thumb_border_color'] ?? $defaults['product_thumb_border_color']) }}"
                                        placeholder="#00A859"
                                        class="w-28 text-xs font-mono rounded-lg border border-slate-300 px-2 py-1"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Price Box & Information Display --}}
                <div class="admin-card">
                    <div class="admin-card-header bg-slate-50/70">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 text-xs font-bold">3</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Price Box & Info Badges</h3>
                        </div>
                    </div>
                    <div class="admin-card-body space-y-3">
                        {{-- Price Highlight Box --}}
                        <div class="p-3 rounded-xl border border-emerald-200/90 bg-emerald-50/20 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-800">💰 Highlighted Price Pill / Card</span>
                                <input type="hidden" name="product_show_price_box" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="product_show_price_box" value="1" class="sr-only peer" {{ ($settings['product_show_price_box'] ?? $defaults['product_show_price_box']) === '1' ? 'checked' : '' }}>
                                    <div class="w-8 h-4 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3 pt-1">
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Price Box Background</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value="{{ old('product_price_box_bg', $settings['product_price_box_bg'] ?? $defaults['product_price_box_bg']) }}"
                                            class="h-7 w-8 rounded border border-slate-300 cursor-pointer p-0.5 bg-white"
                                            oninput="document.getElementById('product_price_box_bg_hex').value = this.value;"
                                        >
                                        <input
                                            type="text"
                                            id="product_price_box_bg_hex"
                                            name="product_price_box_bg"
                                            value="{{ old('product_price_box_bg', $settings['product_price_box_bg'] ?? $defaults['product_price_box_bg']) }}"
                                            class="flex-1 text-xs font-mono rounded-lg border border-slate-300 px-2 py-1"
                                        >
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Price Text Color</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value="{{ old('product_price_color', $settings['product_price_color'] ?? $defaults['product_price_color']) }}"
                                            class="h-7 w-8 rounded border border-slate-300 cursor-pointer p-0.5 bg-white"
                                            oninput="document.getElementById('product_price_color_hex').value = this.value;"
                                        >
                                        <input
                                            type="text"
                                            id="product_price_color_hex"
                                            name="product_price_color"
                                            value="{{ old('product_price_color', $settings['product_price_color'] ?? $defaults['product_price_color']) }}"
                                            class="flex-1 text-xs font-mono rounded-lg border border-slate-300 px-2 py-1"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Metadata Toggles --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1">
                            <label class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50/50 cursor-pointer">
                                <span class="text-xs font-medium text-slate-700">Short Description</span>
                                <input type="hidden" name="product_show_short_description" value="0">
                                <input type="checkbox" name="product_show_short_description" value="1" class="rounded text-emerald-600 focus:ring-emerald-500" {{ ($settings['product_show_short_description'] ?? $defaults['product_show_short_description']) === '1' ? 'checked' : '' }}>
                            </label>

                            <label class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50/50 cursor-pointer">
                                <span class="text-xs font-medium text-slate-700">Show SKU</span>
                                <input type="hidden" name="product_show_sku" value="0">
                                <input type="checkbox" name="product_show_sku" value="1" class="rounded text-emerald-600 focus:ring-emerald-500" {{ ($settings['product_show_sku'] ?? $defaults['product_show_sku']) === '1' ? 'checked' : '' }}>
                            </label>

                            <label class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50/50 cursor-pointer">
                                <span class="text-xs font-medium text-slate-700">In Stock Pill</span>
                                <input type="hidden" name="product_show_stock" value="0">
                                <input type="checkbox" name="product_show_stock" value="1" class="rounded text-emerald-600 focus:ring-emerald-500" {{ ($settings['product_show_stock'] ?? $defaults['product_show_stock']) === '1' ? 'checked' : '' }}>
                            </label>

                            <label class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50/50 cursor-pointer">
                                <span class="text-xs font-medium text-slate-700">Category Tag</span>
                                <input type="hidden" name="product_show_category" value="0">
                                <input type="checkbox" name="product_show_category" value="1" class="rounded text-emerald-600 focus:ring-emerald-500" {{ ($settings['product_show_category'] ?? $defaults['product_show_category']) === '1' ? 'checked' : '' }}>
                            </label>

                            <label class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50/50 cursor-pointer">
                                <span class="text-xs font-medium text-slate-700">Delivery Badge</span>
                                <input type="hidden" name="product_show_delivery_badge" value="0">
                                <input type="checkbox" name="product_show_delivery_badge" value="1" class="rounded text-emerald-600 focus:ring-emerald-500" {{ ($settings['product_show_delivery_badge'] ?? $defaults['product_show_delivery_badge']) === '1' ? 'checked' : '' }}>
                            </label>

                            <div class="col-span-2 rounded-xl border border-slate-200 bg-slate-50/70 p-3 space-y-3">
                                <label class="flex items-center justify-between cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-800">Trust Badges Box (COD, Fast Delivery)</span>
                                        <p class="text-[10px] text-slate-500">Enable assurance badges under the buy buttons</p>
                                    </div>
                                    <input type="hidden" name="product_show_trust_badges" value="0">
                                    <input type="checkbox" name="product_show_trust_badges" value="1" class="rounded text-emerald-600 focus:ring-emerald-500" {{ ($settings['product_show_trust_badges'] ?? $defaults['product_show_trust_badges']) === '1' ? 'checked' : '' }}>
                                </label>

                                <div class="space-y-2.5 pt-1 border-t border-slate-200/60">
                                    {{-- Badge 1 --}}
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-2">
                                            <label class="text-[10px] font-semibold text-slate-500 block mb-0.5">Icon</label>
                                            <input type="text" name="product_trust_badge_1_icon" value="{{ old('product_trust_badge_1_icon', $settings['product_trust_badge_1_icon'] ?? $defaults['product_trust_badge_1_icon']) }}" class="w-full text-center text-xs rounded-lg border border-slate-300 py-1.5 focus:border-emerald-600 focus:outline-none" placeholder="✓">
                                        </div>
                                        <div class="col-span-4">
                                            <label class="text-[10px] font-semibold text-slate-500 block mb-0.5">Title</label>
                                            <input type="text" name="product_trust_badge_1_title" value="{{ old('product_trust_badge_1_title', $settings['product_trust_badge_1_title'] ?? $defaults['product_trust_badge_1_title']) }}" class="w-full text-xs rounded-lg border border-slate-300 px-2 py-1.5 focus:border-emerald-600 focus:outline-none" placeholder="ক্যাশ অন ডেলিভারি">
                                        </div>
                                        <div class="col-span-6">
                                            <label class="text-[10px] font-semibold text-slate-500 block mb-0.5">Short Text</label>
                                            <input type="text" name="product_trust_badge_1_desc" value="{{ old('product_trust_badge_1_desc', $settings['product_trust_badge_1_desc'] ?? $defaults['product_trust_badge_1_desc']) }}" class="w-full text-xs rounded-lg border border-slate-300 px-2 py-1.5 focus:border-emerald-600 focus:outline-none" placeholder="পণ্য হাতে পেয়ে দেখে মূল্য পরিশোধ করুন।">
                                        </div>
                                    </div>

                                    {{-- Badge 2 --}}
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-2">
                                            <label class="text-[10px] font-semibold text-slate-500 block mb-0.5">Icon</label>
                                            <input type="text" name="product_trust_badge_2_icon" value="{{ old('product_trust_badge_2_icon', $settings['product_trust_badge_2_icon'] ?? $defaults['product_trust_badge_2_icon']) }}" class="w-full text-center text-xs rounded-lg border border-slate-300 py-1.5 focus:border-emerald-600 focus:outline-none" placeholder="🚚">
                                        </div>
                                        <div class="col-span-4">
                                            <label class="text-[10px] font-semibold text-slate-500 block mb-0.5">Title</label>
                                            <input type="text" name="product_trust_badge_2_title" value="{{ old('product_trust_badge_2_title', $settings['product_trust_badge_2_title'] ?? $defaults['product_trust_badge_2_title']) }}" class="w-full text-xs rounded-lg border border-slate-300 px-2 py-1.5 focus:border-emerald-600 focus:outline-none" placeholder="দ্রুত ডেলিভারি">
                                        </div>
                                        <div class="col-span-6">
                                            <label class="text-[10px] font-semibold text-slate-500 block mb-0.5">Short Text</label>
                                            <input type="text" name="product_trust_badge_2_desc" value="{{ old('product_trust_badge_2_desc', $settings['product_trust_badge_2_desc'] ?? $defaults['product_trust_badge_2_desc']) }}" class="w-full text-xs rounded-lg border border-slate-300 px-2 py-1.5 focus:border-emerald-600 focus:outline-none" placeholder="ঢাকায় ২৪-৪৮ ঘণ্টা, সারা দেশে ৪৮-৭২ ঘণ্টায় ডেলিভারি।">
                                        </div>
                                    </div>

                                    {{-- Badge 3 --}}
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-2">
                                            <label class="text-[10px] font-semibold text-slate-500 block mb-0.5">Icon</label>
                                            <input type="text" name="product_trust_badge_3_icon" value="{{ old('product_trust_badge_3_icon', $settings['product_trust_badge_3_icon'] ?? $defaults['product_trust_badge_3_icon']) }}" class="w-full text-center text-xs rounded-lg border border-slate-300 py-1.5 focus:border-emerald-600 focus:outline-none" placeholder="★">
                                        </div>
                                        <div class="col-span-4">
                                            <label class="text-[10px] font-semibold text-slate-500 block mb-0.5">Title</label>
                                            <input type="text" name="product_trust_badge_3_title" value="{{ old('product_trust_badge_3_title', $settings['product_trust_badge_3_title'] ?? $defaults['product_trust_badge_3_title']) }}" class="w-full text-xs rounded-lg border border-slate-300 px-2 py-1.5 focus:border-emerald-600 focus:outline-none" placeholder="১০০% খাঁটি পণ্য">
                                        </div>
                                        <div class="col-span-6">
                                            <label class="text-[10px] font-semibold text-slate-500 block mb-0.5">Short Text</label>
                                            <input type="text" name="product_trust_badge_3_desc" value="{{ old('product_trust_badge_3_desc', $settings['product_trust_badge_3_desc'] ?? $defaults['product_trust_badge_3_desc']) }}" class="w-full text-xs rounded-lg border border-slate-300 px-2 py-1.5 focus:border-emerald-600 focus:outline-none" placeholder="গুণগত মান ও সন্তুষ্টির সম্পূর্ণ নিশ্চয়তা।">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Description, Reviews Tabs & Related Section --}}
                <div class="admin-card">
                    <div class="admin-card-header bg-slate-50/70">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 text-xs font-bold">4</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Tabs & Related Section</h3>
                        </div>
                    </div>
                    <div class="admin-card-body space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-semibold text-slate-600 block mb-1">Description Tab Label</label>
                                <input
                                    type="text"
                                    name="product_desc_tab_title"
                                    value="{{ old('product_desc_tab_title', $settings['product_desc_tab_title'] ?? $defaults['product_desc_tab_title']) }}"
                                    class="w-full text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                >
                            </div>

                            <div>
                                <label class="text-[11px] font-semibold text-slate-600 block mb-1">Delivery & Payment Tab Label</label>
                                <input
                                    type="text"
                                    name="product_delivery_payment_tab_title"
                                    value="{{ old('product_delivery_payment_tab_title', $settings['product_delivery_payment_tab_title'] ?? $defaults['product_delivery_payment_tab_title']) }}"
                                    class="w-full text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                                >
                            </div>
                        </div>

                        <div class="space-y-3 pt-1">
                            <div>
                                <label class="text-[11px] font-semibold text-slate-700 block mb-1">
                                    Delivery charges & timeframe (one point per line)
                                </label>
                                <textarea
                                    name="product_delivery_info_text"
                                    rows="3"
                                    placeholder="Inside Dhaka: ৳70 (1-2 business days)&#10;Outside Dhaka: ৳130 (2-3 business days)"
                                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:border-emerald-600 focus:outline-none"
                                >{{ old('product_delivery_info_text', $settings['product_delivery_info_text'] ?? $defaults['product_delivery_info_text']) }}</textarea>
                                <p class="mt-0.5 text-[10px] text-slate-500">
                                    Enter each delivery point on a new line. Existing modern card & bullet design will be preserved.
                                </p>
                            </div>

                            <div>
                                <label class="text-[11px] font-semibold text-slate-700 block mb-1">
                                    Payment methods & details (one point per line)
                                </label>
                                <textarea
                                    name="product_payment_info_text"
                                    rows="3"
                                    placeholder="Cash on Delivery (verify product upon delivery)&#10;bKash / Nagad / Rocket or Card payments supported"
                                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:border-emerald-600 focus:outline-none"
                                >{{ old('product_payment_info_text', $settings['product_payment_info_text'] ?? $defaults['product_payment_info_text']) }}</textarea>
                                <p class="mt-0.5 text-[10px] text-slate-500">
                                    Enter each payment option/point on a new line.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] font-semibold text-slate-600 block mb-1">Related Products Heading</label>
                            <input
                                type="text"
                                name="product_related_title"
                                value="{{ old('product_related_title', $settings['product_related_title'] ?? $defaults['product_related_title']) }}"
                                class="w-full text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 focus:border-emerald-600 focus:outline-none"
                            >
                        </div>

                        <div class="border-t border-slate-100 pt-3 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-slate-800">Related Products Section</span>
                                <p class="text-[10px] text-slate-500">Show similar products from same category at bottom of page</p>
                            </div>
                            <input type="hidden" name="product_show_related" value="0">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="product_show_related" value="1" class="sr-only peer" {{ ($settings['product_show_related'] ?? $defaults['product_show_related']) === '1' ? 'checked' : '' }}>
                                <div class="w-8 h-4 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-700 px-6 py-2.5 text-xs font-bold text-white shadow-md hover:bg-emerald-800 transition cursor-pointer"
                    >
                        Save Product Page Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- Current product page --}}
        <div class="lg:col-span-5">
            <div class="sticky top-20 space-y-3">
                <div class="flex items-center justify-between px-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Current product page</span>
                    @if ($sampleSlug)
                        <a href="{{ route('product.show', $sampleSlug) }}" target="_blank" class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-900">Open live page</a>
                    @endif
                </div>

                @if ($sampleSlug)
                    <iframe
                        src="{{ route('product.show', $sampleSlug) }}"
                        title="Current product page"
                        class="h-[78vh] w-full rounded-2xl border border-slate-200 bg-white shadow-sm"
                    ></iframe>
                    <p class="px-1 text-[11px] text-slate-500">Save settings to refresh this page. Button text, colors, tabs, trust badges, WhatsApp, call, and related products all come from this form.</p>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">Add an active product to preview the current product page here.</div>
                @endif
            </div>
        </div>
    </div>
@endsection
