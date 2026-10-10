<?php

namespace App\Enums;

/**
 * Disponibilité affichée sur le profil et filtrable dans l'annuaire.
 */
enum Availability: string
{
    case Collab = 'collab';
    case Missions = 'missions';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Collab => 'Ouvert aux collaborations',
            self::Missions => 'Ouvert aux missions',
            self::Unavailable => 'Indisponible',
        };
    }

    /** Libellé court du contrôle segmenté de l'annuaire. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Collab => 'Collaborations',
            self::Missions => 'Missions',
            self::Unavailable => 'Indisponibles',
        };
    }

    /** Ton de l'étiquette (x-chip, 08-design.md). */
    public function tone(): string
    {
        return $this === self::Unavailable ? 'muted' : 'info';
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
