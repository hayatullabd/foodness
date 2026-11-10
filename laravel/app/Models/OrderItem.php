<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'variant_id',
    'product_name',
    'variation_name',
    'attributes',
    'color',
    'size',
    'source',
    'price',
    'discount',
    'qty',
])]
class OrderItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'qty' => 'integer',
        ];
    }

    public function lineSubtotal(): float
    {
        return max(0, round(((float) $this->price * (int) $this->qty) - (float) $this->discount, 2));
    }

    public function variantLabel(): ?string
    {
        if (! empty($this->variation_name)) {
            $val = trim((string) $this->variation_name);

            // Filter out corrupt / raw model dump strings like "id: 14, order_id: 15, product_id: 61..."
            if (str_contains($val, 'product_id:') || str_contains($val, 'order_id:') || str_contains($val, 'created_at:')) {
                $val = '';
            } elseif (str_starts_with($val, '{') && str_ends_with($val, '}')) {
                $decoded = json_decode($val, true);
                if (is_array($decoded)) {
                    if (! empty($decoded['name'])) {
                        return (string) $decoded['name'];
                    }
                    if (! empty($decoded['attribute_values']) && is_array($decoded['attribute_values'])) {
                        return implode(', ', $decoded['attribute_values']);
                    }
                }
                $val = '';
            }

            if ($val !== '') {
                return $val;
            }
        }

        if ($this->relationLoaded('variant') && $this->variant?->name) {
            return (string) $this->variant->name;
        }

        if (! empty($this->variant_id) && $this->variant?->name) {
            return (string) $this->variant->name;
        }

        $parts = array_filter([$this->color, $this->size]);
        if (! empty($parts)) {
            return implode(' / ', $parts);
        }

        if (is_array($this->attributes) && ! empty($this->attributes)) {
            return implode(', ', array_map(
                fn ($k, $v) => is_string($k) ? "{$k}: {$v}" : (string) $v,
                array_keys($this->attributes),
                $this->attributes
            ));
        }

        return null;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
