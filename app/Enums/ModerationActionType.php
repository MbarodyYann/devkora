<?php

namespace App\Enums;

enum ModerationActionType: string
{
    case Hide = 'hide';
    case Restore = 'restore';
    case Dismiss = 'dismiss';
    case Retag = 'retag';

    public function label(): string
    {
        return match ($this) {
            self::Hide => 'a masqué',
            self::Restore => 'a rétabli',
            self::Dismiss => 'a classé sans suite les signalements de',
            self::Retag => 'a modifié les tags de',
        };
    }
}
