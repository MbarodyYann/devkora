<?php

namespace App\Support;

/**
 * Montants en FCFA (11, « Règles d'écriture ») : « 150 000 FCFA », espaces insécables.
 */
class Money
{
    /** « 150 000 » (espace fine insécable entre les milliers). */
    public static function format(int $amount): string
    {
        return number_format($amount, 0, ',', "\u{202F}");
    }

    /** « 150 000 FCFA » (espace insécable avant la devise). */
    public static function fcfa(int $amount): string
    {
        return self::format($amount)."\u{00A0}FCFA";
    }
}
