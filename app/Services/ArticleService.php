<?php

namespace App\Services;

use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Models\Skill;
use App\Models\User;
use App\Support\French;
use App\Support\Text;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleService
{
    /** Vitesse de lecture moyenne retenue (mots par minute). */
    public const WORDS_PER_MINUTE = 200;

    public function __construct(private readonly MarkdownRenderer $markdown) {}

    /**
     * @param  list<string>  $tags  noms des compétences, dans l'ordre choisi
     */
    public function publish(User $author, ArticleCategory $category, string $title, string $body, array $tags): Article
    {
        return DB::transaction(function () use ($author, $category, $title, $body, $tags) {
            $article = new Article(['category' => $category, 'title' => French::typography($title), 'body' => $body]);
            $article->forceFill([
                'user_id' => $author->id,
                'slug' => $this->uniqueSlug($title),
                'body_html' => $this->markdown->toHtml($body),
                'excerpt' => Text::excerpt($body, 240),
                'reading_minutes' => self::readingMinutes($body),
            ])->save();

            $article->skills()->sync(Skill::idsFor($tags));

            return $article;
        });
    }

    /** Temps de lecture en minutes (1 au moins), code compris. */
    public static function readingMinutes(string $markdown): int
    {
        return max(1, (int) ceil(str_word_count(Str::ascii($markdown)) / self::WORDS_PER_MINUTE));
    }

    /** « integrer-l-api-wave », puis « integrer-l-api-wave-2 » si le titre est déjà pris. */
    private function uniqueSlug(string $title): string
    {
        // L'apostrophe sépare les mots : « l'API » donne « l-api », pas « lapi ».
        $base = Str::limit(Str::slug(str_replace(["'", '’'], '-', $title)), 80, '') ?: 'article';
        // « ecrire » est la page d'écriture : un article ne prend jamais cette adresse.
        $base = $base === 'ecrire' ? 'ecrire-article' : $base;
        $slug = $base;

        for ($i = 2; Article::where('slug', $slug)->exists(); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }
}
