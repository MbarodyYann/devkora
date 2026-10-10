<?php

namespace App\Enums;

enum ListingType: string
{
    case Team = 'team';
    case Mission = 'mission';

    public function label(): string
    {
        return match ($this) {
            self::Team => 'Cherche coéquipiers',
            self::Mission => 'Mission entre devs',
        };
    }

    /** Libellé du choix à la publication. */
    public function choice(): string
    {
        return match ($this) {
            self::Team => 'Je cherche des coéquipiers',
            self::Mission => 'Je confie une mission',
        };
    }
}
