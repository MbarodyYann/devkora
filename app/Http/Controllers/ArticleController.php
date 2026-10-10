<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function show(Article $article): View
    {
        // Contenu masqué : une 404, comme s'il n'existait pas.
        abort_unless(Gate::allows('view', $article), 404);

        $me = auth()->user();
        $article->load(['user.profile', 'skills']);
        $article->setAttribute('my_vote', $me ? $article->votes()->where('user_id', $me->id)->value('value') : null);

        return view('articles.show', [
            'article' => $article,
            // Du même auteur : jusqu'à 3 autres articles.
            'more' => $article->user->articles()->visible()->whereKeyNot($article->id)->latest()->limit(3)->get(['id', 'slug', 'title', 'category', 'reading_minutes']),
        ]);
    }
}
