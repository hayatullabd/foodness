<?php

namespace Database\Factories;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'color_id' => Color::factory(),
            'size_id' => Size::factory(),
            'size_name' => null,
            'sku' => fake()->unique()->bothify('VAR-####'),
            'price' => fake()->randomFloat(2, 100, 2000),
            'compare_at_price' => fake()->randomFloat(2, 200, 2500),
            'stock' => fake()->numberBetween(5, 50),
            'sort_order' => 0,
        ];
    }
}
