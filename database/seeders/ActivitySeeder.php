<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Demo;
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

        // Notifications : lues si elles datent de plus d'un jour avant la dernière visite ; les plus récentes
        // restent non lues (la cloche montre un compteur crédible).
        DB::update(<<<'SQL'
            update notifications set read_at = notifications.created_at + interval '2 hours'
            from users
            where users.id = notifications.notifiable_id
              and notifications.notifiable_type = 'user'
              and notifications.created_at < users.last_seen_at - interval '1 day'
            SQL);

        // Comptes de démo : leurs 2 notifications les plus récentes restent non lues, pour montrer la cloche.
        foreach (User::whereIn('email', array_column(Demo::PERSONAS, 'email'))->get() as $persona) {
            $persona->notifications()->limit(2)->get()->each->markAsUnread();
        }
    }
}
