<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Données de démonstration, relançables : php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call([
            DevSeeder::class,
        ]);
    }
}
