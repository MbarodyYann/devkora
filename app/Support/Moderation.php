<?php

namespace App\Support;

use App\Models\Answer;
use App\Models\Article;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Contenus signalables et leur description pour la file de modération.
 */
class Moderation
{
    /** Types signalables (alias de la morph map) => classe. */
    public const TYPES = [
        'question' => Question::class,
        'answer' => Answer::class,
        'article' => Article::class,
        'listing' => Listing::class,
        'event' => Event::class,
    ];

    /**
     * @return array{type: string, title: string, excerpt: string, url: string}
     */
    public static function describe(Model $content): array
    {
        return match (true) {
            $content instanceof Question => ['type' => 'Question', 'title' => $content->title, 'excerpt' => $content->excerpt, 'url' => route('questions.show', $content)],
            $content instanceof Answer => ['type' => 'Réponse', 'title' => $content->relationLoaded('question') ? 'Réponse à « '.Str::limit($content->question->title, 70).' »' : 'Réponse à une question', 'excerpt' => Text::excerpt($content->body), 'url' => route('questions.show', $content->question_id).'#reponse-'.$content->id],
            $content instanceof Article => ['type' => 'Article', 'title' => $content->title, 'excerpt' => $content->excerpt, 'url' => route('articles.show', $content)],
            $content instanceof Listing => ['type' => 'Annonce', 'title' => $content->title, 'excerpt' => $content->excerpt, 'url' => route('listings.show', $content)],
            $content instanceof Event => ['type' => 'Événement', 'title' => $content->title, 'excerpt' => $content->excerpt, 'url' => route('events.show', $content)],
            default => ['type' => 'Contenu', 'title' => '', 'excerpt' => '', 'url' => url('/')],
        };
    }

    /** Petit résumé lisible dans le journal. */
    public static function label(Model $content): string
    {
        $d = self::describe($content);

        return Str::lower($d['type']).' « '.Str::limit($d['title'], 50).' »';
    }
}
