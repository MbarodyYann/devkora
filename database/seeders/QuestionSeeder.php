<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Services\AnswerService;
use App\Services\QuestionService;
use App\Services\VoteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Questions, réponses, votes et réponses acceptées de démonstration (data/questions-*.php).
 * Tout passe par les services de l'application : les cauris viennent des événements de réputation,
 * et chaque action est datée dans la fenêtre de DemoClock, après l'inscription de son auteur.
 */
class QuestionSeeder extends Seeder
{
    private DemoClock $clock;

    /** @var Collection<string, User> */
    private Collection $members;

    public function __construct(
        private readonly QuestionService $questions,
        private readonly AnswerService $answers,
        private readonly VoteService $votes,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public static function data(): array
    {
        $questions = array_merge(require __DIR__.'/data/questions-1.php', require __DIR__.'/data/questions-2.php');
        $extra = require __DIR__.'/data/answers-extra.php';

        foreach ($questions as &$question) {
            $question['answers'] = array_merge($question['answers'], $extra[$question['title']] ?? []);
            unset($extra[$question['title']]);
        }

        if ($extra !== []) {
            throw new RuntimeException('Réponses sans question : '.implode(', ', array_keys($extra)));
        }

        return $questions;
    }

    public function run(): void
    {
        $this->clock = DemoClock::make();
        $previousNow = Carbon::getTestNow();
        // Membres qui reviennent sur la plateforme (Ange Yapo ne l'a jamais fait) : eux seuls votent.
        $this->members = User::query()->whereNotNull('last_seen_at')->get()->keyBy('name');

        try {
            foreach (self::data() as $index => $data) {
                $this->seedQuestion($data, $index);
            }
        } finally {
            Carbon::setTestNow($previousNow);
        }

        $this->touchLastSeen();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedQuestion(array $data, int $index): void
    {
        $author = $this->member($data['author']);
        $askedAt = $this->within($this->clock->ago($data['age'], $index), $author, minutesAfterJoin: 120);

        Carbon::setTestNow($askedAt);
        $question = $this->questions->publish($author, $data['title'], $data['body'], $data['tags']);
        $this->castVotes($question, $data['votes'], $askedAt, $author);

        foreach ($data['answers'] as $position => $answerData) {
            $answerer = $this->member($answerData['author']);
            $answeredAt = $this->within($askedAt->copy()->addHours($answerData['after'])->addMinutes(7 * $position), $answerer, minutesAfterJoin: 60);

            Carbon::setTestNow($answeredAt);
            $answer = $this->answers->publish($answerer, $question, $answerData['body']);
            $this->castVotes($answer, $answerData['votes'], $answeredAt, $answerer);

            if ($answerData['accepted'] ?? false) {
                Carbon::setTestNow($this->clock->clamp($answeredAt->copy()->addHours(2)));
                $this->answers->accept($question->fresh(), $answer);
            }
        }
    }

    /**
     * Votes +1 de membres choisis de façon stable, jamais l'auteur. Chacun vote après son inscription :
     * un contenu ancien reçoit aussi les votes des membres arrivés plus tard.
     */
    private function castVotes(Question|Answer $votable, int $count, Carbon $from, User $author): void
    {
        $this->members
            ->reject(fn (User $member) => $member->is($author))
            ->sortBy(fn (User $member) => crc32($member->email.$votable->getMorphClass().$votable->id))
            ->take($count)
            ->values()
            ->each(function (User $voter, int $i) use ($votable, $from) {
                Carbon::setTestNow($this->within($from->copy()->addMinutes(35 * ($i + 1)), $voter, minutesAfterJoin: 60));
                $this->votes->vote($voter, $votable, 1);
            });
    }

    /** Ramène un instant dans la fenêtre, après l'inscription du membre et avant maintenant. */
    private function within(Carbon $moment, User $member, int $minutesAfterJoin): Carbon
    {
        return $this->clock->clamp($moment->max($member->created_at->copy()->addMinutes($minutesAfterJoin)));
    }

    private function member(string $name): User
    {
        return $this->members[$name] ?? throw new RuntimeException("Membre de démo inconnu : $name");
    }

    /**
     * Personne ne paraît absent après avoir publié ou voté : la dernière activité suit le dernier contenu.
     */
    private function touchLastSeen(): void
    {
        DB::update(<<<'SQL'
            update users set last_seen_at = least(?::timestamp, greatest(
                users.last_seen_at,
                (select max(created_at) from questions where user_id = users.id),
                (select max(created_at) from answers where user_id = users.id),
                (select max(created_at) from votes where user_id = users.id)
            ))
            where users.last_seen_at is not null
            SQL, [$this->clock->now->toDateTimeString()]);
    }
}
