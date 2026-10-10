<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    /** Article masqué : visible de son auteur et des modérateurs communautaires, 404 pour les autres. */
    public function view(?User $user, Article $article): bool
    {
        return $article->hidden_at === null || $user?->id === $article->user_id || ModerationPolicy::canModerate($user);
    }

    /** Un membre avec un profil écrit des articles. */
    public function create(User $user): bool
    {
        return $user->profile()->exists();
    }
}
