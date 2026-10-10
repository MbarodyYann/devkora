<?php

use App\Enums\ReputationReason;
use App\Models\Answer;
use App\Models\Article;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Question;
use App\Models\User;
use App\Services\ReputationService;
use App\Support\Copy;
use App\Support\Credits;

test('accueil complet, dans l\'ordre de 08', function () {
    $resolved = Question::factory()->create();
    $resolved->forceFill(['accepted_answer_id' => Answer::factory()->for($resolved)->create()->id])->save();
    Article::factory()->create();
    Listing::factory()->create();
    Event::factory()->create();
    $answer = Answer::factory()->create();
    app(ReputationService::class)->award($answer->user, ReputationReason::AnswerAccepted, $answer);

    $this->get('/')->assertSeeInOrder([
        'Le point de rencontre',
        'Gratuit, sans carte bancaire',
        'Devs inscrits',
        'Ici, on échange.',
        'Ces blocages ont déjà trouvé leur solution.',
        'Ce que les devs du continent ont appris, pour toi.',
        'Des devs cherchent du renfort, peut-être le tien.',
        'Les prochaines occasions de se rencontrer.',
        'Ils ont fait avancer la communauté ce mois-ci.',
        'Ne reste plus seul',
    ]);
});

test('accueil : les trois piliers et le CTA final, sans doublon d\'action', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('Pose tes questions, partage tes tutoriels : la meilleure réponse reste trouvable par tous.')
        ->toContain('Trouve des coéquipiers d&#039;autres pays ou confie une mission à un dev de confiance.')
        ->toContain('Combien facturer ? Quelles compétences sont recherchées ? Les chiffres réels de la communauté.')
        ->toContain('<em class="font-serif italic text-secondary-400">face à ton code.</em>')
        // Le même libellé partout : navigation, hero, CTA final.
        ->and(substr_count($html, '>'.Copy::SIGNUP_CTA.'</a>'))->toBe(3)
        ->and(substr_count($html, Copy::SIGNUP_REASSURANCE))->toBe(2);

    // Un membre n'a ni le CTA final ni l'inscription.
    $member = $this->actingAs(User::factory()->withProfile()->create())->get('/')->getContent();
    expect($member)->not->toContain('data-final-cta')->not->toContain(Copy::SIGNUP_CTA);
});

test('À propos : identité, équipe, licences, IA au mot près, marques ; aucun CTA d\'inscription dans la page', function () {
    $html = $this->get('/a-propos')->assertOk()->getContent();
    $main = substr($html, strpos($html, '<main'), strpos($html, '</main>') - strpos($html, '<main'));

    expect($main)->toContain('Plateforme d&#039;échange')->toContain('Par les devs')
        ->toContain('Ses 21 cordes ne sonnent juste qu\'ensemble')
        ->toContain('Yaya Niang')->toContain('Aminata Sall')->toContain('Baobab Stack')
        ->toContain('<strong class="font-semibold text-ink">Claude Code (Anthropic)</strong> : code, tests et revue de code.')
        ->toContain("<strong class=\"font-semibold text-ink\">ChatGPT (OpenAI)</strong> : image d'illustration de l'accueil.")
        ->toContain("sont les marques de leurs propriétaires. DevKora n'a aucune affiliation avec eux, et le paiement des missions est simulé")
        ->toContain('SIL OFL 1.1')->toContain('Lucide')->toContain('PostgreSQL License')
        ->toContain('Outils de développement ('.collect(Credits::direct())->where('dev', true)->count().')')
        ->not->toContain(Copy::SIGNUP_CTA)
        ->not->toContain('href="'.route('register').'"');

    $ai = substr($main, strpos($main, 'data-ai'), 600);
    expect(substr_count($ai, '<li>'))->toBe(2);
});

test('licences : lues dans les fichiers de verrouillage, aucune GPL retenue', function () {
    $direct = collect(Credits::direct());

    expect($direct->pluck('name'))->toContain('laravel/framework', 'livewire/livewire', 'alpinejs', 'pestphp/pest')
        ->and($direct->firstWhere('name', 'laravel/framework')['license'])->toBe('MIT')
        ->and($direct->firstWhere('name', 'playwright')['dev'])->toBeTrue()
        ->and(Credits::summary()['licenses'])->each->not->toMatch('/GPL/');
});

test('partage : WhatsApp et « Copier le lien » sur les fiches et les profils', function () {
    $question = Question::factory()->create(['title' => 'Webhook Wave refusé']);
    $pages = [
        route('questions.show', $question),
        route('articles.show', Article::factory()->create()),
        route('listings.show', Listing::factory()->create()),
        route('events.show', Event::factory()->create()),
        route('devs.show', User::factory()->withProfile()->create()),
    ];

    foreach ($pages as $url) {
        expect($this->get($url)->getContent())->toContain('data-share')->toContain('https://wa.me/?text=')->toContain('Copier le lien');
    }

    $this->get($pages[0])->assertSee('https://wa.me/?text='.rawurlencode('Webhook Wave refusé '.route('questions.show', $question)), false);
});

test('SEO : titre, description, canonical sans filtres, Open Graph et Twitter, image 1200 × 630', function () {
    $home = $this->get('/')->getContent();
    expect($home)->toContain('<title>DevKora · Le point de rencontre des devs africains</title>')
        ->toContain('<meta name="description" content="Le point de rencontre des développeurs africains pour travailler, apprendre et grandir ensemble.">')
        ->toContain('<meta property="og:image" content="'.asset('images/og-default.png').'">')
        ->toContain('<meta name="twitter:card" content="summary_large_image">');

    $article = Article::factory()->create(['title' => 'Intégrer Wave']);
    expect($this->get(route('articles.show', $article))->getContent())
        ->toContain('<meta property="og:title" content="Intégrer Wave · DevKora">')
        ->toContain('<meta property="og:type" content="article">');

    expect($this->get('/questions?statut=resolues')->getContent())->toContain('<link rel="canonical" href="'.url('/questions').'">');
    expect(array_slice(getimagesize(public_path('images/og-default.png')), 0, 2))->toBe([1200, 630]);
});

test('sitemap.xml (contenus visibles et villes) et robots.txt', function () {
    $question = Question::factory()->create();
    $hidden = Question::factory()->create(['hidden_at' => now()]);
    $user = User::factory()->withProfile(['city' => 'Saint-Louis'])->create();

    $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
    expect($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<loc>'.route('questions.show', $question).'</loc>')
        ->not->toContain('<loc>'.route('questions.show', $hidden).'</loc>')
        ->toContain('<loc>'.route('cities.show', 'saint-louis').'</loc>')
        ->toContain('<loc>'.route('devs.show', $user).'</loc>');
    simplexml_load_string($xml);

    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'))->assertSee('Disallow: /moderation');
});

test('pages ville : devs, événements, annonces et questions de la ville ; liens depuis les profils et les événements', function () {
    $moussa = User::factory()->withProfile(['city' => 'Dakar', 'country' => 'SN'])->create(['name' => 'Moussa Diop']);
    $other = User::factory()->withProfile(['city' => 'Lomé', 'country' => 'TG'])->create(['name' => 'Koffi Mensah']);
    Question::factory()->for($moussa)->create(['title' => 'Une question posée à Dakar']);
    Listing::factory()->for($moussa)->create(['title' => 'Une mission de Dakar']);
    $event = Event::factory()->create(['title' => 'Meetup à Dakar', 'city' => 'Dakar']);

    $this->get('/villes/dakar')->assertOk()
        ->assertSee('Les devs de Dakar,')
        ->assertSee('Moussa Diop')->assertDontSee('Koffi Mensah')
        ->assertSee('Une question posée à Dakar')->assertSee('Une mission de Dakar')->assertSee('Meetup à Dakar');
    $this->get('/villes/nulle-part')->assertNotFound();

    $this->get(route('devs.show', $moussa))->assertSee(route('cities.show', 'dakar'));
    $this->get('/evenements')->assertSee(route('cities.show', 'dakar'));
});
