<?php

namespace App\Models;

use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'supplier_id',
    'invoice_no',
    'purchase_date',
    'subtotal',
    'discount',
    'shipping_cost',
    'total',
    'paid_amount',
    'due',
    'note',
])]
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function isPaid(): bool
    {
        return (float) $this->due <= 0;
    }

    /**
     * @param  array{subtotal: float|int|string, discount?: float|int|string, shipping_cost?: float|int|string, paid_amount?: float|int|string}  $amounts
     * @return array{subtotal: string, discount: string, shipping_cost: string, total: string, paid_amount: string, due: string}
     */
    public static function computeTotals(array $amounts): array
    {
        $subtotal = round((float) $amounts['subtotal'], 2);
        $discount = round((float) ($amounts['discount'] ?? 0), 2);
        $shipping = round((float) ($amounts['shipping_cost'] ?? 0), 2);
        $paid = round((float) ($amounts['paid_amount'] ?? 0), 2);

        $total = max(0, round($subtotal - $discount + $shipping, 2));
        $paid = min($paid, $total);
        $due = max(0, round($total - $paid, 2));

        return [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'discount' => number_format($discount, 2, '.', ''),
            'shipping_cost' => number_format($shipping, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
            'paid_amount' => number_format($paid, 2, '.', ''),
            'due' => number_format($due, 2, '.', ''),
        ];
    }

    public static function generateInvoiceNo(): string
    {
        do {
            $invoice = 'PUR-'.now()->format('YmdHis').random_int(10, 99);
        } while (static::query()->where('invoice_no', $invoice)->exists());

        return $invoice;
    }
}
