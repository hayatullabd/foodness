<?php

namespace Database\Factories;

use App\Models\IncompleteOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IncompleteOrder>
 */
class IncompleteOrderFactory extends Factory
{
    protected $model = IncompleteOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 100, 2000);
        $qty = fake()->numberBetween(1, 3);

        return [
            'session_id' => Str::random(40),
            'user_id' => null,
            'customer_name' => fake()->name(),
            'customer_phone' => '01'.fake()->numerify('#########'),
            'customer_address' => fake()->address(),
            'cart_items' => [
                [
                    'product_id' => fake()->numberBetween(1, 999),
                    'name' => fake()->words(3, true),
                    'qty' => $qty,
                    'price' => (string) $price,
                    'image' => null,
                ],
            ],
            'total' => bcmul((string) $price, (string) $qty, 2),
        ];
    }
}
