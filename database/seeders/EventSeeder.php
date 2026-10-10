<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\User;
use App\Services\EventService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Événements de démonstration (data/events.php). Dates relatives au semis (DemoClock) :
 * les événements « à venir » restent toujours dans le futur, les passés tombent dans la fenêtre.
 */
class EventSeeder extends Seeder
{
    public function __construct(private readonly EventService $events) {}

    /**
     * @return list<array<string, mixed>>
     */
    public static function data(): array
    {
        return require __DIR__.'/data/events.php';
    }

    public function run(): void
    {
        $clock = DemoClock::make();
        $members = User::query()->whereNotNull('last_seen_at')->get()->keyBy('name');
        $previousNow = Carbon::getTestNow();

        try {
            foreach (self::data() as $data) {
                $author = $members[$data['author']] ?? throw new RuntimeException("Membre de démo inconnu : {$data['author']}");
                [$hour, $minute] = array_map('intval', explode(':', $data['at']));
                $startsAt = $clock->now->copy()->addDays($data['in'])->setTime($hour, $minute);
                // Un événement passé ne commence jamais avant la fenêtre ; un à venir reste dans le futur.
                $startsAt = $data['in'] > 0 ? $startsAt->max($clock->now->copy()->addHours(6)) : $clock->clamp($startsAt);
                $publishedAt = $clock->clamp($clock->now->copy()->subDays($data['published'])->max($author->created_at->copy()->addHours(3)))->min($startsAt->copy()->subHours(6));

                Carbon::setTestNow($publishedAt);
                $event = $this->events->publish($author, [
                    'type' => EventType::from($data['type']),
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHours($data['hours']),
                    'online' => $data['online'],
                    'city' => $data['city'],
                    'country' => $data['country'],
                    'registration_url' => $data['url'],
                ]);

                // Les participants s'inscrivent après la publication, avant le début.
                foreach ($data['participants'] as $i => $name) {
                    $participant = $members[$name] ?? throw new RuntimeException("Membre de démo inconnu : $name");
                    Carbon::setTestNow($clock->clamp($publishedAt->copy()->addHours(4 + 7 * $i)->max($participant->created_at->copy()->addHour()))->min($startsAt));
                    $this->events->toggle($participant, $event);
                }
            }
        } finally {
            Carbon::setTestNow($previousNow);
        }
    }
}
