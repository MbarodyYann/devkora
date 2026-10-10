<?php

namespace App\Services;

use App\Enums\ReputationReason;
use App\Models\Answer;
use App\Models\Article;
use App\Models\Question;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

/**
 * Votes +1 / −1 sur les questions et les réponses, « Utile » (+1 seulement) sur les articles.
 * Revoter dans le même sens annule le vote ; voter dans l'autre sens le remplace.
 * Cauris pour l'auteur : réponse +10 / −2, article +5. Un événement par vote, dont la source est le contenu.
 */
class VoteService
{
    public function __construct(
        private readonly ReputationService $reputation,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @return int|null le vote du membre après l'opération (null s'il est annulé)
     */
    public function vote(User $voter, Question|Answer|Article $votable, int $value): ?int
    {
        return DB::transaction(function () use ($voter, $votable, $value) {
            // Verrou sur le contenu : deux votes simultanés ne faussent pas le score.
            $votable->newQuery()->whereKey($votable->getKey())->lockForUpdate()->first();

            $existing = Vote::query()
                ->where('user_id', $voter->id)
                ->where('votable_type', $votable->getMorphClass())
                ->where('votable_id', $votable->getKey())
                ->first();

            if ($existing) {
                $existing->delete();
                $this->reputationFor($votable, $existing->value, revoke: true);
            }

            $result = $existing?->value === $value ? null : $value;

            if ($result !== null) {
                (new Vote)->forceFill([
                    'user_id' => $voter->id,
                    'votable_type' => $votable->getMorphClass(),
                    'votable_id' => $votable->getKey(),
                    'value' => $value,
                ])->save();
                $this->reputationFor($votable, $value);

                if ($votable instanceof Article) {
                    $this->notifier->articleUseful($votable, $voter);
                }
            }

            $votable->forceFill([
                'score' => (int) Vote::query()
                    ->where('votable_type', $votable->getMorphClass())
                    ->where('votable_id', $votable->getKey())
                    ->sum('value'),
            ])->saveQuietly();

            return $result;
        });
    }

    private function reputationFor(Question|Answer|Article $votable, int $value, bool $revoke = false): void
    {
        $reason = match (true) {
            $votable instanceof Answer => $value > 0 ? ReputationReason::AnswerUpvoted : ReputationReason::AnswerDownvoted,
            $votable instanceof Article => ReputationReason::ArticleUseful,
            default => null,
        };

        if ($reason === null) {
            return;
        }

        $author = $votable->user()->firstOrFail();

        $revoke
            ? $this->reputation->revokeOne($author, $reason, $votable)
            : $this->reputation->award($author, $reason, $votable);
    }
}
