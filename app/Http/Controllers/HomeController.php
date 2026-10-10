<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Support\Countries;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Durée de cache des chiffres de la bande de confiance (10 minutes). */
    public const STATS_TTL = 600;

    public function __invoke(): View
    {
        // Chiffres réels, sans arrondi (08, « Bande de confiance »).
        $stats = Cache::remember('home.stats', self::STATS_TTL, function () {
            $countries = Profile::query()->distinct()->pluck('country')->all();

            return [
                'devs' => Profile::query()->count(),
                'countries' => count($countries),
                // Drapeaux dans l'ordre de config/countries.php (pays francophones d'abord).
                'flags' => array_values(array_map(
                    Countries::flag(...),
                    array_keys(array_intersect_key(Countries::all(), array_flip($countries))),
                )),
                // Pile de 4 avatars : les membres les plus actifs récemment.
                'avatarIds' => User::query()->whereHas('profile')
                    ->orderByDesc('reputation')->orderByRaw('last_seen_at desc nulls last')->orderBy('id')
                    ->limit(4)->pluck('id')->all(),
            ];
        });

        $avatars = User::query()->with('profile')->findMany($stats['avatarIds'])
            ->sortBy(fn (User $user) => array_search($user->id, $stats['avatarIds'], true))
            ->values();

        return view('home', [
            'stats' => $stats,
            'avatars' => $avatars,
        ]);
    }
}
