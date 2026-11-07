<?php

namespace Database\Factories;

use App\Models\Childcategory;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Childcategory>
 */
class ChildcategoryFactory extends Factory
{
    protected $model = Childcategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'subcategory_id' => Subcategory::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'image' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
