<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Profile;
use App\Models\Question;
use App\Support\Countries;
use Illuminate\Support\Facades\Cache;

/**
 * Bande de confiance (08) : les chiffres réels de la communauté, sans arrondi, en cache 10 minutes.
 */
class CommunityStats
{
    public const CACHE_KEY = 'community.stats';

    public const CACHE_SECONDS = 600;

    /**
     * @return array{devs: int, resolved: int, articles: int, countries: int, flags: list<string>}
     */
    public function get(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, function () {
            $countries = Profile::query()->distinct()->pluck('country')->all();

            return [
                'devs' => Profile::query()->count(),
                'resolved' => Question::query()->visible()->whereNotNull('accepted_answer_id')->count(),
                'articles' => Article::query()->visible()->count(),
                'countries' => count($countries),
                // Drapeaux dans l'ordre de config/countries.php (pays francophones d'abord).
                'flags' => array_values(array_map(
                    Countries::flag(...),
                    array_keys(array_intersect_key(Countries::all(), array_flip($countries))),
                )),
            ];
        });
    }
}
