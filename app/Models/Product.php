<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'name',
    'slug',
    'category_id',
    'subcategory_id',
    'childcategory_id',
    'brand_id',
    'sku',
    'price',
    'compare_at_price',
    'stock',
    'is_unlimited_stock',
    'unit',
    'description',
    'short_description',
    'note',
    'youtube_video_id',
    'meta_title',
    'meta_keywords',
    'meta_description',
    'meta_image',
    'source',
    'sold',
    'is_featured',
    'free_delivery',
    'delivery_charge',
    'delivery_charges',
    'is_hot',
    'is_flash',
    'is_active',
    'is_visible_in_store',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'delivery_charges' => 'array',
            'stock' => 'integer',
            'is_unlimited_stock' => 'boolean',
            'sold' => 'integer',
            'is_featured' => 'boolean',
            'free_delivery' => 'boolean',
            'is_hot' => 'boolean',
            'is_flash' => 'boolean',
            'is_active' => 'boolean',
            'is_visible_in_store' => 'boolean',
        ];
    }

    /**
     * Get the delivery charge for a specific shipping area/charge ID, if set.
     */
    public function deliveryChargeForArea(int $shippingChargeId): ?string
    {
        if ($this->free_delivery) {
            return '0.00';
        }

        $rates = is_array($this->delivery_charges) ? $this->delivery_charges : [];
        $key = (string) $shippingChargeId;

        if (isset($rates[$key]) && is_numeric($rates[$key])) {
            return number_format((float) $rates[$key], 2, '.', '');
        }

        if ($this->delivery_charge !== null && is_numeric($this->delivery_charge)) {
            return number_format((float) $this->delivery_charge, 2, '.', '');
        }

        return null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function childcategory(): BelongsTo
    {
        return $this->belongsTo(Childcategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    public function primaryImagePath(): ?string
    {
        $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        if ($primary?->path) {
            return $primary->path;
        }

        if ($this->relationLoaded('variants')) {
            foreach ($this->variants as $variant) {
                $vPath = $variant->primaryImagePath();
                if ($vPath) {
                    return $vPath;
                }
            }
        }

        return null;
    }

    /**
     * @return Collection<int, array{path: string, url: string, is_primary: bool, source: string, label: ?string}>
     */
    public function allGalleryImages(): Collection
    {
        $gallery = collect();

        foreach ($this->images as $img) {
            if ($img->path) {
                $gallery->push([
                    'path' => $img->path,
                    'url' => asset('storage/'.$img->path),
                    'is_primary' => (bool) $img->is_primary,
                    'source' => 'product',
                    'label' => $img->is_primary ? 'Product image' : null,
                ]);
            }
        }

        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
        foreach ($variants as $variant) {
            foreach ($variant->storedImagePaths() as $vPath) {
                if ($vPath && ! $gallery->contains('path', $vPath)) {
                    $gallery->push([
                        'path' => $vPath,
                        'url' => asset('storage/'.$vPath),
                        'is_primary' => false,
                        'source' => 'variant',
                        'label' => $variant->label(),
                    ]);
                }
            }
        }

        return $gallery;
    }

    public function hasVariants(): bool
    {
        return $this->relationLoaded('variants')
            ? $this->variants->isNotEmpty()
            : $this->variants()->exists();
    }

    public function totalStock(): int
    {
        if ($this->is_unlimited_stock) {
            return 99999;
        }

        if ($this->hasVariants()) {
            $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
            if ($variants->contains('is_unlimited_stock', true)) {
                return 99999;
            }

            return (int) $variants->sum('stock');
        }

        return (int) $this->stock;
    }

    /**
     * @return array<string, list<string>>
     */
    public function variationAttributes(): array
    {
        $attributes = [];

        foreach ($this->variants as $variant) {
            foreach ($variant->attributeMap() as $attrName => $valName) {
                if (! isset($attributes[$attrName])) {
                    $attributes[$attrName] = [];
                }
                if (! in_array($valName, $attributes[$attrName], true)) {
                    $attributes[$attrName][] = (string) $valName;
                }
            }
        }

        return $attributes;
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function storeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_visible_in_store', true);
    }

    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    #[Scope]
    protected function inhouse(Builder $query): Builder
    {
        return $query->where('source', 'inhouse');
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }
}
