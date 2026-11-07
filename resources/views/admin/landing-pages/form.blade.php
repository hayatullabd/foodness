@php
    $assignedProducts = old('products');
    if ($assignedProducts === null) {
        if (isset($landingPage) && $landingPage->landingPageProducts->isNotEmpty()) {
            $assignedProducts = $landingPage->landingPageProducts->map(function ($lpp) {
                return [
                    'product_id' => $lpp->product_id,
                    'custom_price' => $lpp->custom_price,
                    'is_default' => $lpp->is_default,
                ];
            })->values()->all();
        } elseif (isset($landingPage) && $landingPage->product_id) {
            $assignedProducts = [
                [
                    'product_id' => $landingPage->product_id,
                    'custom_price' => $landingPage->custom_price,
                    'is_default' => true,
                ],
            ];
        } else {
            $assignedProducts = [
                [
                    'product_id' => '',
                    'custom_price' => '',
                    'is_default' => true,
                ],
            ];
        }
    }
    $productSelectionType = old('product_selection_type', $landingPage->product_selection_type ?? 'single');
@endphp

<style>
    .lp-form {
        --lp-border: #e5e7eb;
        --lp-muted: #6b7280;
        --lp-ink: #111827;
        --lp-h: 34px;
        --lp-radius: 8px;
        --lp-green: #059669;
    }
    .lp-form .lp-shell {
        background: #fff;
        border: 1px solid var(--lp-border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(17, 24, 39, 0.04);
    }
    .lp-form .lp-nav {
        display: flex;
        gap: 4px;
        padding: 8px 10px;
        background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%);
        border-bottom: 1px solid var(--lp-border);
        overflow-x: auto;
    }
    .lp-form .lp-nav-btn {
        flex-shrink: 0;
        border: 1px solid transparent;
        background: transparent;
        color: var(--lp-muted);
        font-size: 12px;
        font-weight: 600;
        padding: 7px 12px;
        border-radius: 8px;
        cursor: pointer;
        line-height: 1.2;
        transition: .15s ease;
    }
    .lp-form .lp-nav-btn:hover { color: var(--lp-ink); background: #fff; border-color: #e5e7eb; }
    .lp-form .lp-nav-btn.is-active {
        color: #065f46;
        background: #fff;
        border-color: #a7f3d0;
        box-shadow: 0 1px 2px rgba(5, 150, 105, 0.08);
    }
    .lp-form .lp-panel { display: none; padding: 16px 18px 18px; }
    .lp-form .lp-panel.is-open { display: block; }
    .lp-form .lp-subhead {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin: 0 0 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f3f4f6;
    }
    .lp-form .lp-subhead h4 {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: var(--lp-ink);
        letter-spacing: -0.01em;
    }
    .lp-form .lp-subhead p {
        margin: 2px 0 0;
        font-size: 12px;
        color: var(--lp-muted);
    }
    .lp-form .lp-label {
        display: block;
        margin: 0 0 5px;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        line-height: 1.3;
    }
    .lp-form .lp-hint { display: none; }
    .lp-form .field,
    .lp-form .lp-field,
    .lp-form input.field,
    .lp-form select.field,
    .lp-form textarea.field,
    .admin-app .admin-content .lp-form .field,
    .admin-app .admin-content .lp-form input.field,
    .admin-app .admin-content .lp-form select.field {
        width: 100% !important;
        height: var(--lp-h) !important;
        min-height: var(--lp-h) !important;
        max-height: var(--lp-h) !important;
        padding: 0 11px !important;
        font-size: 13px !important;
        line-height: 1.3 !important;
        border: 1px solid #d1d5db !important;
        border-radius: var(--lp-radius) !important;
        background: #fff !important;
        color: var(--lp-ink) !important;
        box-shadow: none !important;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .lp-form .field:focus,
    .lp-form .lp-field:focus,
    .admin-app .admin-content .lp-form .field:focus {
        border-color: #34d399 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12) !important;
        outline: none !important;
    }
    .lp-form textarea.field {
        height: auto !important;
        max-height: none !important;
        min-height: 72px !important;
        padding: 8px 11px !important;
    }
    .lp-form .lp-grid-2,
    .lp-form .lp-grid-3,
    .lp-form .lp-grid-4 {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }
    @media (min-width: 768px) {
        .lp-form .lp-grid-2 { grid-template-columns: 1fr 1fr; gap: 12px 14px; }
        .lp-form .lp-grid-3 { grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .lp-form .lp-grid-4 { grid-template-columns: 1fr 1fr; gap: 12px 14px; }
    }
    @media (min-width: 1100px) {
        .lp-form .lp-grid-4 { grid-template-columns: repeat(4, 1fr); }
    }
    .lp-form .lp-grid-2 > div,
    .lp-form .lp-grid-3 > div,
    .lp-form .lp-grid-4 > div {
        display: block;
        min-width: 0;
    }
    .lp-form .lp-controls {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        margin-top: 14px;
        padding: 12px;
        border: 1px solid #f3f4f6;
        border-radius: 10px;
        background: #fafafa;
    }
    @media (min-width: 768px) {
        .lp-form .lp-controls { grid-template-columns: 1.1fr .9fr .9fr; align-items: end; }
    }
    .lp-form .lp-chip {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: var(--lp-h);
        padding: 0 12px;
        border: 1px solid #e5e7eb;
        border-radius: var(--lp-radius);
        background: #fff;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
    }
    .lp-form .lp-chip input {
        width: 15px !important;
        height: 15px !important;
        min-height: 0 !important;
        max-height: none !important;
        padding: 0 !important;
        flex: none !important;
        accent-color: var(--lp-green);
    }
    .lp-form .lp-seg {
        display: inline-flex;
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: var(--lp-radius);
        background: #fff;
        padding: 3px;
        height: var(--lp-h);
    }
    .lp-form .sel-mode-label {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        color: #6b7280;
        line-height: 1;
        transition: .15s ease;
    }
    .lp-form .sel-mode-label.is-on {
        background: var(--lp-green);
        color: #fff;
        box-shadow: 0 1px 2px rgba(5, 150, 105, 0.25);
    }
    .lp-form .lp-slug {
        display: flex;
        align-items: stretch;
        height: var(--lp-h);
        border: 1px solid #d1d5db;
        border-radius: var(--lp-radius);
        overflow: hidden;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .lp-form .lp-slug:focus-within {
        border-color: #34d399;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
    }
    .lp-form .lp-slug-prefix {
        display: inline-flex;
        align-items: center;
        padding: 0 10px;
        background: #f9fafb;
        color: #6b7280;
        font-size: 12px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        border-right: 1px solid #e5e7eb;
        white-space: nowrap;
    }
    .lp-form .lp-slug .field {
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        height: 100% !important;
    }
    .lp-form .product-repeater-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        align-items: center;
        padding: 10px;
        border: 1px solid var(--lp-border);
        border-radius: 10px;
        background: #fff;
    }
    @media (min-width: 768px) {
        .lp-form .product-repeater-row {
            grid-template-columns: minmax(0, 1.5fr) 140px auto auto;
            gap: 10px;
        }
    }
    .lp-form .product-repeater-row .field,
    .lp-form .product-repeater-row input[type="number"] {
        height: var(--lp-h) !important;
        min-height: var(--lp-h) !important;
        padding: 0 10px !important;
        font-size: 13px !important;
        border-radius: var(--lp-radius) !important;
        width: 100% !important;
    }
    .lp-form .field-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border: 1px solid var(--lp-border);
        border-radius: 10px;
        background: #fff;
        cursor: grab;
    }
    .lp-form .field-item .field {
        height: 32px !important;
        min-height: 32px !important;
        max-height: 32px !important;
        padding: 0 10px !important;
        font-size: 13px !important;
        width: 100% !important;
    }
    .lp-form .lp-delivery label.lp-radio {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px;
        border: 1px solid var(--lp-border);
        border-radius: 10px;
        cursor: pointer;
        background: #fff;
        font-size: 12px;
        transition: .15s ease;
    }
    .lp-form .lp-delivery label.lp-radio:hover { border-color: #a7f3d0; }
    .lp-form .lp-delivery label.lp-radio:has(:checked) {
        border-color: #34d399;
        background: #ecfdf5;
    }
    .lp-form #add-product-row-btn,
    .lp-form #btn-add-custom-field {
        height: 32px;
        padding: 0 12px !important;
        font-size: 12px !important;
        border-radius: 8px !important;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .lp-form .mt-3 { margin-top: 14px !important; }
    .lp-form .pt-3 { padding-top: 14px !important; }
    .lp-form .border-t { border-color: #f3f4f6 !important; }
</style>

<div class="space-y-4 lp-form">

    <div class="lp-shell">
        <div class="lp-nav" id="lp-form-nav">
            <button type="button" class="lp-nav-btn is-active" data-lp-panel="page">Page</button>
            <button type="button" class="lp-nav-btn" data-lp-panel="products">Products</button>
            <button type="button" class="lp-nav-btn" data-lp-panel="checkout">Checkout</button>
            <button type="button" class="lp-nav-btn" data-lp-panel="fields">Fields</button>
            <button type="button" class="lp-nav-btn" data-lp-panel="delivery">Delivery</button>
        </div>

        {{-- 1. Page --}}
        <div class="lp-panel is-open" data-lp-panel="page">
            <div class="lp-subhead">
                <div>
                    <h4>Page basics</h4>
                    <p>Title, public URL, and publish options</p>
                </div>
            </div>

            <div class="lp-grid-2">
                <div>
                    <label for="title" class="lp-label">Landing page title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" required value="{{ old('title', $landingPage->title ?? '') }}" placeholder="e.g. Am Sotto Offer" class="field lp-field">
                    @error('title')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="slug" class="lp-label">URL slug <span class="text-red-500">*</span></label>
                    <div class="lp-slug">
                        <span class="lp-slug-prefix">/{{ \App\Support\LandingUrl::prefix() }}/</span>
                        <input type="text" id="slug" name="slug" required value="{{ old('slug', $landingPage->slug ?? '') }}" placeholder="am-sotto" class="field lp-field font-mono">
                    </div>
                    @error('slug')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="lp-controls">
                <div>
                    <label class="lp-label">Selection mode</label>
                    <div class="lp-seg">
                        <label class="sel-mode-label {{ $productSelectionType === 'single' ? 'is-on' : '' }}">
                            <input type="radio" name="product_selection_type" value="single" class="sr-only" {{ $productSelectionType === 'single' ? 'checked' : '' }} onchange="document.querySelectorAll('.sel-mode-label').forEach(el=>el.classList.remove('is-on')); this.closest('label').classList.add('is-on')">
                            Single
                        </label>
                        <label class="sel-mode-label {{ $productSelectionType === 'multiple' ? 'is-on' : '' }}">
                            <input type="radio" name="product_selection_type" value="multiple" class="sr-only" {{ $productSelectionType === 'multiple' ? 'checked' : '' }} onchange="document.querySelectorAll('.sel-mode-label').forEach(el=>el.classList.remove('is-on')); this.closest('label').classList.add('is-on')">
                            Multiple
                        </label>
                    </div>
                </div>
                <label class="lp-chip cursor-pointer">
                    <input type="checkbox" name="show_quantity" value="1" class="rounded text-emerald-600" {{ old('show_quantity', $landingPage->show_quantity ?? true) ? 'checked' : '' }}>
                    Show quantity stepper
                </label>
                <label class="lp-chip cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="rounded text-emerald-600" {{ old('is_active', $landingPage->is_active ?? true) ? 'checked' : '' }}>
                    Page is live
                </label>
            </div>
        </div>

        {{-- 2. Products --}}
        <div class="lp-panel" data-lp-panel="products">
            <div class="lp-subhead">
                <div>
                    <h4>Assigned products</h4>
                    <p>Catalog items + optional campaign price</p>
                </div>
                <button type="button" id="add-product-row-btn" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-bold bg-emerald-600 text-white hover:bg-emerald-700 cursor-pointer">
                    + Add product
                </button>
            </div>

            <div class="hidden md:grid grid-cols-[1.4fr_.55fr_auto_auto] gap-2 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                <div>Product</div>
                <div>Offer price (৳)</div>
                <div>Default</div>
                <div></div>
            </div>

            <div id="products-repeater-container" class="space-y-2">
                @foreach ($assignedProducts as $index => $row)
                    <div class="product-repeater-row">
                        <div class="min-w-0">
                            <label class="lp-label md:hidden">Product</label>
                            <select name="products[{{ $index }}][product_id]" required class="product-select field lp-field bg-white">
                                <option value="">-- Choose product --</option>
                                @foreach ($products as $prod)
                                    <option value="{{ $prod->id }}" data-price="{{ $prod->price }}" data-img="{{ $prod->primaryImagePath() ? asset('storage/' . $prod->primaryImagePath()) : '' }}" {{ ($row['product_id'] == $prod->id) ? 'selected' : '' }}>
                                        {{ $prod->name }} — ৳{{ number_format((float) $prod->price) }}{{ ! $prod->is_visible_in_store ? ' [Landing Only]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-0">
                            <label class="lp-label md:hidden">Offer price</label>
                            <div class="flex items-center h-[34px] rounded-lg border border-slate-300 overflow-hidden focus-within:border-emerald-500 focus-within:ring-[3px] focus-within:ring-emerald-500/10">
                                <span class="px-2.5 h-full inline-flex items-center bg-slate-50 text-xs font-semibold text-slate-500 border-r border-slate-200">৳</span>
                                <input type="number" step="0.01" min="0" name="products[{{ $index }}][custom_price]" value="{{ $row['custom_price'] ?? '' }}" placeholder="Original" class="w-full h-full px-2.5 text-sm outline-none" title="Leave blank to use original product price">
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-700 cursor-pointer select-none">
                            <input type="checkbox" name="products[{{ $index }}][is_default]" value="1" class="default-checkbox rounded text-emerald-600 h-3.5 w-3.5" {{ !empty($row['is_default']) ? 'checked' : '' }}>
                            Default
                        </label>
                        <button type="button" class="remove-row-btn p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition justify-self-end" title="Remove">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                @endforeach
            </div>

            <template id="product-row-template">
                <div class="product-repeater-row">
                    <div class="min-w-0">
                        <label class="lp-label md:hidden">Product</label>
                        <select name="products[__INDEX__][product_id]" required class="product-select field lp-field bg-white">
                            <option value="">-- Choose product --</option>
                            @foreach ($products as $prod)
                                <option value="{{ $prod->id }}" data-price="{{ $prod->price }}" data-img="{{ $prod->primaryImagePath() ? asset('storage/' . $prod->primaryImagePath()) : '' }}">
                                    {{ $prod->name }} — ৳{{ number_format((float) $prod->price) }}{{ ! $prod->is_visible_in_store ? ' [Landing Only]' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label class="lp-label md:hidden">Offer price</label>
                        <div class="flex items-center h-[34px] rounded-lg border border-slate-300 overflow-hidden focus-within:border-emerald-500 focus-within:ring-[3px] focus-within:ring-emerald-500/10">
                            <span class="px-2.5 h-full inline-flex items-center bg-slate-50 text-xs font-semibold text-slate-500 border-r border-slate-200">৳</span>
                            <input type="number" step="0.01" min="0" name="products[__INDEX__][custom_price]" placeholder="Original" class="w-full h-full px-2.5 text-sm outline-none" title="Leave blank to use original product price">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" name="products[__INDEX__][is_default]" value="1" class="default-checkbox rounded text-emerald-600 h-3.5 w-3.5">
                        Default
                    </label>
                    <button type="button" class="remove-row-btn p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition justify-self-end" title="Remove">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </template>
        </div>

        {{-- 3. Checkout copy + theme --}}
        <div class="lp-panel" data-lp-panel="checkout">
            <div class="lp-subhead">
                <div>
                    <h4>Checkout copy & theme</h4>
                    <p>Headings, labels, pixel and button color</p>
                </div>
            </div>

            <div class="lp-grid-2">
                <div>
                    <label for="checkout_heading" class="lp-label">Form heading</label>
                    <input type="text" id="checkout_heading" name="checkout_heading" value="{{ old('checkout_heading', $landingPage->checkout_heading ?? 'অর্ডার কনফার্ম করতে নিচের তথ্যগুলো পূরণ করুন') }}" class="field lp-field" placeholder="Checkout heading">
                </div>
                <div>
                    <label for="checkout_subheading" class="lp-label">Form subtitle</label>
                    <input type="text" id="checkout_subheading" name="checkout_subheading" value="{{ old('checkout_subheading', $landingPage->checkout_subheading ?? 'সব তথ্য ঠিক থাকলে অর্ডার কনফার্ম বাটনে ক্লিক করুন') }}" class="field lp-field" placeholder="Checkout subtitle">
                </div>
                <div>
                    <label for="product_selection_label" class="lp-label">Product selection label</label>
                    <input type="text" id="product_selection_label" name="product_selection_label" value="{{ old('product_selection_label', $landingPage->product_selection_label ?? 'পণ্য নির্বাচন করুন:') }}" class="field lp-field">
                </div>
                <div>
                    <label for="variant_label" class="lp-label">Variant / size label</label>
                    <input type="text" id="variant_label" name="variant_label" value="{{ old('variant_label', $landingPage->variant_label ?? 'পরিমাণ / সাইজ / কালার নির্বাচন করুন:') }}" class="field lp-field">
                </div>
                <div>
                    <label for="checkout_button_text" class="lp-label">Submit button text</label>
                    <input type="text" id="checkout_button_text" name="checkout_button_text" value="{{ old('checkout_button_text', $landingPage->checkout_button_text ?? 'অর্ডার কনফার্ম করুন') }}" class="field lp-field">
                </div>
                <div>
                    <label for="fb_pixel_id" class="lp-label">Meta Pixel ID <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="text" id="fb_pixel_id" name="fb_pixel_id" value="{{ old('fb_pixel_id', $landingPage->fb_pixel_id ?? '') }}" class="field lp-field font-mono" placeholder="123456789012345">
                </div>
            </div>

            <div class="mt-3 pt-3 border-t border-slate-100">
                <label class="lp-label mb-2">Button color</label>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 border border-slate-300 rounded-lg px-2 py-1 bg-white">
                        <input type="color" id="checkout_color" name="checkout_color" value="{{ old('checkout_color', $landingPage->checkout_color ?? '#044120') }}" class="w-6 h-6 rounded border-0 cursor-pointer p-0 bg-transparent">
                        <input type="text" id="checkout_color_hex" value="{{ old('checkout_color', $landingPage->checkout_color ?? '#044120') }}" class="w-20 text-[11px] font-mono font-bold uppercase outline-none" placeholder="#044120">
                    </div>
                    @foreach (['#10b981'=>'Green','#e11d48'=>'Red','#2563eb'=>'Blue','#d97706'=>'Amber','#7c3aed'=>'Purple','#0f172a'=>'Dark'] as $hex => $name)
                        <button type="button" onclick="setCheckoutColor('{{ $hex }}')" class="px-2 py-1 rounded-md text-[10px] font-bold text-white" style="background:{{ $hex }}">{{ $name }}</button>
                    @endforeach
                    <div id="checkout-color-preview" class="ml-auto px-3 py-1.5 rounded-lg text-[11px] font-bold text-white" style="background-color: {{ old('checkout_color', $landingPage->checkout_color ?? '#044120') }};">
                        {{ old('checkout_button_text', $landingPage->checkout_button_text ?? 'অর্ডার কনফার্ম করুন') }}
                    </div>
                </div>
            </div>

            <div class="mt-3 pt-3 border-t border-slate-100">
                <div class="lp-subhead !border-0 !pb-0 !mb-2">
                    <div>
                        <h4>Section & table labels</h4>
                        <p>Billing / order titles and field placeholders</p>
                    </div>
                </div>
                <div class="lp-grid-4">
                    <div>
                        <label for="f_billing_title" class="lp-label">Billing title</label>
                        <input type="text" id="f_billing_title" name="checkout_fields_config[billing_title]" value="{{ old('checkout_fields_config.billing_title', $landingPage->checkoutField('billing_title', 'Billing details')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_order_title" class="lp-label">Order title</label>
                        <input type="text" id="f_order_title" name="checkout_fields_config[order_title]" value="{{ old('checkout_fields_config.order_title', $landingPage->checkoutField('order_title', 'Your order')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_name_label" class="lp-label">Name label</label>
                        <input type="text" id="f_name_label" name="checkout_fields_config[name_label]" value="{{ old('checkout_fields_config.name_label', $landingPage->checkoutField('name_label', 'আপনার নাম')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_name_placeholder" class="lp-label">Name placeholder</label>
                        <input type="text" id="f_name_placeholder" name="checkout_fields_config[name_placeholder]" value="{{ old('checkout_fields_config.name_placeholder', $landingPage->checkoutField('name_placeholder', 'যেমন: মো: আরিফুল ইসলাম')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_address_label" class="lp-label">Address label</label>
                        <input type="text" id="f_address_label" name="checkout_fields_config[address_label]" value="{{ old('checkout_fields_config.address_label', $landingPage->checkoutField('address_label', 'আপনার সম্পূর্ণ ঠিকানা')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_address_placeholder" class="lp-label">Address placeholder</label>
                        <input type="text" id="f_address_placeholder" name="checkout_fields_config[address_placeholder]" value="{{ old('checkout_fields_config.address_placeholder', $landingPage->checkoutField('address_placeholder', 'বাড়ি/রোড নং, এলাকা, থানা ও জেলার নাম লিখুন...')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_phone_label" class="lp-label">Phone label</label>
                        <input type="text" id="f_phone_label" name="checkout_fields_config[phone_label]" value="{{ old('checkout_fields_config.phone_label', $landingPage->checkoutField('phone_label', 'আপনার ফোন নাম্বার')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_phone_placeholder" class="lp-label">Phone placeholder</label>
                        <input type="text" id="f_phone_placeholder" name="checkout_fields_config[phone_placeholder]" value="{{ old('checkout_fields_config.phone_placeholder', $landingPage->checkoutField('phone_placeholder', 'যেমন: 017XXXXXXXX')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_shipping_label" class="lp-label">Delivery area label</label>
                        <input type="text" id="f_shipping_label" name="checkout_fields_config[shipping_label]" value="{{ old('checkout_fields_config.shipping_label', $landingPage->checkoutField('shipping_label', 'ডেলিভারি এলাকা নির্বাচন করুন')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_notes_placeholder" class="lp-label">Notes placeholder</label>
                        <input type="text" id="f_notes_placeholder" name="checkout_fields_config[notes_placeholder]" value="{{ old('checkout_fields_config.notes_placeholder', $landingPage->checkoutField('notes_placeholder', 'অতিরিক্ত কোনো মন্তব্য বা স্পেশাল নোট (ঐচ্ছিক)')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_table_product" class="lp-label">Table: Product</label>
                        <input type="text" id="f_table_product" name="checkout_fields_config[table_product]" value="{{ old('checkout_fields_config.table_product', $landingPage->checkoutField('table_product', 'Product')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_table_subtotal" class="lp-label">Table: Subtotal</label>
                        <input type="text" id="f_table_subtotal" name="checkout_fields_config[table_subtotal]" value="{{ old('checkout_fields_config.table_subtotal', $landingPage->checkoutField('table_subtotal', 'Subtotal')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_table_shipment" class="lp-label">Table: Shipment</label>
                        <input type="text" id="f_table_shipment" name="checkout_fields_config[table_shipment]" value="{{ old('checkout_fields_config.table_shipment', $landingPage->checkoutField('table_shipment', 'Shipment')) }}" class="field lp-field">
                    </div>
                    <div>
                        <label for="f_table_total" class="lp-label">Table: Total</label>
                        <input type="text" id="f_table_total" name="checkout_fields_config[table_total]" value="{{ old('checkout_fields_config.table_total', $landingPage->checkoutField('table_total', 'Total')) }}" class="field lp-field">
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Field builder --}}
        <div class="lp-panel" data-lp-panel="fields">
            <div class="lp-subhead">
                <div>
                    <h4>Form fields</h4>
                    <p>Reorder, rename, or add custom checkout fields</p>
                </div>
                <button type="button" id="btn-add-custom-field" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-bold bg-emerald-600 text-white hover:bg-emerald-700 cursor-pointer">
                    + Custom field
                </button>
            </div>

            @php
                $resolvedBuilderFields = $landingPage->resolvedCheckoutFields();
            @endphp

            <div id="checkout-fields-sortable-list" class="space-y-1.5">
                @foreach ($resolvedBuilderFields as $fIdx => $bFld)
                    <div class="field-item" draggable="true" data-field-index="{{ $fIdx }}">
                        <span class="drag-handle text-slate-400 font-mono text-sm px-0.5 select-none">⋮⋮</span>
                        <input type="hidden" name="checkout_fields_config[fields_builder][{{ $fIdx }}][key]" value="{{ $bFld['key'] }}" class="f-key">
                        <input type="hidden" name="checkout_fields_config[fields_builder][{{ $fIdx }}][is_system]" value="{{ !empty($bFld['is_system']) ? '1' : '0' }}" class="f-is-system">
                        <div class="flex-1 min-w-0">
                            <input type="text" name="checkout_fields_config[fields_builder][{{ $fIdx }}][label]" value="{{ $bFld['label'] }}" class="field lp-field !py-1 font-semibold" placeholder="Label">
                        </div>
                        <div class="flex-1 min-w-0 hidden sm:block">
                            <input type="text" name="checkout_fields_config[fields_builder][{{ $fIdx }}][placeholder]" value="{{ $bFld['placeholder'] }}" class="field lp-field !py-1" placeholder="Placeholder">
                        </div>
                        <div class="w-20 shrink-0">
                            @if (!empty($bFld['is_system']) && in_array($bFld['key'], ['customer_name', 'customer_address', 'customer_phone', 'shipping_option'], true))
                                <input type="hidden" name="checkout_fields_config[fields_builder][{{ $fIdx }}][type]" value="{{ $bFld['type'] }}">
                                <span class="text-[10px] font-bold px-1.5 py-1 rounded bg-slate-100 text-slate-600 block text-center uppercase">{{ $bFld['type'] }}</span>
                            @else
                                <select name="checkout_fields_config[fields_builder][{{ $fIdx }}][type]" class="field lp-field !py-1">
                                    <option value="text" @selected($bFld['type'] === 'text')>Text</option>
                                    <option value="textarea" @selected($bFld['type'] === 'textarea')>Textarea</option>
                                    <option value="tel" @selected($bFld['type'] === 'tel')>Phone</option>
                                    <option value="email" @selected($bFld['type'] === 'email')>Email</option>
                                    <option value="number" @selected($bFld['type'] === 'number')>Number</option>
                                </select>
                            @endif
                        </div>
                        <label class="flex items-center gap-1 text-[10px] font-semibold text-slate-700 shrink-0 cursor-pointer select-none">
                            <input type="checkbox" name="checkout_fields_config[fields_builder][{{ $fIdx }}][required]" value="1" @checked(!empty($bFld['required'])) @disabled(!empty($bFld['is_system']) && $bFld['key'] !== 'order_notes') class="w-3.5 h-3.5 rounded text-emerald-600">
                            Req
                        </label>
                        <div class="flex items-center gap-0.5 shrink-0">
                            <button type="button" class="btn-move-field-up p-1 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100" title="Move Up">▲</button>
                            <button type="button" class="btn-move-field-down p-1 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100" title="Move Down">▼</button>
                        </div>
                        @if (empty($bFld['is_system']) || $bFld['key'] === 'order_notes')
                            <button type="button" class="btn-remove-custom-field text-red-500 hover:bg-red-50 p-1 rounded font-bold text-xs shrink-0" title="Remove">✕</button>
                        @else
                            <span class="w-4 text-center text-slate-300 text-[10px] shrink-0">•</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 5. Delivery --}}
        <div class="lp-panel lp-delivery" data-lp-panel="delivery">
            <div class="lp-subhead">
                <div>
                    <h4>Delivery charges</h4>
                    <p>How shipping is calculated on this page</p>
                </div>
            </div>

            <div class="lp-grid-3">
                <label class="lp-radio">
                    <input type="radio" name="delivery_charge_type" value="default" class="text-emerald-600 mt-0.5" {{ old('delivery_charge_type', $landingPage->delivery_charge_type ?? 'default') === 'default' ? 'checked' : '' }}>
                    <span>
                        <span class="block font-bold text-slate-800">Inherit from products</span>
                        <span class="block text-slate-500 text-[10px] mt-0.5">Use product & variation delivery settings</span>
                    </span>
                </label>
                <label class="lp-radio">
                    <input type="radio" name="delivery_charge_type" value="free" class="text-emerald-600 mt-0.5" {{ old('delivery_charge_type', $landingPage->delivery_charge_type ?? '') === 'free' ? 'checked' : '' }}>
                    <span>
                        <span class="block font-bold text-slate-800">Free delivery</span>
                        <span class="block text-slate-500 text-[10px] mt-0.5">Zero shipping for all orders</span>
                    </span>
                </label>
                <label class="lp-radio">
                    <input type="radio" name="delivery_charge_type" value="custom" class="text-emerald-600 mt-0.5" {{ old('delivery_charge_type', $landingPage->delivery_charge_type ?? '') === 'custom' ? 'checked' : '' }}>
                    <span>
                        <span class="block font-bold text-slate-800">Custom rates</span>
                        <span class="block text-slate-500 text-[10px] mt-0.5">Flat Inside / Outside Dhaka</span>
                    </span>
                </label>
            </div>

            <div id="custom-delivery-fields" class="mt-3 lp-grid-2 {{ old('delivery_charge_type', $landingPage->delivery_charge_type ?? 'default') === 'custom' ? '' : 'hidden' }}">
                <div>
                    <label for="custom_delivery_inside" class="lp-label">Inside Dhaka (৳)</label>
                    <input type="number" step="0.01" min="0" id="custom_delivery_inside" name="custom_delivery_inside" value="{{ old('custom_delivery_inside', $landingPage->custom_delivery_inside ?? 70) }}" class="field lp-field" placeholder="70">
                </div>
                <div>
                    <label for="custom_delivery_outside" class="lp-label">Outside Dhaka (৳)</label>
                    <input type="number" step="0.01" min="0" id="custom_delivery_outside" name="custom_delivery_outside" value="{{ old('custom_delivery_outside', $landingPage->custom_delivery_outside ?? 130) }}" class="field lp-field" placeholder="130">
                </div>
            </div>
        </div>
    </div>

    {{-- Landing Page Design — VS Code style IDE --}}
    <style>
        .lp-ide {
            --vsc-bg: #1e1e1e;
            --vsc-sidebar: #252526;
            --vsc-activity: #333333;
            --vsc-editor: #1e1e1e;
            --vsc-tabbar: #2d2d2d;
            --vsc-tab: #2d2d2d;
            --vsc-tab-active: #1e1e1e;
            --vsc-border: #3c3c3c;
            --vsc-fg: #cccccc;
            --vsc-muted: #858585;
            --vsc-hover: #2a2d2e;
            --vsc-active: #37373d;
            --vsc-accent: #007acc;
            --vsc-selection: #264f78;
            --vsc-gutter: #1e1e1e;
            --vsc-line-num: #858585;
            --vsc-title: #3c3c3c;
            --vsc-status: #007acc;
            --vsc-status-fg: #ffffff;
            --vsc-input: #3c3c3c;
            --vsc-code-fg: #d4d4d4;
            --vsc-font: "Cascadia Code", "Fira Code", Consolas, "Courier New", monospace;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #3c3c3c;
            box-shadow: 0 12px 40px rgba(0,0,0,.18);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--vsc-fg);
            background: var(--vsc-bg);
        }
        .lp-ide[data-theme="light"] {
            --vsc-bg: #ffffff;
            --vsc-sidebar: #f3f3f3;
            --vsc-activity: #2c2c2c;
            --vsc-editor: #ffffff;
            --vsc-tabbar: #f3f3f3;
            --vsc-tab: #ececec;
            --vsc-tab-active: #ffffff;
            --vsc-border: #e5e5e5;
            --vsc-fg: #333333;
            --vsc-muted: #6e6e6e;
            --vsc-hover: #e8e8e8;
            --vsc-active: #d6d6d6;
            --vsc-accent: #005fb8;
            --vsc-selection: #add6ff;
            --vsc-gutter: #ffffff;
            --vsc-line-num: #237893;
            --vsc-title: #dddddd;
            --vsc-status: #005fb8;
            --vsc-status-fg: #ffffff;
            --vsc-input: #ffffff;
            --vsc-code-fg: #000000;
        }
        .lp-ide * { box-sizing: border-box; }
        .lp-ide .vsc-titlebar {
            height: 36px;
            background: var(--vsc-title);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            border-bottom: 1px solid var(--vsc-border);
            user-select: none;
        }
        .lp-ide .vsc-dots { display: flex; gap: 7px; align-items: center; }
        .lp-ide .vsc-dot { width: 11px; height: 11px; border-radius: 50%; }
        .lp-ide .vsc-title-center {
            position: absolute; left: 50%; transform: translateX(-50%);
            font-size: 12px; color: var(--vsc-fg); opacity: .85; white-space: nowrap;
        }
        .lp-ide .vsc-shell { display: flex; min-height: 620px; height: min(78vh, 780px); }
        .lp-ide .vsc-activity {
            width: 48px; background: var(--vsc-activity); display: flex; flex-direction: column;
            align-items: center; padding: 8px 0; gap: 2px; flex-shrink: 0;
        }
        .lp-ide .vsc-act-btn {
            width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;
            color: #858585; background: transparent; border: 0; border-left: 2px solid transparent;
            cursor: pointer; position: relative;
        }
        .lp-ide .vsc-act-btn:hover { color: #fff; }
        .lp-ide .vsc-act-btn.is-active { color: #fff; border-left-color: #fff; }
        .lp-ide[data-theme="light"] .vsc-act-btn:hover,
        .lp-ide[data-theme="light"] .vsc-act-btn.is-active { color: #fff; }
        .lp-ide .vsc-sidebar {
            width: 280px; background: var(--vsc-sidebar); border-right: 1px solid var(--vsc-border);
            display: flex; flex-direction: column; flex-shrink: 0; min-width: 0;
        }
        .lp-ide .vsc-sidebar.is-collapsed { display: none; }
        .lp-ide .vsc-side-head {
            height: 35px; display: flex; align-items: center; justify-content: space-between;
            padding: 0 12px; font-size: 11px; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: var(--vsc-muted);
        }
        .lp-ide .vsc-side-body { flex: 1; overflow: auto; padding-bottom: 8px; }
        .lp-ide .vsc-section-label {
            padding: 6px 12px; font-size: 11px; font-weight: 700; letter-spacing: .04em;
            text-transform: uppercase; color: var(--vsc-fg); display: flex; align-items: center; gap: 6px;
            cursor: default;
        }
        .lp-ide .vsc-file {
            width: 100%; display: flex; align-items: center; gap: 8px; padding: 5px 12px 5px 20px;
            border: 0; background: transparent; color: var(--vsc-fg); font-size: 13px; cursor: pointer; text-align: left;
        }
        .lp-ide .vsc-file:hover { background: var(--vsc-hover); }
        .lp-ide .vsc-file.is-active { background: var(--vsc-active); }
        .lp-ide .vsc-file-icon { width: 14px; height: 14px; flex-shrink: 0; }
        .lp-ide .vsc-snippet-block { padding: 6px 10px 10px; }
        .lp-ide .vsc-snip {
            background: var(--vsc-input); border: 1px solid var(--vsc-border); border-radius: 4px;
            padding: 7px 8px; margin-top: 6px; display: flex; gap: 8px; align-items: flex-start;
        }
        .lp-ide .vsc-snip code {
            flex: 1; min-width: 0; font-family: var(--vsc-font); font-size: 11px; line-height: 1.45;
            color: #9cdcfe; word-break: break-all; white-space: pre-wrap;
        }
        .lp-ide .vsc-snip code.green { color: #4ec9b0; }
        .lp-ide .vsc-snip code.amber { color: #dcdcaa; }
        .lp-ide .vsc-snip code.pink { color: #c586c0; }
        .lp-ide .lp-copy-btn {
            background: #3c3c3c; border: 1px solid #555; color: #fff; border-radius: 3px;
            padding: 2px 7px; font-size: 10px; font-weight: 600; cursor: pointer; flex-shrink: 0;
        }
        .lp-ide .lp-copy-btn:hover { background: #505050; }
        .lp-ide .lp-copy-btn.is-copied { background: #0e639c; border-color: #1177bb; color: #fff; }
        .lp-ide .vsc-upload {
            margin: 8px 10px; border: 1px dashed var(--vsc-border); border-radius: 6px; padding: 12px;
            text-align: center; background: rgba(0,0,0,.12);
        }
        .lp-ide[data-theme="light"] .vsc-upload { background: #fff; }
        .lp-ide .vsc-upload p { margin: 0; font-size: 12px; color: var(--vsc-fg); }
        .lp-ide .vsc-upload .hint { color: var(--vsc-muted); font-size: 11px; margin-top: 2px; }
        .lp-ide .vsc-upload-btn {
            margin-top: 8px; background: var(--vsc-accent); color: #fff; border: 0; border-radius: 3px;
            padding: 5px 10px; font-size: 11px; font-weight: 600; cursor: pointer;
        }
        .lp-ide #media-thumbnails-container {
            margin: 0 10px 8px; max-height: 160px; overflow: auto; display: flex; flex-direction: column; gap: 6px;
        }
        .lp-ide .vsc-main { flex: 1; min-width: 0; display: flex; flex-direction: column; background: var(--vsc-editor); }
        .lp-ide .vsc-tabs {
            display: flex; align-items: stretch; background: var(--vsc-tabbar); border-bottom: 1px solid var(--vsc-border);
            overflow-x: auto; min-height: 35px;
        }
        .lp-ide .tab-btn, .lp-ide .lp-tab-btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; height: 35px;
            border: 0; border-right: 1px solid var(--vsc-border); background: var(--vsc-tab);
            color: var(--vsc-muted); font-size: 12px; cursor: pointer; white-space: nowrap;
        }
        .lp-ide .tab-btn:hover { color: var(--vsc-fg); }
        .lp-ide .tab-btn.is-active {
            background: var(--vsc-tab-active); color: var(--vsc-fg);
            box-shadow: inset 0 -1px 0 var(--vsc-accent);
        }
        .lp-ide .vsc-breadcrumb {
            height: 22px; display: flex; align-items: center; gap: 6px; padding: 0 12px;
            font-size: 12px; color: var(--vsc-muted); border-bottom: 1px solid var(--vsc-border);
            background: var(--vsc-editor);
        }
        .lp-ide .vsc-breadcrumb strong { color: var(--vsc-fg); font-weight: 500; }
        .lp-ide .tab-content { display: none; flex: 1; min-height: 0; flex-direction: column; }
        .lp-ide .tab-content.is-open { display: flex; }
        .lp-ide .vsc-editor-row {
            flex: 1; display: flex; min-height: 0; background: var(--vsc-editor); position: relative;
        }
        .lp-ide .vsc-gutter {
            width: 52px; flex-shrink: 0; background: var(--vsc-gutter); color: var(--vsc-line-num);
            font-family: var(--vsc-font); font-size: 13px; line-height: 20px; text-align: right;
            padding: 12px 8px 12px 0; overflow: hidden; user-select: none; border-right: 1px solid transparent;
        }
        .lp-ide .code-editor-area {
            flex: 1; min-width: 0; width: 100%; height: 100%; min-height: 480px;
            background: var(--vsc-editor) !important; color: var(--vsc-code-fg) !important;
            border: 0 !important; border-radius: 0 !important; outline: none !important; resize: none;
            font-family: var(--vsc-font) !important; font-size: 13px !important; line-height: 20px !important;
            padding: 12px 16px 24px 12px !important; caret-color: var(--vsc-fg) !important;
            tab-size: 2; white-space: pre; overflow: auto; box-shadow: none !important;
        }
        .lp-ide #custom_html.code-editor-area { color: #ce9178 !important; }
        .lp-ide #custom_css.code-editor-area { color: #9cdcfe !important; }
        .lp-ide #custom_js.code-editor-area { color: #dcdcaa !important; }
        .lp-ide[data-theme="light"] #custom_html.code-editor-area { color: #a31515 !important; }
        .lp-ide[data-theme="light"] #custom_css.code-editor-area { color: #0451a5 !important; }
        .lp-ide[data-theme="light"] #custom_js.code-editor-area { color: #795e26 !important; }
        .lp-ide .code-editor-area::selection { background: var(--vsc-selection); }
        .lp-ide .vsc-statusbar {
            height: 22px; background: var(--vsc-status); color: var(--vsc-status-fg);
            display: flex; align-items: center; justify-content: space-between; padding: 0 10px;
            font-size: 11px; flex-shrink: 0;
        }
        .lp-ide .vsc-statusbar .left, .lp-ide .vsc-statusbar .right { display: flex; align-items: center; gap: 12px; }
        .lp-ide .vsc-statusbar button {
            background: transparent; border: 0; color: inherit; font-size: 11px; cursor: pointer; padding: 0;
        }
        .lp-ide .vsc-statusbar button:hover { opacity: .85; }
        @media (max-width: 900px) {
            .lp-ide .vsc-shell { flex-direction: column; height: auto; min-height: 0; }
            .lp-ide .vsc-activity { flex-direction: row; width: 100%; height: 44px; padding: 0 4px; }
            .lp-ide .vsc-act-btn { width: 44px; height: 44px; border-left: 0; border-bottom: 2px solid transparent; }
            .lp-ide .vsc-act-btn.is-active { border-left-color: transparent; border-bottom-color: #fff; }
            .lp-ide .vsc-sidebar { width: 100%; max-height: 280px; border-right: 0; border-bottom: 1px solid var(--vsc-border); }
            .lp-ide .vsc-title-center { display: none; }
        }
    </style>

    <div class="lp-ide" id="lp-design-studio" data-theme="dark">
        {{-- Title bar --}}
        <div class="vsc-titlebar relative">
            <div class="vsc-dots">
                <span class="vsc-dot" style="background:#ff5f56"></span>
                <span class="vsc-dot" style="background:#ffbd2e"></span>
                <span class="vsc-dot" style="background:#27c93f"></span>
            </div>
            <div class="vsc-title-center">Landing Page Design — index.html — Foodness</div>
            <div class="flex items-center gap-2 text-[11px]" style="color:var(--vsc-muted)">
                <button type="button" id="toggle-editor-theme-btn" class="px-2 py-0.5 rounded cursor-pointer" style="background:transparent;border:1px solid var(--vsc-border);color:var(--vsc-fg)">
                    <span id="theme-btn-text">Light</span>
                </button>
            </div>
        </div>

        <div class="vsc-shell">
            {{-- Activity bar --}}
            <nav class="vsc-activity" aria-label="Activity Bar">
                <button type="button" class="vsc-act-btn is-active" data-vsc-panel="explorer" title="Explorer" id="toggle-guide-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 0h-9L7 1.5V6H2.5L1 7.5v15.07L2.5 24h12.07L16 22.57V18h4.7l1.3-1.43V4.5L17.5 0zm0 2.12l2.38 2.38H17.5V2.12zm-3 20.38h-12v-15H7v9.07L8.5 18h6v4.5zm6-6h-12v-15H16V6h4.5v10.5z"/></svg>
                </button>
                <button type="button" class="vsc-act-btn" data-vsc-panel="snippets" title="Snippets">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l-4 3 4 3m8-6l4 3-4 3M14 5l-4 14"/></svg>
                </button>
                <button type="button" class="vsc-act-btn" data-vsc-panel="media" title="Media">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
                <div class="flex-1"></div>
                <button type="button" class="vsc-act-btn" title="Settings" onclick="document.getElementById('toggle-editor-theme-btn').click()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>
            </nav>

            {{-- Sidebar --}}
            <aside class="vsc-sidebar" id="guide-details">
                <div class="vsc-side-head">
                    <span id="vsc-side-title">Explorer</span>
                    <span style="opacity:.7">⋯</span>
                </div>
                <div class="vsc-side-body">
                    <div class="vsc-panel" data-panel="explorer">
                        <div class="vsc-section-label">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                            LANDING-PAGE
                        </div>
                        <button type="button" class="vsc-file is-active tab-btn" data-tab="tab-html" data-file="index.html" data-lang="HTML">
                            <svg class="vsc-file-icon" viewBox="0 0 16 16"><path fill="#e37933" d="M2 2h8l4 4v8a1 1 0 01-1 1H2a1 1 0 01-1-1V3a1 1 0 011-1z"/><path fill="#fff" d="M5.2 11.2l1.1-5.4h1.1l-1.1 5.4H5.2zm3.1 0l1.8-5.4h1.2l-1.8 5.4H8.3z"/></svg>
                            index.html
                        </button>
                        <button type="button" class="vsc-file tab-btn" data-tab="tab-css" data-file="styles.css" data-lang="CSS">
                            <svg class="vsc-file-icon" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7" fill="#563d7c"/><text x="8" y="11" text-anchor="middle" fill="#fff" font-size="7" font-weight="700">#</text></svg>
                            styles.css
                        </button>
                        <button type="button" class="vsc-file tab-btn" data-tab="tab-js" data-file="script.js" data-lang="JavaScript">
                            <svg class="vsc-file-icon" viewBox="0 0 16 16"><rect width="16" height="16" rx="2" fill="#f7df1e"/><text x="8" y="12" text-anchor="middle" fill="#000" font-size="8" font-weight="800">JS</text></svg>
                            script.js
                        </button>
                        <div class="px-3 pt-3 pb-2 text-[11px] space-y-2" style="color:var(--vsc-muted)">
                            <p>Put <code style="color:#4ec9b0">[checkout_form]</code> in HTML where the order form should appear. Tailwind classes work.</p>
                            <div class="rounded-md p-2 space-y-1" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25)">
                                <div class="font-bold" style="color:#4ec9b0">Safe landing rules</div>
                                <p>• HTML = hero/content only; keep <code style="color:#4ec9b0">[checkout_form]</code></p>
                                <p>• Checkout text/color → use <strong>Checkout</strong> tab, not custom CSS</p>
                                <p>• CSS: style your classes (<code>.hero-*</code>), avoid <code>* { padding:0 }</code></p>
                                <p>• Never restyle <code>#checkout-section</code> or form fields</p>
                                <p>• JS: no form hijack / don’t remove checkout inputs</p>
                            </div>
                        </div>
                    </div>

                    <div class="vsc-panel hidden" data-panel="snippets">
                        <div class="vsc-section-label">SNIPPETS</div>
                        <div class="vsc-snippet-block">
                            <div class="text-[11px] mb-1" style="color:var(--vsc-muted)">Checkout (required)</div>
                            <div class="vsc-snip">
                                <code class="green">[checkout_form]</code>
                                <button type="button" onclick="copySnippet('[checkout_form]', this)" class="lp-copy-btn">Copy</button>
                            </div>
                            <div class="vsc-snip">
                                <code>href="#checkout-section"</code>
                                <button type="button" onclick="copySnippet('href=&quot;#checkout-section&quot;', this)" class="lp-copy-btn">Copy</button>
                            </div>
                            <div class="text-[11px] mt-3 mb-1" style="color:var(--vsc-muted)">Images</div>
                            <div class="vsc-snip">
                                <code class="amber">&lt;img src="IMAGE_URL" class="w-full rounded-xl"&gt;</code>
                                <button type="button" onclick="copySnippet('<img src=&quot;IMAGE_URL&quot; alt=&quot;Product&quot; class=&quot;w-full rounded-xl&quot;>', this)" class="lp-copy-btn">Copy</button>
                            </div>
                            <div class="vsc-snip">
                                <code class="green">@{{product_image_url}}</code>
                                <button type="button" onclick="copySnippet('@{{product_image_url}}', this)" class="lp-copy-btn">Copy</button>
                            </div>
                            <div class="text-[11px] mt-3 mb-1" style="color:var(--vsc-muted)">Videos</div>
                            <div class="vsc-snip">
                                <code>&lt;iframe class="w-full aspect-video rounded-xl" src="https://www.youtube.com/embed/VIDEO_ID" allowfullscreen&gt;&lt;/iframe&gt;</code>
                                <button type="button" onclick="copySnippet('<iframe class=&quot;w-full aspect-video rounded-xl&quot; src=&quot;https://www.youtube.com/embed/VIDEO_ID&quot; allowfullscreen frameborder=&quot;0&quot;></iframe>', this)" class="lp-copy-btn">Copy</button>
                            </div>
                            <div class="vsc-snip">
                                <code class="pink">&lt;video autoplay loop muted playsinline class="w-full"&gt;&lt;source src="VIDEO_URL"&gt;&lt;/video&gt;</code>
                                <button type="button" onclick="copySnippet('<video autoplay loop muted playsinline class=&quot;w-full rounded-xl&quot;><source src=&quot;VIDEO_URL&quot; type=&quot;video/mp4&quot;></video>', this)" class="lp-copy-btn">Copy</button>
                            </div>
                        </div>
                    </div>

                    <div class="vsc-panel hidden" data-panel="media">
                        <div class="vsc-section-label">MEDIA</div>
                        <div class="vsc-upload">
                            <input type="file" id="media-file-input" accept="image/*,video/mp4,video/webm" class="hidden" multiple>
                            <p>Upload or pick from Media</p>
                            <p class="hint">JPG, PNG, WEBP, MP4 — multiple allowed</p>
                            <div class="flex flex-wrap gap-2 justify-center mt-2">
                                <button type="button" class="vsc-upload-btn" onclick="document.getElementById('media-file-input').click()">Upload files</button>
                                <button type="button" id="lp-open-media-picker" class="vsc-upload-btn" style="background:transparent;border:1px solid var(--vsc-border);color:var(--vsc-fg);">From Media</button>
                            </div>
                            <span id="upload-status" class="block text-[11px] mt-2" style="color:var(--vsc-muted)"></span>
                        </div>
                        <div class="px-3 text-[11px] font-semibold mb-1" style="color:var(--vsc-muted)">Available assets · Copy = /storage/…</div>
                        <div id="media-thumbnails-container">
                            <span class="text-xs italic px-1" style="color:var(--vsc-muted)">
                                Select a product, upload, or pick from Media Library.
                            </span>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Editor --}}
            <section class="vsc-main" id="lp-code-panel">
                <div class="vsc-tabs" id="editor-tabs">
                    <button type="button" data-tab="tab-html" data-file="index.html" data-lang="HTML" class="tab-btn lp-tab-btn is-active">
                        <svg width="14" height="14" viewBox="0 0 16 16"><path fill="#e37933" d="M2 2h8l4 4v8a1 1 0 01-1 1H2a1 1 0 01-1-1V3a1 1 0 011-1z"/></svg>
                        index.html
                    </button>
                    <button type="button" data-tab="tab-css" data-file="styles.css" data-lang="CSS" class="tab-btn lp-tab-btn">
                        <svg width="14" height="14" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7" fill="#563d7c"/></svg>
                        styles.css
                    </button>
                    <button type="button" data-tab="tab-js" data-file="script.js" data-lang="JavaScript" class="tab-btn lp-tab-btn">
                        <svg width="14" height="14" viewBox="0 0 16 16"><rect width="16" height="16" rx="2" fill="#f7df1e"/></svg>
                        script.js
                    </button>
                </div>

                <div class="vsc-breadcrumb">
                    <span>landing-page</span>
                    <span>›</span>
                    <strong id="lp-editor-file-label">index.html</strong>
                </div>

                <div id="tab-html" class="tab-content is-open">
                    <div class="vsc-editor-row">
                        <div class="vsc-gutter" data-gutter-for="custom_html"></div>
                        <textarea name="custom_html" id="custom_html" rows="24" class="code-editor-area" placeholder="<!-- Enter custom HTML here -->" spellcheck="false" wrap="off">{{ old('custom_html', $landingPage->custom_html ?? ($defaultHtml ?? '')) }}</textarea>
                    </div>
                </div>

                <div id="tab-css" class="tab-content">
                    <div class="vsc-editor-row">
                        <div class="vsc-gutter" data-gutter-for="custom_css"></div>
                        <textarea name="custom_css" id="custom_css" rows="24" class="code-editor-area" placeholder="/* Enter custom CSS styles here */" spellcheck="false" wrap="off">{{ old('custom_css', $landingPage->custom_css ?? ($defaultCss ?? '')) }}</textarea>
                    </div>
                </div>

                <div id="tab-js" class="tab-content">
                    <div class="vsc-editor-row">
                        <div class="vsc-gutter" data-gutter-for="custom_js"></div>
                        <textarea name="custom_js" id="custom_js" rows="24" class="code-editor-area" placeholder="// Enter custom JavaScript code here" spellcheck="false" wrap="off">{{ old('custom_js', $landingPage->custom_js ?? '') }}</textarea>
                    </div>
                </div>

                <div class="vsc-statusbar">
                    <div class="left">
                        <span>⎇ main</span>
                        <span id="lp-editor-lang">HTML</span>
                        <span id="lp-editor-chars">0 chars</span>
                    </div>
                    <div class="right">
                        <span>Ln <span id="lp-editor-line">1</span>, Col <span id="lp-editor-col">1</span></span>
                        <span>Spaces: 2</span>
                        <span>UTF-8</span>
                        <span>Tailwind</span>
                    </div>
                </div>
            </section>
        </div>
    </div>

</div>

<!-- Inline Scripts for Editor Tabs, Image Upload & Product Change -->
<script>
// Global Snippet Copy Function with visual feedback
function copySnippet(text, btn) {
    navigator.clipboard.writeText(text);
    if (!btn) return;
    const originalHtml = btn.innerHTML;
    btn.classList.add('is-copied');
    btn.innerHTML = 'Copied';
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.classList.remove('is-copied');
    }, 1400);
}

// Checkout Color Picker Sync
function setCheckoutColor(hex) {
    const colorPicker = document.getElementById('checkout_color');
    const colorHex = document.getElementById('checkout_color_hex');
    const preview = document.getElementById('checkout-color-preview');
    if (colorPicker) colorPicker.value = hex;
    if (colorHex) colorHex.value = hex.toUpperCase();
    if (preview) preview.style.backgroundColor = hex;
}

document.addEventListener('DOMContentLoaded', function() {
    // Compact settings panels
    const lpNav = document.getElementById('lp-form-nav');
    const lpPanels = document.querySelectorAll('.lp-form .lp-panel');
    function openLpPanel(key) {
        if (!key) return;
        document.querySelectorAll('.lp-form .lp-nav-btn').forEach(btn => {
            btn.classList.toggle('is-active', btn.getAttribute('data-lp-panel') === key);
        });
        lpPanels.forEach(panel => {
            panel.classList.toggle('is-open', panel.getAttribute('data-lp-panel') === key);
        });
    }
    if (lpNav) {
        lpNav.addEventListener('click', function(e) {
            const btn = e.target.closest('.lp-nav-btn');
            if (!btn) return;
            openLpPanel(btn.getAttribute('data-lp-panel'));
        });
    }
    document.querySelectorAll('.lp-form input, .lp-form select, .lp-form textarea').forEach(el => {
        el.addEventListener('invalid', function() {
            const panel = el.closest('.lp-panel');
            if (panel) openLpPanel(panel.getAttribute('data-lp-panel'));
        });
    });

    // 0.0 Checkout Color Picker Event Listeners
    const colorPickerInput = document.getElementById('checkout_color');
    const colorHexInput = document.getElementById('checkout_color_hex');
    const colorPreviewBox = document.getElementById('checkout-color-preview');
    if (colorPickerInput && colorHexInput) {
        colorPickerInput.addEventListener('input', function() {
            colorHexInput.value = this.value.toUpperCase();
            if (colorPreviewBox) colorPreviewBox.style.backgroundColor = this.value;
        });
        colorHexInput.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                colorPickerInput.value = this.value;
                if (colorPreviewBox) colorPreviewBox.style.backgroundColor = this.value;
            }
        });
    }

    const buttonTextInput = document.getElementById('checkout_button_text');
    if (buttonTextInput && colorPreviewBox) {
        buttonTextInput.addEventListener('input', function() {
            colorPreviewBox.textContent = this.value || 'Confirm Order';
        });
    }

    // 0. VS Code Theme Switcher
    const studioEl = document.getElementById('lp-design-studio');
    const toggleThemeBtn = document.getElementById('toggle-editor-theme-btn');
    const themeBtnText = document.getElementById('theme-btn-text');
    let currentEditorTheme = localStorage.getItem('landing_page_editor_theme') || 'dark';

    function setEditorTheme(theme) {
        if (!studioEl) return;
        studioEl.setAttribute('data-theme', theme);
        if (themeBtnText) themeBtnText.textContent = theme === 'dark' ? 'Light' : 'Dark';
        localStorage.setItem('landing_page_editor_theme', theme);
    }

    if (toggleThemeBtn) {
        toggleThemeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentEditorTheme = currentEditorTheme === 'dark' ? 'light' : 'dark';
            setEditorTheme(currentEditorTheme);
        });
        setEditorTheme(currentEditorTheme);
    }

    // Activity bar panels
    const actButtons = studioEl ? studioEl.querySelectorAll('.vsc-act-btn[data-vsc-panel]') : [];
    const sidePanels = studioEl ? studioEl.querySelectorAll('.vsc-panel') : [];
    const sideTitle = document.getElementById('vsc-side-title');
    const guideDetails = document.getElementById('guide-details');
    const panelTitles = { explorer: 'Explorer', snippets: 'Snippets', media: 'Media' };

    actButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const panel = this.getAttribute('data-vsc-panel');
            const already = this.classList.contains('is-active') && guideDetails && !guideDetails.classList.contains('is-collapsed');

            if (already && panel === 'explorer') {
                // Double-click explorer collapses sidebar like VS Code
                guideDetails.classList.toggle('is-collapsed');
                return;
            }

            actButtons.forEach(b => b.classList.remove('is-active'));
            this.classList.add('is-active');
            if (guideDetails) guideDetails.classList.remove('is-collapsed');
            sidePanels.forEach(p => {
                if (p.getAttribute('data-panel') === panel) p.classList.remove('hidden');
                else p.classList.add('hidden');
            });
            if (sideTitle) sideTitle.textContent = panelTitles[panel] || 'Explorer';
        });
    });

    // 1. Tab Switching + line numbers + status
    const tabButtons = studioEl ? studioEl.querySelectorAll('.tab-btn') : [];
    const tabContents = studioEl ? studioEl.querySelectorAll('.tab-content') : [];
    const fileLabel = document.getElementById('lp-editor-file-label');
    const langLabel = document.getElementById('lp-editor-lang');
    const charsLabel = document.getElementById('lp-editor-chars');
    const lineLabel = document.getElementById('lp-editor-line');
    const colLabel = document.getElementById('lp-editor-col');

    function activeEditorArea() {
        const open = studioEl ? studioEl.querySelector('.tab-content.is-open .code-editor-area') : null;
        return open || document.getElementById('custom_html');
    }

    function updateGutter(textarea) {
        if (!textarea) return;
        const gutter = studioEl.querySelector('[data-gutter-for="' + textarea.id + '"]');
        if (!gutter) return;
        const lines = Math.max(1, (textarea.value || '').split('\n').length);
        let html = '';
        for (let i = 1; i <= lines; i++) html += i + '\n';
        gutter.textContent = html;
        gutter.scrollTop = textarea.scrollTop;
    }

    function updateEditorStats() {
        const area = activeEditorArea();
        if (!area) return;
        if (charsLabel) charsLabel.textContent = ((area.value || '').length).toLocaleString() + ' chars';
        updateGutter(area);
        updateCursorPos(area);
    }

    function updateCursorPos(area) {
        if (!area || (!lineLabel && !colLabel)) return;
        const pos = area.selectionStart || 0;
        const before = (area.value || '').slice(0, pos);
        const parts = before.split('\n');
        if (lineLabel) lineLabel.textContent = String(parts.length);
        if (colLabel) colLabel.textContent = String((parts[parts.length - 1] || '').length + 1);
    }

    function activateTab(targetId, fileName, langName) {
        tabButtons.forEach(b => {
            const on = b.getAttribute('data-tab') === targetId;
            b.classList.toggle('is-active', on);
        });
        tabContents.forEach(content => {
            content.classList.toggle('is-open', content.id === targetId);
        });
        if (fileLabel) fileLabel.textContent = fileName || 'index.html';
        if (langLabel) langLabel.textContent = langName || 'HTML';
        const titleCenter = studioEl.querySelector('.vsc-title-center');
        if (titleCenter) titleCenter.textContent = 'Landing Page Design — ' + (fileName || 'index.html') + ' — Foodness';
        updateEditorStats();
        const area = activeEditorArea();
        if (area) area.focus({ preventScroll: true });
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            activateTab(this.dataset.tab, this.dataset.file, this.dataset.lang);
        });
    });

    document.querySelectorAll('.code-editor-area').forEach(area => {
        area.addEventListener('input', function() {
            updateEditorStats();
        });
        area.addEventListener('scroll', function() {
            const gutter = studioEl.querySelector('[data-gutter-for="' + area.id + '"]');
            if (gutter) gutter.scrollTop = area.scrollTop;
        });
        area.addEventListener('keyup', function() { updateCursorPos(area); });
        area.addEventListener('click', function() { updateCursorPos(area); });
        area.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.slice(0, start) + '  ' + this.value.slice(end);
                this.selectionStart = this.selectionEnd = start + 2;
                updateEditorStats();
                return;
            }

            // Ctrl+/ or Cmd+/ (also Ctrl+Shift+/ → '?') toggles comments
            if ((e.ctrlKey || e.metaKey) && (e.key === '/' || e.key === '?' || e.code === 'Slash')) {
                e.preventDefault();
                toggleEditorComments(this);
            }
        });
        updateGutter(area);
    });
    updateEditorStats();

    function toggleEditorComments(textarea) {
        if (!textarea) return;
        const value = textarea.value;
        const selStart = textarea.selectionStart;
        const selEnd = textarea.selectionEnd;

        const lineStart = value.lastIndexOf('\n', selStart - 1) + 1;
        let lineEnd = value.indexOf('\n', selEnd);
        if (lineEnd === -1) lineEnd = value.length;
        if (selEnd > selStart && value[selEnd - 1] === '\n') {
            lineEnd = selEnd - 1;
        }

        const block = value.slice(lineStart, lineEnd);
        const lines = block.split('\n');
        const id = textarea.id;
        let nextLines;
        let deltaPerLine = 0;

        if (id === 'custom_html') {
            const allCommented = lines.every(l => /^\s*<!--/.test(l) && /-->\s*$/.test(l));
            if (allCommented) {
                nextLines = lines.map(l => l.replace(/^(\s*)<!--\s?/, '$1').replace(/\s?-->\s*$/, ''));
                deltaPerLine = -9;
            } else {
                nextLines = lines.map(l => {
                    if (l.trim() === '') return l;
                    return l.replace(/^(\s*)(.*)$/, '$1<!-- $2 -->');
                });
                deltaPerLine = 9;
            }
        } else if (id === 'custom_css') {
            const allCommented = lines.every(l => /^\s*\/\*/.test(l) && /\*\/\s*$/.test(l));
            if (allCommented) {
                nextLines = lines.map(l => l.replace(/^(\s*)\/\*\s?/, '$1').replace(/\s?\*\/\s*$/, ''));
                deltaPerLine = -5;
            } else {
                nextLines = lines.map(l => {
                    if (l.trim() === '') return l;
                    return l.replace(/^(\s*)(.*)$/, '$1/* $2 */');
                });
                deltaPerLine = 5;
            }
        } else {
            const allCommented = lines.every(l => /^\s*\/\//.test(l));
            if (allCommented) {
                nextLines = lines.map(l => l.replace(/^(\s*)\/\/\s?/, '$1'));
                deltaPerLine = -3;
            } else {
                nextLines = lines.map(l => {
                    if (l.trim() === '') return l;
                    return l.replace(/^(\s*)/, '$1// ');
                });
                deltaPerLine = 3;
            }
        }

        const nextBlock = nextLines.join('\n');
        textarea.value = value.slice(0, lineStart) + nextBlock + value.slice(lineEnd);

        const nonEmpty = lines.filter(l => l.trim() !== '').length || lines.length;
        const totalDelta = deltaPerLine * nonEmpty;
        const newStart = selStart + (selStart === lineStart ? 0 : Math.sign(deltaPerLine) * Math.min(Math.abs(deltaPerLine), Math.abs(totalDelta)));
        const newEnd = selEnd + totalDelta;
        textarea.selectionStart = Math.max(lineStart, Math.min(newStart, lineStart + nextBlock.length));
        textarea.selectionEnd = Math.max(textarea.selectionStart, Math.min(newEnd, lineStart + nextBlock.length));
        textarea.dispatchEvent(new Event('input'));
        updateEditorStats();
        updateCursorPos(textarea);
    }

    // 3. Delivery Charge Type Toggle
    const deliveryRadios = document.querySelectorAll('input[name="delivery_charge_type"]');
    const customDeliveryFields = document.getElementById('custom-delivery-fields');
    deliveryRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDeliveryFields.classList.remove('hidden');
            } else {
                customDeliveryFields.classList.add('hidden');
            }
        });
    });

    // 4. Auto-generate slug from title if slug is empty
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function() {
            if (!slugInput.dataset.manual) {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .trim()
                    .replace(/\s+/g, '-');
            }
        });
        slugInput.addEventListener('input', function() {
            this.dataset.manual = 'true';
        });
    }

    // 5. Media Upload / Media Library
    const mediaFileInput = document.getElementById('media-file-input');
    const uploadStatus = document.getElementById('upload-status');
    const thumbsContainer = document.getElementById('media-thumbnails-container');
    const openMediaPickerBtn = document.getElementById('lp-open-media-picker');

    function toStorageCopyPath(pathOrUrl) {
        if (!pathOrUrl) return '';
        let value = String(pathOrUrl).trim();
        const storageIdx = value.indexOf('/storage/');
        if (storageIdx !== -1) {
            return value.slice(storageIdx); // /storage/...
        }
        if (value.startsWith('/storage/')) {
            return value;
        }
        if (value.startsWith('storage/')) {
            return '/' + value;
        }
        // Disk-relative path from API (landing-pages/x.jpg or media/x.jpg)
        if (!/^https?:\/\//i.test(value)) {
            return '/storage/' + value.replace(/^\/+/, '');
        }
        return value;
    }

    async function uploadLandingMediaFile(file) {
        const formData = new FormData();
        formData.append('media', file);
        formData.append('_token', '{{ csrf_token() }}');

        const res = await fetch('{{ route("admin.landing-pages.upload-media") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (!data.ok) {
            throw new Error(data.message || 'Upload failed');
        }
        return data;
    }

    if (mediaFileInput) {
        mediaFileInput.addEventListener('change', async function() {
            const files = Array.from(this.files || []);
            if (!files.length) return;

            let okCount = 0;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                uploadStatus.textContent = `Uploading ${i + 1}/${files.length}: ${file.name}...`;
                uploadStatus.className = 'block text-[11px] text-emerald-600 font-bold';
                try {
                    const data = await uploadLandingMediaFile(file);
                    addThumbnail(data.url, data.name, data.path);
                    okCount++;
                } catch (err) {
                    uploadStatus.textContent = 'Upload error: ' + (err.message || 'Unknown error');
                    uploadStatus.className = 'block text-[11px] text-red-600 font-bold';
                }
            }

            if (okCount > 0) {
                uploadStatus.textContent = okCount === files.length
                    ? `Uploaded ${okCount} file(s). Copy uses /storage/… path.`
                    : `Uploaded ${okCount}/${files.length} file(s).`;
                uploadStatus.className = 'block text-[11px] text-emerald-600 font-bold';
            }
            this.value = '';
        });
    }

    if (openMediaPickerBtn) {
        openMediaPickerBtn.addEventListener('click', function() {
            if (typeof window.openMediaPicker !== 'function') {
                alert('Media picker is not available on this page.');
                return;
            }
            window.openMediaPicker({
                multiple: true,
                title: 'Select media for landing page',
                buttonText: 'Add selected',
                onSelect: function(selected) {
                    const items = Array.isArray(selected) ? selected : (selected ? [selected] : []);
                    items.forEach(item => {
                        addThumbnail(item.url, item.name || item.path, item.path);
                    });
                    if (uploadStatus && items.length) {
                        uploadStatus.textContent = `Added ${items.length} from Media Library.`;
                        uploadStatus.className = 'block text-[11px] text-emerald-600 font-bold';
                    }
                }
            });
        });
    }

    function addThumbnail(url, label, path) {
        if (!thumbsContainer) return;
        const emptyHint = thumbsContainer.querySelector('span.italic');
        if (emptyHint) emptyHint.remove();

        const copyPath = toStorageCopyPath(path || url);
        const previewUrl = url || ('/' + copyPath.replace(/^\/+/, ''));
        const safeLabel = (label || copyPath || 'Image').replace(/"/g, '&quot;');

        const item = document.createElement('div');
        item.className = 'flex items-center gap-2 p-1.5 rounded text-xs';
        item.style.cssText = 'background:var(--vsc-hover);border:1px solid var(--vsc-border);';
        item.innerHTML = `
            <img src="${previewUrl}" class="w-8 h-8 object-cover rounded shrink-0" style="background:#111;border:1px solid var(--vsc-border)" onerror="this.src='/images/placeholder.png'">
            <div class="truncate flex-1 min-w-0">
                <div class="text-[11px]" style="color:var(--vsc-fg)" title="${safeLabel}">${safeLabel}</div>
                <div class="text-[10px] truncate" style="color:var(--vsc-muted)" title="${copyPath.replace(/"/g, '&quot;')}">${copyPath}</div>
            </div>
            <button type="button" class="lp-copy-btn shrink-0" title="Copy storage path">Copy</button>
        `;
        const copyBtn = item.querySelector('button');
        copyBtn.addEventListener('click', function() {
            navigator.clipboard.writeText(copyPath);
            copyBtn.classList.add('is-copied');
            const prev = copyBtn.textContent;
            copyBtn.textContent = 'Copied';
            setTimeout(() => {
                copyBtn.textContent = prev;
                copyBtn.classList.remove('is-copied');
            }, 1200);
        });

        thumbsContainer.insertBefore(item, thumbsContainer.firstChild);
    }

    // 6. Products Repeater Logic & Dynamic Image Fetching
    const repeaterContainer = document.getElementById('products-repeater-container');
    const addRowBtn = document.getElementById('add-product-row-btn');
    const rowTemplate = document.getElementById('product-row-template');
    let nextRowIndex = {{ count($assignedProducts) }};

    function loadProductImages(productId) {
        if (!productId || !thumbsContainer) return;
        fetch(`/admin/landing-pages/product-images/${productId}`)
            .then(res => res.json())
            .then(data => {
                if (data.ok && data.images && data.images.length > 0) {
                    thumbsContainer.innerHTML = '';
                    data.images.forEach(img => {
                        addThumbnail(img.url, img.label, img.path);
                    });
                }
            })
            .catch(() => {});
    }

    function attachRowEvents(row) {
        // Remove button
        const removeBtn = row.querySelector('.remove-row-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                const totalRows = repeaterContainer.querySelectorAll('.product-repeater-row').length;
                if (totalRows <= 1) {
                    alert('At least one product must remain assigned to this page.');
                    return;
                }
                const wasDefault = row.querySelector('.default-checkbox')?.checked;
                row.remove();
                if (wasDefault) {
                    const firstRowCheckbox = repeaterContainer.querySelector('.default-checkbox');
                    if (firstRowCheckbox) firstRowCheckbox.checked = true;
                }
            });
        }

        // Default checkbox exclusivity
        const defaultCheckbox = row.querySelector('.default-checkbox');
        if (defaultCheckbox) {
            defaultCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    repeaterContainer.querySelectorAll('.default-checkbox').forEach(cb => {
                        if (cb !== defaultCheckbox) cb.checked = false;
                    });
                }
            });
        }

        // Product select change -> load images into media box
        const select = row.querySelector('.product-select');
        if (select) {
            select.addEventListener('change', function() {
                if (this.value) {
                    loadProductImages(this.value);
                }
            });
        }
    }

    if (addRowBtn && rowTemplate && repeaterContainer) {
        addRowBtn.addEventListener('click', function() {
            const html = rowTemplate.innerHTML.replace(/__INDEX__/g, nextRowIndex++);
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html.trim();
            const newRow = tempDiv.firstElementChild;
            repeaterContainer.appendChild(newRow);
            attachRowEvents(newRow);
        });

        // Attach events to existing rows
        repeaterContainer.querySelectorAll('.product-repeater-row').forEach(attachRowEvents);
    }

    // Load initial images for the first/default selected product
    const initialSelected = repeaterContainer?.querySelector('.product-select');
    if (initialSelected && initialSelected.value) {
        loadProductImages(initialSelected.value);
    }

    // ----------------------------------------------------
    // CHECKOUT FIELDS BUILDER (Drag & Drop Reordering & Add/Remove Custom Fields)
    // ----------------------------------------------------
    const fieldsList = document.getElementById('checkout-fields-sortable-list');
    const addCustomFieldBtn = document.getElementById('btn-add-custom-field');
    let customFieldCounter = {{ count($resolvedBuilderFields) }};

    function reindexFieldRows() {
        if (!fieldsList) return;
        const rows = fieldsList.querySelectorAll('.field-item');
        rows.forEach((row, idx) => {
            row.setAttribute('data-field-index', idx);
            row.querySelectorAll('input, select').forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('fields_builder')) {
                    const newName = name.replace(/fields_builder\[\d+\]/, `fields_builder[${idx}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
    }

    function initFieldDragAndDrop(item) {
        item.addEventListener('dragstart', (e) => {
            item.classList.add('opacity-40', 'border-emerald-500', 'bg-emerald-50/20');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', item.getAttribute('data-field-index'));
            window.draggedFieldItem = item;
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('opacity-40', 'border-emerald-500', 'bg-emerald-50/20');
            window.draggedFieldItem = null;
            reindexFieldRows();
        });

        item.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const dragged = window.draggedFieldItem;
            if (!dragged || dragged === item) return;

            const rect = item.getBoundingClientRect();
            const mid = rect.top + rect.height / 2;
            if (e.clientY < mid) {
                item.parentNode.insertBefore(dragged, item);
            } else {
                item.parentNode.insertBefore(dragged, item.nextSibling);
            }
        });

        // Up & Down Arrow Controls
        const upBtn = item.querySelector('.btn-move-field-up');
        const downBtn = item.querySelector('.btn-move-field-down');
        const removeBtn = item.querySelector('.btn-remove-custom-field');

        if (upBtn) {
            upBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const prev = item.previousElementSibling;
                if (prev) {
                    item.parentNode.insertBefore(item, prev);
                    reindexFieldRows();
                }
            });
        }

        if (downBtn) {
            downBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const next = item.nextElementSibling;
                if (next) {
                    item.parentNode.insertBefore(next, item);
                    reindexFieldRows();
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Remove this custom field?')) {
                    item.remove();
                    reindexFieldRows();
                }
            });
        }
    }

    if (fieldsList) {
        fieldsList.querySelectorAll('.field-item').forEach(initFieldDragAndDrop);
    }

    if (addCustomFieldBtn && fieldsList) {
        addCustomFieldBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const idx = customFieldCounter++;
            const randKey = 'cf_' + Math.random().toString(36).substring(2, 8);
            
            const fieldHtml = `
                <div class="field-item" draggable="true" data-field-index="${idx}" style="border-color:#6ee7b7;background:#ecfdf5;">
                    <span class="drag-handle text-slate-400 font-mono text-sm px-0.5 select-none">⋮⋮</span>
                    <input type="hidden" name="checkout_fields_config[fields_builder][${idx}][key]" value="${randKey}" class="f-key">
                    <input type="hidden" name="checkout_fields_config[fields_builder][${idx}][is_system]" value="0" class="f-is-system">
                    <div class="flex-1 min-w-0">
                        <input type="text" name="checkout_fields_config[fields_builder][${idx}][label]" value="Custom Field Label" class="field lp-field !py-1 font-semibold" placeholder="Field Label">
                    </div>
                    <div class="flex-1 min-w-0 hidden sm:block">
                        <input type="text" name="checkout_fields_config[fields_builder][${idx}][placeholder]" value="" class="field lp-field !py-1" placeholder="Placeholder">
                    </div>
                    <div class="w-20 shrink-0">
                        <select name="checkout_fields_config[fields_builder][${idx}][type]" class="field lp-field !py-1">
                            <option value="text" selected>Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="tel">Phone</option>
                            <option value="email">Email</option>
                            <option value="number">Number</option>
                        </select>
                    </div>
                    
                    <label class="flex items-center gap-1 text-[11px] font-semibold text-slate-700 shrink-0 px-1 cursor-pointer select-none">
                        <input type="checkbox" name="checkout_fields_config[fields_builder][${idx}][required]" value="1" class="w-3.5 h-3.5 rounded text-emerald-600">
                        <span>Req</span>
                    </label>
                    
                    <div class="flex items-center gap-0.5 shrink-0">
                        <button type="button" class="btn-move-field-up p-1 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100" title="Move Up">▲</button>
                        <button type="button" class="btn-move-field-down p-1 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100" title="Move Down">▼</button>
                    </div>
                    
                    <button type="button" class="btn-remove-custom-field text-red-500 hover:text-red-700 hover:bg-red-50 p-1 rounded font-bold text-xs shrink-0" title="Remove Field">✕</button>
                </div>
            `;

            const wrapper = document.createElement('div');
            wrapper.innerHTML = fieldHtml.trim();
            const newElem = wrapper.firstElementChild;
            fieldsList.appendChild(newElem);
            initFieldDragAndDrop(newElem);
            reindexFieldRows();
        });
    }
});
</script>
