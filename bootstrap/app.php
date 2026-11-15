<?php

use App\Http\Middleware\TouchLastSeen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Dernière activité des membres (« Actif aujourd'hui »), au plus toutes les 5 minutes.
        $middleware->web(append: [TouchLastSeen::class]);
        // Un membre connecté qui ouvre /connexion ou /inscription revient à l'accueil.
        $middleware->redirectUsersTo('/');
        // En production, le proxy de l'hébergeur termine le HTTPS : on lit X-Forwarded-Proto (TRUSTED_PROXIES=*).
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
