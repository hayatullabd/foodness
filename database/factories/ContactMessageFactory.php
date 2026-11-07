<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '017'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(3),
            'message' => fake()->paragraph(),
            'is_read' => false,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
