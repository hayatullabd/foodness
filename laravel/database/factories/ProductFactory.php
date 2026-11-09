<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'category_id' => Category::factory(),
            'sku' => 'SKU-'.fake()->unique()->numerify('######'),
            'price' => fake()->randomFloat(2, 100, 5000),
            'compare_at_price' => null,
            'stock' => fake()->numberBetween(0, 100),
            'description' => fake()->sentence(),
            'source' => 'inhouse',
            'is_featured' => false,
            'free_delivery' => false,
            'is_hot' => false,
            'is_flash' => false,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function hot(): static
    {
        return $this->state(fn (): array => ['is_hot' => true, 'is_featured' => true]);
    }
}
