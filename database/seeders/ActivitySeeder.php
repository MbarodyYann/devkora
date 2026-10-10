<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * En fin de semis : personne ne paraît absent après avoir publié ou voté.
 * La dernière activité suit le dernier contenu ou vote, sans dépasser maintenant.
 */
class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        DB::update(<<<'SQL'
            update users set last_seen_at = least(?::timestamp, greatest(
                users.last_seen_at,
                (select max(created_at) from questions where user_id = users.id),
                (select max(created_at) from answers where user_id = users.id),
                (select max(created_at) from articles where user_id = users.id),
                (select max(created_at) from listings where user_id = users.id),
                (select max(created_at) from proposals where user_id = users.id),
                (select max(created_at) from votes where user_id = users.id)
            ))
            where users.last_seen_at is not null
            SQL, [DemoClock::make()->now->toDateTimeString()]);
    }
}
