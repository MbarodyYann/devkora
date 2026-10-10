<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    /** Contenu masqué : visible de son auteur et des modérateurs communautaires, 404 pour les autres. */
    public function view(?User $user, Question $question): bool
    {
        return $question->hidden_at === null || $user?->id === $question->user_id || ModerationPolicy::canModerate($user);
    }

    /** Un membre avec un profil pose des questions. */
    public function create(User $user): bool
    {
        return $user->profile()->exists();
    }

    /** On répond à une question visible, même à la sienne (pour préciser ce qui a marché). */
    public function answer(User $user, Question $question): bool
    {
        return $question->hidden_at === null && $user->profile()->exists();
    }

    /** Seul l'auteur de la question accepte une réponse. */
    public function accept(User $user, Question $question): bool
    {
        return $user->id === $question->user_id;
    }
}
