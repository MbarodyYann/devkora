<?php

use App\Services\ReputationService;
use Illuminate\Support\Facades\Artisan;

// Recalcule les cauris de chaque membre à partir de reputation_events (filet de sécurité).
Artisan::command('reputation:recalculate', function (ReputationService $reputation) {
    $fixed = $reputation->recalculate();
    $this->info($fixed === 0 ? 'Tous les cauris sont justes.' : "$fixed membre(s) corrigé(s).");
})->purpose('Recalcule users.reputation à partir des événements de réputation');
