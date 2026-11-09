@php
    $product = $product ?? null;
    $allAttributes = $allAttributes ?? \App\Models\Attribute::query()->active()->with('values')->orderBy('sort_order')->orderBy('name')->get();

    $attributeDefs = [];
    foreach ($allAttributes as $attr) {
        $attributeDefs[$attr->name] = [
            'name' => $attr->name,
            'type' => $attr->type,
            'values' => $attr->values->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'color_code' => $v->color_code,
            ])->values()->all(),
        ];
    }

    if (! isset($attributeDefs['Color'])) {
        $attributeDefs['Color'] = ['name' => 'Color', 'type' => 'color', 'values' => []];
    }
    $existingColorNames = collect($attributeDefs['Color']['values'])->pluck('name')->all();
    foreach ($colors ?? [] as $c) {
        if (! in_array($c->name, $existingColorNames, true)) {
            $attributeDefs['Color']['values'][] = [
                'id' => $c->id,
                'name' => $c->name,
                'color_code' => $c->code ?? null,
            ];
        }
    }

    if (! isset($attributeDefs['Size'])) {
        $attributeDefs['Size'] = ['name' => 'Size', 'type' => 'button', 'values' => []];
    }
    $existingSizeNames = collect($attributeDefs['Size']['values'])->pluck('name')->all();
    foreach ($sizes ?? [] as $s) {
        if (! in_array($s->name, $existingSizeNames, true)) {
            $attributeDefs['Size']['values'][] = [
                'id' => $s->id,
                'name' => $s->name,
                'color_code' => null,
            ];
        }
    }

    $variantRows = old('variants', $product?->variants?->map(fn ($variant) => [
        'id' => $variant->id,
        'name' => $variant->name,
        'attribute_values' => $variant->attribute_values,
        'color_id' => $variant->color_id,
        'size_id' => $variant->size_id,
        'size_name' => $variant->size_name,
        'sku' => $variant->sku,
        'price' => $variant->price,
        'compare_at_price' => $variant->compare_at_price,
        'stock' => $variant->stock,
        'is_unlimited_stock' => $variant->is_unlimited_stock,
        'delivery_charge_type' => $variant->delivery_charge_type ?? 'default',
        'custom_delivery_inside' => $variant->custom_delivery_inside,
        'custom_delivery_outside' => $variant->custom_delivery_outside,
        'image' => $variant->image,
    ])->values()->all() ?? []);

    $productId = $product?->id;
@endphp

<section id="section-variants" class="admin-card overflow-hidden scroll-mt-28">
    <input type="hidden" name="has_variants_section" value="1">
    <div class="admin-card-header flex-wrap">
        <div>
            <h2 class="text-sm font-semibold text-ink">Product Variations</h2>
            <p class="mt-0.5 text-[11px] text-muted">Generate combinations from attributes (Storage, Color, RAM, Size, etc.) or add variations manually.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.attributes.index') }}" target="_blank" class="text-xs font-semibold text-sage hover:underline">+ Manage Attributes</a>
            <button type="button" class="admin-btn-secondary text-xs" data-add-variant>+ Add Manual</button>
        </div>
    </div>

    {{-- Attribute Variation Generator Box --}}
    <div class="border-b border-ink/10 bg-fog/25 p-4" data-wc-generator-box>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-ink">Step 1: Select Attributes to Combine</h3>
                <p class="text-[11px] text-muted">Check terms for each attribute below. Click Generate to produce all combinations automatically.</p>
            </div>
            <button type="button" class="admin-btn-primary" data-generate-variations>
                ⚡ Generate Variations
            </button>
        </div>

        @if ($allAttributes->isNotEmpty())
            <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($allAttributes as $attr)
                    <div class="rounded border border-ink/10 bg-white p-3 shadow-xs" data-attribute-group="{{ $attr->name }}">
                        <div class="mb-2 flex items-center justify-between border-b border-ink/10 pb-1.5">
                            <span class="text-xs font-bold text-ink">{{ $attr->name }}</span>
                            <div class="flex items-center gap-1 text-[10px]">
                                <button type="button" class="text-sage hover:underline" data-select-all-attr>All</button>
                                <span class="text-ink/20">|</span>
                                <button type="button" class="text-muted hover:underline" data-deselect-all-attr>None</button>
                            </div>
                        </div>
                        <div class="max-h-36 space-y-1 overflow-y-auto pr-1 text-xs">
                            @forelse ($attr->values as $val)
                                <label class="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1.5 text-muted transition hover:bg-mist/40 hover:text-ink">
                                    <input
                                        type="checkbox"
                                        class="rounded border-ink/20 text-sage focus:ring-sage"
                                        data-attr-name="{{ $attr->name }}"
                                        data-val-name="{{ $val->name }}"
                                        value="{{ $val->id }}"
                                    >
                                    @if ($val->color_code)
                                        <span class="h-3 w-3 rounded-full border border-ink/20" style="background-color: {{ $val->color_code }}"></span>
                                    @endif
                                    <span class="truncate">{{ $val->name }}</span>
                                </label>
                            @empty
                                <p class="text-[11px] italic text-muted">No values yet.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded border border-dashed border-ink/20 bg-white p-4 text-center text-xs text-muted">
                No attributes found. <a href="{{ route('admin.attributes.create') }}" target="_blank" class="font-bold text-sage hover:underline">Click here to create your first attribute</a> (e.g. Storage, RAM, Color, Size).
            </div>
        @endif
    </div>

    {{-- Bulk Actions Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-ink/10 bg-white px-3 sm:px-4 py-2.5 text-xs">
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <span class="font-semibold text-muted whitespace-nowrap">Bulk Actions:</span>
            <select class="field py-1 text-xs flex-1 sm:w-48" data-bulk-action-select>
                <option value="">Choose bulk action...</option>
                <option value="set_regular_price">Set Regular Price (MRP) for all</option>
                <option value="set_sale_price">Set Sale Price for all</option>
                <option value="set_stock">Set Stock quantity for all</option>
                <option value="delete_all">Delete all variations</option>
            </select>
            <button type="button" class="admin-btn-secondary py-1 text-xs whitespace-nowrap" data-apply-bulk-action>Apply</button>
        </div>
        <div class="flex items-center justify-end gap-2 w-full sm:w-auto">
            <button type="button" class="admin-btn-secondary py-1 text-xs w-full sm:w-auto" data-add-variant>+ Add variation</button>
        </div>
    </div>

    {{-- Variations Accordion List --}}
    <div class="wc-variants" data-variant-list>
        @forelse ($variantRows as $index => $row)
            @include('admin.products._variant_row', [
                'index' => $index,
                'row' => $row,
                'colors' => $colors,
                'sizes' => $sizes,
                'productId' => $productId,
                'attributeDefs' => $attributeDefs,
            ])
        @empty
            <div class="p-8 text-center" data-variant-empty-state>
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sage/10 text-sage mb-3">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-ink">No variations added yet</h4>
                <p class="mt-1 text-xs text-muted max-w-sm mx-auto">
                    Select attributes above and click <strong>⚡ Generate Variations</strong> first, or click <strong>+ Add variation</strong> to create manually.
                </p>
            </div>
        @endforelse
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-ink/10 px-4 py-3 bg-fog/20">
        <p class="text-[11px] text-muted">Empty variations are skipped. Click below to save your changes directly.</p>
        <button type="submit" class="admin-btn-primary flex items-center gap-1.5 shadow-sm text-xs sm:text-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span>Save variations</span>
        </button>
    </div>
</section>

<template id="variant-row-template">
    @include('admin.products._variant_row', [
        'index' => '__INDEX__',
        'row' => [
            'id' => null,
            'name' => '__NAME__',
            'attribute_values' => '__ATTRS__',
            'color_id' => null,
            'size_id' => null,
            'size_name' => '',
            'sku' => '',
            'price' => '',
            'compare_at_price' => '',
            'stock' => '',
            'image' => null,
        ],
        'colors' => $colors,
        'sizes' => $sizes,
        'productId' => $productId,
        'attributeDefs' => $attributeDefs,
        'asTemplate' => true,
    ])
</template>

<script>
(() => {
    const list = document.querySelector('[data-variant-list]');
    const addButtons = document.querySelectorAll('[data-add-variant]');
    const template = document.getElementById('variant-row-template');
    const objectUrls = new Set();

    const emptyStateHtml = `
        <div class="p-8 text-center" data-variant-empty-state>
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sage/10 text-sage mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <h4 class="text-sm font-semibold text-ink">No variations added yet</h4>
            <p class="mt-1 text-xs text-muted max-w-sm mx-auto">
                Select attributes above and click <strong>⚡ Generate Variations</strong> first, or click <strong>+ Add variation</strong> to create manually.
            </p>
        </div>
    `;

    const checkEmptyState = () => {
        const rows = list.querySelectorAll('[data-variant-row]');
        const emptyState = list.querySelector('[data-variant-empty-state]');
        if (rows.length === 0) {
            if (! emptyState) {
                list.innerHTML = emptyStateHtml;
            }
        } else {
            emptyState?.remove();
        }
    };

    const attributeDefs = @json($attributeDefs);
    const colorsCatalog = @json(collect($colors ?? [])->pluck('id', 'name'));
    const sizesCatalog = @json(collect($sizes ?? [])->pluck('id', 'name'));

    if (! list || ! template) {
        return;
    }

    const escapeHtml = (str) => {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const revokeUrl = (img) => {
        const url = img?.getAttribute('data-object-url');
        if (! url) {
            return;
        }
        URL.revokeObjectURL(url);
        objectUrls.delete(url);
        img.removeAttribute('data-object-url');
    };

    const setImagePreview = (slotEl, src, { isObjectUrl = false } = {}) => {
        const img = slotEl.querySelector('[data-slot-img]');
        const placeholder = slotEl.querySelector('[data-slot-placeholder]');
        const clearBtn = slotEl.querySelector('[data-clear-slot]');
        if (! img) {
            return;
        }

        revokeUrl(img);

        const setBtnText = slotEl.querySelector('[data-variant-set-image-text]');
        if (src) {
            img.src = src;
            img.classList.remove('hidden');
            if (isObjectUrl) {
                img.setAttribute('data-object-url', src);
                objectUrls.add(src);
            }
            placeholder?.classList.add('hidden');
            clearBtn?.classList.remove('hidden');
            if (setBtnText) setBtnText.textContent = 'Change image';
        } else {
            img.removeAttribute('src');
            img.classList.add('hidden');
            placeholder?.classList.remove('hidden');
            clearBtn?.classList.add('hidden');
            if (setBtnText) setBtnText.textContent = 'Set image';
        }
    };

    const syncThumb = (row) => {
        const preview = row.querySelector('[data-slot-img]');
        const wrap = row.querySelector('[data-variant-thumb-wrap]');
        if (! wrap || ! preview) {
            return;
        }

        const hasSrc = preview.getAttribute('src') && ! preview.classList.contains('hidden');
        wrap.innerHTML = '';

        if (hasSrc) {
            const thumb = document.createElement('img');
            thumb.setAttribute('data-variant-thumb', '');
            thumb.src = preview.getAttribute('src');
            wrap.appendChild(thumb);
            return;
        }

        wrap.innerHTML = '<span data-variant-thumb-empty><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" /></svg></span>';
    };

    const syncCatalogIds = (row, currentAttrs) => {
        const colorIdField = row.querySelector('[data-field-color-id]');
        const sizeIdField = row.querySelector('[data-field-size-id]');

        if (colorIdField) {
            const colorName = currentAttrs['Color'] || '';
            colorIdField.value = (colorName && colorsCatalog[colorName]) ? colorsCatalog[colorName] : '';
        }
        if (sizeIdField) {
            const sizeName = currentAttrs['Size'] || '';
            sizeIdField.value = (sizeName && sizesCatalog[sizeName]) ? sizesCatalog[sizeName] : '';
        }
    };

    const rebuildRowAttributes = (row, attrObj) => {
        const grid = row.querySelector('[data-variant-attributes-grid]');
        if (! grid) return;

        const noteInput = row.querySelector('[data-field-size-name]');
        const existingNote = noteInput ? noteInput.value : '';

        grid.innerHTML = '';

        const attrEntries = Object.entries(attrObj || {});
        attrEntries.forEach(([attrName, attrVal]) => {
            const def = attributeDefs[attrName] || { name: attrName, values: [] };
            const col = document.createElement('div');
            col.setAttribute('data-attr-field-col', attrName);

            let optionsHtml = `<option value="">Select ${escapeHtml(attrName)}</option>`;
            let hasSelected = false;
            (def.values || []).forEach(v => {
                const isSel = String(v.name) === String(attrVal);
                if (isSel) hasSelected = true;
                optionsHtml += `<option value="${escapeHtml(v.name)}"${isSel ? ' selected' : ''}>${escapeHtml(v.name)}</option>`;
            });
            if (! hasSelected && attrVal) {
                optionsHtml += `<option value="${escapeHtml(attrVal)}" selected>${escapeHtml(attrVal)}</option>`;
            }

            col.innerHTML = `
                <label class="admin-label">${escapeHtml(attrName)}</label>
                <select class="field font-medium" data-attr-select="${escapeHtml(attrName)}">
                    ${optionsHtml}
                </select>
            `;
            grid.appendChild(col);
        });

        const noteCol = document.createElement('div');
        const indexMatch = row.querySelector('[name^="variants["]')?.name.match(/variants\[([^\]]+)\]/);
        const indexKey = indexMatch ? indexMatch[1] : '0';
        noteCol.innerHTML = `
            <label class="admin-label">Custom note <span class="font-normal text-muted">(optional)</span></label>
            <input
                type="text"
                name="variants[${indexKey}][size_name]"
                value="${escapeHtml(existingNote)}"
                class="field"
                placeholder='e.g. Length 28" / warranty note'
                data-field-size-name
            >
        `;
        grid.appendChild(noteCol);

        bindRowAttributeListeners(row);
    };

    const bindRowAttributeListeners = (row) => {
        row.querySelectorAll('[data-attr-select]').forEach((select) => {
            if (select.dataset.attrBound === '1') return;
            select.dataset.attrBound = '1';

            select.addEventListener('change', () => {
                const currentAttrs = {};
                row.querySelectorAll('[data-attr-select]').forEach((s) => {
                    if (s.value) {
                        currentAttrs[s.dataset.attrSelect] = s.value;
                    }
                });

                const attrField = row.querySelector('[data-field-attribute-values]');
                if (attrField) {
                    attrField.value = Object.keys(currentAttrs).length > 0 ? JSON.stringify(currentAttrs) : '';
                }

                const nameField = row.querySelector('[data-field-name]');
                const autoTitle = Object.values(currentAttrs).join(' / ');
                if (nameField) {
                    nameField.value = autoTitle;
                }

                syncCatalogIds(row, currentAttrs);
                syncSummary(row);
            });
        });

        const noteField = row.querySelector('[data-field-size-name]');
        if (noteField && noteField.dataset.noteBound !== '1') {
            noteField.dataset.noteBound = '1';
            noteField.addEventListener('input', () => syncSummary(row));
        }
    };

    const syncSummary = (row) => {
        const customName = row.querySelector('[data-field-name]')?.value?.trim();
        const currentAttrs = {};
        row.querySelectorAll('[data-attr-select]').forEach((s) => {
            if (s.value) currentAttrs[s.dataset.attrSelect] = s.value;
        });
        const attrsTitle = Object.values(currentAttrs).join(' / ');
        const noteVal = row.querySelector('[data-field-size-name]')?.value?.trim() || '';

        const price = row.querySelector('[data-field-price]')?.value;
        const comparePrice = row.querySelector('[data-field-compare-price]')?.value;
        const stock = row.querySelector('[data-field-stock]')?.value;
        const sku = row.querySelector('[data-field-sku]')?.value?.trim();

        const summaryEl = row.querySelector('[data-variant-summary]');
        if (summaryEl) {
            summaryEl.textContent = customName || attrsTitle || noteVal || 'Custom variation';
        }

        const priceEl = row.querySelector('[data-variant-summary-price]');
        if (priceEl) {
            const hasSale = price !== '' && price != null;
            const hasRegular = comparePrice !== '' && comparePrice != null;
            if (hasSale && hasRegular && Number(comparePrice) > Number(price)) {
                priceEl.textContent = `${Number(price).toFixed(2)} ৳ (was ${Number(comparePrice).toFixed(2)})`;
            } else if (hasSale) {
                priceEl.textContent = `${Number(price).toFixed(2)} ৳`;
            } else if (hasRegular) {
                priceEl.textContent = `${Number(comparePrice).toFixed(2)} ৳`;
            } else {
                priceEl.textContent = 'No price set';
            }
        }

        const isUnlimited = row.querySelector('[data-field-unlimited-stock]')?.checked;
        const stockEl = row.querySelector('[data-variant-summary-stock]');
        const stockIndicator = row.querySelector('[data-stock-indicator]');
        if (stockEl) {
            if (isUnlimited) {
                stockEl.textContent = 'Unlimited (∞)';
                stockEl.className = 'text-emerald-700 font-semibold';
                if (stockIndicator) {
                    stockIndicator.textContent = 'Unlimited (∞)';
                    stockIndicator.className = 'text-[10px] text-emerald-700 font-semibold';
                }
            } else {
                const hasStock = stock !== '' && stock != null;
                const stockNum = parseInt(stock, 10);
                if (!hasStock || isNaN(stockNum)) {
                    stockEl.textContent = 'Stock not set';
                    stockEl.className = 'text-muted';
                    if (stockIndicator) {
                        stockIndicator.textContent = 'Stock not set';
                        stockIndicator.className = 'text-[10px] text-muted';
                    }
                } else if (stockNum === 0) {
                    stockEl.textContent = 'Out of stock (0)';
                    stockEl.className = 'text-accent font-semibold';
                    if (stockIndicator) {
                        stockIndicator.textContent = 'Out of stock (0)';
                        stockIndicator.className = 'text-[10px] text-accent font-semibold';
                    }
                } else if (stockNum <= 5) {
                    stockEl.textContent = `${stockNum} in stock (Low)`;
                    stockEl.className = 'text-amber-600 font-semibold';
                    if (stockIndicator) {
                        stockIndicator.textContent = `${stockNum} in stock (Low)`;
                        stockIndicator.className = 'text-[10px] text-amber-600 font-semibold';
                    }
                } else {
                    stockEl.textContent = `${stockNum} in stock`;
                    stockEl.className = 'text-emerald-700 font-medium';
                    if (stockIndicator) {
                        stockIndicator.textContent = `${stockNum} in stock`;
                        stockIndicator.className = 'text-[10px] text-emerald-700 font-medium';
                    }
                }
            }
        }

        const skuEl = row.querySelector('[data-variant-summary-sku]');
        const skuDot = row.querySelector('[data-variant-summary-sku-dot]');
        if (skuEl) {
            if (sku) {
                skuEl.textContent = `SKU: ${sku}`;
                skuEl.classList.remove('hidden');
                skuDot?.classList.remove('hidden');
            } else {
                skuEl.textContent = '';
                skuEl.classList.add('hidden');
                skuDot?.classList.add('hidden');
            }
        }
    };

    const setOpen = (row, open) => {
        row.classList.toggle('is-open', open);
        const body = row.querySelector('[data-variant-body]');
        const header = row.querySelector('[data-variant-toggle]');
        if (body) {
            body.hidden = ! open;
        }
        if (header) {
            header.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    };

    const closeOthers = (except) => {
        list.querySelectorAll('[data-variant-row].is-open').forEach((row) => {
            if (row !== except) {
                setOpen(row, false);
            }
        });
    };

    const reindexVariants = () => {
        list.querySelectorAll('[data-variant-row]').forEach((row, index) => {
            row.querySelectorAll('[name^="variants["]').forEach((input) => {
                input.name = input.name.replace(/^variants\[[^\]]+\]/, `variants[${index}]`);
            });
        });
    };

    const bindFileInput = (input) => {
        if (! (input instanceof HTMLInputElement) || input.dataset.bound === '1') {
            return;
        }
        input.dataset.bound = '1';
        input.addEventListener('change', () => {
            const slotEl = input.closest('[data-image-slot]');
            const row = input.closest('[data-variant-row]');
            const removeInput = slotEl?.querySelector('[data-remove-slot]');
            const mediaInput = slotEl?.querySelector('[data-variant-media-input]');
            const file = input.files?.[0];

            if (removeInput instanceof HTMLInputElement) {
                removeInput.value = '0';
            }
            if (mediaInput instanceof HTMLInputElement) {
                mediaInput.value = '';
            }

            if (file && file.type.startsWith('image/') && slotEl) {
                setImagePreview(slotEl, URL.createObjectURL(file), { isObjectUrl: true });
            }

            if (row) {
                syncThumb(row);
            }
        });
    };

    const bindRow = (row) => {
        row.querySelectorAll('[data-slot-file]').forEach(bindFileInput);
        row.querySelectorAll('[data-field-name], [data-field-size-name], [data-field-price], [data-field-compare-price], [data-field-stock], [data-field-sku], [data-field-unlimited-stock]')
            .forEach((field) => {
                if (field.dataset.summaryBound === '1') {
                    return;
                }
                field.dataset.summaryBound = '1';
                field.addEventListener('input', () => syncSummary(row));
                field.addEventListener('change', () => {
                    if (field.matches('[data-field-unlimited-stock]')) {
                        const sInp = row.querySelector('[data-field-stock]');
                        if (sInp) {
                            if (field.checked) {
                                sInp.readOnly = true;
                                sInp.classList.add('opacity-50', 'bg-slate-50');
                                sInp.placeholder = 'Unlimited (∞)';
                            } else {
                                sInp.readOnly = false;
                                sInp.classList.remove('opacity-50', 'bg-slate-50');
                                sInp.placeholder = '0';
                            }
                        }
                    }
                    syncSummary(row);
                });
            });
        bindRowAttributeListeners(row);
        syncSummary(row);
    };

    list.querySelectorAll('[data-variant-row]').forEach((row) => bindRow(row));

    const first = list.querySelector('[data-variant-row]');
    if (first) {
        setOpen(first, true);
    }

    addButtons.forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            const emptyEl = list.querySelector('[data-variant-empty-state]');
            if (emptyEl) {
                emptyEl.remove();
            }

            const index = list.querySelectorAll('[data-variant-row]').length;
            let html = template.innerHTML.replaceAll('__INDEX__', String(index));
            html = html.replaceAll('__NAME__', '').replaceAll('__ATTRS__', '');
            list.insertAdjacentHTML('beforeend', html);
            const row = list.querySelector('[data-variant-row]:last-child');
            if (! row) {
                return;
            }

            const activeAttrGroups = [];
            document.querySelectorAll('[data-attribute-group]').forEach((group) => {
                const attrName = group.getAttribute('data-attribute-group');
                const checked = group.querySelectorAll('input[type="checkbox"]:checked');
                if (checked.length > 0) {
                    activeAttrGroups.push(attrName);
                }
            });

            const manualAttrs = {};
            if (activeAttrGroups.length > 0) {
                activeAttrGroups.forEach((name) => { manualAttrs[name] = ''; });
            } else {
                const firstRowAttrs = list.querySelector('[data-variant-row]:not(:last-child) [data-field-attribute-values]');
                if (firstRowAttrs && firstRowAttrs.value) {
                    try {
                        const parsed = JSON.parse(firstRowAttrs.value);
                        Object.keys(parsed).forEach((k) => { manualAttrs[k] = ''; });
                    } catch (e) {}
                }
            }

            if (Object.keys(manualAttrs).length === 0) {
                const keys = Object.keys(attributeDefs);
                if (keys.length > 0) {
                    keys.slice(0, 2).forEach((k) => { manualAttrs[k] = ''; });
                }
            }

            rebuildRowAttributes(row, manualAttrs);
            bindRow(row);
            closeOthers(row);
            setOpen(row, true);
            reindexVariants();
            row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    const toggleRow = (row) => {
        const willOpen = ! row.classList.contains('is-open');
        if (willOpen) {
            closeOthers(row);
        }
        setOpen(row, willOpen);
    };

    list.addEventListener('click', (event) => {
        const target = event.target;
        if (! (target instanceof Element)) {
            return;
        }

        const row = target.closest('[data-variant-row]');
        if (! row) {
            return;
        }

        if (target.closest('[data-clear-slot]')) {
            event.preventDefault();
            event.stopPropagation();
            const slotEl = target.closest('[data-image-slot]');
            const fileInput = slotEl?.querySelector('[data-slot-file]');
            const removeInput = slotEl?.querySelector('[data-remove-slot]');
            const mediaInput = slotEl?.querySelector('[data-variant-media-input]');
            if (fileInput instanceof HTMLInputElement) {
                fileInput.value = '';
            }
            if (mediaInput instanceof HTMLInputElement) {
                mediaInput.value = '';
            }
            if (removeInput instanceof HTMLInputElement) {
                removeInput.value = '1';
            }
            if (slotEl) {
                setImagePreview(slotEl, null);
            }
            syncThumb(row);
            return;
        }

        if (target.closest('[data-variant-set-image]') || target.closest('.wc-variant__image-box')) {
            event.preventDefault();
            event.stopPropagation();
            const slotEl = target.closest('[data-image-slot]');
            const fileInput = slotEl?.querySelector('[data-slot-file]');
            const removeInput = slotEl?.querySelector('[data-remove-slot]');
            const mediaInput = slotEl?.querySelector('[data-variant-media-input]');

            if (typeof window.openMediaPicker === 'function') {
                window.openMediaPicker({
                    multiple: false,
                    filter: 'image',
                    initialTab: 'library',
                    title: 'Select Variant Image',
                    buttonText: 'Set variant image',
                    onSelect: (item) => {
                        if (!item) return;
                        if (fileInput instanceof HTMLInputElement) {
                            fileInput.value = '';
                        }
                        if (mediaInput instanceof HTMLInputElement) {
                            mediaInput.value = item.path;
                        }
                        if (removeInput instanceof HTMLInputElement) {
                            removeInput.value = '0';
                        }
                        if (slotEl) {
                            setImagePreview(slotEl, item.url);
                            const setBtnText = slotEl.querySelector('[data-variant-set-image-text]');
                            if (setBtnText) setBtnText.textContent = 'Change image';
                        }
                        syncThumb(row);
                    }
                });
            } else if (fileInput) {
                fileInput.click();
            }
            return;
        }

        if (target.closest('[data-remove-variant]')) {
            event.preventDefault();
            event.stopPropagation();

            row.querySelectorAll('img[data-object-url]').forEach((img) => revokeUrl(img));
            row.remove();
            reindexVariants();
            checkEmptyState();
            return;
        }

        if (target.closest('[data-variant-toggle]')) {
            event.preventDefault();
            toggleRow(row);
        }
    });

    // Attribute selection helpers (All / None)
    document.querySelectorAll('[data-select-all-attr]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const group = btn.closest('[data-attribute-group]');
            group?.querySelectorAll('input[type="checkbox"]').forEach((cb) => cb.checked = true);
        });
    });

    document.querySelectorAll('[data-deselect-all-attr]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const group = btn.closest('[data-attribute-group]');
            group?.querySelectorAll('input[type="checkbox"]').forEach((cb) => cb.checked = false);
        });
    });

    // Cartesian Product Generator
    const cartesian = (arrays) => {
        return arrays.reduce((acc, curr) => {
            return acc.flatMap((a) => curr.map((c) => [...a, c]));
        }, [[]]);
    };

    const generateBtn = document.querySelector('[data-generate-variations]');
    if (generateBtn) {
        generateBtn.addEventListener('click', (event) => {
            event.preventDefault();

            // Collect selected attributes
            const groups = [];
            document.querySelectorAll('[data-attribute-group]').forEach((group) => {
                const attrName = group.getAttribute('data-attribute-group');
                const checked = Array.from(group.querySelectorAll('input[type="checkbox"]:checked')).map((cb) => ({
                    attrName,
                    valName: cb.getAttribute('data-val-name'),
                    valId: cb.value,
                }));
                if (checked.length > 0) {
                    groups.push(checked);
                }
            });

            if (groups.length === 0) {
                alert('Please select at least one attribute value to generate variations.');
                return;
            }

            const combinations = cartesian(groups);

            const existingRows = list.querySelectorAll('[data-variant-row]');
            const hasExistingContent = Array.from(existingRows).some((row) => {
                const idVal = row.querySelector('[name$="[id]"]')?.value;
                const nameVal = row.querySelector('[name$="[name]"]')?.value?.trim();
                const priceVal = row.querySelector('[name$="[price]"]')?.value;
                return Boolean(idVal || nameVal || priceVal);
            });

            if (hasExistingContent) {
                const shouldReplace = confirm(`Generate ${combinations.length} variation combinations?\n\n• OK: REPLACE existing variations with new combinations\n• Cancel: APPEND new combinations to existing variations`);
                if (shouldReplace) {
                    list.querySelectorAll('img[data-object-url]').forEach((img) => revokeUrl(img));
                    list.innerHTML = '';
                }
            } else {
                list.querySelectorAll('img[data-object-url]').forEach((img) => revokeUrl(img));
                list.innerHTML = '';
            }

            list.querySelector('[data-variant-empty-state]')?.remove();

            const basePrice = document.getElementById('price')?.value || '';
            const baseRegularPrice = document.getElementById('compare_at_price')?.value || '';
            const baseStock = document.getElementById('stock')?.value || '';

            combinations.forEach((combo) => {
                const comboNames = combo.map((item) => item.valName);
                const comboTitle = comboNames.join(' / ');
                const attrObj = {};
                combo.forEach((item) => {
                    attrObj[item.attrName] = item.valName;
                });
                const attrJson = JSON.stringify(attrObj);

                const currentIndex = list.querySelectorAll('[data-variant-row]').length;
                let html = template.innerHTML.replaceAll('__INDEX__', String(currentIndex));
                html = html.replaceAll('__NAME__', comboTitle);
                html = html.replaceAll('__ATTRS__', attrJson.replace(/"/g, '&quot;'));

                list.insertAdjacentHTML('beforeend', html);
                const newRow = list.querySelector('[data-variant-row]:last-child');
                if (! newRow) {
                    return;
                }

                // Render ONLY the attribute boxes that were selected for this variation!
                rebuildRowAttributes(newRow, attrObj);

                // Pre-populate fields
                const nameField = newRow.querySelector('[data-field-name]');
                if (nameField) {
                    nameField.value = comboTitle;
                }
                const attrField = newRow.querySelector('[data-field-attribute-values]');
                if (attrField) {
                    attrField.value = attrJson;
                }
                syncCatalogIds(newRow, attrObj);

                const priceField = newRow.querySelector('[data-field-price]');
                if (priceField && basePrice) {
                    priceField.value = basePrice;
                }
                const compareField = newRow.querySelector('[data-field-compare-price]');
                if (compareField && baseRegularPrice) {
                    compareField.value = baseRegularPrice;
                }
                const stockField = newRow.querySelector('[data-field-stock]');
                if (stockField && baseStock) {
                    stockField.value = baseStock;
                }

                bindRow(newRow);
                syncSummary(newRow);
            });

            reindexVariants();
            alert(`Successfully generated ${combinations.length} variations!`);
        });
    }

    // Bulk actions handler
    const applyBulkBtn = document.querySelector('[data-apply-bulk-action]');
    const bulkSelect = document.querySelector('[data-bulk-action-select]');

    if (applyBulkBtn && bulkSelect) {
        applyBulkBtn.addEventListener('click', (event) => {
            event.preventDefault();
            const action = bulkSelect.value;
            if (! action) {
                alert('Please choose a bulk action first.');
                return;
            }

            if (action === 'delete_all') {
                if (confirm('Are you sure you want to delete all variations?')) {
                    list.querySelectorAll('img[data-object-url]').forEach((img) => revokeUrl(img));
                    list.innerHTML = '';
                    checkEmptyState();
                }
                return;
            }

            if (action === 'set_regular_price') {
                const val = prompt('Enter Regular Price (MRP) for all variations:');
                if (val !== null && val !== '') {
                    list.querySelectorAll('[data-field-compare-price]').forEach((input) => {
                        input.value = val;
                        input.dispatchEvent(new Event('input'));
                    });
                }
                return;
            }

            if (action === 'set_sale_price') {
                const val = prompt('Enter Sale Price for all variations:');
                if (val !== null && val !== '') {
                    list.querySelectorAll('[data-field-price]').forEach((input) => {
                        input.value = val;
                        input.dispatchEvent(new Event('input'));
                    });
                }
                return;
            }

            if (action === 'set_stock') {
                const val = prompt('Enter Stock quantity for all variations:');
                if (val !== null && val !== '') {
                    list.querySelectorAll('[data-field-stock]').forEach((input) => {
                        input.value = val;
                        input.dispatchEvent(new Event('input'));
                    });
                }
                return;
            }
        });
    }
})();
</script>
