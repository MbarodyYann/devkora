<?php

namespace App\Services;

use App\Enums\ReputationReason;
use App\Models\ReputationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Seul point d'entrée des cauris : chaque variation laisse une trace dans reputation_events
 * et users.reputation reste la somme des événements.
 */
class ReputationService
{
    /**
     * @param  int|null  $points  points de cet événement quand ils varient (bonus d'avis), sinon ceux de la raison
     */
    public function award(User $user, ReputationReason $reason, Model $source, ?int $points = null): ReputationEvent
    {
        $points ??= $reason->points();

        return DB::transaction(function () use ($user, $reason, $source, $points) {
            $event = (new ReputationEvent)->forceFill([
                'user_id' => $user->id,
                'points' => $points,
                'reason' => $reason,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
            ]);
            $event->save();

            User::whereKey($user->id)->increment('reputation', $points);

            return $event;
        });
    }

    /**
     * Annule un seul événement de cette raison pour cette source (un vote retiré parmi plusieurs).
     */
    public function revokeOne(User $user, ReputationReason $reason, Model $source): void
    {
        DB::transaction(function () use ($user, $reason, $source) {
            $event = ReputationEvent::query()
                ->where('user_id', $user->id)
                ->where('reason', $reason)
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($event) {
                User::whereKey($user->id)->decrement('reputation', $event->points);
                $event->delete();
            }
        });
    }

    /**
     * Annule les événements de cette raison pour cette source (vote retiré, réponse plus acceptée).
     */
    public function revoke(User $user, ReputationReason $reason, Model $source): void
    {
        DB::transaction(function () use ($user, $reason, $source) {
            $events = ReputationEvent::query()
                ->where('user_id', $user->id)
                ->where('reason', $reason)
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey())
                ->lockForUpdate()
                ->get();

            if ($events->isNotEmpty()) {
                User::whereKey($user->id)->decrement('reputation', $events->sum('points'));
                ReputationEvent::whereKey($events->modelKeys())->delete();
            }
        });
    }

    /**
     * Recalcule users.reputation à partir des événements. Renvoie le nombre de membres corrigés.
     */
    public function recalculate(): int
    {
        return DB::update(<<<'SQL'
            update users set reputation = coalesce(totals.points, 0)
            from users as u
            left join (select user_id, sum(points) as points from reputation_events group by user_id) as totals
                on totals.user_id = u.id
            where users.id = u.id and users.reputation <> coalesce(totals.points, 0)
            SQL);
    }
}
