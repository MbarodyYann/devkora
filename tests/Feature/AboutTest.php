<?php

use App\Enums\ReputationReason;
use App\Http\Controllers\AboutController;
use App\Models\Answer;
use App\Models\Article;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Profile;
use App\Models\Proposal;
use App\Models\Question;
use App\Models\Skill;
use App\Models\User;
use App\Services\CommunityStats;
use App\Services\ReputationService;
use App\Support\Copy;
use Illuminate\Support\Facades\Cache;

/*
| /a-propos : la présentation de DevKora (hero, chiffres réels, piliers, aperçu du Pouls),
| puis l'identité, l'équipe, l'usage de l'IA et les licences.
*/

/** Trois montants (un budget, deux prix proposés) pour une compétence : le minimum pour un tarif du Pouls. */
function aboutRate(string $skill, int $budget): void
{
    $listing = Listing::factory()->create(['budget_fcfa' => $budget]);
    $listing->skills()->attach(Skill::factory()->create(['name' => $skill]));
    foreach ([$budget - 20000, $budget + 20000] as $price) {
        Proposal::factory()->for($listing)->create(['proposed_fcfa' => $price]);
    }
}

/** Le contenu de la page, sans la navigation ni le pied de page. */
function aboutBody(string $html): string
{
    $start = strpos($html, 'id="hero"');

    return substr($html, $start, strpos($html, '</main>') - $start);
}

test('À propos : les sections dans l\'ordre, du hero au CTA final', function () {
    aboutRate('Laravel', 150000);
    $answer = Answer::factory()->create();
    app(ReputationService::class)->award($answer->user, ReputationReason::AnswerAccepted, $answer);

    $this->get('/a-propos')->assertOk()->assertSeeInOrder([
        'Le point de rencontre',
        'Trouve des missions. Partage tes connaissances. Construis avec les tiens.',
        'Gratuit, sans abonnement',
        'Devs inscrits',
        'Ici, on échange.',
        'Combien facturer ? <em',
        'Ils ont fait avancer la communauté ce mois-ci.',
        'Une plateforme d&#039;échange africaine, pour les devs, par les devs',
        'L&#039;équipe',
        'Usage de l&#039;intelligence artificielle',
        'Les briques et leurs licences',
        'Les moyens de paiement cités',
        'Ne reste plus seul',
    ], false);
});

test('À propos : les sections de l\'accueil-produit n\'y sont pas', function () {
    Question::factory()->create(['title' => 'Une question du moment']);
    Article::factory()->create(['title' => 'Un article récent']);
    Listing::factory()->create(['title' => 'Une annonce ouverte']);
    Event::factory()->create(['title' => 'Un événement à venir']);

    $this->get('/a-propos')
        ->assertDontSee('Ce que les devs se demandent en ce moment.')
        ->assertDontSee('Ce que les devs du continent ont appris, pour toi.')
        ->assertDontSee('Des devs cherchent du renfort')
        ->assertDontSee('Les prochaines occasions de se rencontrer.')
        ->assertDontSee('Une question du moment')
        ->assertDontSee('Un article récent')
        ->assertDontSee('Une annonce ouverte')
        ->assertDontSee('Un événement à venir')
        ->assertDontSee('Pose ta question');
});

test('À propos, hero : titre, promesse, CTA du visiteur et réassurance', function () {
    $html = $this->get('/a-propos')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>À propos · DevKora</title>')
        ->toContain('<em class="block font-serif italic text-secondary-400">des devs africains.</em>')
        ->toContain('Trouve des missions. Partage tes connaissances. Construis avec les tiens.')
        ->toContain('<p class="mt-6 max-w-xl text-balance')
        ->and(substr_count($html, '<h1'))->toBe(1)
        ->and(substr_count($html, 'data-cta="principal"'))->toBe(1)
        ->and(substr_count($html, 'data-cta="final"'))->toBe(1)
        // Le même libellé partout : navigation, hero, CTA final.
        ->and(substr_count($html, '>'.Copy::SIGNUP_CTA.'</a>'))->toBe(3)
        ->and(substr_count($html, 'href="'.route('register').'"'))->toBe(3)
        // Réassurance sous le CTA du hero et sous le CTA final.
        ->and(substr_count($html, Copy::SIGNUP_REASSURANCE))->toBe(2);
});

test('À propos : le CTA d\'inscription est réservé au visiteur ; le membre a un lien vers les questions', function () {
    $visitor = $this->get('/a-propos')->getContent();
    expect($visitor)->toContain('data-final-cta')
        ->toContain('<em class="font-serif italic text-secondary-400">face à ton code.</em>')
        ->not->toContain('data-member-link');

    $member = $this->actingAs(User::factory()->withProfile()->create())->get('/a-propos')->assertOk()->getContent();
    expect($member)->not->toContain(Copy::SIGNUP_CTA)
        ->not->toContain(Copy::SIGNUP_REASSURANCE)
        ->not->toContain('data-final-cta')
        ->not->toContain('data-cta=')
        ->not->toContain('href="'.route('register').'"')
        ->toContain('<a href="'.route('questions.index').'" class="text-[15px] font-semibold text-white underline decoration-secondary-400 decoration-2 underline-offset-4 hover:text-secondary-400" data-member-link>Voir les questions →</a>');
});

test('À propos : l\'image du hero, WebP en deux tailles, dimensions et texte alternatif, chargée à la demande sur mobile', function () {
    $html = $this->get('/a-propos')->getContent();

    expect($html)->toContain('srcset="/images/hero/portrait-480.webp 480w, /images/hero/portrait-800.webp 800w"')
        ->toContain('srcset="/images/hero/paysage-480.webp 480w, /images/hero/paysage-800.webp 800w"')
        ->toContain('width="800" height="450"')
        ->toContain('alt="Deux développeurs travaillent ensemble sur du code"')
        ->toContain('loading="lazy"')
        ->toContain('media="(min-width: 1024px)" fetchpriority="high"');

    foreach (['portrait-480' => 40_000, 'portrait-800' => 80_000, 'paysage-480' => 40_000, 'paysage-800' => 80_000] as $file => $max) {
        expect(filesize(public_path("images/hero/$file.webp")))->toBeLessThanOrEqual($max);
    }
});

test('À propos : bande de garanties et bande de confiance aux chiffres réels', function () {
    User::factory()->count(3)->withProfile(['country' => 'SN'])->create();
    User::factory()->withProfile(['country' => 'GH'])->create();
    User::factory()->create(); // sans profil : pas compté
    $resolved = Question::factory()->create(); // son auteur a un profil : 5 devs
    $resolved->forceFill(['accepted_answer_id' => Answer::factory()->for($resolved)->create()->id])->save();
    Question::factory()->create();
    Article::factory()->count(2)->create();
    Article::factory()->create(['hidden_at' => now()]);

    $html = $this->get('/a-propos')
        ->assertSeeInOrder(['Gratuit, sans abonnement', 'Pensé pour ton contexte', 'Une réputation qui te suit'])
        ->assertSee('missions payées en Mobile Money')
        ->assertDontSee('carte bancaire')
        ->assertSee('🇸🇳')
        ->assertSee('🇬🇭')
        ->getContent();

    $band = substr($html, strpos($html, 'aria-label="DevKora en chiffres"'), 5000);
    preg_match_all('/<dt[^>]*>([^<]+)<\/dt>/', $band, $labels);
    $stats = app(CommunityStats::class)->get();

    expect(array_map('trim', $labels[1]))->toBe(['Devs inscrits', 'Pays', 'Questions résolues', 'Articles partagés'])
        ->and($band)->toContain('data-stat="devs">'.$stats['devs'].'<')
        ->toContain('data-stat="countries">'.$stats['countries'].'<')
        ->toContain('data-stat="resolved">1<')
        ->toContain('data-stat="articles">2<')
        ->and($stats['devs'])->toBe(Profile::count())
        ->and($stats['devs'])->toBeGreaterThanOrEqual(4);

    // « Devs inscrits » affiche le chiffre seul, sans pile d'avatars.
    $devs = substr($html, strpos($html, 'Devs inscrits'), strpos($html, '>Pays<') - strpos($html, 'Devs inscrits'));
    expect(substr_count($devs, '<dd'))->toBe(1);
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

test('À propos, bande de confiance : sur une ligne, 5 drapeaux sous 360 px, 6 sous 640 px et 8 au-dessus, puis le reste en texte', function () {
    foreach (['SN', 'CI', 'TG', 'BJ', 'ML', 'BF', 'CM', 'GN', 'NE', 'GH'] as $country) {
        User::factory()->withProfile(['country' => $country])->create();
    }

    $html = $this->get('/a-propos')->getContent();
    $flags = substr($html, strpos($html, 'data-flags'), 1500);

    expect(substr_count($flags, '<span class="">') + substr_count($flags, '<span>'))->toBe(5)
        ->and(substr_count($flags, '<span class="max-[360px]:hidden">'))->toBe(1)
        ->and(substr_count($flags, '<span class="max-sm:hidden">'))->toBe(2)
        ->and($flags)->toContain('max-[360px]:inline">+5</span>')
        ->toContain('max-[360px]:hidden sm:hidden">+4</span>')
        ->toContain('sm:inline">+2</span>')
        ->and($html)->toContain('flex-nowrap items-center gap-1 text-base leading-7" aria-hidden="true" data-flags');
});

test('À propos : les trois piliers, leurs textes et leurs liens', function () {
    $html = $this->get('/a-propos')->getContent();
    $pillars = substr($html, strpos($html, 'data-pillars'), 4000);

    expect($pillars)->toContain('Pose tes questions, partage tes tutoriels : la meilleure réponse reste trouvable par tous.')
        ->toContain('Trouve des coéquipiers d&#039;autres pays ou confie une mission à un dev de confiance.')
        ->toContain('Combien facturer ? Quelles compétences sont recherchées ? Les chiffres réels de la communauté.')
        ->toContain('href="'.route('questions.index').'"')
        ->toContain('href="'.route('listings.index').'"')
        ->toContain('href="'.route('pulse').'"')
        ->and(substr_count($pillars, '<h3'))->toBe(3);
    expect($html)->toContain('>Entraide</h3>')->toContain('>Communauté</h3>')->toContain('>Le Pouls</h3>');
});

test('À propos, aperçu du Pouls : 4 compétences au plus, tarifs réels, lien vers le Pouls', function () {
    foreach (['Laravel' => 150000, 'Flutter' => 200000, 'Wave' => 90000, 'Django' => 120000, 'React' => 110000] as $skill => $budget) {
        aboutRate($skill, $budget);
    }
    // Deux montants seulement : pas de tarif affiché.
    $thin = Listing::factory()->create(['budget_fcfa' => 300000]);
    $thin->skills()->attach(Skill::factory()->create(['name' => 'Kotlin']));
    Proposal::factory()->for($thin)->create(['proposed_fcfa' => 310000]);

    $html = $this->get('/a-propos')->getContent();
    $start = strpos($html, 'data-pulse-preview');
    $preview = substr($html, $start, strpos($html, '</section>', $start) - $start);

    expect(AboutController::RATES_PREVIEW)->toBe(4)
        ->and(substr_count($preview, 'data-rate='))->toBe(4)
        ->and($preview)->toContain('Le marché te répond.')
        ->toContain('href="'.route('pulse').'"')
        ->toContain('Voir tous les tarifs →')
        ->toContain("150\u{202F}000 <span")
        ->toContain("Fourchette habituelle : 140\u{202F}000 à 160\u{202F}000\u{00A0}FCFA")
        ->not->toContain('Kotlin');
});

test('À propos, aperçu du Pouls : aucun chiffre inventé, la section disparaît sans tarif', function () {
    $this->get('/a-propos')->assertOk()
        ->assertDontSee('data-pulse-preview', false)
        ->assertDontSee('Voir tous les tarifs');
});

test('À propos : identité, équipe et déclaration de l\'IA mot pour mot', function () {
    $body = aboutBody($this->get('/a-propos')->getContent());

    expect($body)->toContain("« Africaine » dit qui nous sommes. « D'échange » dit ce que tu viens y faire.")
        ->toContain('Plateforme d&#039;échange')->toContain('Africaine')->toContain('Pour les devs')->toContain('Par les devs')
        ->toContain('<strong class="font-semibold text-ink">Baobab Stack</strong> (Sénégal)')
        ->toContain('Yaya Niang')->toContain('Aminata Sall')
        ->toContain('<li><strong class="font-semibold text-ink">Claude Code (Anthropic)</strong> : code, tests et revue de code.</li>')
        ->toContain("<li><strong class=\"font-semibold text-ink\">ChatGPT (OpenAI)</strong> : image d'illustration de l'accueil.</li>")
        ->toContain('data-licenses');

    // Un titre, puis la liste : deux lignes, sans phrase d'introduction (11, « Ne jamais se justifier »).
    $start = strpos($body, 'id="ia-titre"');
    $ai = substr($body, $start, strpos($body, '</section>', $start) - $start);
    expect(substr_count($ai, '<li>'))->toBe(2)->and(substr_count($ai, '<p'))->toBe(0);
});

test('À propos : deux couleurs, aucun dégradé, aucun point devant les textes', function () {
    $view = file_get_contents(resource_path('views/about.blade.php'));

    expect($view)->not->toMatch('/gradient|from-|via-|bg-(red|green|emerald|rose|blue|sky|slate|gray|zinc)-/')
        ->not->toContain('●')->not->toContain('•');
});
