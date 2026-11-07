<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_id',
    'product_id',
    'variant_id',
    'variation_name',
    'color',
    'size',
    'attributes',
    'quantity',
    'unit_cost',
    'line_total',
])]
class PurchaseItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function attributeLabel(): ?string
    {
        if ($this->variation_name) {
            return $this->variation_name;
        }

        $parts = array_filter([$this->color, $this->size]);
        if (! empty($parts)) {
            return implode(' / ', $parts);
        }

        if ($this->variant?->name) {
            return $this->variant->name;
        }

        return null;
    }
}
