<?php

use Symfony\Component\Process\Process;

/*
| Fichiers de déploiement (deploy/, .env.example.production) : ce que le serveur recevra.
| La CSP elle-même est éprouvée dans Chromium par tests/Browser/CspTest.php.
*/

function securityConf(): string
{
    return (string) file_get_contents(base_path('deploy/nginx/devkora-security-headers.conf'));
}

test('le script anti-flash du thème est autorisé par son empreinte dans la CSP', function () {
    preg_match_all('#<script>(.*?)</script>#s', $this->get('/')->assertOk()->getContent(), $inline);

    // Un seul script en ligne sur la page : l'anti-flash. Le modifier impose de mettre la CSP à jour.
    expect($inline[1])->toHaveCount(1)
        ->and(securityConf())->toContain("'sha256-".base64_encode(hash('sha256', $inline[1][0], true))."'");
});

test('les en-têtes de sécurité : HSTS, cadre interdit, nosniff, référent, permissions, CSP sans origine externe', function () {
    $conf = securityConf();

    expect($conf)->toContain('Strict-Transport-Security "max-age=31536000; includeSubDomains" always')
        ->toContain('X-Frame-Options "DENY" always')
        ->toContain('X-Content-Type-Options "nosniff" always')
        ->toContain('Referrer-Policy "strict-origin-when-cross-origin" always')
        ->toContain('Permissions-Policy "camera=(), microphone=(), geolocation=()')
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'")
        ->toContain("base-uri 'self'")
        ->toContain("form-action 'self'")
        ->not->toContain('https:')
        ->not->toContain('*');
});

test('Nginx : HTTPS, gzip, assets en cache longue durée, 6 Mo, fichiers cachés refusés, seul index.php exécuté', function () {
    $app = (string) file_get_contents(base_path('deploy/nginx/devkora-app.conf'));
    $https = (string) file_get_contents(base_path('deploy/nginx/devkora.conf'));

    expect($https)->toContain('listen 443 ssl http2;')->toContain('return 301 https://')->toContain('/etc/letsencrypt/live/')
        ->and($app)->toContain('gzip on;')
        ->toContain('location ^~ /build/')
        ->toContain('max-age=31536000, immutable')
        ->toContain('client_max_body_size 6m;')
        ->toContain('location ~ /\.(?!well-known/)')
        ->toContain('location = /index.php')
        ->toContain("location ~ \\.php$ {\n    return 404;")
        ->toContain('include snippets/devkora-security-headers.conf;')
        // Chaque emplacement qui ajoute ses en-têtes inclut aussi ceux de sécurité (Nginx ne les hérite pas).
        ->and(substr_count($app, 'add_header'))->toBe(substr_count($app, 'include snippets/devkora-security-headers.conf;') - 1);
});

test('PHP-FPM : pool dédié sous devkora, envoi de 4 Mo ; planificateur systemd chaque minute', function () {
    $pool = (string) file_get_contents(base_path('deploy/php-fpm/devkora.conf'));
    $service = (string) file_get_contents(base_path('deploy/systemd/devkora-scheduler.service'));
    $timer = (string) file_get_contents(base_path('deploy/systemd/devkora-scheduler.timer'));
    $nginx = (string) file_get_contents(base_path('deploy/nginx/devkora-app.conf'));

    expect($pool)->toContain('[devkora]')->toContain('user = devkora')->toContain('upload_max_filesize] = 4M')
        ->toContain('listen = /run/php/php8.2-fpm-devkora.sock')
        ->and($nginx)->toContain('unix:/run/php/php8.2-fpm-devkora.sock')
        ->and($service)->toContain('User=devkora')->toContain('artisan schedule:run')
        ->and($timer)->toContain('OnCalendar=*-*-* *:*:00')->toContain('Unit=devkora-scheduler.service');
});

test('deploy.sh : syntaxe valide (shellcheck s\'il est installé), exécutable, toutes les étapes', function () {
    $script = base_path('deploy/deploy.sh');
    $shellcheck = (new Process(['sh', '-c', 'command -v shellcheck']))->run() === 0;
    $check = new Process($shellcheck ? ['shellcheck', $script] : ['bash', '-n', $script]);
    $check->run();

    expect($check->isSuccessful())->toBeTrue($check->getErrorOutput().$check->getOutput())
        ->and(is_executable($script))->toBeTrue()
        ->and((string) file_get_contents($script))
        ->toContain('set -Eeuo pipefail')
        ->toContain('git merge --ff-only')
        ->toContain('install --no-dev')
        ->toContain('npm ci')
        ->toContain('npm run build')
        ->toContain('artisan migrate --force')
        ->toContain('artisan optimize')
        ->toContain('systemctl reload "$FPM_SERVICE"')
        ->toContain('artisan up');
});

test('.env.example.production : démo, production, cookies sécurisés, base devkora, aucun secret', function () {
    $env = (string) file_get_contents(base_path('.env.example.production'));
    $values = collect(preg_split('/\R/', $env))
        ->reject(fn ($line) => $line === '' || str_starts_with($line, '#'))
        ->mapWithKeys(fn ($line) => [strstr($line, '=', true) => substr(strstr($line, '='), 1)]);

    expect($values)->toMatchArray([
        'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_DEMO' => 'true',
        'SESSION_SECURE_COOKIE' => 'true', 'SESSION_HTTP_ONLY' => 'true', 'SESSION_ENCRYPT' => 'true',
        'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => 'devkora', 'DB_USERNAME' => 'devkora',
        // Aucun secret : vides, remplis sur le serveur.
        'APP_KEY' => '', 'DB_PASSWORD' => '',
    ])
        ->and(config('app.timezone'))->toBe('Africa/Dakar')
        ->and($env)->toContain('Africa/Dakar');
});
