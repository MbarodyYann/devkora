<?php

namespace Database\Factories;

use App\Enums\Availability;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'headline' => 'Dev '.fake()->randomElement(['Laravel', 'Flutter', 'React', 'Django', 'Node.js']),
            'bio' => fake()->sentence(12),
            'country' => fake()->randomElement(['SN', 'CI', 'TG', 'BJ', 'CM', 'ML']),
            'city' => fake()->city(),
            'languages' => ['fr'],
            'availability' => Availability::Collab,
        ];
    }
}
