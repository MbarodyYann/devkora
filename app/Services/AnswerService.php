<?php

namespace App\Services;

use App\Enums\ReputationReason;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Réponses : publication et réponse acceptée (+15 cauris, retirés si l'auteur en accepte une autre).
 */
class AnswerService
{
    public function __construct(
        private readonly ReputationService $reputation,
        private readonly MarkdownRenderer $markdown,
        private readonly Notifier $notifier,
    ) {}

    public function publish(User $author, Question $question, string $body): Answer
    {
        $answer = DB::transaction(function () use ($author, $question, $body) {
            $answer = new Answer(['body' => $body]);
            $answer->forceFill([
                'question_id' => $question->id,
                'user_id' => $author->id,
                'body_html' => $this->markdown->toHtml($body),
            ])->save();

            $question->newQuery()->whereKey($question->id)->increment('answers_count');

            return $answer;
        });

        $this->notifier->answerPosted($answer, $question);

        return $answer;
    }

    /**
     * Accepte une réponse ; accepter la réponse déjà acceptée la retire.
     */
    public function accept(Question $question, Answer $answer): void
    {
        DB::transaction(function () use ($question, $answer) {
            $question = $question->newQuery()->lockForUpdate()->findOrFail($question->id);
            $previous = $question->accepted_answer_id ? Answer::with('user')->find($question->accepted_answer_id) : null;

            if ($previous) {
                $this->reputation->revoke($previous->user, ReputationReason::AnswerAccepted, $previous);
            }

            $unaccept = $previous?->is($answer);
            $question->forceFill(['accepted_answer_id' => $unaccept ? null : $answer->id])->save();

            // Pas de cauris pour sa propre réponse.
            if (! $unaccept && $answer->user_id !== $question->user_id) {
                $this->reputation->award($answer->user()->firstOrFail(), ReputationReason::AnswerAccepted, $answer);
                $this->notifier->answerAccepted($answer, $question);
            }
        });
    }
}
