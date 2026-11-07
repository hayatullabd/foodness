@php
    $product = $product ?? null;
@endphp

<div class="grid gap-3 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <section id="section-basic" class="admin-card admin-card-body scroll-mt-28">
            <h2 class="mb-2.5 admin-section-title">Basic Information</h2>
            <div class="space-y-4">
                <div>
                    <label class="admin-label" for="name">Product Name <span class="text-accent">*</span></label>
                    <input id="name" name="name" type="text" value="{{ old('name', $product?->name) }}" required class="field" placeholder="e.g. Men's Cotton Casual Shirt">
                    @error('name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="admin-label" for="category_id">Category <span class="text-accent">*</span></label>
                        <select id="category_id" name="category_id" required class="field" data-category-select>
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product?->category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label" for="subcategory_id">SubCategories</label>
                        <select id="subcategory_id" name="subcategory_id" class="field" data-subcategory-select>
                            <option value="">Select subcategory</option>
                            @foreach ($subcategories as $subcategory)
                                <option
                                    value="{{ $subcategory->id }}"
                                    data-category-id="{{ $subcategory->category_id }}"
                                    @selected(old('subcategory_id', $product?->subcategory_id) == $subcategory->id)
                                >
                                    {{ $subcategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="childcategory_id">Child Categories</label>
                        <select id="childcategory_id" name="childcategory_id" class="field" data-childcategory-select>
                            <option value="">Select child category</option>
                            @foreach ($childcategories as $childcategory)
                                <option
                                    value="{{ $childcategory->id }}"
                                    data-subcategory-id="{{ $childcategory->subcategory_id }}"
                                    @selected(old('childcategory_id', $product?->childcategory_id) == $childcategory->id)
                                >
                                    {{ $childcategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="admin-label" for="short_description">Short description</label>
                    <textarea
                        id="short_description"
                        name="short_description"
                        rows="3"
                        class="field"
                        maxlength="1000"
                        placeholder="Brief summary shown near the product title"
                    >{{ old('short_description', $product?->short_description) }}</textarea>
                    <p class="mt-1 text-[11px] text-muted">Plain text · max 1000 characters · used as a quick product intro.</p>
                    @error('short_description') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                </div>
                <div class="min-w-0">
                    <div class="flex items-center justify-between mb-2">
                        <label class="admin-label !mb-0" for="description">Description <span class="text-xs font-normal text-muted">(Full Details)</span></label>
                        @if(app(\App\Services\GeminiService::class)->isContentGenerationEnabled())
                            <button
                                type="button"
                                onclick="window.openGeminiAiModal()"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-bold text-white shadow-sm transition active:scale-95 cursor-pointer hover:opacity-95"
                                style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff !important; border: 1px solid rgba(255,255,255,0.2);"
                                title="Open Gemini AI Copywriter Studio to write descriptions and SEO tags"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                </svg>
                                <span>✨ AI Copywriter Studio</span>
                            </button>
                        @endif
                    </div>
                    <div class="product-desc-editor min-w-0 max-w-full">
                        <textarea id="description" name="description" rows="10" class="field">{{ old('description', $product?->description) }}</textarea>
                    </div>
                    <p class="mt-1 text-[11px] text-muted">Bold, color, lists, images, video, and more. Drag the bottom bar to resize.</p>
                    @error('description') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        @include('admin.products._variants')

        <section id="section-seo" class="admin-card admin-card-body scroll-mt-28">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-1 border-b border-ink/10 pb-2">
                <h2 class="admin-section-title !mb-0">SEO Configuration</h2>
                <span class="text-[11px] text-muted">Google & Social Search Preview</span>
            </div>

            {{-- Google Search Snippet Live Preview --}}
            <div class="mb-4 rounded-lg border border-ink/15 bg-fog/20 p-3.5 shadow-xs">
                <div class="mb-2 flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-ink">
                        <svg class="h-3.5 w-3.5 text-sage" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        Google Search Preview
                    </span>
                    <span class="rounded bg-sage/10 px-1.5 py-0.5 text-[10px] font-bold text-sage">Live</span>
                </div>
                <div class="space-y-1 rounded border border-ink/10 bg-white p-3 shadow-xs">
                    <div class="flex items-center gap-1.5 text-xs text-muted">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-mist text-[10px]">🛍️</span>
                        <div class="truncate text-[11px]">
                            <span class="font-medium text-ink">{{ config('app.name', 'Foodness') }}</span>
                            <span class="text-muted"> › product › <span id="seo-preview-slug">{{ $product?->slug ?? 'product-slug' }}</span></span>
                        </div>
                    </div>
                    <h3 class="cursor-pointer truncate text-[15px] font-medium text-[#1a0dab] hover:underline" id="seo-preview-title">
                        {{ old('meta_title', $product?->meta_title ?: ($product?->name ?: 'Product Title Preview')) }}
                    </h3>
                    <p class="line-clamp-2 text-[12px] leading-relaxed text-[#4d5156]" id="seo-preview-desc">
                        {{ old('meta_description', $product?->meta_description ?: ($product?->short_description ?: 'Add a meta description to see how this product appears in Google search engine results.')) }}
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between">
                        <label class="admin-label" for="meta_title">Meta Title</label>
                        <span class="text-[11px] text-muted" id="meta-title-counter"><span class="font-mono font-bold" id="meta-title-count">0</span> / 60 chars</span>
                    </div>
                    <input id="meta_title" name="meta_title" type="text" value="{{ old('meta_title', $product?->meta_title) }}" class="field" placeholder="Catchy title for Google search">
                </div>
                <div>
                    <label class="admin-label" for="meta_keywords">Meta Keywords</label>
                    <input id="meta_keywords" name="meta_keywords" type="text" value="{{ old('meta_keywords', $product?->meta_keywords) }}" class="field" placeholder="keyword1, keyword2">
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="admin-label" for="meta_description">Meta Description</label>
                        <span class="text-[11px] text-muted" id="meta-desc-counter"><span class="font-mono font-bold" id="meta-desc-count">0</span> / 160 chars</span>
                    </div>
                    <textarea id="meta_description" name="meta_description" rows="3" class="field" placeholder="Concise summary for search engine results">{{ old('meta_description', $product?->meta_description) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="meta_image_file">Meta Image (og:image)</label>
                    <div class="mb-2 rounded-md border border-dashed border-ink/15 bg-mist/60 px-3 py-2.5 text-center">
                        <p class="text-sm font-semibold text-ink">Best size: 1200 × 630 px</p>
                        <p class="mt-0.5 text-[11px] text-muted">Ratio 1.91:1 · JPG/PNG · Max 2 MB · Facebook / WhatsApp share</p>
                    </div>

                    <div class="wc-product-gallery wc-product-gallery--meta" data-media-gallery="meta">
                        <input
                            id="meta_image_file"
                            name="meta_image_file"
                            type="file"
                            accept="image/*"
                            class="sr-only"
                            data-meta-image-input
                            data-preview-target="meta-image-preview"
                        >
                        <input type="hidden" name="meta_image_media_path" id="meta_image_media_path" value="">

                        <div class="wc-product-gallery__meta-slot">
                            @if ($product?->meta_image)
                                <div class="wc-gallery-item wc-gallery-item--meta" data-meta-current>
                                    <div class="wc-gallery-item__thumb-wrap">
                                        <button
                                            type="button"
                                            class="wc-gallery-item__thumb wc-gallery-item__thumb--replace"
                                            data-meta-replace-trigger
                                            title="Click to replace image"
                                            aria-label="Click to replace meta image"
                                        >
                                            <img src="{{ asset('storage/'.$product->meta_image) }}" alt="" onerror="this.style.opacity='0.3'">
                                            <span class="wc-gallery-item__badge">Current</span>
                                            <span class="wc-gallery-item__replace-hint">Replace</span>
                                        </button>
                                        <button
                                            type="submit"
                                            form="delete-product-meta-image"
                                            class="wc-gallery-item__remove-overlay"
                                            title="Remove meta image"
                                            aria-label="Remove meta image"
                                            onclick="return confirm('Remove meta image?')"
                                        >
                                            ×
                                        </button>
                                    </div>
                                    <div class="wc-gallery-item__actions wc-gallery-item__actions--meta">
                                        <button type="button" class="wc-gallery-item__replace-link" data-meta-replace-trigger>Replace</button>
                                        <button
                                            type="submit"
                                            form="delete-product-meta-image"
                                            class="wc-gallery-item__remove-link"
                                            onclick="return confirm('Remove meta image?')"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <div
                                id="meta-image-preview"
                                class="wc-product-gallery__meta-preview"
                                data-image-preview-list
                                data-preview-style="meta"
                                data-preview-mode="wc-tile"
                                data-preview-aspect="meta"
                            ></div>

                            <button
                                type="button"
                                class="wc-gallery-add wc-gallery-add--meta {{ $product?->meta_image ? 'is-hidden' : '' }}"
                                data-meta-add-tile
                            >
                                <span class="wc-gallery-add__plus">+</span>
                                <span class="wc-gallery-add__label">Add meta image</span>
                            </button>
                        </div>
                    </div>
                    @error('meta_image_file') <p class="mt-2 text-sm text-accent">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>
    </div>

    <div class="space-y-4">
        <section id="section-pricing" class="admin-card admin-card-body scroll-mt-28">
            <h2 class="mb-2.5 admin-section-title">Pricing & Inventory</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="admin-label" for="compare_at_price">Regular Price</label>
                    <div class="flex items-stretch">
                        <span class="inline-flex items-center justify-center px-2.5 border border-r-0 border-ink/15 bg-canvas text-xs font-semibold text-muted rounded-l select-none">৳</span>
                        <input id="compare_at_price" name="compare_at_price" type="number" step="0.01" min="0" value="{{ old('compare_at_price', $product?->compare_at_price ? (float)$product->compare_at_price : '') }}" class="field !rounded-l-none" placeholder="0.00">
                    </div>
                    <p class="mt-1 text-[11px] text-muted">Original / MRP — shown crossed out when on sale. Auto-fills from variations if left blank.</p>
                </div>
                <div>
                    <label class="admin-label" for="price">Sale Price</label>
                    <div class="flex items-stretch">
                        <span class="inline-flex items-center justify-center px-2.5 border border-r-0 border-ink/15 bg-canvas text-xs font-semibold text-muted rounded-l select-none">৳</span>
                        <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product?->price ? (float)$product->price : '') }}" class="field !rounded-l-none" placeholder="0.00">
                    </div>
                    <p class="mt-1 text-[11px] text-muted">Price customers pay. Leave blank for variable products to auto-use minimum variation price.</p>
                    @error('price') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                </div>
                @php
                    $hasVariants = $product && $product->relationLoaded('variants') && $product->variants->isNotEmpty();
                    $isUnlimited = old('is_unlimited_stock', $product?->is_unlimited_stock ?? false);
                    $totalVariantStock = $hasVariants ? (int) $product->variants->sum('stock') : (int) ($product?->stock ?? 0);
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="admin-label !mb-0" for="stock">Stock</label>
                        <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded cursor-pointer select-none hover:bg-emerald-100 transition">
                            <input
                                type="checkbox"
                                name="is_unlimited_stock"
                                id="is_unlimited_stock"
                                value="1"
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                {{ $isUnlimited ? 'checked' : '' }}
                            >
                            <span>Unlimited Stock</span>
                        </label>
                    </div>
                    <input
                        id="stock"
                        name="stock"
                        type="number"
                        min="0"
                        value="{{ old('stock', $hasVariants ? $totalVariantStock : ($product?->stock ?? 0)) }}"
                        class="field {{ $isUnlimited ? 'opacity-50 bg-slate-50' : '' }}"
                        placeholder="{{ $isUnlimited ? 'Unlimited (∞)' : '0' }}"
                        {{ $isUnlimited ? 'readonly' : '' }}
                    >
                    <p id="stock-variants-hint" class="mt-1 text-[11px] text-muted {{ $hasVariants ? '' : 'hidden' }}">
                        Auto-synced from variations (<span id="stock-variants-total" class="font-semibold text-ink">{{ $totalVariantStock }}</span> in stock)
                    </p>
                    @error('stock') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label" for="unit">Unit</label>
                    <input id="unit" name="unit" type="text" value="{{ old('unit', $product?->unit) }}" class="field" placeholder="PCS">
                </div>
                <div class="sm:col-span-2">
                    <label class="admin-label" for="brand_id">Brand</label>
                    <select id="brand_id" name="brand_id" class="field">
                        <option value="">Select brand</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product?->brand_id) == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-label" for="sku">SKU</label>
                    <input id="sku" name="sku" type="text" value="{{ old('sku', $product?->sku) }}" class="field" placeholder="auto-generated if empty">
                    @error('sku') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="admin-label !mb-0" for="slug">Slug</label>
                        @if ($product?->exists && $product?->slug)
                            <a href="{{ route('product.show', $product->slug) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[11px] font-medium text-sage hover:underline">
                                <span>View live product</span>
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                            </a>
                        @endif
                    </div>
                    <input id="slug" name="slug" type="text" value="{{ old('slug', $product?->slug) }}" class="field mt-1" placeholder="auto from name">
                </div>
            </div>
        </section>

        <section id="section-gallery" class="admin-card admin-card-body scroll-mt-28">
            <div class="mb-2.5 flex items-center justify-between">
                <h2 class="admin-section-title !mb-0">Product gallery</h2>
                <span class="text-[11px] text-muted">Drag or use ◀ ▶ to reorder</span>
            </div>
            <p class="mb-3 text-xs text-muted">Add multiple images, set primary image, and reorder as needed.</p>
            <div class="mb-3 rounded-md border border-dashed border-ink/15 bg-mist/60 px-3 py-2.5 text-center">
                <p class="text-sm font-semibold text-ink">Best size: 1200 × 1600 px</p>
                <p class="mt-0.5 text-[11px] text-muted">Ratio 3:4 · JPG/WebP/PNG · Max 2 MB each</p>
            </div>

            <div class="wc-product-gallery" data-media-gallery="images">
                <div class="wc-product-gallery__grid" data-gallery-sortable>
                    @if ($product?->relationLoaded('images'))
                        @foreach ($product->images as $image)
                            <div
                                @class(['wc-gallery-item', 'is-primary' => $image->is_primary])
                                draggable="true"
                                data-gallery-item
                                data-image-id="{{ $image->id }}"
                            >
                                <input type="hidden" name="image_order[]" value="{{ $image->id }}" data-image-order-input>
                                <div class="wc-gallery-item__thumb relative">
                                    <img src="{{ asset('storage/'.$image->path) }}" alt="" onerror="this.style.opacity='0.3'">
                                    @if ($image->is_primary)
                                        <span class="wc-gallery-item__badge">Product image</span>
                                    @endif
                                    <span class="absolute top-1 left-1 rounded bg-black/70 px-1 py-0.5 font-mono text-[9px] font-bold text-white pointer-events-none" data-order-badge>
                                        #{{ $loop->iteration }}
                                    </span>
                                    <button
                                        type="submit"
                                        form="delete-product-image-{{ $image->id }}"
                                        class="wc-gallery-item__remove-overlay"
                                        title="Remove image"
                                        aria-label="Remove image"
                                        onclick="return confirm('Remove this gallery image?')"
                                    >
                                        ×
                                    </button>
                                </div>
                                <div class="wc-gallery-item__actions flex items-center justify-between gap-1">
                                    <label class="wc-gallery-item__primary truncate">
                                        <input
                                            type="radio"
                                            name="primary_image_id"
                                            value="{{ $image->id }}"
                                            @checked(old('primary_image_id', $product->images->firstWhere('is_primary', true)?->id ?? $product->images->first()?->id) == $image->id)
                                        >
                                        <span>Primary</span>
                                    </label>
                                    <div class="flex items-center gap-0.5">
                                        <button type="button" class="rounded p-1 text-xs leading-none text-muted transition hover:bg-mist hover:text-ink" data-move-image="left" title="Move earlier">◀</button>
                                        <button type="button" class="rounded p-1 text-xs leading-none text-muted transition hover:bg-mist hover:text-ink" data-move-image="right" title="Move later">▶</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    <div
                        id="gallery-image-preview"
                        class="contents"
                        data-image-preview-list
                        data-preview-style="gallery"
                        data-preview-mode="wc-tile"
                    ></div>

                    <button
                        type="button"
                        class="wc-gallery-add"
                        id="btn-gallery-add-images"
                        title="Add product gallery images"
                    >
                        <span class="wc-gallery-add__plus">+</span>
                        <span class="wc-gallery-add__label">Add images</span>
                    </button>
                    <input
                        id="images"
                        name="images[]"
                        type="file"
                        accept="image/*"
                        multiple
                        class="sr-only"
                    >
                </div>
                @error('images') <p class="mt-2 text-sm text-accent">{{ $message }}</p> @enderror
                @error('images.*') <p class="mt-2 text-sm text-accent">{{ $message }}</p> @enderror
                @error('primary_image_id') <p class="mt-2 text-sm text-accent">{{ $message }}</p> @enderror
            </div>

            @php
                $variantImagesCount = 0;
                if ($product?->relationLoaded('variants')) {
                    foreach ($product->variants as $v) {
                        $variantImagesCount += count($v->storedImagePaths());
                    }
                }
            @endphp
            @if ($variantImagesCount > 0)
                <div class="mt-4 pt-3 border-t border-ink/10">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-ink">Variation images ({{ $variantImagesCount }})</span>
                        <span class="text-[11px] text-muted">Auto-included in live storefront gallery</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->variants as $variant)
                            @foreach ($variant->storedImagePaths() as $vPath)
                                <div class="relative group rounded border border-ink/10 bg-mist/40 p-1 flex items-center gap-1.5" title="{{ $variant->label() }}">
                                    <img src="{{ asset('storage/'.$vPath) }}" alt="" class="h-10 w-10 object-cover rounded">
                                    <div class="pr-1 text-[11px]">
                                        <p class="font-medium text-ink leading-tight">{{ $variant->label() }}</p>
                                        <p class="text-[10px] text-muted">Variant image</p>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <section class="admin-card admin-card-body">
            <h2 class="mb-2.5 admin-section-title">Video gallery</h2>
            <p class="mb-3 text-xs text-muted">Upload videos or paste YouTube links — multiple videos supported.</p>

            <div class="wc-product-gallery" data-media-gallery="videos">
                <div class="wc-product-gallery__grid">
                    @if ($product?->relationLoaded('videos'))
                        @foreach ($product->videos as $video)
                            <div class="wc-gallery-item wc-gallery-item--video">
                                <div class="wc-gallery-item__thumb">
                                    @if ($video->isYoutube())
                                        <img src="{{ $video->thumbnailUrl() }}" alt="" onerror="this.style.opacity='0.3'">
                                        <span class="wc-gallery-item__badge">YouTube</span>
                                    @else
                                        <video src="{{ asset('storage/'.$video->path) }}" muted preload="metadata"></video>
                                        <span class="wc-gallery-item__badge">Upload</span>
                                    @endif
                                    <button
                                        type="submit"
                                        form="delete-product-video-{{ $video->id }}"
                                        class="wc-gallery-item__remove-overlay"
                                        title="Remove video"
                                        aria-label="Remove video"
                                        onclick="return confirm('Remove this video?')"
                                    >
                                        ×
                                    </button>
                                </div>
                                <div class="wc-gallery-item__actions">
                                    <span class="truncate text-[11px] text-muted">
                                        {{ $video->isYoutube() ? ($video->youtube_id ?? 'YouTube') : ($video->title ?: 'Video') }}
                                    </span>
                                    <button
                                        type="submit"
                                        form="delete-product-video-{{ $video->id }}"
                                        class="wc-gallery-item__remove-link"
                                        onclick="return confirm('Remove this video?')"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    <div
                        id="gallery-video-preview"
                        class="contents"
                        data-video-preview-list
                    ></div>

                    <label class="wc-gallery-add" for="videos">
                        <input
                            id="videos"
                            name="videos[]"
                            type="file"
                            accept="video/mp4,video/webm,video/quicktime,video/*"
                            multiple
                            class="sr-only"
                            data-media-accumulate
                            data-video-preview-target="gallery-video-preview"
                        >
                        <span class="wc-gallery-add__plus">+</span>
                        <span class="wc-gallery-add__label">Upload videos</span>
                    </label>
                </div>

                <div class="mt-4 space-y-2" data-youtube-urls>
                    <label class="admin-label" for="youtube_url_input">Add YouTube URL</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input
                            id="youtube_url_input"
                            type="text"
                            class="field min-w-0 flex-1"
                            placeholder="https://www.youtube.com/watch?v=... or video ID"
                            data-youtube-input
                        >
                        <button type="button" class="admin-btn-secondary shrink-0" data-youtube-add>
                            Add YouTube
                        </button>
                    </div>
                    <div class="space-y-1.5" data-youtube-list>
                        @foreach (old('youtube_urls', []) as $youtubeUrl)
                            @if (filled($youtubeUrl))
                                <div class="flex items-center gap-2 rounded-md border border-ink/10 bg-mist/40 px-2.5 py-1.5" data-youtube-row>
                                    <input type="hidden" name="youtube_urls[]" value="{{ $youtubeUrl }}">
                                    <span class="min-w-0 flex-1 truncate text-xs text-ink">{{ $youtubeUrl }}</span>
                                    <button type="button" class="text-xs font-medium text-accent hover:underline" data-youtube-remove>Remove</button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <p class="text-[11px] text-muted">MP4 / WebM / MOV · Max 50 MB each · YouTube links open in the store gallery.</p>
                </div>

                @error('videos') <p class="mt-2 text-sm text-accent">{{ $message }}</p> @enderror
                @error('videos.*') <p class="mt-2 text-sm text-accent">{{ $message }}</p> @enderror
                @error('youtube_urls') <p class="mt-2 text-sm text-accent">{{ $message }}</p> @enderror
                @error('youtube_urls.*') <p class="mt-2 text-sm text-accent">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="admin-card admin-card-body">
            <h2 class="mb-2.5 admin-section-title">Settings</h2>
            <div class="space-y-3">
                <div>
                    <label class="admin-label" for="sold">Sold Count</label>
                    <input id="sold" name="sold" type="number" min="0" value="{{ old('sold', $product?->sold ?? 0) }}" class="field">
                </div>
                @include('admin.partials.toggle-switch', [
                    'id' => 'is_active',
                    'name' => 'is_active',
                    'checked' => old('is_active', $product?->is_active ?? true),
                    'label' => 'Enable product',
                    'hint' => 'Disabled products stay inactive system-wide',
                ])
                @include('admin.partials.toggle-switch', [
                    'id' => 'is_visible_in_store',
                    'name' => 'is_visible_in_store',
                    'checked' => old('is_visible_in_store', $product?->is_visible_in_store ?? true),
                    'label' => 'Show in main website/store',
                    'hint' => 'Turn off to hide from shop, home & search while keeping available for landing pages',
                ])
                @include('admin.partials.toggle-switch', [
                    'id' => 'is_hot',
                    'name' => 'is_hot',
                    'checked' => old('is_hot', $product?->is_hot),
                    'label' => 'Hot Deal',
                ])
                @include('admin.partials.toggle-switch', [
                    'id' => 'is_flash',
                    'name' => 'is_flash',
                    'checked' => old('is_flash', $product?->is_flash),
                    'label' => 'Flash sale',
                ])
                @include('admin.partials.toggle-switch', [
                    'id' => 'is_featured',
                    'name' => 'is_featured',
                    'checked' => old('is_featured', $product?->is_featured),
                    'label' => 'Featured',
                ])
            </div>
        </section>

        <section id="section-shipping" class="admin-card admin-card-body scroll-mt-28">
            <div class="flex items-center gap-2 mb-3 border-b border-line/60 pb-2">
                <svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
                <h2 class="admin-section-title !mb-0">Delivery & Shipping</h2>
            </div>

            <div class="space-y-3">
                @include('admin.partials.toggle-switch', [
                    'id' => 'free_delivery',
                    'name' => 'free_delivery',
                    'checked' => old('free_delivery', $product?->free_delivery),
                    'label' => 'Free delivery',
                    'hint' => 'Skip shipping charge for this product',
                ])

                <div>
                    <label class="admin-label text-xs mb-1" for="delivery_charge">Flat Delivery Charge</label>
                    <div class="flex items-stretch">
                        <span class="inline-flex items-center justify-center px-2.5 border border-r-0 border-ink/15 bg-canvas text-xs font-semibold text-muted rounded-l select-none">৳</span>
                        <input
                            id="delivery_charge"
                            name="delivery_charge"
                            type="number"
                            step="0.01"
                            min="0"
                            value="{{ old('delivery_charge', $product?->delivery_charge) }}"
                            class="field !rounded-l-none text-xs py-1.5 flex-1"
                            placeholder="0.00 (All areas fallback)"
                        >
                    </div>
                    <p class="mt-1 text-[10px] text-muted">Flat delivery charge across all areas.</p>
                    @error('delivery_charge') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-md border border-line/80 bg-surface-raised/50 p-2.5 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-muted">Area-based Rates</span>
                        <span class="text-[10px] text-muted">Optional</span>
                    </div>

                    @php
                        $savedCharges = old('delivery_charges', $product?->delivery_charges ?? []);
                    @endphp

                    <div class="space-y-1.5">
                        <select id="custom-area-select" class="field w-full text-xs py-1.5">
                            <option value="">-- Select Shipping Area --</option>
                            @foreach($shippingCharges ?? [] as $sc)
                                <option value="{{ $sc->id }}" data-name="{{ $sc->name }}" data-default="{{ number_format((float)$sc->amount, 2) }}">
                                    {{ $sc->name }} (Default: ৳{{ number_format((float)$sc->amount, 2) }})
                                </option>
                            @endforeach
                        </select>

                        <div class="grid grid-cols-[1fr_auto] gap-2 items-center">
                            <div class="flex items-stretch">
                                <span class="inline-flex items-center justify-center px-2.5 border border-r-0 border-ink/15 bg-canvas text-xs font-semibold text-muted rounded-l select-none">৳</span>
                                <input
                                    type="number"
                                    id="custom-area-amount"
                                    step="0.01"
                                    min="0"
                                    placeholder="Rate"
                                    class="field !rounded-l-none text-xs py-1.5 flex-1 min-w-0"
                                >
                            </div>
                            <button
                                type="button"
                                id="add-area-rate-btn"
                                class="admin-btn-primary text-xs px-3 py-1.5 h-[34px] flex items-center justify-center gap-1 font-medium whitespace-nowrap"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Add Area</span>
                            </button>
                        </div>
                    </div>

                    <div id="configured-area-rates" class="space-y-1.5 pt-1">
                        @foreach($shippingCharges ?? [] as $sc)
                            @php
                                $val = $savedCharges[$sc->id] ?? $savedCharges[(string)$sc->id] ?? null;
                                $hasVal = $val !== null && $val !== '';
                            @endphp
                            <div
                                class="area-rate-row flex items-center justify-between gap-2 p-2 rounded border border-line bg-surface text-xs {{ $hasVal ? '' : 'hidden' }}"
                                data-area-id="{{ $sc->id }}"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-foreground truncate text-xs">{{ $sc->name }}</div>
                                    <div class="text-[10px] text-muted">Default: ৳{{ number_format((float)$sc->amount, 2) }}</div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center justify-center px-1.5 border border-r-0 border-ink/15 bg-canvas text-[11px] font-semibold text-muted rounded-l select-none">৳</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="delivery_charges[{{ $sc->id }}]"
                                            value="{{ $val }}"
                                            placeholder="{{ number_format((float)$sc->amount, 2) }}"
                                            class="field !rounded-l-none !w-20 text-xs py-1 px-1.5 font-medium area-rate-input"
                                            {{ $hasVal ? '' : 'disabled' }}
                                        >
                                    </div>
                                    <button
                                        type="button"
                                        class="text-accent hover:text-accent-deep hover:bg-accent/10 p-1 rounded transition remove-area-rate-btn"
                                        title="Remove area rate"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="no-area-rates-msg" class="text-[10px] text-muted italic {{ count(array_filter($savedCharges, fn($v) => $v !== null && $v !== '')) > 0 ? 'hidden' : '' }}">
                        No custom area rates. Default or flat rates apply.
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
(() => {
    const categorySelect = document.querySelector('[data-category-select]');
    const subcategorySelect = document.querySelector('[data-subcategory-select]');
    const childcategorySelect = document.querySelector('[data-childcategory-select]');

    const filterOptions = (select, attr, value) => {
        if (! select) {
            return;
        }

        const selectedValue = String(value || '');

        [...select.options].forEach((option, index) => {
            if (index === 0) {
                return;
            }

            const match = selectedValue === '' || String(option.getAttribute(attr) || '') === selectedValue;
            option.hidden = ! match;
            option.disabled = ! match;

            if (! match && option.selected) {
                option.selected = false;
            }
        });
    };

    const syncTaxonomy = () => {
        filterOptions(subcategorySelect, 'data-category-id', categorySelect?.value || '');
        filterOptions(childcategorySelect, 'data-subcategory-id', subcategorySelect?.value || '');
    };

    categorySelect?.addEventListener('change', () => {
        if (subcategorySelect) {
            subcategorySelect.value = '';
        }
        if (childcategorySelect) {
            childcategorySelect.value = '';
        }
        syncTaxonomy();
    });
    subcategorySelect?.addEventListener('change', () => {
        if (childcategorySelect) {
            childcategorySelect.value = '';
        }
        syncTaxonomy();
    });
    syncTaxonomy();

    const objectUrls = new Set();

    const revokePreviewUrls = (container) => {
        if (! container) {
            return;
        }

        container.querySelectorAll('img[data-object-url]').forEach((img) => {
            const url = img.getAttribute('data-object-url');
            if (url) {
                URL.revokeObjectURL(url);
                objectUrls.delete(url);
            }
        });
    };

    const formatFileSize = (bytes) => {
        if (bytes < 1024) {
            return `${bytes} B`;
        }
        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(0)} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    const fileKey = (file) => `${file.name}:${file.size}:${file.lastModified}`;

    const makePendingRemoveControls = (onRemove) => {
        const overlay = document.createElement('button');
        overlay.type = 'button';
        overlay.className = 'wc-gallery-item__remove-overlay';
        overlay.title = 'Remove';
        overlay.setAttribute('aria-label', 'Remove');
        overlay.textContent = '×';
        overlay.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            onRemove();
        });

        const link = document.createElement('button');
        link.type = 'button';
        link.className = 'wc-gallery-item__remove-link';
        link.textContent = 'Remove';
        link.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            onRemove();
        });

        return { overlay, link };
    };

    const renderFilePreviews = (files, container, { replace = true, style = 'gallery', mode = null, onRemove = null } = {}) => {
        if (! container) {
            return;
        }

        if (replace) {
            revokePreviewUrls(container);
            container.innerHTML = '';
        }

        const imageFiles = [...files].filter((file) => file.type.startsWith('image/'));
        const previewMode = mode || container.getAttribute('data-preview-mode') || 'cards';

        if (imageFiles.length === 0) {
            if (previewMode !== 'wc-tile') {
                container.classList.add('hidden');
            }

            return;
        }

        container.classList.remove('hidden');

        if (previewMode !== 'wc-tile') {
            if (style === 'gallery') {
                container.classList.add('grid', 'grid-cols-2', 'sm:grid-cols-3', 'gap-2.5');
            } else if (style === 'meta') {
                container.classList.add('grid', 'grid-cols-1', 'gap-2.5');
            }
        }

        imageFiles.forEach((file, index) => {
            const url = URL.createObjectURL(file);
            objectUrls.add(url);

            if (previewMode === 'wc-tile') {
                const wrap = document.createElement('div');
                const aspect = container.getAttribute('data-preview-aspect');
                wrap.className = aspect === 'meta'
                    ? 'wc-gallery-item wc-gallery-item--meta is-new'
                    : 'wc-gallery-item is-new';

                const thumb = document.createElement(aspect === 'meta' ? 'button' : 'div');
                thumb.className = aspect === 'meta'
                    ? 'wc-gallery-item__thumb wc-gallery-item__thumb--replace'
                    : 'wc-gallery-item__thumb';
                if (aspect === 'meta') {
                    thumb.type = 'button';
                    thumb.title = 'Click to replace image';
                    thumb.setAttribute('data-meta-replace-trigger', '');
                }

                const img = document.createElement('img');
                img.src = url;
                img.alt = file.name;
                img.setAttribute('data-object-url', url);

                const badge = document.createElement('span');
                badge.className = 'wc-gallery-item__badge';
                badge.textContent = 'New';

                thumb.append(img, badge);

                if (aspect === 'meta') {
                    const hint = document.createElement('span');
                    hint.className = 'wc-gallery-item__replace-hint';
                    hint.textContent = 'Replace';
                    thumb.appendChild(hint);
                }

                const actions = document.createElement('div');
                actions.className = 'wc-gallery-item__actions';

                const name = document.createElement('span');
                name.className = 'truncate text-[11px] text-muted';
                name.title = file.name;
                name.textContent = file.name;
                actions.appendChild(name);

                let mediaNode = thumb;

                if (aspect === 'meta') {
                    const wrapThumb = document.createElement('div');
                    wrapThumb.className = 'wc-gallery-item__thumb-wrap';
                    wrapThumb.appendChild(thumb);

                    actions.classList.add('wc-gallery-item__actions--meta');
                    actions.innerHTML = '';

                    const replace = document.createElement('label');
                    replace.htmlFor = 'meta_image_file';
                    replace.className = 'wc-gallery-item__replace-link';
                    replace.textContent = 'Replace';
                    actions.appendChild(replace);

                    if (typeof onRemove === 'function') {
                        const controls = makePendingRemoveControls(() => onRemove(file));
                        wrapThumb.appendChild(controls.overlay);
                        actions.appendChild(controls.link);
                    }

                    mediaNode = wrapThumb;
                } else if (typeof onRemove === 'function') {
                    const controls = makePendingRemoveControls(() => onRemove(file));
                    thumb.appendChild(controls.overlay);
                    actions.appendChild(controls.link);
                }

                wrap.append(mediaNode, actions);
                container.appendChild(wrap);

                return;
            }

            const wrap = document.createElement('div');
            wrap.className = 'overflow-hidden rounded-lg border border-ink/10 bg-white shadow-sm';

            const frame = document.createElement('div');
            frame.className = style === 'meta'
                ? 'relative aspect-[1.91/1] bg-mist'
                : 'relative aspect-[3/4] bg-mist';

            const img = document.createElement('img');
            img.src = url;
            img.alt = file.name;
            img.className = 'h-full w-full object-cover';
            img.setAttribute('data-object-url', url);

            const badge = document.createElement('span');
            badge.className = 'absolute left-1.5 top-1.5 rounded bg-sage px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white';
            badge.textContent = style === 'meta' ? 'New meta' : (index === 0 ? 'New · 1st' : 'New');

            frame.append(img, badge);

            const meta = document.createElement('div');
            meta.className = 'space-y-0.5 px-2 py-1.5';

            const name = document.createElement('p');
            name.className = 'truncate text-[11px] font-medium text-ink';
            name.textContent = file.name;
            name.title = file.name;

            const details = document.createElement('p');
            details.className = 'text-[10px] text-muted';
            details.textContent = formatFileSize(file.size);

            const probe = new Image();
            probe.onload = () => {
                details.textContent = `${probe.naturalWidth}×${probe.naturalHeight} · ${formatFileSize(file.size)}`;
            };
            probe.src = url;

            meta.append(name, details);
            wrap.append(frame, meta);
            container.appendChild(wrap);
        });
    };

    const renderVideoPreviews = (files, container, { onRemove = null } = {}) => {
        if (! container) {
            return;
        }

        revokePreviewUrls(container);
        container.innerHTML = '';

        const videoFiles = [...files].filter((file) => file.type.startsWith('video/'));

        videoFiles.forEach((file) => {
            const url = URL.createObjectURL(file);
            objectUrls.add(url);

            const wrap = document.createElement('div');
            wrap.className = 'wc-gallery-item wc-gallery-item--video is-new';

            const thumb = document.createElement('div');
            thumb.className = 'wc-gallery-item__thumb';

            const video = document.createElement('video');
            video.src = url;
            video.muted = true;
            video.preload = 'metadata';
            video.setAttribute('data-object-url', url);

            const badge = document.createElement('span');
            badge.className = 'wc-gallery-item__badge';
            badge.textContent = 'New';

            thumb.append(video, badge);

            const actions = document.createElement('div');
            actions.className = 'wc-gallery-item__actions';

            const label = document.createElement('span');
            label.className = 'truncate text-[11px] text-muted';
            label.title = file.name;
            label.textContent = `${file.name} · ${formatFileSize(file.size)}`;
            actions.appendChild(label);

            if (typeof onRemove === 'function') {
                const controls = makePendingRemoveControls(() => onRemove(file));
                thumb.appendChild(controls.overlay);
                actions.appendChild(controls.link);
            }

            wrap.append(thumb, actions);
            container.appendChild(wrap);
        });
    };

    const syncAccumulatedFiles = (input, files) => {
        const transfer = new DataTransfer();
        files.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    };

    document.querySelectorAll('[data-image-preview-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const targetId = input.getAttribute('data-preview-target');
            const container = targetId ? document.getElementById(targetId) : null;
            const style = container?.getAttribute('data-preview-style') || 'gallery';
            renderFilePreviews(input.files || [], container, { replace: true, style });
        });
    });

    document.querySelectorAll('[data-meta-image-input]').forEach((input) => {
        const root = input.closest('[data-media-gallery="meta"]');
        const targetId = input.getAttribute('data-preview-target');
        const container = targetId ? document.getElementById(targetId) : null;
        const currentTile = root?.querySelector('[data-meta-current]');
        const addTile = root?.querySelector('[data-meta-add-tile]');
        const metaMediaInput = root?.querySelector('#meta_image_media_path');
        const metaMediaBtns = root?.querySelectorAll('[data-meta-open-media]');

        const applyMetaMedia = (item) => {
            if (!item) return;

            input.value = '';
            if (metaMediaInput) metaMediaInput.value = item.path;

            if (container) {
                container.innerHTML = `
                    <div class="wc-gallery-item wc-gallery-item--meta is-new">
                        <div class="wc-gallery-item__thumb-wrap">
                            <div class="wc-gallery-item__thumb">
                                <img src="${item.url}" alt="${item.name}">
                                <span class="wc-gallery-item__badge">From Media</span>
                            </div>
                            <button type="button" class="wc-gallery-item__remove-overlay" title="Remove meta image">×</button>
                        </div>
                        <div class="wc-gallery-item__actions wc-gallery-item__actions--meta">
                            <span class="truncate text-[11px] text-muted">${item.name}</span>
                            <button type="button" class="wc-gallery-item__remove-link">Remove</button>
                        </div>
                    </div>
                `;

                const handleRemove = () => {
                    container.innerHTML = '';
                    if (metaMediaInput) metaMediaInput.value = '';
                    if (currentTile) currentTile.classList.remove('is-hidden');
                    if (addTile) addTile.classList.toggle('is-hidden', Boolean(currentTile));
                    metaMediaBtns?.forEach((b) => b.classList.toggle('is-hidden', Boolean(currentTile)));
                };

                container.querySelector('.wc-gallery-item__remove-overlay')?.addEventListener('click', handleRemove);
                container.querySelector('.wc-gallery-item__remove-link')?.addEventListener('click', handleRemove);
            }

            if (currentTile) currentTile.classList.add('is-hidden');
            if (addTile) addTile.classList.add('is-hidden');
            metaMediaBtns?.forEach((b) => b.classList.add('is-hidden'));
        };

        const openMetaPicker = (tab = 'upload') => {
            if (typeof window.openMediaPicker === 'function') {
                window.openMediaPicker({
                    multiple: false,
                    filter: 'image',
                    initialTab: tab,
                    title: 'Select Meta Image',
                    buttonText: 'Set meta image',
                    onSelect: applyMetaMedia
                });
            } else {
                input.click();
            }
        };

        metaMediaBtns?.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openMetaPicker('library');
            });
        });

        if (addTile) {
            addTile.addEventListener('click', (e) => {
                if (typeof window.openMediaPicker === 'function') {
                    e.preventDefault();
                    e.stopPropagation();
                    openMetaPicker('upload');
                }
            });
        }

        root?.addEventListener('click', (event) => {
            if (event.target.closest('.wc-gallery-item__remove-overlay, .wc-gallery-item__remove-link')) {
                return;
            }

            if (event.target.closest('[data-meta-replace-trigger]')) {
                event.preventDefault();
                openMetaPicker();
            }
        });

        input.addEventListener('change', refreshMetaPreview);
    });

    document.querySelectorAll('[data-media-accumulate]').forEach((input) => {
        const accumulated = [];

        const refreshPreviews = () => {
            syncAccumulatedFiles(input, accumulated);

            const imageTargetId = input.getAttribute('data-preview-target');
            const videoTargetId = input.getAttribute('data-video-preview-target');

            const removePending = (file) => {
                const key = fileKey(file);
                const index = accumulated.findIndex((item) => fileKey(item) === key);
                if (index >= 0) {
                    accumulated.splice(index, 1);
                    refreshPreviews();
                }
            };

            if (imageTargetId) {
                const container = document.getElementById(imageTargetId);
                const style = container?.getAttribute('data-preview-style') || 'gallery';
                renderFilePreviews(accumulated, container, {
                    replace: true,
                    style,
                    mode: container?.getAttribute('data-preview-mode'),
                    onRemove: removePending,
                });
            }

            if (videoTargetId) {
                renderVideoPreviews(accumulated, document.getElementById(videoTargetId), {
                    onRemove: removePending,
                });
            }
        };

        input.addEventListener('change', () => {
            const incoming = [...(input.files || [])];
            incoming.forEach((file) => {
                const duplicate = accumulated.some((existing) => fileKey(existing) === fileKey(file));

                if (! duplicate) {
                    accumulated.push(file);
                }
            });

            refreshPreviews();
        });
    });

    const youtubeRoot = document.querySelector('[data-youtube-urls]');
    if (youtubeRoot) {
        const input = youtubeRoot.querySelector('[data-youtube-input]');
        const list = youtubeRoot.querySelector('[data-youtube-list]');
        const addBtn = youtubeRoot.querySelector('[data-youtube-add]');

        const addYoutubeUrl = () => {
            const value = (input?.value || '').trim();
            if (! value || ! list) {
                return;
            }

            const exists = [...list.querySelectorAll('input[name="youtube_urls[]"]')]
                .some((el) => el.value === value);

            if (exists) {
                input.value = '';
                return;
            }

            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 rounded-md border border-ink/10 bg-mist/40 px-2.5 py-1.5';
            row.setAttribute('data-youtube-row', '');

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'youtube_urls[]';
            hidden.value = value;

            const label = document.createElement('span');
            label.className = 'min-w-0 flex-1 truncate text-xs text-ink';
            label.textContent = value;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'text-xs font-medium text-accent hover:underline';
            removeBtn.setAttribute('data-youtube-remove', '');
            removeBtn.textContent = 'Remove';

            row.append(hidden, label, removeBtn);
            list.appendChild(row);
            input.value = '';
        };

        addBtn?.addEventListener('click', addYoutubeUrl);
        input?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addYoutubeUrl();
            }
        });

        list?.addEventListener('click', (event) => {
            const removeBtn = event.target.closest('[data-youtube-remove]');
            if (removeBtn) {
                removeBtn.closest('[data-youtube-row]')?.remove();
            }
        });
    }
})();
</script>

{{-- Gemini AI Copywriter Studio Modal --}}
@include('admin.products._ai_modal')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css">
    <link rel="stylesheet" href="https://fonts.cdnfonts.com/css/solaimanlipi">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $description = window.jQuery?.('#description');

            if (! $description?.length || typeof $description.summernote !== 'function') {
                return;
            }

            $description.summernote({
                height: 320,
                minHeight: 180,
                maxHeight: null,
                focus: false,
                disableDragAndDrop: false,
                dialogsInBody: true,
                placeholder: 'Write product details…',
                fontNames: [
                    'SolaimanLipi',
                    'Arial',
                    'Arial Black',
                    'Comic Sans MS',
                    'Courier New',
                    'Helvetica Neue',
                    'Helvetica',
                    'Impact',
                    'Tahoma',
                    'Times New Roman',
                    'Verdana',
                    'Figtree',
                ],
                fontNamesIgnoreCheck: ['SolaimanLipi', 'Figtree'],
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']],
                ],
                styleTags: ['p', 'h2', 'h3', 'h4', 'blockquote'],
                styleTags: ['p', 'h2', 'h3', 'h4', 'blockquote'],
                callbacks: {
                    onImageUpload: function (files) {
                        Array.from(files).forEach((file) => {
                            const reader = new FileReader();
                            reader.onload = () => {
                                $description.summernote('insertImage', reader.result, (image) => {
                                    image.css('max-width', '100%');
                                });
                            };
                            reader.readAsDataURL(file);
                        });
                    },
                },
            });

            $description.closest('form').on('submit', () => {
                $description.val($description.summernote('code'));
            });

            // Area-based delivery charge dynamic manager
            const areaSelect = document.getElementById('custom-area-select');
            const areaAmount = document.getElementById('custom-area-amount');
            const addAreaBtn = document.getElementById('add-area-rate-btn');
            const areaRatesContainer = document.getElementById('configured-area-rates');
            const noAreaRatesMsg = document.getElementById('no-area-rates-msg');

            function syncAreaEmptyState() {
                if (!areaRatesContainer || !noAreaRatesMsg) return;
                const visible = areaRatesContainer.querySelectorAll('.area-rate-row:not(.hidden)');
                noAreaRatesMsg.classList.toggle('hidden', visible.length > 0);
            }

            if (addAreaBtn && areaSelect && areaRatesContainer) {
                addAreaBtn.addEventListener('click', function () {
                    const areaId = areaSelect.value;
                    const amount = areaAmount ? areaAmount.value : '';
                    if (!areaId) {
                        areaSelect.focus();
                        return;
                    }

                    const row = areaRatesContainer.querySelector(`.area-rate-row[data-area-id="${areaId}"]`);
                    if (row) {
                        row.classList.remove('hidden');
                        const input = row.querySelector('.area-rate-input');
                        if (input) {
                            input.removeAttribute('disabled');
                            if (amount !== '') {
                                input.value = parseFloat(amount).toFixed(2);
                            }
                            input.focus();
                        }
                        if (areaAmount) areaAmount.value = '';
                        areaSelect.value = '';
                        syncAreaEmptyState();
                    }
                });

                areaRatesContainer.addEventListener('click', function (e) {
                    const removeBtn = e.target.closest('.remove-area-rate-btn');
                    if (!removeBtn) return;

                    const row = removeBtn.closest('.area-rate-row');
                    if (row) {
                        row.classList.add('hidden');
                        const input = row.querySelector('.area-rate-input');
                        if (input) {
                            input.value = '';
                            input.setAttribute('disabled', 'disabled');
                        }
                        syncAreaEmptyState();
                    }
                });
            }

            // Product Gallery Sortable Re-ordering (Drag & Drop + Move Arrows)
            const gallerySortable = document.querySelector('[data-gallery-sortable]');
            if (gallerySortable) {
                let draggedItem = null;

                function updateGalleryOrderNumbers() {
                    const items = gallerySortable.querySelectorAll('[data-gallery-item]');
                    items.forEach((item, index) => {
                        const badge = item.querySelector('[data-order-badge]');
                        if (badge) {
                            badge.textContent = '#' + (index + 1);
                        }
                    });
                }

                gallerySortable.addEventListener('dragstart', (e) => {
                    const item = e.target.closest('[data-gallery-item]');
                    if (!item) return;
                    draggedItem = item;
                    item.classList.add('opacity-50');
                    e.dataTransfer.effectAllowed = 'move';
                });

                gallerySortable.addEventListener('dragend', (e) => {
                    const item = e.target.closest('[data-gallery-item]');
                    if (item) item.classList.remove('opacity-50');
                    draggedItem = null;
                    updateGalleryOrderNumbers();
                });

                gallerySortable.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    const targetItem = e.target.closest('[data-gallery-item]');
                    if (!targetItem || targetItem === draggedItem) return;

                    const rect = targetItem.getBoundingClientRect();
                    const next = (e.clientX - rect.left) / (rect.right - rect.left) > 0.5;
                    targetItem.parentNode.insertBefore(draggedItem, next ? targetItem.nextSibling : targetItem);
                });

                gallerySortable.addEventListener('click', (e) => {
                    const moveBtn = e.target.closest('[data-move-image]');
                    if (!moveBtn) return;
                    const item = moveBtn.closest('[data-gallery-item]');
                    if (!item) return;

                    const direction = moveBtn.dataset.moveImage;
                    if (direction === 'left' && item.previousElementSibling && item.previousElementSibling.matches('[data-gallery-item]')) {
                        item.parentNode.insertBefore(item, item.previousElementSibling);
                    } else if (direction === 'right' && item.nextElementSibling && item.nextElementSibling.matches('[data-gallery-item]')) {
                        item.parentNode.insertBefore(item.nextElementSibling, item);
                    }
                    updateGalleryOrderNumbers();
                });

                window.updateGalleryOrderNumbers = updateGalleryOrderNumbers;

                const galleryMediaBtn = document.getElementById('btn-gallery-from-media');
                const galleryAddLabel = gallerySortable.querySelector('label.wc-gallery-add[for="images"]');

                function handleAddGalleryMedia(items) {
                    if (!Array.isArray(items)) {
                        items = items ? [items] : [];
                    }
                    if (items.length === 0) return;

                    const addLabel = gallerySortable.querySelector('.wc-gallery-add');

                    items.forEach((item) => {
                        const el = document.createElement('div');
                        el.className = 'wc-gallery-item is-media';
                        el.setAttribute('draggable', 'true');
                        el.setAttribute('data-gallery-item', '');
                        el.innerHTML = `
                            <input type="hidden" name="media_images[]" value="${item.path}">
                            <div class="wc-gallery-item__thumb relative">
                                <img src="${item.url}" alt="" onerror="this.style.opacity='0.3'">
                                <span class="wc-gallery-item__badge">Media</span>
                                <span class="absolute top-1 left-1 rounded bg-black/70 px-1 py-0.5 font-mono text-[9px] font-bold text-white pointer-events-none" data-order-badge>#</span>
                                <button type="button" class="wc-gallery-item__remove-overlay" title="Remove image" onclick="this.closest('.wc-gallery-item').remove(); window.updateGalleryOrderNumbers?.();">×</button>
                            </div>
                            <div class="wc-gallery-item__actions flex items-center justify-between gap-1">
                                <label class="wc-gallery-item__primary truncate">
                                    <input type="radio" name="primary_image_path" value="${item.path}">
                                    <span>Primary</span>
                                </label>
                                <div class="flex items-center gap-0.5">
                                    <button type="button" class="rounded p-1 text-xs leading-none text-muted transition hover:bg-mist hover:text-ink" data-move-image="left" title="Move earlier">◀</button>
                                    <button type="button" class="rounded p-1 text-xs leading-none text-muted transition hover:bg-mist hover:text-ink" data-move-image="right" title="Move later">▶</button>
                                </div>
                            </div>
                        `;
                        if (addLabel) {
                            gallerySortable.insertBefore(el, addLabel);
                        } else {
                            gallerySortable.appendChild(el);
                        }
                    });

                    updateGalleryOrderNumbers();
                }

                const galleryAddBtn = document.getElementById('btn-gallery-add-images');
                if (galleryAddBtn) {
                    galleryAddBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof window.openMediaPicker === 'function') {
                            window.openMediaPicker({
                                multiple: true,
                                filter: 'image',
                                initialTab: 'library',
                                title: 'Add Images to Gallery',
                                buttonText: 'Add to gallery',
                                onSelect: handleAddGalleryMedia
                            });
                        }
                    });
                }
            }

            // Unlimited Stock Checkbox Toggle
            const unlimitedCheckbox = document.getElementById('is_unlimited_stock');
            if (unlimitedCheckbox && stockInput) {
                unlimitedCheckbox.addEventListener('change', () => {
                    if (unlimitedCheckbox.checked) {
                        stockInput.readOnly = true;
                        stockInput.classList.add('opacity-50', 'bg-slate-50');
                        stockInput.placeholder = 'Unlimited (∞)';
                    } else {
                        stockInput.readOnly = false;
                        stockInput.classList.remove('opacity-50', 'bg-slate-50');
                        stockInput.placeholder = '0';
                    }
                });
            }

            // Variant Stock Synchronization with Main Stock Field
            const stockInput = document.getElementById('stock');
            const stockHint = document.getElementById('stock-variants-hint');
            const stockTotalDisplay = document.getElementById('stock-variants-total');
            function syncVariantStockTotal() {
                const variantStockInputs = document.querySelectorAll('[data-field-stock]');
                if (!stockInput) return;

                if (variantStockInputs.length > 0) {
                    let total = 0;
                    variantStockInputs.forEach((inp) => {
                        const val = parseInt(inp.value, 10);
                        if (!isNaN(val) && val > 0) total += val;
                    });

                    stockInput.value = total;
                    if (stockTotalDisplay) stockTotalDisplay.textContent = total;
                    if (stockHint) stockHint.classList.remove('hidden');
                } else {
                    if (stockHint) stockHint.classList.add('hidden');
                }
            }

            document.addEventListener('input', (e) => {
                if (e.target && e.target.matches('[data-field-stock]')) {
                    syncVariantStockTotal();
                }
            });

            const variantList = document.querySelector('[data-variant-list]');
            if (variantList) {
                const observer = new MutationObserver(() => {
                    syncVariantStockTotal();
                });
                observer.observe(variantList, { childList: true, subtree: true });
            }

            // Live Google Search Preview & Counters
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            const metaTitleInput = document.getElementById('meta_title');
            const metaDescInput = document.getElementById('meta_description');

            const previewTitle = document.getElementById('seo-preview-title');
            const previewDesc = document.getElementById('seo-preview-desc');
            const previewSlug = document.getElementById('seo-preview-slug');

            const metaTitleCount = document.getElementById('meta-title-count');
            const metaDescCount = document.getElementById('meta-desc-count');

            function updateSeoPreview() {
                const titleText = (metaTitleInput && metaTitleInput.value.trim()) || (nameInput && nameInput.value.trim()) || 'Product Title Preview';
                if (previewTitle) previewTitle.textContent = titleText;

                if (metaTitleInput && metaTitleCount) {
                    const len = metaTitleInput.value.length;
                    metaTitleCount.textContent = len;
                    metaTitleCount.parentElement.classList.toggle('text-accent', len > 60);
                    metaTitleCount.parentElement.classList.toggle('text-emerald-700', len >= 40 && len <= 60);
                }

                const descText = (metaDescInput && metaDescInput.value.trim()) || 'Add a meta description to see how this product appears in Google search engine results.';
                if (previewDesc) previewDesc.textContent = descText;

                if (metaDescInput && metaDescCount) {
                    const len = metaDescInput.value.length;
                    metaDescCount.textContent = len;
                    metaDescCount.parentElement.classList.toggle('text-accent', len > 160);
                    metaDescCount.parentElement.classList.toggle('text-emerald-700', len >= 120 && len <= 160);
                }

                const slugText = (slugInput && slugInput.value.trim()) || 'product-slug';
                if (previewSlug) previewSlug.textContent = slugText;
            }

            if (metaTitleInput) metaTitleInput.addEventListener('input', updateSeoPreview);
            if (metaDescInput) metaDescInput.addEventListener('input', updateSeoPreview);
            if (nameInput) nameInput.addEventListener('input', updateSeoPreview);
            if (slugInput) slugInput.addEventListener('input', updateSeoPreview);
            updateSeoPreview();
        });
    </script>
@endpush
