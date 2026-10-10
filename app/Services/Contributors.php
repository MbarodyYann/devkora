<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Contributeurs du mois (02, P1) : les devs qui ont gagné le plus de cauris sur 30 jours
 * (somme de reputation_events), en cache 10 minutes.
 */
class Contributors
{
    public const CACHE_SECONDS = 600;

    /**
     * @return Collection<int, User> chaque membre porte month_points
     */
    public function ofMonth(int $limit = 5): Collection
    {
        $ranking = Cache::remember("contributors.month.$limit", self::CACHE_SECONDS, fn () => DB::table('reputation_events')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('user_id')
            ->havingRaw('sum(points) > 0')
            ->orderByRaw('sum(points) desc')
            ->orderBy('user_id')
            ->limit($limit)
            ->selectRaw('user_id, sum(points) as total')
            ->pluck('total', 'user_id')
            ->all());

        $users = User::query()->with('profile')->findMany(array_keys($ranking))->keyBy('id');

        return collect($ranking)
            ->map(fn ($points, $id) => $users->get($id)?->setAttribute('month_points', (int) $points))
            ->filter()
            ->values();
    }
}
