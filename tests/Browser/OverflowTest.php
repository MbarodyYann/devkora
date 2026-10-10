<?php

use App\Models\Article;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Question;
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

function measure(array $pages, ?string $persona = null, int $width = 360, bool $javascript = true): array
{
    $process = new Process(['node', 'tests/Browser/overflow.mjs', 'http://127.0.0.1:8765', (string) $width, json_encode($pages), ...($persona ? [$persona] : [])], base_path(), ['NOJS' => $javascript ? '0' : '1']);
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
        $question = Question::orderByDesc('answers_count')->first();
        $article = Article::orderByDesc('reading_minutes')->first();
        $hackathon = Event::where('type', 'hackathon')->where('starts_at', '>', now())->first();
        $aissatou = User::where('email', Demo::AISSATOU_EMAIL)->sole();
        $openMission = Listing::where('user_id', $aissatou->id)->where('type', 'mission')->where('status', 'open')->sole();
        $doneMission = Listing::where('status', 'completed')->has('review')->first();
        $team = Listing::where('type', 'team')->has('acceptedProposals')->first();
        $visitor = measure(['/evenements', "/evenements/{$hackathon->id}", '/privileges', '/annonces', "/annonces/{$openMission->id}", "/annonces/{$doneMission->id}", "/annonces/{$team->id}", '/articles', "/articles/{$article->slug}", '/questions', '/questions?statut=resolues', "/questions/{$question->id}", '/', '/devs', '/devs?competence=laravel', "/devs/{$moussa->id}", "/devs/{$longest->id}", '/connexion', '/inscription', '/mot-de-passe-oublie', '/devs/999999']);
        $member = measure(['/', '/devs', "/devs/{$moussa->id}", "/devs/{$moussa->id}/modifier", '/questions', '/questions/poser', "/questions/{$question->id}", '/articles/ecrire', '/annonces/publier', "/annonces/{$openMission->id}", "/evenements/{$hackathon->id}", '/evenements/proposer', '/notifications', '/privileges'], 'Moussa');
        $moderator = measure(['/moderation'], 'Koffi');
        $author = measure(["/annonces/{$openMission->id}", "/annonces/{$openMission->id}/reponses/{$openMission->proposals()->first()->id}/paiement"], 'Aïssatou');
        $desktop = measure(['/', '/devs', "/devs/{$moussa->id}", "/questions/{$question->id}", "/articles/{$article->slug}", "/annonces/{$openMission->id}"], width: 1280);
        // Sans JavaScript (script pas encore arrivé en 3G) : la bande au-dessus de la barre reste couverte.
        $noScript = measure(['/', '/questions', "/devs/{$moussa->id}"], javascript: false);
    } finally {
        $server->stop();
    }

    expect($visitor)->toHaveCount(42)->and($member)->toHaveCount(28)->and($moderator)->toHaveCount(2)->and($author)->toHaveCount(4)->and($desktop)->toHaveCount(12)->and($noScript)->toHaveCount(6);

    // Garde-fou : les pages ont bien été servies par l'application (et non une page d'erreur).
    expect(collect([...$visitor, ...$member, ...$moderator, ...$author, ...$desktop, ...$noScript])->pluck('title')->filter(fn ($t) => str_contains($t, 'DevKora')))->toHaveCount(94);

    // F1 : la barre d'action de la fiche (Aïssatou, 360 px, défilée à 250 px) est visible et opaque.
    $bars = collect($author)->filter(fn ($p) => $p['actionBar'] !== null);
    expect($bars)->toHaveCount(2); // fiche en clair et en sombre
    foreach ($bars as $page) {
        expect($page['actionBar'])->toBe(['opacity' => 1, 'bgAlpha' => 1, 'buttonOpacity' => 1, 'hit' => true], "Barre d'action translucide : {$page['path']} ({$page['scheme']})");
    }

    foreach ($noScript as $page) {
        expect($page['navTopCovered'])->toBeTrue("Texte visible au-dessus de la navigation sans JavaScript : {$page['path']} ({$page['scheme']})");
    }

    foreach ([...$visitor, ...$member, ...$moderator, ...$author, ...$desktop] as $page) {
        $where = "{$page['path']} ({$page['scheme']}, {$page['viewport']} px)";
        expect($page['scrollWidth'])->toBe($page['viewport'], "La page défile horizontalement : $where ".json_encode($page['offenders']))
            // Navigation défilée : opaque sur mobile, 96 % au moins sur ordinateur.
            ->and($page['navAlpha'])->toBeGreaterThanOrEqual($page['viewport'] < 768 ? 1.0 : 0.96, "Texte lisible sous la navigation : $where")
            // Sur mobile, rien de la page au-dessus de la barre une fois défilée (sur ordinateur, la barre flotte : accepté).
            ->and($page['viewport'] >= 1024 || $page['navTopCovered'])->toBeTrue("Texte visible au-dessus de la navigation : $where")
            ->and($page['offenders'])->toBe([], "Élément hors de l'écran : $where")
            ->and($page['clip'])->toBe(['visible', 'visible'], "Débordement masqué au lieu d'être corrigé : $where")
            ->and($page['dark'])->toBe($page['scheme'] === 'dark', "Thème non appliqué : $where");
    }
});
