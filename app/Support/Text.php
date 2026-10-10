<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Extraits lisibles à partir du Markdown des membres.
 */
class Text
{
    /**
     * Extrait sans Markdown : blocs de code retirés, syntaxe effacée, espaces resserrés.
     */
    public static function excerpt(string $markdown, int $limit = 220): string
    {
        $text = preg_replace('/^(```|~~~).*?^\1\s*$/ms', ' ', $markdown) ?? $markdown; // blocs de code
        $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', ' ', $text);                   // images
        $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text);                 // liens : le texte seul
        // Code en ligne mis de côté, pour que « `*144#` » ne se mélange pas avec l'emphase.
        $code = [];
        $text = preg_replace_callback('/`([^`]*)`/', function (array $m) use (&$code) {
            $code[] = $m[1];

            return "\u{E000}".(count($code) - 1)."\u{E001}";
        }, $text);
        $text = preg_replace('/^\s{0,3}(#{1,6}|>|[-*+]|\d+[.)])\s+/m', '', $text);    // titres, citations, listes
        $text = preg_replace('/(?<![\w*_~])(\*\*|__|~~|\*|_)(?=\S)(.+?)(?<=\S)\1(?![\w*_~])/u', '$2', $text);  // emphase
        $text = preg_replace_callback("/\u{E000}(\d+)\u{E001}/u", fn (array $m) => $code[(int) $m[1]], $text);

        return Str::limit(Str::squish(html_entity_decode(strip_tags($text), ENT_QUOTES)), $limit);
    }
}
