<?php

namespace App\Support;

use App\Enums\ModerationActionType;
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
        return [
            'type' => self::noun($content)['type'],
            'title' => self::noun($content)['phrase'],
            'excerpt' => $content instanceof Answer ? Text::excerpt($content->body) : (string) $content->getAttribute('excerpt'),
            'url' => match (true) {
                $content instanceof Question => route('questions.show', $content),
                $content instanceof Answer => route('questions.show', $content->question_id).'#reponse-'.$content->id,
                $content instanceof Article => route('articles.show', $content),
                $content instanceof Listing => route('listings.show', $content),
                $content instanceof Event => route('events.show', $content),
                default => url('/'),
            },
        ];
    }

    /**
     * Le contenu en toutes lettres, avec son déterminant : « la question « … » », « une réponse à « … » »,
     * « l'article « … » ». feminine sert à l'accord (« masquée »).
     *
     * @return array{type: string, phrase: string, feminine: bool}
     */
    public static function noun(Model $content): array
    {
        $quoted = fn (?string $title) => '« '.Str::limit((string) $title, 60, '…').' »';

        return match (true) {
            $content instanceof Question => ['type' => 'Question', 'phrase' => 'la question '.$quoted($content->title), 'feminine' => true],
            $content instanceof Answer => ['type' => 'Réponse', 'phrase' => $content->relationLoaded('question') ? 'une réponse à '.$quoted($content->question->title) : 'une réponse', 'feminine' => true],
            $content instanceof Article => ['type' => 'Article', 'phrase' => 'l\'article '.$quoted($content->title), 'feminine' => false],
            $content instanceof Listing => ['type' => 'Annonce', 'phrase' => 'l\'annonce '.$quoted($content->title), 'feminine' => true],
            $content instanceof Event => ['type' => 'Événement', 'phrase' => 'l\'événement '.$quoted($content->title), 'feminine' => false],
            default => ['type' => 'Contenu', 'phrase' => 'un contenu', 'feminine' => false],
        };
    }

    /**
     * Phrase du journal, sans le nom du modérateur : « a masqué une réponse à « … » »,
     * « a classé sans suite les signalements sur l'article « … » ».
     */
    public static function sentence(ModerationActionType $action, ?Model $content): string
    {
        $noun = $content ? self::noun($content)['phrase'] : 'un contenu supprimé';

        return match ($action) {
            ModerationActionType::Hide => "a masqué $noun",
            ModerationActionType::Restore => "a rétabli $noun",
            ModerationActionType::Dismiss => "a classé sans suite les signalements sur $noun",
            ModerationActionType::Retag => "a modifié les tags de $noun",
        };
    }

    /** En début de ligne : « La question « … » », « Une réponse à « … » ». */
    public static function heading(Model $content): string
    {
        return Str::ucfirst(self::noun($content)['phrase']);
    }
}
