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

require_once __DIR__.'/server.php';

/*
| CSP de production (deploy/nginx/devkora-security-headers.conf) appliquée en local par router.php :
| aucune violation sur aucune page, en visiteur et pour chaque rôle, y compris ce qui exécute du JavaScript
| (Livewire, Alpine, Copier le lien, thème, recherche, vote, menus).
*/

function csp(array $pages, ?string $persona = null): array
{
    $process = new Process(['node', 'tests/Browser/csp.mjs', 'http://127.0.0.1:8765', json_encode($pages), ...($persona ? [$persona] : [])], base_path());
    $process->setTimeout(300)->mustRun();

    return json_decode($process->getOutput(), true);
}

/** Statut HTTP d'un formulaire envoyé sans jeton CSRF (requête réelle, hors du client HTTP de Laravel). */
function postWithoutToken(string $url): int
{
    $context = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => 'email=x%40y.z', 'ignore_errors' => true]]);
    file_get_contents($url, false, $context);

    return (int) explode(' ', $http_response_header[0])[1];
}

test('la CSP de production ne bloque rien, sur toutes les pages et pour chaque rôle', function () {
    Artisan::call('migrate:fresh', ['--seed' => true]);
    RefreshDatabaseState::$migrated = false;

    $moussa = User::where('email', Demo::MOUSSA_EMAIL)->sole();
    $aissatou = User::where('email', Demo::AISSATOU_EMAIL)->sole();
    $question = Question::orderByDesc('answers_count')->first();
    $other = Question::where('user_id', '!=', $moussa->id)->latest()->first();
    $article = Article::orderByDesc('reading_minutes')->first();
    $event = Event::where('starts_at', '>', now())->first();
    $openMission = Listing::where('user_id', $aissatou->id)->where('type', 'mission')->where('status', 'open')->sole();
    $doneMission = Listing::where('status', 'completed')->has('review')->first();

    $server = serve();

    try {
        $visitor = csp(['/', '/questions', "/questions/{$question->id}", '/articles', "/articles/{$article->slug}", '/annonces', "/annonces/{$openMission->id}", "/annonces/{$doneMission->id}", '/evenements', "/evenements/{$event->id}", '/devs', "/devs/{$moussa->id}", '/pouls', '/a-propos', '/privileges', '/villes/dakar', '/connexion', '/inscription', '/mot-de-passe-oublie', '/devs/999999']);
        $member = csp(['/', "/questions/{$other->id}", '/questions/poser', '/articles/ecrire', '/annonces/publier', '/evenements/proposer', "/devs/{$moussa->id}/modifier", '/notifications', "/annonces/{$openMission->id}"], 'Moussa');
        $moderator = csp(['/moderation'], 'Koffi');
        $author = csp(["/annonces/{$openMission->id}", "/annonces/{$openMission->id}/reponses/{$openMission->proposals()->first()->id}/paiement"], 'Aïssatou');
        // CSRF (désactivé dans les tests Feature, actif ici) : un formulaire envoyé sans jeton est refusé.
        $csrf = collect(['/connexion', '/inscription', '/demo/moussa', '/livewire/update'])->mapWithKeys(fn ($path) => [$path => postWithoutToken("http://127.0.0.1:8765$path")]);
    } finally {
        $server->stop();
    }

    $pages = collect([...$visitor, ...$member, ...$moderator, ...$author]);
    expect($pages)->toHaveCount(32)
        ->and($pages->pluck('title')->filter(fn ($t) => str_contains($t, 'DevKora')))->toHaveCount(32);

    $csp = (string) Str::of(file_get_contents(base_path('deploy/nginx/devkora-security-headers.conf')))->match('/Content-Security-Policy "([^"]+)"/');
    foreach ($pages as $page) {
        expect($page['csp'])->toBe($csp, "CSP absente : {$page['path']}")
            ->and($page['alpine'])->toBeTrue("Alpine n'a pas démarré : {$page['path']}")
            ->and($page['violations'])->toBe([], "Violation de la CSP : {$page['path']}");
    }

    expect($csrf->unique()->values()->all())->toBe([419], 'Envoi sans jeton CSRF accepté : '.$csrf->toJson());

    // Les interactions ont bien eu lieu (sinon le test ne prouverait rien).
    $actions = $pages->pluck('actions')->flatten();
    expect($actions)->toContain('copier', 'sombre', 'clair', 'recherche', 'vote', 'menu');
    fwrite(STDERR, '[csp] '.$pages->count().' pages, actions : '.$actions->countBy()->map(fn ($n, $a) => "$a × $n")->join(', ').PHP_EOL);
});
