<?php

namespace App\Support;

/**
 * Identité visuelle : source unique du nom et du logo (08-design.md, « Logo »).
 *
 * Logo provisoire : le mot-symbole « DevKora » en Newsreader 600, « Dev » en ink et « Kora » en
 * primary-600 (secondary-400 en sombre). Le composant x-logo et la commande brand:export
 * (favicons) lisent tous ces valeurs.
 */
class Brand
{
    public const NAME = 'DevKora';

    /** Première partie du mot-symbole, en ink. */
    public const WORD_START = 'Dev';

    /** Seconde partie, en primary-600 (secondary-400 en sombre). */
    public const WORD_END = 'Kora';

    /** Monogramme du favicon provisoire. */
    public const MONOGRAM = 'DK';

    public const TAGLINE = 'Le point de rencontre des développeurs africains pour travailler, apprendre et grandir ensemble.';

    public const PRIMARY_600 = '#314A82';

    public const SECONDARY_400 = '#F9B218';

    /**
     * Favicon provisoire : « DK » en blanc sur un aplat primary-600 (aucun dégradé).
     */
    public static function faviconSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" rx="14" fill="'.self::PRIMARY_600.'"/>'
            .'<text x="32" y="43" text-anchor="middle" font-family="Newsreader, Georgia, \'Times New Roman\', serif" '
            .'font-size="30" font-weight="600" letter-spacing="-1" fill="#FFFFFF">'.self::MONOGRAM.'</text>'
            .'</svg>';
    }
}
