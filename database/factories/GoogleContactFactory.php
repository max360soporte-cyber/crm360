<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GoogleContact>
 */
class GoogleContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1, // Assuming a default user
            'google_id' => $this->faker->unique()->uuid(),
            'name' => $this->faker->name(),
            'phone_number' => $this->faker->phoneNumber(),
            'photo_url' => $this->faker->imageUrl(100, 100, 'people', true),
            'etag' => $this->faker->md5(),
            'synced_at' => now(),
        ];
    }
}
