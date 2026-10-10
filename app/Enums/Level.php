<?php

namespace App\Enums;

/**
 * Niveau d'un membre, calculé à partir de ses cauris (02-fonctionnalites.md, « Niveaux »).
 */
enum Level: string
{
    case Newcomer = 'newcomer';
    case Contributor = 'contributor';
    case Confirmed = 'confirmed';
    case Referent = 'referent';

    /** Cauris minimum de chaque niveau, du plus haut au plus bas. */
    public const THRESHOLDS = [
        'referent' => 1000,
        'confirmed' => 250,
        'contributor' => 50,
        'newcomer' => 0,
    ];

    public static function for(int $reputation): self
    {
        foreach (self::THRESHOLDS as $level => $minimum) {
            if ($reputation >= $minimum) {
                return self::from($level);
            }
        }

        return self::Newcomer;
    }

    public function label(): string
    {
        return match ($this) {
            self::Newcomer => 'Nouveau',
            self::Contributor => 'Contributeur',
            self::Confirmed => 'Confirmé',
            self::Referent => 'Référent',
        };
    }

    public function minimum(): int
    {
        return self::THRESHOLDS[$this->value];
    }
}
