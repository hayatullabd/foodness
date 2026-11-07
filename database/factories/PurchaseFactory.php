<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);
        $discount = 0;
        $shipping = 0;
        $paid = $subtotal;
        $totals = Purchase::computeTotals([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_cost' => $shipping,
            'paid_amount' => $paid,
        ]);

        return array_merge([
            'supplier_id' => Supplier::factory(),
            'invoice_no' => 'PUR-'.fake()->unique()->numerify('#############'),
            'purchase_date' => now()->toDateString(),
            'note' => null,
        ], $totals);
    }

    public function withDue(float $due = 100): static
    {
        return $this->state(function () use ($due): array {
            $subtotal = 500;
            $paid = max(0, $subtotal - $due);

            return Purchase::computeTotals([
                'subtotal' => $subtotal,
                'discount' => 0,
                'shipping_cost' => 0,
                'paid_amount' => $paid,
            ]);
        });
    }
}
