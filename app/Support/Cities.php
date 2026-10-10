<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Profile;
use Illuminate\Support\Str;

/**
 * Villes de la communauté : les villes des profils et des événements, retrouvées par leur slug.
 */
class Cities
{
    public static function slug(string $city): string
    {
        return Str::slug($city);
    }

    /**
     * Nom et pays d'une ville à partir de son slug (« dakar » → Dakar, SN), ou null.
     *
     * @return array{name: string, country: string|null}|null
     */
    public static function find(string $slug): ?array
    {
        $profile = Profile::query()->select('city', 'country')->distinct()->get()
            ->first(fn (Profile $p) => self::slug($p->city) === $slug);
        if ($profile) {
            return ['name' => $profile->city, 'country' => $profile->country];
        }

        $event = Event::query()->visible()->whereNotNull('city')->select('city', 'country')->distinct()->get()
            ->first(fn (Event $e) => self::slug($e->city) === $slug);

        return $event ? ['name' => $event->city, 'country' => $event->country] : null;
    }
}
