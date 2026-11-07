<?php

namespace Database\Factories;

use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Size>
 */
class SizeFactory extends Factory
{
    protected $model = Size::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL', '1 BOX', 'Free Size']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
