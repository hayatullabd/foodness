<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'product_id',
        'custom_price',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'custom_price' => 'decimal:2',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function effectivePrice(?ProductVariant $variant = null): float
    {
        if ($variant !== null && (float) $variant->price > 0) {
            return (float) $variant->price;
        }

        if ($this->custom_price !== null && (float) $this->custom_price > 0) {
            return (float) $this->custom_price;
        }

        return (float) ($this->product?->price ?? 0);
    }
}
