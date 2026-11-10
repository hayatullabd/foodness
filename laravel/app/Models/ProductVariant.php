<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'product_id',
    'name',
    'attribute_values',
    'color_id',
    'size_id',
    'size_name',
    'sku',
    'price',
    'compare_at_price',
    'stock',
    'is_unlimited_stock',
    'delivery_charge_type',
    'custom_delivery_inside',
    'custom_delivery_outside',
    'image',
    'image_2',
    'image_3',
    'image_4',
    'sort_order',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attribute_values' => 'array',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock' => 'integer',
            'is_unlimited_stock' => 'boolean',
            'custom_delivery_inside' => 'decimal:2',
            'custom_delivery_outside' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<int, string|null>
     */
    public function imageSlots(): array
    {
        return [
            1 => $this->image,
            2 => $this->image_2,
            3 => $this->image_3,
            4 => $this->image_4,
        ];
    }

    /**
     * @return list<string>
     */
    public function storedImagePaths(): array
    {
        return array_values(array_filter($this->imageSlots()));
    }

    public function effectivePrice(): string
    {
        if (filled($this->price)) {
            return (string) $this->price;
        }

        return (string) ($this->product?->price ?? '0.00');
    }

    public function effectiveCompareAtPrice(): ?string
    {
        if (filled($this->compare_at_price)) {
            return (string) $this->compare_at_price;
        }

        return $this->product?->compare_at_price ? (string) $this->product->compare_at_price : null;
    }

    public function isFreeDelivery(): bool
    {
        if ($this->delivery_charge_type === 'free') {
            return true;
        }

        if ($this->delivery_charge_type === 'custom') {
            return false;
        }

        return (bool) $this->product?->free_delivery;
    }

    /**
     * Get the delivery charge for a specific shipping area/charge ID for this variant.
     */
    public function deliveryChargeForArea(int $shippingChargeId): ?string
    {
        if ($this->delivery_charge_type === 'free') {
            return '0.00';
        }

        if ($this->delivery_charge_type === 'custom') {
            $shippingCharge = ShippingCharge::query()->find($shippingChargeId);
            if (! $shippingCharge) {
                return null;
            }

            $name = strtolower($shippingCharge->name);
            $isInside = str_contains($name, 'inside') || str_contains($name, 'ভেতরে') || str_contains($name, 'ভেতর');
            $isOutside = str_contains($name, 'outside') || str_contains($name, 'বাইরে');

            if ($isInside && $this->custom_delivery_inside !== null) {
                return number_format((float) $this->custom_delivery_inside, 2, '.', '');
            }

            if ($isOutside && $this->custom_delivery_outside !== null) {
                return number_format((float) $this->custom_delivery_outside, 2, '.', '');
            }

            if ($this->custom_delivery_inside !== null) {
                return number_format((float) $this->custom_delivery_inside, 2, '.', '');
            }

            if ($this->custom_delivery_outside !== null) {
                return number_format((float) $this->custom_delivery_outside, 2, '.', '');
            }

            return null;
        }

        // 'default': fall back to parent product if available
        return $this->product?->deliveryChargeForArea($shippingChargeId);
    }

    public function label(): string
    {
        if (filled($this->name)) {
            return $this->name;
        }

        if (is_array($this->attribute_values) && $this->attribute_values !== []) {
            return implode(' / ', array_values($this->attribute_values));
        }

        $parts = [];
        if ($this->color) {
            $parts[] = $this->color->name;
        }
        $sizeName = $this->size?->name ?? $this->size_name;
        if (filled($sizeName)) {
            $parts[] = $sizeName;
        }

        return implode(' / ', $parts);
    }

    /**
     * Returns key-value array of attributes, e.g. ['Storage' => '128GB', 'RAM' => '8GB']
     *
     * @return array<string, string>
     */
    public function attributeMap(): array
    {
        if (is_array($this->attribute_values) && $this->attribute_values !== []) {
            return $this->attribute_values;
        }

        $map = [];
        if ($this->color) {
            $map['Color'] = $this->color->name;
        }
        $sizeName = $this->size?->name ?? $this->size_name;
        if (filled($sizeName)) {
            $map['Size'] = $sizeName;
        }

        return $map;
    }

    public function primaryImagePath(): ?string
    {
        $path = $this->image ?: ($this->image_2 ?: ($this->image_3 ?: $this->image_4));
        if ($path) {
            return $path;
        }

        if ($this->relationLoaded('product')) {
            return $this->product?->primaryImagePath();
        }

        return null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_attribute_value',
            'product_variant_id',
            'attribute_value_id'
        )->withTimestamps();
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
