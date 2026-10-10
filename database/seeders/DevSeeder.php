<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Membres de démonstration (data/devs.php), dont les trois comptes de démo, avec leurs compétences.
 * Dates dans la fenêtre de DemoClock, relatives au moment du semis : la démo ne paraît jamais figée.
 * « joined » (data/devs.php) donne l'ancienneté relative : le plus grand est le premier inscrit.
 */
class DevSeeder extends Seeder
{
    /** Compétences propres au contexte africain (02, tags locaux). */
    public const LOCAL_SKILLS = ['Wave', 'Orange Money', 'MTN MoMo', 'USSD', 'SMS', 'Hors ligne', '3G'];

    public function run(): void
    {
        $devs = require __DIR__.'/data/devs.php';
        $clock = DemoClock::make();
        $now = $clock->now;
        $oldest = max(array_column($devs, 'joined'));
        $password = Hash::make('password');

        $skills = collect($devs)->pluck('skills')->flatten()->merge(self::LOCAL_SKILLS)->unique()
            ->mapWithKeys(fn (string $name) => [$name => Skill::firstOrCreate(
                ['slug' => Skill::slugFor($name)],
                ['name' => $name, 'is_local' => in_array($name, self::LOCAL_SKILLS, true)],
            )->id]);

        foreach ($devs as $index => $dev) {
            $joinedAt = $clock->ago($dev['joined'] / $oldest, $index * 5);

            $user = new User(['name' => $dev['name'], 'email' => $dev['email']]);
            $user->password = $password;
            $user->email_verified_at = $joinedAt;
            $user->created_at = $joinedAt;
            $user->updated_at = $joinedAt;
            $user->last_seen_at = $this->lastSeen($dev['seen'], $index, $now, $joinedAt);
            $user->save();

            $user->profile()->create([
                'headline' => $dev['headline'],
                'bio' => $dev['bio'],
                'country' => $dev['country'],
                'city' => $dev['city'],
                'languages' => $dev['languages'],
                'availability' => $dev['availability'],
                'github_url' => $dev['github'] ?? null,
                'portfolio_url' => $dev['portfolio'] ?? null,
            ]);

            $user->skills()->attach(collect($dev['skills'])->mapWithKeys(fn (string $name, int $position) => [$skills[$name] => ['position' => $position]])->all());
        }
    }

    /**
     * Dernière visite : aujourd'hui (sans déborder sur la veille juste après minuit), ou il y a N jours en journée.
     */
    private function lastSeen(?int $days, int $index, Carbon $now, Carbon $joinedAt): ?Carbon
    {
        $seen = match (true) {
            $days === null => null,
            $days === 0 => $now->copy()->subMinutes(5 + ($index * 37) % 240)->max($now->copy()->startOfDay()),
            default => $now->copy()->subDays($days)->setTime(8 + ($index * 7) % 13, ($index * 11) % 60),
        };

        // Jamais avant l'inscription (au moins 40 minutes après), jamais dans le futur.
        return $seen?->max($joinedAt->copy()->addMinutes(40))->min($now);
    }
}
