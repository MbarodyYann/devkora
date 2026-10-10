<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\Article;
use App\Models\Question;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Votes (02) : +1 ouvert à tous, −1 à partir de 50 cauris (jamais sur un article : « Utile » seulement),
 * jamais pour son propre contenu ni pour un contenu masqué.
 */
class VotePolicy
{
    public function vote(User $user, Question|Answer|Article $votable, int $value): Response
    {
        if ($votable->user_id === $user->id) {
            return Response::deny('Tu ne peux pas voter pour ton propre contenu.');
        }

        if ($votable->hidden_at !== null || ! in_array($value, [1, -1], true) || ($votable instanceof Article && $value !== 1)) {
            return Response::deny('Ce vote n\'est pas possible.');
        }

        if ($value === -1 && $user->reputation < config('privileges.downvote')) {
            return Response::deny('Le vote négatif se débloque à '.config('privileges.downvote').' cauris.');
        }

        return Response::allow();
    }
}
