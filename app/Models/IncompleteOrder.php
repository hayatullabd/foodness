<?php

namespace App\Models;

use Database\Factories\IncompleteOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'session_id',
    'user_id',
    'customer_name',
    'customer_phone',
    'customer_address',
    'cart_items',
    'total',
    'fraud_score',
    'fraud_report',
    'source_platform',
    'landing_url',
    'referrer',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_content',
    'utm_term',
    'fbclid',
    'gclid',
    'ttclid',
    'user_agent',
    'attribution_json',
])]
class IncompleteOrder extends Model
{
    /** @use HasFactory<IncompleteOrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cart_items' => 'array',
            'total' => 'decimal:2',
            'fraud_score' => 'decimal:2',
            'fraud_report' => 'array',
            'attribution_json' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<int, array{product_id: int|null, name: string, qty: int, price: string|float, image: string|null}>
     */
    public function lineItems(): array
    {
        $items = $this->cart_items;

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, fn (mixed $row): bool => is_array($row) && (isset($row['product_id']) || isset($row['name']))));
    }
}
