<?php

namespace App\Enums;

enum ListingStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(ListingType $type): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::InProgress => 'En cours',
            self::Completed => 'Terminée',
            self::Closed => $type === ListingType::Team ? 'Équipe complète' : 'Close',
            self::Cancelled => 'Annulée',
        };
    }

    /** Ton de l'étiquette (08, « Usages »). */
    public function tone(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::InProgress => 'warm',
            self::Completed => 'done',
            self::Closed, self::Cancelled => 'muted',
        };
    }
}
