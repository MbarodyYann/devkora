<?php

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

require_once __DIR__.'/server.php';

/*
| /a-propos dans Chromium (Playwright), sur la base semée : aucun débordement horizontal à 320, 360 et 1 280 px,
| en clair et en sombre, pour le visiteur et pour un membre ; CSP de production sans violation.
| Port à part (8771) : ce test ne gêne pas ceux qui utilisent le port 8765.
*/

const ABOUT_PORT = 8771;

function aboutMeasure(int $width, ?string $persona = null): array
{
    $process = new Process(['node', 'tests/Browser/overflow.mjs', 'http://127.0.0.1:'.ABOUT_PORT, (string) $width, json_encode(['/a-propos']), ...($persona ? [$persona] : [])], base_path(), ['NOJS' => '0']);
    $process->setTimeout(180)->mustRun();

    return json_decode($process->getOutput(), true);
}

function aboutCsp(?string $persona = null): array
{
    $process = new Process(['node', 'tests/Browser/csp.mjs', 'http://127.0.0.1:'.ABOUT_PORT, json_encode(['/a-propos']), ...($persona ? [$persona] : [])], base_path());
    $process->setTimeout(180)->mustRun();

    return json_decode($process->getOutput(), true);
}

test('/a-propos : aucun débordement à 320, 360 et 1 280 px, et CSP de production sans violation', function () {
    Artisan::call('migrate:fresh', ['--seed' => true]);
    RefreshDatabaseState::$migrated = false;

    $server = serve(ABOUT_PORT);

    try {
        $measures = [
            ...aboutMeasure(320), ...aboutMeasure(360), ...aboutMeasure(1280),
            ...aboutMeasure(320, 'Moussa'), ...aboutMeasure(360, 'Moussa'),
        ];
        $csp = [...aboutCsp(), ...aboutCsp('Moussa')];
    } finally {
        $server->stop();
    }

    // 5 mesures, en clair et en sombre.
    expect($measures)->toHaveCount(10);

    foreach ($measures as $page) {
        $where = "{$page['path']} ({$page['scheme']}, {$page['viewport']} px)";
        expect($page['title'])->toBe('À propos · DevKora', "Page d'erreur servie : $where")
            ->and($page['scrollWidth'])->toBe($page['viewport'], "La page défile horizontalement : $where ".json_encode($page['offenders']))
            ->and($page['offenders'])->toBe([], "Élément hors de l'écran : $where")
            ->and($page['clip'])->toBe(['visible', 'visible'], "Débordement masqué au lieu d'être corrigé : $where")
            ->and($page['dark'])->toBe($page['scheme'] === 'dark', "Thème non appliqué : $where")
            // Navigation défilée : opaque sur mobile, 96 % au moins sur ordinateur.
            ->and($page['navAlpha'])->toBeGreaterThanOrEqual($page['viewport'] < 768 ? 1.0 : 0.96, "Texte lisible sous la navigation : $where")
            ->and($page['viewport'] >= 1024 || $page['navTopCovered'])->toBeTrue("Texte visible au-dessus de la navigation : $where");
    }

    $policy = (string) Str::of(file_get_contents(base_path('deploy/nginx/devkora-security-headers.conf')))->match('/Content-Security-Policy "([^"]+)"/');
    expect($csp)->toHaveCount(2);

    foreach ($csp as $page) {
        expect($page['status'])->toBe(200)
            ->and($page['title'])->toBe('À propos · DevKora')
            ->and($page['csp'])->toBe($policy, 'CSP absente')
            ->and($page['alpine'])->toBeTrue("Alpine n'a pas démarré")
            ->and($page['violations'])->toBe([], 'Violation de la CSP');
    }

    // Les interactions ont bien eu lieu : bascule du thème pour le visiteur, menu « Publier » pour le membre.
    expect($csp[0]['actions'])->toContain('sombre', 'clair')
        ->and($csp[1]['actions'])->toContain('menu');
});
