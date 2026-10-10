<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use App\Services\MarkdownRenderer;
use App\Support\Text;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Mission entre devs ouverte, par défaut.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $description = 'Je cherche un dev pour '.fake()->sentence(14)."\n\nLe code existe déjà, il faut l'intégrer proprement.";

        return [
            'user_id' => User::factory()->withProfile(),
            'type' => ListingType::Mission,
            'status' => ListingStatus::Open,
            'title' => 'Mission : '.fake()->sentence(5),
            'description' => $description,
            'description_html' => app(MarkdownRenderer::class)->toHtml($description),
            'excerpt' => Text::excerpt($description),
            'budget_fcfa' => 150000,
            'delivery_days' => 10,
            'country' => 'SN',
        ];
    }

    public function team(): static
    {
        return $this->state([
            'type' => ListingType::Team,
            'title' => 'Équipe : '.fake()->sentence(5),
            'roles_wanted' => 'Dev back-end, designer',
            'hours_per_week' => 8,
            'budget_fcfa' => null,
            'delivery_days' => null,
        ]);
    }
}
