<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Recherche plein texte en français, insensible aux accents (f_unaccent), titre plus important que le corps.
 * L'expression est identique à celle de l'index GIN de la table : PostgreSQL l'utilise.
 */
trait Searchable
{
    public function searchVector(): string
    {
        $table = $this->getTable();

        return "(setweight(to_tsvector('french', f_unaccent($table.title)), 'A') || setweight(to_tsvector('french', f_unaccent($table.body)), 'B'))";
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeFullTextSearch(Builder $query, string $terms): void
    {
        $vector = $this->searchVector();
        $tsquery = "websearch_to_tsquery('french', f_unaccent(?))";

        $query->whereRaw("$vector @@ $tsquery", [$terms])
            ->orderByRaw("ts_rank($vector, $tsquery) desc", [$terms]);
    }
}
