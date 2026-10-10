<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Modération communautaire : à partir de 250 cauris (config/privileges.php). Un modérateur ne modère jamais
 * son propre contenu ni un contenu qu'il a lui-même signalé (04, « Règles métier »).
 */
class ModerationPolicy
{
    public static function canModerate(?User $user): bool
    {
        return $user !== null && ($user->is_admin || $user->reputation >= config('privileges.moderate'));
    }

    public static function canRetag(?User $user): bool
    {
        return $user !== null && ($user->is_admin || $user->reputation >= config('privileges.retag'));
    }

    public function act(User $user, Model $content): Response
    {
        if (! self::canModerate($user)) {
            return Response::deny('La modération communautaire se débloque à '.config('privileges.moderate').' cauris.');
        }

        if ((int) $content->getAttribute('user_id') === $user->id) {
            return Response::deny('Tu ne modères pas ton propre contenu : un autre modérateur s\'en charge.');
        }

        $reported = Report::query()
            ->where('user_id', $user->id)
            ->where('reportable_type', $content->getMorphClass())
            ->where('reportable_id', $content->getKey())
            ->exists();

        return $reported
            ? Response::deny('Tu as signalé ce contenu : un autre modérateur s\'en charge.')
            : Response::allow();
    }
}
