<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Petites règles de typographie française.
 */
class French
{
    /** Prénoms et mots en h muet (élision). Les autres h sont aspirés. */
    private const MUTE_H = ['hélène', 'hervé', 'hugo', 'henri', 'homme', 'hôtel', 'heure', 'histoire'];

    /**
     * « de » élidé devant une voyelle ou un h muet : de('Aïssatou') → « d'Aïssatou », de('Moussa') → « de Moussa ».
     */
    public static function de(string $word): string
    {
        return (self::elides($word) ? "d'" : 'de ').$word;
    }

    /**
     * Espaces insécables de la typographie française : avant « : ; ! ? » et à l'intérieur des guillemets,
     * pour qu'un titre ne commence jamais une ligne par « : ».
     */
    public static function typography(string $text): string
    {
        $text = preg_replace('/[ \t]+([:;!?»])/u', "\u{00A0}$1", $text);

        return preg_replace('/«[ \t]+/u', "«\u{00A0}", $text);
    }

    /** Le mot commence-t-il par une voyelle ou un h muet ? (Y est traité comme une consonne : « de Yaya ».) */
    public static function elides(string $word): bool
    {
        $plain = Str::lower(Str::ascii(trim($word)));

        if ($plain === '') {
            return false;
        }

        if (in_array($plain[0], ['a', 'e', 'i', 'o', 'u'], true)) {
            return true;
        }

        return $plain[0] === 'h' && in_array(Str::lower(Str::before(trim($word), ' ')), self::MUTE_H, true);
    }
}
