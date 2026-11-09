<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'refund_number' => Refund::generateRefundNumber(),
            'amount' => fake()->randomFloat(2, 100, 3000),
            'method' => 'original_payment',
            'status' => Refund::STATUS_PENDING,
            'reason' => fake()->sentence(),
            'admin_note' => null,
            'account' => null,
            'account_name' => null,
            'transaction_id' => null,
            'processed_by' => null,
            'processed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => Refund::STATUS_PENDING,
            'processed_by' => null,
            'processed_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => Refund::STATUS_APPROVED,
            'processed_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => Refund::STATUS_REJECTED,
            'processed_at' => now(),
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (): array => [
            'status' => Refund::STATUS_PROCESSED,
            'transaction_id' => 'TXN-'.fake()->numerify('######'),
            'method' => 'bkash',
            'account' => '01'.fake()->numerify('#########'),
            'processed_at' => now(),
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (): array => [
            'order_id' => $order->id,
            'amount' => $order->total,
        ]);
    }
}
