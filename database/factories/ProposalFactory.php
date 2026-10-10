<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Listing;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'user_id' => User::factory()->withProfile(),
            'message' => 'J\'ai déjà fait ce type de travail pour une boutique à Dakar, je peux commencer demain.',
            'proposed_fcfa' => 140000,
            'delivery_days' => 9,
            'status' => ProposalStatus::Pending,
        ];
    }
}
