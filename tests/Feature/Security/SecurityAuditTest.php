<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as Router;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/*
| Audit de sécurité automatisé (documentation 13) : chaque point de l'audit a sa preuve exécutable ici.
*/

/** @return Collection<int, Route> */
function writeRoutes()
{
    return collect(Router::getRoutes()->getRoutes())
        ->filter(fn (Route $route) => array_diff($route->methods(), ['GET', 'HEAD']) !== []);
}

test('chaque route d\'écriture (POST, PUT, PATCH, DELETE) est limitée par un throttle', function () {
    $routes = writeRoutes();

    expect($routes->count())->toBeGreaterThan(25);
    foreach ($routes as $route) {
        $throttled = collect(Router::gatherRouteMiddleware($route))->contains(fn ($m) => str_starts_with($m, ThrottleRequests::class));
        expect($throttled)->toBeTrue('Sans throttle : '.implode('|', $route->methods()).' '.$route->uri());
    }
});

test('chaque route d\'écriture exige une connexion, sauf l\'authentification, la démo et Livewire', function () {
    $public = ['connexion', 'inscription', 'mot-de-passe-oublie', 'nouveau-mot-de-passe', 'demo/{persona}', 'livewire/update', 'livewire/upload-file'];

    foreach (writeRoutes() as $route) {
        if (in_array($route->uri(), $public, true)) {
            continue;
        }
        $authenticated = collect(Router::gatherRouteMiddleware($route))->contains(fn ($m) => str_starts_with($m, Authenticate::class));
        expect($authenticated)->toBeTrue('Accessible sans connexion : '.$route->uri());
    }
});

test('chaque modèle déclare ses champs remplissables, ou interdit toute affectation de masse', function () {
    $models = collect(Finder::create()->files()->in(app_path('Models'))->depth(0)->name('*.php'))
        ->map(fn ($file) => 'App\\Models\\'.$file->getBasename('.php'))
        ->filter(fn ($class) => is_subclass_of($class, Model::class));

    expect($models->count())->toBeGreaterThanOrEqual(15);
    foreach ($models as $class) {
        $model = new $class;
        expect($model->getFillable() !== [] || $model->getGuarded() === ['*'])->toBeTrue("Affectation de masse ouverte : $class");
    }
    // Personne ne désactive la protection.
    expect(Model::isUnguarded())->toBeFalse();
});

test('cookie de session : HttpOnly, SameSite=Lax, Secure piloté par SESSION_SECURE_COOKIE', function () {
    config(['session.secure' => true]);
    $cookie = collect($this->get('/')->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === config('session.cookie'));

    expect($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax')
        ->and($cookie->isSecure())->toBeTrue();
});

test('fichiers sensibles : aucun .env ni clé dans Git, seul index.php est un fichier PHP public', function () {
    $tracked = new Process(['git', 'ls-files'], base_path());
    $tracked->mustRun();
    $files = collect(explode("\n", trim($tracked->getOutput())));

    expect($files->filter(fn ($f) => preg_match('#(^|/)\.env($|\.(?!example))|\.(pem|key|p12)$|^documentation/|^storage/.*\.log$#', $f)))->toBeEmpty()
        ->and(collect(Finder::create()->files()->in(public_path())->name('*.php'))->map->getRelativePathname()->values()->all())->toBe(['index.php']);
});

test('paiement simulé : aucune requête HTTP sortante dans le code de l\'application', function () {
    $outgoing = collect(Finder::create()->files()->in(app_path())->name('*.php'))
        ->filter(fn ($file) => preg_match('/\bHttp::|GuzzleHttp|curl_init|file_get_contents\(\s*[\'"]https?:/', $file->getContents()))
        ->map->getRelativePathname();

    expect($outgoing->values()->all())->toBe([]);
});

test('SQL : les seules expressions brutes avec une saisie passent par des paramètres liés', function () {
    // Toute expression brute qui contient une variable PHP doit l'avoir reçue du code, jamais de la requête.
    $raw = collect(Finder::create()->files()->in(app_path())->name('*.php'))
        ->flatMap(fn ($file) => collect(explode("\n", $file->getContents()))
            ->filter(fn ($line) => preg_match('/(Raw|DB::statement|DB::select)\(\s*"[^"]*\$/', $line))
            ->map(fn ($line) => $file->getRelativePathname().' : '.trim($line)));

    // Searchable : $vector et $tsquery sont construits à partir des noms de colonnes ; les termes sont liés (?).
    expect($raw->values()->all())->toBe([
        'Models/Concerns/Searchable.php : $query->whereRaw("$vector @@ $tsquery", [$terms])',
        'Models/Concerns/Searchable.php : ->orderByRaw("ts_rank($vector, $tsquery) desc", [$terms]);',
    ]);
});
