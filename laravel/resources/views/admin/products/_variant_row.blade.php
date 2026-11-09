@php
    $asTemplate = $asTemplate ?? false;
    $indexKey = $asTemplate ? '__INDEX__' : $index;
    $attributeDefs = $attributeDefs ?? [];

    $colorName = null;
    if (! empty($row['color_id'])) {
        $colorName = collect($colors ?? [])->firstWhere('id', (int) $row['color_id'])?->name;
    }
    $sizeCatalogName = null;
    if (! empty($row['size_id'])) {
        $sizeCatalogName = collect($sizes ?? [])->firstWhere('id', (int) $row['size_id'])?->name;
    }
    $sizeLabel = $sizeCatalogName ?: (filled($row['size_name'] ?? null) ? $row['size_name'] : null);
    $colorLabel = $colorName ?: null;

    $rawAttrs = $row['attribute_values'] ?? null;
    if (is_string($rawAttrs)) {
        $rawAttrs = json_decode($rawAttrs, true);
    }
    $rowAttrs = [];
    if (is_array($rawAttrs) && $rawAttrs !== []) {
        $rowAttrs = $rawAttrs;
    } else {
        if ($colorLabel) {
            $rowAttrs['Color'] = $colorLabel;
        }
        if ($sizeLabel) {
            $rowAttrs['Size'] = $sizeLabel;
        }
    }

    $nameSummary = filled($row['name'] ?? null) ? $row['name'] : null;
    if (! $nameSummary && $rowAttrs !== []) {
        $nameSummary = implode(' / ', array_values($rowAttrs));
    }

    $summary = $nameSummary ?: ($sizeLabel ?: ($colorLabel ?: (filled($row['size_name'] ?? null) ? $row['size_name'] : 'Custom variation')));
    $salePrice = $row['price'] ?? null;
    $regularPrice = $row['compare_at_price'] ?? null;
    if (filled($salePrice)) {
        $priceLabel = number_format((float) $salePrice, 2).' ৳';
        if (filled($regularPrice) && (float) $regularPrice > (float) $salePrice) {
            $priceLabel = number_format((float) $salePrice, 2).' ৳ (was '.number_format((float) $regularPrice, 2).')';
        }
    } elseif (filled($regularPrice)) {
        $priceLabel = number_format((float) $regularPrice, 2).' ৳';
    } else {
        $priceLabel = 'No price set';
    }
    $stockVal = (int) ($row['stock'] ?? 0);
    $isUnlimitedStock = filter_var($row['is_unlimited_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $hasStock = filled($row['stock'] ?? null) || $isUnlimitedStock;
    if ($isUnlimitedStock) {
        $stockLabel = 'Unlimited (∞)';
        $stockBadgeClass = 'text-emerald-700 font-semibold';
    } elseif (! $hasStock) {
        $stockLabel = 'Stock not set';
        $stockBadgeClass = 'text-muted';
    } elseif ($stockVal === 0) {
        $stockLabel = 'Out of stock (0)';
        $stockBadgeClass = 'text-accent font-semibold';
    } elseif ($stockVal <= 5) {
        $stockLabel = $stockVal.' in stock (Low)';
        $stockBadgeClass = 'text-amber-600 font-semibold';
    } else {
        $stockLabel = $stockVal.' in stock';
        $stockBadgeClass = 'text-emerald-700 font-medium';
    }
    $hasImage = filled($row['image'] ?? null);
@endphp

<div class="wc-variant" data-variant-row>
    <input type="hidden" name="variants[{{ $indexKey }}][id]" value="{{ $row['id'] ?? '' }}">
    <input type="hidden" name="variants[{{ $indexKey }}][attribute_values]" value="{{ is_array($row['attribute_values'] ?? null) ? json_encode($row['attribute_values']) : ($row['attribute_values'] ?? '') }}" data-field-attribute-values>
    <input type="hidden" name="variants[{{ $indexKey }}][color_id]" value="{{ $row['color_id'] ?? '' }}" data-field-color-id>
    <input type="hidden" name="variants[{{ $indexKey }}][size_id]" value="{{ $row['size_id'] ?? '' }}" data-field-size-id>

    <div class="wc-variant__header" data-variant-toggle role="button" tabindex="0" aria-expanded="false">
        <span class="wc-variant__chevron pointer-events-none" aria-hidden="true">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </span>

        <div class="wc-variant__thumb" data-variant-thumb-wrap>
            @if ($hasImage)
                <img src="{{ asset('storage/'.$row['image']) }}" alt="" data-variant-thumb>
            @else
                <span data-variant-thumb-empty>
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
                    </svg>
                </span>
            @endif
        </div>

        <div class="wc-variant__meta">
            <div class="wc-variant__title font-semibold text-ink" data-variant-summary>{{ $summary }}</div>
            <div class="wc-variant__sub">
                <span data-variant-summary-price>{{ $priceLabel }}</span>
                <span class="wc-variant__dot">·</span>
                <span data-variant-summary-stock class="{{ $stockBadgeClass }}">{{ $stockLabel }}</span>
                <span class="wc-variant__dot {{ empty($row['sku']) ? 'hidden' : '' }}" data-variant-summary-sku-dot>·</span>
                <span @class(['hidden' => empty($row['sku'])]) data-variant-summary-sku>
                    @if (! empty($row['sku'])) SKU: {{ $row['sku'] }} @endif
                </span>
            </div>
        </div>

        <button type="button" class="wc-variant__remove" data-remove-variant title="Remove">
            Remove
        </button>
    </div>

    <div class="wc-variant__body" data-variant-body hidden>
        <div class="wc-variant__grid">
            <div class="wc-variant__image-col" data-image-slot data-slot="1">
                <div class="wc-variant__image-box cursor-pointer" role="button" tabindex="0" data-variant-set-image title="Click to choose variant image">
                    <input
                        type="file"
                        name="variants[{{ $indexKey }}][image]"
                        accept="image/*"
                        class="wc-variant__file sr-only"
                        data-slot-file
                        data-primary-slot
                    >
                    <img
                        src="{{ $hasImage ? asset('storage/'.$row['image']) : '' }}"
                        alt=""
                        class="wc-variant__image-preview {{ $hasImage ? '' : 'hidden' }}"
                        data-slot-img
                    >
                    <span class="wc-variant__image-placeholder {{ $hasImage ? 'hidden' : '' }}" data-slot-placeholder>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Image</span>
                    </span>
                </div>
                <input type="hidden" name="variants[{{ $indexKey }}][media_image]" value="" data-variant-media-input>
                <input type="hidden" name="variants[{{ $indexKey }}][remove_image]" value="0" data-remove-slot>

                <button type="button" class="wc-variant__image-clear {{ $hasImage ? '' : 'hidden' }} mt-1 text-red-600 hover:text-red-700 hover:underline text-[11px] font-medium block w-full text-center" data-clear-slot>
                    Remove image
                </button>
                <p class="wc-variant__image-hint mt-1 text-center">800×1067 · 2MB</p>
            </div>

            <div class="wc-variant__fields">
                <div class="mb-3">
                    <label class="admin-label">Variation Name / Attributes</label>
                    <input
                        type="text"
                        name="variants[{{ $indexKey }}][name]"
                        value="{{ $row['name'] ?? $nameSummary ?? '' }}"
                        class="field font-medium"
                        placeholder="e.g. 128GB / 8GB / Space Grey or Red / XL"
                        data-field-name
                    >
                </div>

                <div class="mb-3" data-variant-attributes-section>
                    <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3" data-variant-attributes-grid>
                        @foreach ($rowAttrs as $attrName => $attrValue)
                            <div data-attr-field-col="{{ $attrName }}">
                                <label class="admin-label">{{ $attrName }}</label>
                                @php
                                    $availableValues = $attributeDefs[$attrName]['values'] ?? [];
                                @endphp
                                <select class="field font-medium" data-attr-select="{{ $attrName }}">
                                    <option value="">Select {{ $attrName }}</option>
                                    @foreach ($availableValues as $v)
                                        <option value="{{ $v['name'] }}" @selected($attrValue === $v['name'])>{{ $v['name'] }}</option>
                                    @endforeach
                                    @if (! collect($availableValues)->contains('name', $attrValue) && filled($attrValue))
                                        <option value="{{ $attrValue }}" selected>{{ $attrValue }}</option>
                                    @endif
                                </select>
                            </div>
                        @endforeach

                        <div>
                            <label class="admin-label">Custom note <span class="font-normal text-muted">(optional)</span></label>
                            <input
                                type="text"
                                name="variants[{{ $indexKey }}][size_name]"
                                value="{{ $row['size_name'] ?? '' }}"
                                class="field"
                                placeholder="e.g. Length 28&quot; / warranty note"
                                data-field-size-name
                            >
                        </div>
                    </div>
                </div>

                <div class="wc-variant__field-row wc-variant__field-row--2">
                    <div>
                        <label class="admin-label">SKU</label>
                        <input
                            type="text"
                            name="variants[{{ $indexKey }}][sku]"
                            value="{{ $row['sku'] ?? '' }}"
                            class="field"
                            placeholder="Optional"
                            data-field-sku
                        >
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="admin-label">Stock quantity</label>
                            <label class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-800 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    name="variants[{{ $indexKey }}][is_unlimited_stock]"
                                    value="1"
                                    class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 scale-90"
                                    data-field-unlimited-stock
                                    {{ $isUnlimitedStock ? 'checked' : '' }}
                                >
                                <span>Unlimited</span>
                            </label>
                        </div>
                        <input
                            type="number"
                            min="0"
                            name="variants[{{ $indexKey }}][stock]"
                            value="{{ $row['stock'] ?? '' }}"
                            class="field {{ $isUnlimitedStock ? 'opacity-50 bg-slate-50' : '' }}"
                            placeholder="{{ $isUnlimitedStock ? 'Unlimited (∞)' : '0' }}"
                            data-field-stock
                            {{ $isUnlimitedStock ? 'readonly' : '' }}
                        >
                    </div>
                </div>

                <div class="wc-variant__field-row wc-variant__field-row--2">
                    <div>
                        <label class="admin-label">Regular price (৳)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="variants[{{ $indexKey }}][compare_at_price]"
                            value="{{ $row['compare_at_price'] ?? '' }}"
                            class="field"
                            placeholder="Optional MRP"
                            data-field-compare-price
                        >
                        <p class="mt-1 text-[10px] text-muted">Original / MRP — crossed out when on sale.</p>
                    </div>
                    <div>
                        <label class="admin-label">Sale price (৳)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="variants[{{ $indexKey }}][price]"
                            value="{{ $row['price'] ?? '' }}"
                            class="field"
                            placeholder="0.00"
                            data-field-price
                        >
                        <p class="mt-1 text-[10px] text-muted">Price customers pay for this variation.</p>
                    </div>
                </div>

                {{-- Variation Delivery Charge --}}
                <div class="mt-3 pt-3 border-t border-ink/10">
                    <label class="admin-label text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                        <span>🚚</span> <span>Delivery Charge for this Variation</span>
                    </label>
                    @php
                        $varDelType = $row['delivery_charge_type'] ?? 'default';
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer bg-white hover:border-emerald-500">
                            <input type="radio" name="variants[{{ $indexKey }}][delivery_charge_type]" value="default" class="text-emerald-600 var-del-radio" {{ $varDelType === 'default' ? 'checked' : '' }} onchange="this.closest('.wc-variant').querySelector('.var-custom-del-row')?.classList.add('hidden')">
                            <span>Default</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer bg-white hover:border-emerald-500">
                            <input type="radio" name="variants[{{ $indexKey }}][delivery_charge_type]" value="free" class="text-emerald-600 var-del-radio" {{ $varDelType === 'free' ? 'checked' : '' }} onchange="this.closest('.wc-variant').querySelector('.var-custom-del-row')?.classList.add('hidden')">
                            <span class="font-bold text-emerald-700">Free Delivery</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer bg-white hover:border-emerald-500">
                            <input type="radio" name="variants[{{ $indexKey }}][delivery_charge_type]" value="custom" class="text-emerald-600 var-del-radio" {{ $varDelType === 'custom' ? 'checked' : '' }} onchange="this.closest('.wc-variant').querySelector('.var-custom-del-row')?.classList.remove('hidden')">
                            <span>Custom Charge</span>
                        </label>
                    </div>

                    <div class="var-custom-del-row mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 {{ $varDelType === 'custom' ? '' : 'hidden' }}">
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600">Inside Dhaka (৳)</label>
                            <input type="number" step="0.01" min="0" name="variants[{{ $indexKey }}][custom_delivery_inside]" value="{{ $row['custom_delivery_inside'] ?? '' }}" placeholder="70" class="field text-xs py-1.5">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600">Outside Dhaka (৳)</label>
                            <input type="number" step="0.01" min="0" name="variants[{{ $indexKey }}][custom_delivery_outside]" value="{{ $row['custom_delivery_outside'] ?? '' }}" placeholder="130" class="field text-xs py-1.5">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
