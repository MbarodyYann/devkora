<?php

namespace App\Enums;

/**
 * Raisons d'un gain ou d'une perte de cauris (02, « Réputation : les cauris »).
 */
enum ReputationReason: string
{
    case AnswerUpvoted = 'answer_upvoted';
    case AnswerDownvoted = 'answer_downvoted';
    case AnswerAccepted = 'answer_accepted';

    public function points(): int
    {
        return match ($this) {
            self::AnswerUpvoted => 10,
            self::AnswerDownvoted => -2,
            self::AnswerAccepted => 15,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AnswerUpvoted => 'Réponse jugée utile',
            self::AnswerDownvoted => 'Réponse jugée peu utile',
            self::AnswerAccepted => 'Réponse acceptée',
        };
    }
}
