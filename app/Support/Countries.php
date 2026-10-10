<?php

namespace App\Support;

/**
 * Pays du continent (config/countries.php) : noms, groupes et drapeaux.
 */
class Countries
{
    /**
     * Tous les pays, francophones en premier.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return config('countries.francophone') + config('countries.others');
    }

    /**
     * Pays regroupés pour un <select> avec <optgroup>.
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'Pays francophones' => config('countries.francophone'),
            'Autres pays' => config('countries.others'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function name(?string $code): ?string
    {
        return $code ? (self::all()[$code] ?? $code) : null;
    }

    /**
     * Drapeau emoji construit à partir du code ISO (indicateurs régionaux).
     */
    public static function flag(?string $code): string
    {
        if (! $code || ! preg_match('/^[A-Z]{2}$/', $code)) {
            return '';
        }

        return mb_chr(0x1F1E6 + ord($code[0]) - 65).mb_chr(0x1F1E6 + ord($code[1]) - 65);
    }
}
