<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Profile;
use App\Models\Question;
use App\Models\User;
use App\Support\Cities;
use Illuminate\Http\Response;

/**
 * SEO : plan du site (contenus visibles seulement) et consignes aux robots.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $static = collect(['home', 'questions.index', 'articles.index', 'listings.index', 'events.index', 'devs.index', 'pulse', 'privileges', 'about'])
            ->map(fn (string $route) => ['loc' => route($route), 'lastmod' => null]);

        $urls = $static
            ->merge(Question::query()->visible()->latest('updated_at')->get(['id', 'updated_at'])->map(fn ($q) => ['loc' => route('questions.show', $q), 'lastmod' => $q->updated_at]))
            ->merge(Article::query()->visible()->latest('updated_at')->get(['id', 'slug', 'updated_at'])->map(fn ($a) => ['loc' => route('articles.show', $a), 'lastmod' => $a->updated_at]))
            ->merge(Listing::query()->visible()->latest('updated_at')->get(['id', 'updated_at'])->map(fn ($l) => ['loc' => route('listings.show', $l), 'lastmod' => $l->updated_at]))
            ->merge(Event::query()->visible()->latest('updated_at')->get(['id', 'updated_at'])->map(fn ($e) => ['loc' => route('events.show', $e), 'lastmod' => $e->updated_at]))
            ->merge(Profile::query()->distinct()->pluck('city')->map(fn (string $city) => ['loc' => route('cities.show', Cities::slug($city)), 'lastmod' => null]))
            ->merge(User::query()->whereHas('profile')->latest('updated_at')->get(['id', 'updated_at'])->map(fn ($u) => ['loc' => route('devs.show', $u), 'lastmod' => $u->updated_at]));

        // L'en-tête XML est ajouté ici : « <?xml » dans une vue Blade serait pris pour du PHP.
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".view('sitemap', ['urls' => $urls])->render();

        return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            // Pages personnelles ou d'action : rien à indexer.
            'Disallow: /notifications',
            'Disallow: /moderation',
            'Disallow: /connexion',
            'Disallow: /inscription',
            'Disallow: /mot-de-passe-oublie',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
