<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'reviewer_id' => User::factory(),
            'reviewee_id' => User::factory(),
            'rating' => 5,
            'communication' => 5,
            'quality' => 5,
            'timeliness' => 4,
            'comment' => 'Travail propre, livré dans les temps.',
        ];
    }
}
