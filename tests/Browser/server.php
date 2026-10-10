<?php

use Symfony\Component\Process\Process;

/*
| Serveur PHP intégré partagé par les tests navigateur : l'application sur la base de test semée,
| avec le build Vite et les en-têtes de sécurité de production (voir router.php).
*/

function serve(): Process
{
    $port = 8765;
    // Un ancien serveur encore actif fausserait toutes les mesures : on refuse de continuer.
    if (@fsockopen('127.0.0.1', $port)) {
        throw new RuntimeException("Le port $port est déjà utilisé : arrête l'ancien serveur de test.");
    }
    $server = new Process(
        [PHP_BINARY, '-S', "127.0.0.1:$port", base_path('tests/Browser/router.php')],
        public_path(),
        ['APP_ENV' => 'local', 'APP_DEBUG' => 'false', 'APP_URL' => "http://127.0.0.1:$port", 'APP_DEMO' => 'true',
            'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => config('database.connections.pgsql.database'),
            'SESSION_DRIVER' => 'file', 'CACHE_STORE' => 'array', 'QUEUE_CONNECTION' => 'sync'],
    );
    // Journal du serveur ignoré : un tube jamais lu finit par se remplir et bloquer le serveur.
    $server->disableOutput();
    $server->start();

    // Attend que le serveur réponde (5 s au plus).
    foreach (range(1, 50) as $attempt) {
        if (@fsockopen('127.0.0.1', $port)) {
            break;
        }
        usleep(100_000);
    }

    return $server;
}
