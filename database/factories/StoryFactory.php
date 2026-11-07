<?php

namespace Database\Factories;

use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Story>
 */
class StoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'media_type' => 'image',
            'media_path' => 'stories/demo-story.jpg',
            'thumbnail_path' => null,
            'link_url' => '/shop',
            'link_text' => 'SHOP NOW',
            'duration' => 5,
            'sort_order' => 0,
            'is_active' => true,
            'views_count' => 0,
        ];
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'media_type' => 'video',
            'media_path' => 'stories/demo-story.mp4',
            'thumbnail_path' => 'stories/demo-thumb.jpg',
            'duration' => 10,
        ]);
    }
}
