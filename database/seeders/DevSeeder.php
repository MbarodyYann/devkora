<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Membres de démonstration (data/devs.php), dont les trois comptes de démo, avec leurs compétences.
 * Dates relatives au moment du semis : la démo ne paraît jamais figée.
 */
class DevSeeder extends Seeder
{
    /** Compétences propres au contexte africain (02, tags locaux). */
    public const LOCAL_SKILLS = ['Wave', 'Orange Money', 'MTN MoMo', 'USSD', 'SMS', 'Hors ligne', '3G'];

    public function run(): void
    {
        $devs = require __DIR__.'/data/devs.php';
        $now = Carbon::now();
        $password = Hash::make('password');

        $skills = collect($devs)->pluck('skills')->flatten()->merge(self::LOCAL_SKILLS)->unique()
            ->mapWithKeys(fn (string $name) => [$name => Skill::firstOrCreate(
                ['slug' => Skill::slugFor($name)],
                ['name' => $name, 'is_local' => in_array($name, self::LOCAL_SKILLS, true)],
            )->id]);

        foreach ($devs as $index => $dev) {
            // Inscription en journée, entre 8 h et 20 h.
            $joinedAt = $now->copy()->subDays($dev['joined'])->setTime(8 + ($index * 5) % 12, ($index * 17) % 60);

            $user = new User(['name' => $dev['name'], 'email' => $dev['email']]);
            $user->password = $password;
            $user->email_verified_at = $joinedAt;
            $user->created_at = $joinedAt;
            $user->updated_at = $joinedAt;
            $user->last_seen_at = $this->lastSeen($dev['seen'], $index, $now);
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
    private function lastSeen(?int $days, int $index, Carbon $now): ?Carbon
    {
        return match (true) {
            $days === null => null,
            $days === 0 => $now->copy()->subMinutes(5 + ($index * 37) % 240)->max($now->copy()->startOfDay()),
            default => $now->copy()->subDays($days)->setTime(8 + ($index * 7) % 13, ($index * 11) % 60),
        };
    }
}
