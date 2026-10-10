<?php

use App\Models\User;
use App\Support\Demo;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

/*
| Aucun débordement horizontal à 360 px (08, « Mise en page ») : mesure réelle dans Chromium
| (getBoundingClientRect), en clair et en sombre, sans masquer avec overflow-x: clip.
| Sert l'application sur la base de test semée, avec le build Vite (npm run build).
*/

beforeAll(function () {
    if (! is_file(dirname(__DIR__, 2).'/node_modules/playwright/package.json')) {
        throw new RuntimeException('Playwright manque : npm install, puis npx playwright install chromium.');
    }
});

function serve(): Process
{
    $port = 8765;
    $server = new Process(
        // Le routeur de Laravel attend d'être lancé depuis public/.
        [PHP_BINARY, '-S', "127.0.0.1:$port", base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
        public_path(),
        ['APP_ENV' => 'local', 'APP_DEBUG' => 'false', 'APP_URL' => "http://127.0.0.1:$port", 'APP_DEMO' => 'true',
            'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => config('database.connections.pgsql.database'),
            'SESSION_DRIVER' => 'file', 'CACHE_STORE' => 'array', 'QUEUE_CONNECTION' => 'sync'],
    );
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

function measure(array $pages, ?string $persona = null): array
{
    $process = new Process(['node', 'tests/Browser/overflow.mjs', 'http://127.0.0.1:8765', '360', json_encode($pages), ...($persona ? [$persona] : [])], base_path());
    $process->setTimeout(180)->mustRun();

    return json_decode($process->getOutput(), true);
}

test('aucun débordement horizontal à 360 px, en clair et en sombre', function () {
    Artisan::call('migrate:fresh', ['--seed' => true]);
    // Les tests Feature suivants (s'il y en a dans ce processus) repartiront d'une base propre.
    RefreshDatabaseState::$migrated = false;

    $moussa = User::where('email', Demo::MOUSSA_EMAIL)->sole();
    $longest = User::with('profile')->get()->sortByDesc(fn (User $u) => mb_strlen($u->name.$u->profile->headline))->first();

    $server = serve();

    try {
        $visitor = measure(['/', '/devs', '/devs?competence=laravel', "/devs/{$moussa->id}", "/devs/{$longest->id}", '/connexion', '/inscription', '/mot-de-passe-oublie', '/devs/999999']);
        $member = measure(['/', '/devs', "/devs/{$moussa->id}", "/devs/{$moussa->id}/modifier"], 'Moussa');
    } finally {
        $server->stop();
    }

    expect($visitor)->toHaveCount(18)->and($member)->toHaveCount(8);

    // Garde-fou : les pages ont bien été servies par l'application (et non une page d'erreur).
    expect(collect([...$visitor, ...$member])->pluck('title')->filter(fn ($t) => str_contains($t, 'DevKora')))->toHaveCount(26);

    foreach ([...$visitor, ...$member] as $page) {
        $where = "{$page['path']} ({$page['scheme']})";
        expect($page['scrollWidth'])->toBe(360, "La page défile horizontalement : $where")
            ->and($page['offenders'])->toBe([], "Élément hors de l'écran : $where")
            ->and($page['clip'])->toBe(['visible', 'visible'], "Débordement masqué au lieu d'être corrigé : $where")
            ->and($page['dark'])->toBe($page['scheme'] === 'dark', "Thème non appliqué : $where");
    }
});
