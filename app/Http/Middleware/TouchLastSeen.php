<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Met à jour la dernière activité du membre connecté, au plus toutes les 5 minutes
 * (une seule requête UPDATE, sans toucher à updated_at ni déclencher d'événements).
 */
class TouchLastSeen
{
    public const INTERVAL_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->last_seen_at === null || $user->last_seen_at->lt(now()->subMinutes(self::INTERVAL_MINUTES)))) {
            $user->newQuery()->whereKey($user->getKey())->update(['last_seen_at' => now()]);
            $user->last_seen_at = now();
        }

        return $next($request);
    }
}
