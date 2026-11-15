<?php

use App\Models\Answer;
use App\Models\Article;
use App\Models\Profile;
use App\Models\Question;
use App\Models\User;
use App\Services\CommunityStats;
use App\Support\Copy;
use Illuminate\Support\Facades\Cache;

/*
| /a-propos : une page courte. La présentation en trois phrases, les chiffres réels, l'équipe,
| l'usage de l'IA mot pour mot et les licences.
*/

/** Le contenu de la page, sans la navigation ni le pied de page. */
function aboutMain(string $html): string
{
    $start = strpos($html, '<main');

    return substr($html, $start, strpos($html, '</main>') - $start);
}

test('À propos : présentation, chiffres, équipe, IA et licences, dans cet ordre', function () {
    $this->get('/a-propos')->assertOk()->assertSeeInOrder([
        '<title>À propos · DevKora</title>',
        'À propos de DevKora</h1>',
        'En chiffres',
        'L\'équipe',
        'Usage de l\'intelligence artificielle',
        '>Licences</h2>',
    ], false);
});

test('À propos : la présentation tient en trois phrases, sans slogan', function () {
    $main = aboutMain($this->get('/a-propos')->getContent());
    $start = strpos($main, 'data-presentation');
    $presentation = substr($main, $start, strpos($main, '</div>', $start) - $start);

    expect(substr_count($presentation, '<p>'))->toBe(3)
        ->and($presentation)->toContain("DevKora est une plateforme d'échange entre développeurs africains.")
        // Aucune promesse fausse : le paiement est simulé, et c'est écrit.
        ->toContain('(paiement simulé)')
        ->and(substr_count($main, '<h1'))->toBe(1)
        ->and($main)->not->toContain('italic')
        ->not->toContain('Ne reste plus seul')
        ->not->toContain('Ici, on échange.')
        ->not->toContain('Le marché te répond.')
        ->not->toContain('Trouve des missions. Partage tes connaissances.');
});

test('À propos : ni image de présentation ni bouton d\'inscription dans la page, pour le visiteur comme pour le membre', function () {
    $html = $this->get('/a-propos')->getContent();

    expect(aboutMain($html))->not->toContain('<img')->not->toContain('<picture')
        ->not->toContain(Copy::SIGNUP_CTA)
        ->not->toContain('href="'.route('register').'"')
        ->and($html)->not->toContain('id="hero"');

    $member = $this->actingAs(User::factory()->withProfile()->create())->get('/a-propos')->assertOk()->getContent();
    expect($member)->toContain('À propos de DevKora</h1>')->not->toContain(Copy::SIGNUP_CTA);
});

test('À propos : les chiffres sont réels', function () {
    User::factory()->count(3)->withProfile(['country' => 'SN'])->create();
    User::factory()->withProfile(['country' => 'GH'])->create();
    User::factory()->create(); // sans profil : pas compté
    $resolved = Question::factory()->create();
    $resolved->forceFill(['accepted_answer_id' => Answer::factory()->for($resolved)->create()->id])->save();
    Question::factory()->create();
    Article::factory()->count(2)->create();
    Article::factory()->create(['hidden_at' => now()]);

    $html = $this->get('/a-propos')->assertSee('🇸🇳')->getContent();
    $band = substr($html, strpos($html, 'aria-label="DevKora en chiffres"'), 4000);
    preg_match_all('/<dt[^>]*>([^<]+)<\/dt>/', $band, $labels);
    $stats = app(CommunityStats::class)->get();

    expect(array_map('trim', $labels[1]))->toBe(['Devs inscrits', 'Pays', 'Questions résolues', 'Articles partagés'])
        ->and($band)->toContain('data-stat="devs">'.$stats['devs'].'<')
        ->toContain('data-stat="countries">'.$stats['countries'].'<')
        ->toContain('data-stat="resolved">1<')
        ->toContain('data-stat="articles">2<')
        ->toContain('href="'.route('pulse').'"')
        ->and($stats['flags'])->toContain('🇸🇳', '🇬🇭')
        ->and($stats['devs'])->toBe(Profile::count())
        ->and($stats['devs'])->toBeGreaterThanOrEqual(4);
});

test('À propos : les chiffres sont mis en cache 10 minutes', function () {
    User::factory()->withProfile()->create();
    $this->get('/a-propos');
    User::factory()->withProfile()->create();

    $this->get('/a-propos')->assertSee('data-stat="devs">1<', false);

    Cache::forget(CommunityStats::CACHE_KEY);
    $this->get('/a-propos')->assertSee('data-stat="devs">2<', false);
    expect(CommunityStats::CACHE_SECONDS)->toBe(600);
});

test('À propos : 5 drapeaux sur une ligne, puis le reste en texte', function () {
    foreach (['SN', 'CI', 'TG', 'BJ', 'ML', 'BF', 'CM', 'GN', 'NE', 'GH'] as $country) {
        User::factory()->withProfile(['country' => $country])->create();
    }

    $html = $this->get('/a-propos')->getContent();
    $start = strpos($html, 'data-flags');
    $flags = substr($html, $start, strpos($html, '</dd>', $start) - $start);

    expect(substr_count($flags, '<span>'))->toBe(5)
        ->and($flags)->toContain('>+5</span>')
        ->and($html)->toContain('flex-nowrap');
});

test('À propos : équipe et déclaration de l\'IA mot pour mot', function () {
    $main = aboutMain($this->get('/a-propos')->getContent());

    expect($main)->toContain('<strong class="font-semibold text-ink">Baobab Stack</strong> (Sénégal)')
        ->toContain('Yaya Niang')->toContain('Aminata Sall')->toContain('Baydi Sao')
        ->not->toContain('eprésentant')
        ->toContain('<li><strong class="font-semibold text-ink">Claude Code (Anthropic)</strong> : code, tests et revue de code.</li>')
        ->toContain("<li><strong class=\"font-semibold text-ink\">ChatGPT (OpenAI)</strong> : image d'illustration de l'accueil.</li>")
        ->toContain('data-licenses');

    // Trois membres, sans autre titre que « Membre de l'équipe ».
    $teamStart = strpos($main, 'data-team');
    $team = substr($main, $teamStart, strpos($main, '</ul>', $teamStart) - $teamStart);
    expect(substr_count($team, '<li'))->toBe(3)->and(substr_count($team, 'Membre de l&#039;équipe') + substr_count($team, "Membre de l'équipe"))->toBe(3);

    // Un titre, puis la liste : deux lignes, sans phrase d'introduction (11, « Ne jamais se justifier »).
    $start = strpos($main, 'id="ia-titre"');
    $ai = substr($main, $start, strpos($main, '</section>', $start) - $start);
    expect(substr_count($ai, '<li>'))->toBe(2)->and(substr_count($ai, '<p'))->toBe(0);
});

test('À propos : deux couleurs, aucun dégradé, aucun point devant les textes', function () {
    $view = file_get_contents(resource_path('views/about.blade.php'));

    expect($view)->not->toMatch('/gradient|from-|via-|bg-(red|green|emerald|rose|blue|sky|slate|gray|zinc)-/')
        ->not->toContain('●')->not->toContain('•');
});
