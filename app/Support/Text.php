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
        $text = preg_replace('/`([^`]*)`/', '$1', $text);                             // code en ligne
        $text = preg_replace('/^\s{0,3}(#{1,6}|>|[-*+]|\d+[.)])\s+/m', '', $text);    // titres, citations, listes
        $text = preg_replace('/(?<![\w*_~])(\*\*|__|~~|\*|_)(?=\S)(.+?)(?<=\S)\1(?![\w*_~])/u', '$2', $text);  // emphase

        return Str::limit(Str::squish(html_entity_decode(strip_tags($text), ENT_QUOTES)), $limit);
    }
}
