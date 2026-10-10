<?php

use App\Models\User;
use App\Support\Copy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;

/** Pages publiques du lot 1. */
function publicPages(): array
{
    $user = User::factory()->withProfile()->create();

    return ['/', '/devs', route('devs.show', $user, false), '/connexion', '/inscription', '/mot-de-passe-oublie', '/devs/999999'];
}

test('le visiteur voit le CTA unique dans la navigation, mot pour mot', function () {
    foreach (['/', '/devs', '/connexion'] as $url) {
        $html = $this->get($url)->getContent();
        expect($html)->toContain('data-nav-cta="true">'.Copy::SIGNUP_CTA.'</a>');
    }
});

test('le membre voit son menu, pas le CTA', function () {
    $user = User::factory()->withProfile()->create();

    $this->actingAs($user)->get('/devs')
        ->assertDontSee(Copy::SIGNUP_CTA)
        ->assertSee('Mon profil')
        ->assertSee('Modifier mon profil')
        ->assertSee('Me déconnecter');
});

test('la navigation ne montre que les rubriques qui existent', function () {
    $html = $this->get('/devs')->getContent();

    expect($html)->toContain('aria-current="page"')
        ->not->toContain('>Questions<')
        ->and(Route::has('questions.index'))->toBeFalse();
});

test('chaque page : français, thème sans flash, favicon, lien d\'évitement, pied de page lisible', function () {
    foreach (publicPages() as $url) {
        $html = $this->get($url)->getContent();

        expect($html)->toContain('<html lang="fr">')
            ->toContain("localStorage.getItem('theme')")
            ->toContain('<meta name="theme-color" content="#314A82">')
            ->toContain('href="/favicon.svg"')
            ->toContain('Aller au contenu')
            ->toContain('Le point de rencontre des développeurs africains pour travailler, apprendre et grandir ensemble.')
            ->toContain('<span class="italic text-primary-600 dark:text-secondary-400">Construis avec les tiens.</span>')
            ->toContain('aria-label="Thème"');
    }
});

test('tutoiement partout : aucun « vous » dans les pages', function () {
    $user = User::factory()->withProfile()->create();
    $pages = [...publicPages()];

    foreach ($pages as $url) {
        $text = strip_tags($this->get($url)->getContent());
        expect(preg_match('/\b(vous|votre|vos)\b/iu', $text))->toBe(0, "« vous » trouvé sur $url");
    }

    $text = strip_tags($this->actingAs($user)->get(route('profile.edit', $user))->getContent());
    expect(preg_match('/\b(vous|votre|vos)\b/iu', $text))->toBe(0);
});

test('Livewire seulement sur les pages qui ont un composant, Alpine seul ailleurs', function (string $url, bool $livewire) {
    SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest = false;

    $html = $this->get($url)->assertOk()->getContent();

    expect(str_contains($html, 'livewire.min.js') || str_contains($html, 'livewire.js'))->toBe($livewire)
        ->and(str_contains($html, 'alpine'))->toBe(! $livewire);
})->with([
    'accueil' => ['/', false],
    'connexion' => ['/connexion', false],
    'annuaire' => ['/devs', true],
]);

test('chaque route POST, PUT, PATCH ou DELETE a une limite de débit', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $throttled = collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'throttle:'))
            || in_array('throttle:auth', config('fortify.middleware'), true) && str_starts_with($route->getActionName(), 'Laravel\Fortify');
        expect($throttled)->toBeTrue('Pas de limite sur '.$route->uri());
    }
});

test('le chargement paresseux est interdit hors production (pas de N+1)', function () {
    expect(Model::preventsLazyLoading())->toBeTrue();
});

test('charte : aucun dégradé, aucun point devant un texte, la palette par défaut désactivée', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $views = collect(File::allFiles(resource_path('views')))->map(fn ($f) => $f->getContents())->join("\n");

    expect($css)->toContain('--color-*: initial;')
        ->toContain('--color-primary-600: #314a82;')
        ->toContain('--color-secondary-400: #f9b218;')
        ->not->toMatch('/gradient/i')
        ->and($views)->not->toMatch('/gradient|bg-linear|bg-radial|●|•/i')
        // Aucun masquage du débordement horizontal : il est mesuré (tests/Browser).
        ->and($css.$views)->not->toMatch('/overflow-x:\s*clip|overflow-x-clip/');
});

test('tous les boutons sont ambre : aplat ou contour', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toMatch('/@utility btn-amber \{\s+color: var\(--color-primary-950\);\s+background-color: var\(--color-secondary-400\);/')
        ->toMatch('/@utility btn-outline \{\s+color: var\(--color-ink\);\s+background-color: transparent;\s+border: 1.5px solid var\(--color-secondary-400\);/');

    // Chaque <button> visible des vues porte une classe de bouton ambre, ou est un contrôle d'interface
    // (menus, segmentés, liens de pagination) sans fond coloré.
    $views = collect(File::allFiles(resource_path('views')))->map(fn ($f) => $f->getContents())->join("\n");
    expect($views)->not->toMatch('/<button[^>]*class="[^"]*\bbg-primary-\d/');
});

test('aucun texte lisible sous la navigation : opaque sous 768 px, 96 % et flou au-dessus', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $nav = substr($css, strpos($css, '@utility nav-glass'), strpos($css, '@utility nav-menu') - strpos($css, '@utility nav-glass'));

    expect($nav)->toContain('color-mix(in srgb, var(--color-bg-alt) 96%, transparent)')
        ->toContain('color-mix(in srgb, var(--color-primary-800) 96%, transparent)')
        ->toContain('color-mix(in srgb, var(--color-primary-950) 96%, transparent)')
        ->not->toMatch('/\b([0-8]\d|9[0-5])%/')
        // Flou seulement à partir de 768 px : chaque backdrop-filter est dans un bloc @media (min-width: 768px).
        ->toContain('min-width: 768px')
        ->not->toContain('min-width: 1024px')
        ->and(preg_match_all('/^\s{4}(-webkit-)?backdrop-filter/m', $nav))->toBe(0);
});

test('aucun doublon de lien d\'inscription ou de connexion sur les pages d\'authentification', function () {
    expect(substr_count($this->get('/connexion')->getContent(), Copy::SIGNUP_CTA))->toBe(1);
    expect(substr_count($this->get('/mot-de-passe-oublie')->getContent(), 'Retour à la connexion'))->toBe(0);
    $register = $this->get('/inscription')->getContent();
    expect(substr_count($register, '>Connexion</a>'))->toBe(1)->and($register)->not->toContain('Connecte-toi');
});
