<?php

namespace App\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case Offensive = 'offensive';
    case OffTopic = 'off_topic';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam ou publicité',
            self::Offensive => 'Propos offensants',
            self::OffTopic => 'Hors sujet',
            self::Other => 'Autre raison',
        };
    }
}
