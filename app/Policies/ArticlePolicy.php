<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    /** Article masqué : visible seulement de son auteur (la modération arrive au lot 5). */
    public function view(?User $user, Article $article): bool
    {
        return $article->hidden_at === null || $user?->id === $article->user_id;
    }

    /** Un membre avec un profil écrit des articles. */
    public function create(User $user): bool
    {
        return $user->profile()->exists();
    }
}
