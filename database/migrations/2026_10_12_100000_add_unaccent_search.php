<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recherche insensible aux accents : sur mobile, on tape « cafe » pour « café ».
 * f_unaccent est déclarée IMMUTABLE pour pouvoir servir dans un index (unaccent seule ne l'est pas).
 * Sur Ubuntu, l'extension est fournie par postgresql-contrib.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create extension if not exists unaccent');
        DB::statement(<<<'SQL'
            create or replace function f_unaccent(text) returns text
            language sql immutable parallel safe strict
            as $$ select public.unaccent('public.unaccent'::regdictionary, $1) $$
            SQL);

        DB::statement('drop index if exists questions_search_index');
        DB::statement("create index questions_search_index on questions using gin ((setweight(to_tsvector('french', f_unaccent(title)), 'A') || setweight(to_tsvector('french', f_unaccent(body)), 'B')))");
    }

    public function down(): void
    {
        DB::statement('drop index if exists questions_search_index');
        DB::statement("create index questions_search_index on questions using gin ((setweight(to_tsvector('french', title), 'A') || setweight(to_tsvector('french', body), 'B')))");
        DB::statement('drop function if exists f_unaccent(text)');
    }
};
