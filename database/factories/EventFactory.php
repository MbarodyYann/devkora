<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\User;
use App\Services\MarkdownRenderer;
use App\Support\Text;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Meetup à Dakar dans une semaine, par défaut.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $description = 'Rencontre autour de '.fake()->sentence(8);

        return [
            'user_id' => User::factory()->withProfile(),
            'type' => EventType::Meetup,
            'title' => 'Meetup '.fake()->words(3, true),
            'description' => $description,
            'description_html' => app(MarkdownRenderer::class)->toHtml($description),
            'excerpt' => Text::excerpt($description),
            'starts_at' => now()->addWeek()->setTime(18, 30),
            'ends_at' => now()->addWeek()->setTime(21, 0),
            'online' => false,
            'city' => 'Dakar',
            'country' => 'SN',
        ];
    }
}
