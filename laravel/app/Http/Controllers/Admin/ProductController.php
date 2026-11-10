<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVideo;
use App\Models\ShippingCharge;
use App\Models\Size;
use App\Models\Subcategory;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();
        $pageTitle = 'All Products';
        $pageSubtitle = 'Manage your store catalog.';
        $listFilter = 'all';

        $stockFilter = $request->string('stock')->toString();
        $statusFilter = $request->string('status')->toString();
        $dealFilter = $request->string('deal')->toString();

        $products = Product::query()
            ->with(['category', 'images', 'brand'])
            ->when($filter === 'pending', function ($query) use (&$pageTitle, &$pageSubtitle, &$listFilter): void {
                $pageTitle = 'Pending Products';
                $pageSubtitle = 'Inactive products waiting for activation.';
                $listFilter = 'pending';
                $query->pending();
            })
            ->when($filter !== 'pending', function ($query): void {
                $query->inhouse();
            })
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($stockFilter === 'in_stock', fn ($query) => $query->where('stock', '>', 5))
            ->when($stockFilter === 'low_stock', fn ($query) => $query->where('stock', '>', 0)->where('stock', '<=', 5))
            ->when($stockFilter === 'out_of_stock', fn ($query) => $query->where('stock', '<=', 0))
            ->when($statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($dealFilter === 'hot', fn ($query) => $query->where('is_hot', true))
            ->when($dealFilter === 'regular', fn ($query) => $query->where('is_hot', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'pageTitle' => $pageTitle,
            'pageSubtitle' => $pageSubtitle,
            'listFilter' => $listFilter,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', $this->formCatalog());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedProduct($request);

        $product = DB::transaction(function () use ($request, $validated): Product {
            $slug = $this->uniqueSlug($validated['slug'] ?? null, $validated['name']);
            $sku = filled($validated['sku'] ?? null)
                ? $validated['sku']
                : $this->generateSku($validated['name']);

            $attributes = $this->productAttributes($request, $validated);

            $metaImage = $this->storeOptionalImage($request, 'meta_image_file', 'products/meta');
            if (! $metaImage && $request->filled('meta_image_media_path') && ImageUploadService::exists($request->input('meta_image_media_path'))) {
                $metaImage = ImageUploadService::sanitizePath($request->input('meta_image_media_path'));
            }

            $product = Product::query()->create([
                ...$attributes,
                'slug' => $slug,
                'sku' => $sku,
                'source' => 'inhouse',
                'is_active' => $request->boolean('is_active', true),
                'is_visible_in_store' => $request->boolean('is_visible_in_store', true),
                'meta_image' => $metaImage,
            ]);

            $this->storeImages($request, $product);
            $this->storeVideos($request, $product);
            $this->syncPrimaryImage($request, $product);
            $this->syncYoutubeVideoId($product);
            $this->syncVariants($request, $product);

            return $product;
        });

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'subcategory', 'childcategory', 'brand', 'images', 'videos', 'variants.color', 'variants.size']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $product->load(['images', 'videos', 'variants.color', 'variants.size']);

        return view('admin.products.edit', [
            'product' => $product,
            ...$this->formCatalog(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validatedProduct($request, $product);

        DB::transaction(function () use ($request, $product, $validated): void {
            $slug = $this->uniqueSlug($validated['slug'] ?? null, $validated['name'], $product->id);
            $attributes = $this->productAttributes($request, $validated);

            if ($request->hasFile('meta_image_file')) {
                if ($product->meta_image) {
                    Storage::disk('public')->delete($product->meta_image);
                }
                $attributes['meta_image'] = ImageUploadService::upload(
                    $request->file('meta_image_file'),
                    'products/meta',
                    $product->name
                );
            } elseif ($request->filled('meta_image_media_path') && ImageUploadService::exists($request->input('meta_image_media_path'))) {
                $attributes['meta_image'] = ImageUploadService::sanitizePath($request->input('meta_image_media_path'));
            }

            $product->update([
                ...$attributes,
                'slug' => $slug,
                'sku' => filled($validated['sku'] ?? null) ? $validated['sku'] : $product->sku,
                'is_active' => $request->boolean('is_active'),
                'is_visible_in_store' => $request->boolean('is_visible_in_store'),
            ]);

            $this->storeImages($request, $product);
            $this->storeVideos($request, $product);
            $this->syncPrimaryImage($request, $product);
            $this->syncImagesOrder($request, $product);
            $this->syncYoutubeVideoId($product);
            $this->syncVariants($request, $product);
        });

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        DB::transaction(function () use ($product): void {
            $product->load(['images', 'videos', 'variants']);

            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }

            foreach ($product->videos as $video) {
                if ($video->isUpload() && $video->path) {
                    Storage::disk('public')->delete($video->path);
                }
                $video->delete();
            }

            foreach ($product->variants as $variant) {
                foreach ($variant->storedImagePaths() as $path) {
                    Storage::disk('public')->delete($path);
                }
                $variant->delete();
            }

            if ($product->meta_image) {
                Storage::disk('public')->delete($product->meta_image);
            }

            $product->delete();
        });

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function destroyImage(Request $request, Product $product, ProductImage $image): JsonResponse|RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        try {
            $path = $image->path;
            $wasPrimary = $image->is_primary;
            $image->delete();

            // Safely check if this image path is used anywhere else before deleting the physical file
            if ($path) {
                $isUsedElsewhere = ProductImage::query()->where('path', $path)->exists()
                    || Product::query()->where('meta_image', $path)->exists()
                    || ProductVariant::query()->where('image', $path)->orWhere('image_2', $path)->orWhere('image_3', $path)->orWhere('image_4', $path)->exists()
                    || Category::query()->where('image', $path)->exists()
                    || Brand::query()->where('image', $path)->exists();

                if (! $isUsedElsewhere) {
                    try {
                        Storage::disk('public')->delete($path);
                    } catch (\Throwable $e) {
                        \Log::warning('Physical image delete failed: '.$e->getMessage());
                    }
                }
            }

            if ($wasPrimary) {
                $next = $product->images()->first();
                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image removed from gallery.',
                ]);
            }

            return back()->with('success', 'Image removed from gallery.');
        } catch (\Throwable $e) {
            \Log::error('Image removal error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove image: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to remove image.');
        }
    }

    public function destroyMetaImage(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        if ($product->meta_image) {
            try {
                Storage::disk('public')->delete($product->meta_image);
            } catch (\Throwable $e) {
                \Log::warning('Meta image disk delete failed: '.$e->getMessage());
            }
            $product->update(['meta_image' => null]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Meta image removed.',
            ]);
        }

        return back()->with('success', 'Meta image removed.');
    }

    public function destroyVideo(Request $request, Product $product, ProductVideo $video): JsonResponse|RedirectResponse
    {
        abort_unless($video->product_id === $product->id, 404);

        if ($video->isUpload() && $video->path) {
            try {
                Storage::disk('public')->delete($video->path);
            } catch (\Throwable $e) {
                \Log::warning('Video disk delete failed: '.$e->getMessage());
            }
        }

        $video->delete();
        $this->syncYoutubeVideoId($product);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Video removed.',
            ]);
        }

        return back()->with('success', 'Video removed.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active ? 'Product activated.' : 'Product deactivated.');
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $newProduct = DB::transaction(function () use ($product): Product {
            $product->load(['images', 'videos', 'variants']);

            $newProduct = $product->replicate([
                'slug',
                'sku',
            ]);

            $newProduct->name = $product->name.' (Copy)';
            $newProduct->slug = $this->uniqueSlug(null, $newProduct->name);
            $newProduct->sku = $this->generateSku($newProduct->name);
            $newProduct->is_active = false;
            $newProduct->save();

            foreach ($product->images as $image) {
                $newProduct->images()->create([
                    'path' => $image->path,
                    'is_primary' => $image->is_primary,
                    'sort_order' => $image->sort_order,
                ]);
            }

            foreach ($product->videos as $video) {
                $newProduct->videos()->create([
                    'type' => $video->type,
                    'path' => $video->path,
                    'youtube_id' => $video->youtube_id,
                    'title' => $video->title,
                    'sort_order' => $video->sort_order,
                ]);
            }

            foreach ($product->variants as $variant) {
                $newVariant = $variant->replicate(['product_id', 'sku']);
                $newVariant->product_id = $newProduct->id;
                $newVariant->sku = filled($variant->sku) ? $variant->sku.'-COPY' : null;
                $newVariant->save();
            }

            return $newProduct;
        });

        return redirect()
            ->route('admin.products.edit', $newProduct)
            ->with('success', 'Product duplicated successfully as draft. You can now edit and publish it.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'action' => ['required', Rule::in(['set_deal', 'remove_deal', 'activate', 'deactivate', 'delete'])],
        ]);

        $query = Product::query()->whereIn('id', $validated['product_ids']);

        if ($validated['action'] === 'delete') {
            $productsToDelete = Product::query()
                ->whereIn('id', $validated['product_ids'])
                ->with(['images', 'videos', 'variants'])
                ->get();

            DB::transaction(function () use ($productsToDelete): void {
                foreach ($productsToDelete as $product) {
                    foreach ($product->images as $image) {
                        $path = $image->path;
                        $image->delete();
                        if ($path) {
                            $isUsedElsewhere = ProductImage::query()->where('path', $path)->exists()
                                || Product::query()->where('meta_image', $path)->exists()
                                || ProductVariant::query()->where('image', $path)->orWhere('image_2', $path)->orWhere('image_3', $path)->orWhere('image_4', $path)->exists()
                                || Category::query()->where('image', $path)->exists()
                                || Brand::query()->where('image', $path)->exists();

                            if (! $isUsedElsewhere) {
                                Storage::disk('public')->delete($path);
                            }
                        }
                    }

                    foreach ($product->videos as $video) {
                        if ($video->isUpload() && $video->path) {
                            Storage::disk('public')->delete($video->path);
                        }
                        $video->delete();
                    }

                    foreach ($product->variants as $variant) {
                        foreach ($variant->storedImagePaths() as $path) {
                            Storage::disk('public')->delete($path);
                        }
                        $variant->delete();
                    }

                    if ($product->meta_image) {
                        Storage::disk('public')->delete($product->meta_image);
                    }

                    $product->delete();
                }
            });

            return back()->with('success', 'Selected products deleted successfully.');
        }

        match ($validated['action']) {
            'set_deal' => $query->update(['is_hot' => true, 'is_featured' => true]),
            'remove_deal' => $query->update(['is_hot' => false]),
            'activate' => $query->update(['is_active' => true]),
            'deactivate' => $query->update(['is_active' => false]),
        };

        return back()->with('success', 'Bulk action completed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formCatalog(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(),
            'subcategories' => Subcategory::query()->with('category')->orderBy('name')->get(),
            'childcategories' => Childcategory::query()->with('subcategory')->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'colors' => Color::query()->active()->orderBy('name')->get(),
            'sizes' => Size::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'allAttributes' => Attribute::query()->active()->with('values')->orderBy('sort_order')->orderBy('name')->get(),
            'shippingCharges' => ShippingCharge::query()->active()->orderBy('amount')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProduct(Request $request, ?Product $product = null): array
    {
        $skuRule = ['nullable', 'string', 'max:100', 'unique:products,sku'];
        $slugRule = ['nullable', 'string', 'max:255', 'unique:products,slug'];

        if ($product) {
            $skuRule[3] = 'unique:products,sku,'.$product->id;
            $slugRule[3] = 'unique:products,slug,'.$product->id;
        }

        if ($request->filled('youtube_urls')) {
            $normalizedYoutubeUrls = collect($request->input('youtube_urls', []))
                ->filter(fn ($value): bool => filled($value))
                ->map(fn ($value): string => $this->normalizeYoutubeVideoId((string) $value))
                ->filter(fn (string $value): bool => $value !== '')
                ->values()
                ->all();

            $request->merge(['youtube_urls' => $normalizedYoutubeUrls]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'childcategory_id' => ['nullable', 'exists:childcategories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'sku' => $skuRule,
            'price' => [
                Rule::requiredIf(function () use ($request) {
                    $variants = $request->input('variants');
                    if (! is_array($variants) || empty($variants)) {
                        return true;
                    }

                    // If at least one variant has a valid price, main price is optional
                    foreach ($variants as $v) {
                        if (is_array($v) && isset($v['price']) && $v['price'] !== '' && is_numeric($v['price'])) {
                            return false;
                        }
                    }

                    return true;
                }),
                'nullable',
                'numeric',
                'min:0',
            ],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_unlimited_stock' => ['nullable', 'boolean'],
            'unit' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_image_file' => ['nullable', 'image', 'max:2048'],
            'meta_image_media_path' => ['nullable', 'string', 'max:500'],
            'sold' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'free_delivery' => ['nullable', 'boolean'],
            'delivery_charge' => ['nullable', 'numeric', 'min:0'],
            'delivery_charges' => ['nullable', 'array'],
            'delivery_charges.*' => ['nullable'],
            'is_hot' => ['nullable', 'boolean'],
            'is_flash' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_visible_in_store' => ['nullable', 'boolean'],
            'primary_image_id' => [
                'nullable',
                'integer',
                Rule::exists('product_images', 'id')->where('product_id', $product?->id ?? 0),
            ],
            'primary_image_path' => ['nullable', 'string', 'max:500'],
            'image_order' => ['nullable', 'array'],
            'image_order.*' => ['integer'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
            'media_images' => ['nullable', 'array'],
            'media_images.*' => ['nullable', 'string', 'max:500'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska', 'max:51200'],
            'youtube_urls' => ['nullable', 'array'],
            'youtube_urls.*' => ['nullable', 'string', 'max:255'],
            'has_variants_section' => ['nullable'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.attribute_values' => ['nullable'],
            'variants.*.color_id' => ['nullable'],
            'variants.*.size_id' => ['nullable'],
            'variants.*.size_name' => ['nullable', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_unlimited_stock' => ['nullable', 'boolean'],
            'variants.*.delivery_charge_type' => ['nullable', 'string', 'in:default,free,custom'],
            'variants.*.custom_delivery_inside' => ['nullable', 'numeric', 'min:0'],
            'variants.*.custom_delivery_outside' => ['nullable', 'numeric', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:2048'],
            'variants.*.media_image' => ['nullable', 'string', 'max:500'],
            'variants.*.image_2' => ['nullable', 'image', 'max:2048'],
            'variants.*.image_3' => ['nullable', 'image', 'max:2048'],
            'variants.*.image_4' => ['nullable', 'image', 'max:2048'],
            'variants.*.remove_image' => ['nullable', 'boolean'],
            'variants.*.remove_image_2' => ['nullable', 'boolean'],
            'variants.*.remove_image_3' => ['nullable', 'boolean'],
            'variants.*.remove_image_4' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function productAttributes(Request $request, array $validated): array
    {
        $attributes = collect($validated)
            ->except([
                'image_order',
                'images',
                'media_images',
                'videos',
                'youtube_urls',
                'primary_image_id',
                'primary_image_path',
                'meta_image_file',
                'meta_image_media_path',
                'has_variants_section',
                'variants',
                'sku',
                'slug',
                'is_featured',
                'free_delivery',
                'is_hot',
                'is_flash',
                'is_active',
                'is_visible_in_store',
            ])
            ->all();

        $isHot = $request->boolean('is_hot');
        $isUnlimitedStock = $request->boolean('is_unlimited_stock');

        $deliveryCharges = collect($request->input('delivery_charges', []))
            ->filter(fn ($val) => $val !== null && $val !== '' && is_numeric($val))
            ->map(fn ($val) => number_format((float) $val, 2, '.', ''))
            ->all();

        return [
            ...$attributes,
            'price' => filled($validated['price'] ?? null) ? (float) $validated['price'] : 0,
            'compare_at_price' => filled($validated['compare_at_price'] ?? null) ? (float) $validated['compare_at_price'] : null,
            'stock' => $isUnlimitedStock ? 0 : (int) ($validated['stock'] ?? 0),
            'is_unlimited_stock' => $isUnlimitedStock,
            'delivery_charges' => $deliveryCharges !== [] ? $deliveryCharges : null,
            'sold' => (int) ($validated['sold'] ?? 0),
            'is_featured' => $request->boolean('is_featured') || $isHot,
            'free_delivery' => $request->boolean('free_delivery'),
            'is_hot' => $isHot,
            'is_flash' => $request->boolean('is_flash'),
        ];
    }

    private function normalizeYoutubeVideoId(string $value): string
    {
        $value = trim($value);

        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/', $value, $matches) === 1) {
            return $matches[1];
        }

        return $value;
    }

    private function storeImages(Request $request, Product $product): void
    {
        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        $sortOrder = (int) $product->images()->max('sort_order');

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $sortOrder++;
                $path = ImageUploadService::upload($file, 'products', $product->name);

                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'is_primary' => ! $hasPrimary && $index === 0,
                    'sort_order' => $sortOrder,
                ]);

                if (! $hasPrimary && $index === 0) {
                    $hasPrimary = true;
                }
            }
        }

        if ($request->filled('media_images') && is_array($request->input('media_images'))) {
            foreach ($request->input('media_images') as $index => $rawPath) {
                if (! ImageUploadService::exists($rawPath)) {
                    continue;
                }

                $path = ImageUploadService::sanitizePath($rawPath);
                $sortOrder++;

                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'is_primary' => ! $hasPrimary && $index === 0,
                    'sort_order' => $sortOrder,
                ]);

                if (! $hasPrimary && $index === 0) {
                    $hasPrimary = true;
                }
            }
        }
    }

    private function storeVideos(Request $request, Product $product): void
    {
        $sortOrder = (int) $product->videos()->max('sort_order');

        foreach ($request->input('youtube_urls', []) as $youtubeId) {
            if (! filled($youtubeId)) {
                continue;
            }

            $exists = $product->videos()
                ->where('type', 'youtube')
                ->where('youtube_id', $youtubeId)
                ->exists();

            if ($exists) {
                continue;
            }

            $sortOrder++;

            ProductVideo::query()->create([
                'product_id' => $product->id,
                'type' => 'youtube',
                'youtube_id' => $youtubeId,
                'sort_order' => $sortOrder,
            ]);
        }

        if (! $request->hasFile('videos')) {
            return;
        }

        foreach ($request->file('videos') as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $sortOrder++;
            $path = $file->store('products/videos', 'public');

            ProductVideo::query()->create([
                'product_id' => $product->id,
                'type' => 'upload',
                'path' => $path,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function syncPrimaryImage(Request $request, Product $product): void
    {
        $primaryImageId = $request->integer('primary_image_id');

        if ($primaryImageId > 0 && $product->images()->whereKey($primaryImageId)->exists()) {
            $product->images()->update(['is_primary' => false]);
            $product->images()->whereKey($primaryImageId)->update(['is_primary' => true]);

            return;
        }

        if ($request->filled('primary_image_path')) {
            $path = ImageUploadService::sanitizePath($request->input('primary_image_path'));
            if ($path && $product->images()->where('path', $path)->exists()) {
                $product->images()->update(['is_primary' => false]);
                $product->images()->where('path', $path)->update(['is_primary' => true]);
            }
        }
    }

    private function syncImagesOrder(Request $request, Product $product): void
    {
        $order = $request->input('image_order');
        if (! is_array($order)) {
            return;
        }

        foreach ($order as $index => $imageId) {
            $product->images()
                ->whereKey((int) $imageId)
                ->update(['sort_order' => $index + 1]);
        }
    }

    private function syncYoutubeVideoId(Product $product): void
    {
        $firstYoutubeId = $product->videos()
            ->where('type', 'youtube')
            ->orderBy('sort_order')
            ->value('youtube_id');

        $product->update([
            'youtube_video_id' => $firstYoutubeId,
        ]);
    }

    private function syncVariants(Request $request, Product $product): void
    {
        if (! $request->has('variants') && ! $request->has('has_variants_section')) {
            return;
        }

        $rows = $request->input('variants', []);
        if (! is_array($rows)) {
            $rows = [];
        }

        $imageFields = [
            1 => 'image',
            2 => 'image_2',
            3 => 'image_3',
            4 => 'image_4',
        ];

        $keptIds = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $hasUploadedImage = collect($imageFields)
                ->contains(fn (string $field): bool => $request->hasFile("variants.$index.$field"));

            $hasMediaImage = filled($row['media_image'] ?? null);

            $hasContent = filled($row['name'] ?? null)
                || filled($row['attribute_values'] ?? null)
                || filled($row['color_id'] ?? null)
                || filled($row['size_id'] ?? null)
                || filled($row['size_name'] ?? null)
                || filled($row['sku'] ?? null)
                || filled($row['price'] ?? null)
                || filled($row['compare_at_price'] ?? null)
                || filled($row['stock'] ?? null)
                || filter_var($row['is_unlimited_stock'] ?? false, FILTER_VALIDATE_BOOLEAN)
                || $hasUploadedImage
                || $hasMediaImage;

            if (! $hasContent) {
                continue;
            }

            $variant = null;
            if (! empty($row['id'])) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->whereKey($row['id'])
                    ->first();
            }

            if (! $variant) {
                $variant = new ProductVariant(['product_id' => $product->id]);
            }

            $rawAttrs = $row['attribute_values'] ?? null;
            if (is_string($rawAttrs)) {
                $rawAttrs = json_decode($rawAttrs, true);
            }

            $colorId = ($row['color_id'] ?? null) ?: null;
            $sizeId = ($row['size_id'] ?? null) ?: null;
            if (is_array($rawAttrs)) {
                if (! $colorId && isset($rawAttrs['Color'])) {
                    $colorId = Color::query()->where('name', $rawAttrs['Color'])->value('id');
                }
                if (! $sizeId && isset($rawAttrs['Size'])) {
                    $sizeId = Size::query()->where('name', $rawAttrs['Size'])->value('id');
                }
            }

            $varIsUnlimited = filter_var($row['is_unlimited_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $variant->fill([
                'name' => ($row['name'] ?? null) ?: null,
                'attribute_values' => is_array($rawAttrs) ? $rawAttrs : null,
                'color_id' => $colorId,
                'size_id' => $sizeId,
                'size_name' => ($row['size_name'] ?? null) ?: null,
                'sku' => ($row['sku'] ?? null) ?: null,
                'price' => (($row['price'] ?? null) !== '' && ($row['price'] ?? null) !== null) ? $row['price'] : null,
                'compare_at_price' => (($row['compare_at_price'] ?? null) !== '' && ($row['compare_at_price'] ?? null) !== null)
                    ? $row['compare_at_price']
                    : null,
                'stock' => $varIsUnlimited ? 0 : (int) ($row['stock'] ?? 0),
                'is_unlimited_stock' => $varIsUnlimited,
                'delivery_charge_type' => in_array($row['delivery_charge_type'] ?? 'default', ['default', 'free', 'custom'], true) ? ($row['delivery_charge_type'] ?? 'default') : 'default',
                'custom_delivery_inside' => isset($row['custom_delivery_inside']) && $row['custom_delivery_inside'] !== '' ? $row['custom_delivery_inside'] : null,
                'custom_delivery_outside' => isset($row['custom_delivery_outside']) && $row['custom_delivery_outside'] !== '' ? $row['custom_delivery_outside'] : null,
                'sort_order' => $index,
            ]);

            foreach ($imageFields as $slot => $field) {
                $removeKey = $slot === 1 ? 'remove_image' : 'remove_image_'.$slot;
                $shouldRemove = filter_var($row[$removeKey] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($shouldRemove && $variant->{$field}) {
                    Storage::disk('public')->delete($variant->{$field});
                    $variant->{$field} = null;
                }

                if ($request->hasFile("variants.$index.$field")) {
                    if ($variant->{$field}) {
                        Storage::disk('public')->delete($variant->{$field});
                    }
                    $variant->{$field} = ImageUploadService::upload(
                        $request->file("variants.$index.$field"),
                        'products/variants',
                        $product->name.'-variant'
                    );
                } elseif ($slot === 1 && filled($row['media_image'] ?? null) && ImageUploadService::exists($row['media_image'])) {
                    $variant->{$field} = ImageUploadService::sanitizePath($row['media_image']);
                }
            }

            $variant->save();
            $keptIds[] = $variant->id;
        }

        $orphans = $product->variants()->when(
            $keptIds !== [],
            fn ($query) => $query->whereNotIn('id', $keptIds),
            fn ($query) => $query,
        )->get();

        foreach ($orphans as $orphan) {
            foreach ($orphan->storedImagePaths() as $path) {
                Storage::disk('public')->delete($path);
            }
            $orphan->delete();
        }

        if ($keptIds !== []) {
            $hasAnyUnlimited = $product->variants()->where('is_unlimited_stock', true)->exists();
            $updatePayload = [
                'stock' => (int) $product->variants()->sum('stock'),
                'is_unlimited_stock' => $hasAnyUnlimited,
            ];

            // If base price was left empty or 0, fallback to lowest variant price
            $minVariantPrice = $product->variants()->whereNotNull('price')->where('price', '>', 0)->min('price');
            if ($minVariantPrice !== null && ((float) $product->price <= 0)) {
                $updatePayload['price'] = $minVariantPrice;

                // Also if compare_at_price was left empty, take variant's compare_at_price
                if (empty($product->compare_at_price) || (float) $product->compare_at_price <= 0) {
                    $minVariantCompare = $product->variants()->whereNotNull('compare_at_price')->where('compare_at_price', '>', 0)->min('compare_at_price');
                    if ($minVariantCompare !== null) {
                        $updatePayload['compare_at_price'] = $minVariantCompare;
                    }
                }
            }

            $product->update($updatePayload);
        }
    }

    private function uniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'product';
        $candidate = $base;
        $suffix = 1;

        while (
            Product::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function generateSku(string $name): string
    {
        $base = Str::upper(Str::limit(Str::slug($name, ''), 8, ''));
        $sku = $base.'-'.Str::upper(Str::random(4));

        while (Product::query()->where('sku', $sku)->exists()) {
            $sku = $base.'-'.Str::upper(Str::random(4));
        }

        return $sku;
    }

    private function storeOptionalImage(Request $request, string $field, string $directory): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return ImageUploadService::upload(
            $request->file($field),
            $directory,
            $request->input('name')
        );
    }
}
