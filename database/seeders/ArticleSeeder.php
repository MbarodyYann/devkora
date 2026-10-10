<?php

namespace Database\Seeders;

use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleService;
use App\Services\VoteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Articles de démonstration (data/articles/*.php) et leurs votes « Utile », publiés via les services :
 * les cauris viennent des événements, et tout est daté dans la fenêtre de DemoClock.
 */
class ArticleSeeder extends Seeder
{
    public function __construct(
        private readonly ArticleService $articles,
        private readonly VoteService $votes,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public static function data(): array
    {
        return array_map(fn (string $file) => require $file, glob(__DIR__.'/data/articles/*.php'));
    }

    public function run(): void
    {
        $clock = DemoClock::make();
        $previousNow = Carbon::getTestNow();
        /** @var Collection<string, User> $members */
        $members = User::query()->whereNotNull('last_seen_at')->get()->keyBy('name');

        try {
            foreach (self::data() as $index => $data) {
                $author = $members[$data['author']] ?? throw new RuntimeException("Membre de démo inconnu : {$data['author']}");
                $publishedAt = $clock->clamp($clock->ago($data['age'], $index + 3)->max($author->created_at->copy()->addHours(3)));

                Carbon::setTestNow($publishedAt);
                $article = $this->articles->publish($author, ArticleCategory::from($data['category']), $data['title'], $data['body'], $data['tags']);

                $this->useful($article, $data['useful'], $publishedAt, $members, $clock);
            }
        } finally {
            Carbon::setTestNow($previousNow);
        }
    }

    /**
     * Votes « Utile » de membres choisis de façon stable, jamais l'auteur, chacun après son inscription.
     *
     * @param  Collection<string, User>  $members
     */
    private function useful(Article $article, int $count, Carbon $from, Collection $members, DemoClock $clock): void
    {
        $members
            ->reject(fn (User $member) => $member->id === $article->user_id)
            ->sortBy(fn (User $member) => crc32($member->email.'article'.$article->id))
            ->take($count)
            ->values()
            ->each(function (User $voter, int $i) use ($article, $from, $clock) {
                Carbon::setTestNow($clock->clamp($from->copy()->addMinutes(50 * ($i + 1))->max($voter->created_at->copy()->addHour())));
                $this->votes->vote($voter, $article, 1);
            });
    }
}
