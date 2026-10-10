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
    case ArticleUseful = 'article_useful';
    case MissionCompleted = 'mission_completed';
    case ReviewBonus = 'review_bonus';

    public function points(): int
    {
        return match ($this) {
            self::AnswerUpvoted => 10,
            self::AnswerDownvoted => -2,
            self::AnswerAccepted => 15,
            self::ArticleUseful => 5,
            self::MissionCompleted => 20,
            // Avis 5, 4 ou 3 étoiles : voir reviewBonus() ; en dessous, pas de bonus.
            self::ReviewBonus => 0,
        };
    }

    /** Bonus d'un avis selon sa note (02) : 5 étoiles +30, 4 +20, 3 +10. */
    public static function reviewBonus(int $rating): int
    {
        return match ($rating) {
            5 => 30,
            4 => 20,
            3 => 10,
            default => 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AnswerUpvoted => 'Réponse jugée utile',
            self::AnswerDownvoted => 'Réponse jugée peu utile',
            self::AnswerAccepted => 'Réponse acceptée',
            self::ArticleUseful => 'Article jugé utile',
            self::MissionCompleted => 'Mission terminée',
            self::ReviewBonus => 'Avis reçu',
        };
    }
}
