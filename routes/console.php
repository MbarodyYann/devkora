<?php

use App\Services\ReputationService;
use App\Support\Demo;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// Recalcule les cauris de chaque membre à partir de reputation_events (filet de sécurité).
Artisan::command('reputation:recalculate', function (ReputationService $reputation) {
    $fixed = $reputation->recalculate();
    $this->info($fixed === 0 ? 'Tous les cauris sont justes.' : "$fixed membre(s) corrigé(s).");
})->purpose('Recalcule users.reputation à partir des événements de réputation');

// Démo : vide la base et la ressème, chronologie relative à maintenant (DemoClock). APP_DEMO=true seulement.
Artisan::command('demo:reset', function () {
    if (! Demo::enabled()) {
        $this->error('Refusé : demo:reset ne tourne qu\'avec APP_DEMO=true (il efface toute la base).');

        return 1;
    }

    $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
    Cache::flush();
    $this->info('Démo réinitialisée : '.now()->translatedFormat('j M Y, G \h i').'.');

    return 0;
})->purpose('Réinitialise les données de démonstration (APP_DEMO=true seulement)');

// Chaque nuit à 3 h, heure de Dakar : la démo ne paraît jamais figée.
Schedule::command('demo:reset')->dailyAt('03:00')->timezone('Africa/Dakar')->withoutOverlapping()
    ->when(fn () => Demo::enabled());
