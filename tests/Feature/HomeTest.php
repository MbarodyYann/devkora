<?php

use App\Livewire\Questions\Index;
use App\Models\Answer;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Question;
use App\Models\Skill;
use App\Models\User;
use App\Support\Copy;
use Livewire\Livewire;

/** Le contenu de la page, sans la navigation ni le pied de page. */
function mainOf(string $html): string
{
    return substr($html, strpos($html, '<main'), strpos($html, '</main>') - strpos($html, '<main'));
}

function markResolved(Question $question): Question
{
    $question->forceFill(['accepted_answer_id' => Answer::factory()->for($question)->create()->id])->save();

    return $question;
}

test('l\'accueil est le fil des questions : titre, recherche et questions, sans compte', function () {
    Question::factory()->create(['title' => 'Webhook Wave refusé en production']);
    Question::factory()->create(['title' => 'Question masquée par la modération', 'hidden_at' => now()]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>DevKora · Le point de rencontre des devs africains</title>')
        ->toContain('<h1 id="page-titre" class="text-[28px] leading-tight sm:text-[36px]">Questions</h1>')
        ->toContain('Cherche une question, lis les réponses, ou pose la tienne.')
        ->not->toContain('en ce moment.')
        ->toContain('role="search"')
        ->toContain('Webhook Wave refusé en production')
        ->not->toContain('Question masquée par la modération')
        // L'ancienne présentation n'est plus sur l'accueil.
        ->not->toContain('Ici, on échange.')
        ->not->toContain('Ne reste plus seul');
});

test('onglets : chacun filtre le fil et a son adresse', function () {
    $open = Question::factory()->create(['title' => 'Question ouverte sans réponse']);
    $answered = Question::factory()->create(['title' => 'Question avec une réponse', 'answers_count' => 1]);
    $resolved = markResolved(Question::factory()->create(['title' => 'Question déjà résolue', 'answers_count' => 1]));

    $titles = fn (string $url) => collect([$open, $answered, $resolved])->pluck('title')
        ->filter(fn (string $title) => str_contains(mainOf($this->get($url)->assertOk()->getContent()), $title))->values()->all();

    expect($titles('/'))->toHaveCount(3)
        ->and($titles('/?statut=recentes'))->toHaveCount(3)
        ->and($titles('/?statut=sans-reponse'))->toBe([$open->title])
        ->and($titles('/?statut=resolues'))->toBe([$resolved->title])
        // Un onglet inconnu ramène à « En ce moment ».
        ->and($titles('/?statut=nimporte'))->toHaveCount(3);

    $html = $this->get('/?statut=resolues&tag=wave')->getContent();
    $tabs = substr($html, strpos($html, 'data-tabs'), 3000);

    expect($tabs)->toContain('En ce moment')->toContain('Récentes')->toContain('Sans réponse')->toContain('Résolues')
        // Chaque onglet est un lien qui garde le tag en cours ; l'onglet ouvert est signalé.
        ->toContain('href="'.url('/').'?tag=wave"')
        ->toContain('href="'.url('/').'?statut=recentes&amp;tag=wave"')
        ->toContain('href="'.url('/').'?statut=sans-reponse&amp;tag=wave"')
        ->and(substr_count($tabs, 'aria-current="page"'))->toBe(1)
        ->and($tabs)->toMatch('/statut=resolues&amp;tag=wave"[^>]*aria-current="page"/');
});

test('« En ce moment » classe par votes et réponses pondérés par l\'âge ; « Récentes » par date', function () {
    $this->freezeTime();
    $old = Question::factory()->create(['title' => 'Vieille question très votée', 'score' => 8, 'created_at' => now()->subDays(10)]);
    $fresh = Question::factory()->create(['title' => 'Question récente avec réponses', 'answers_count' => 3, 'created_at' => now()->subHours(5)]);
    $unanswered = Question::factory()->create(['title' => 'Question sans réponse de ce matin', 'created_at' => now()->subHours(2)]);
    $ancient = Question::factory()->create(['title' => 'Question d\'il y a 40 jours', 'score' => 30, 'created_at' => now()->subDays(40)]);

    $ids = fn (array $state) => Livewire::test(Index::class)->set($state)->instance()->questions()->pluck('id')->all();

    // 5 h et 3 réponses : 7 / 7^1,5 ≈ 0,38 ; 2 h sans réponse : 1 / 4^1,5 ≈ 0,13 ; 10 jours et 8 votes : 9 / 242^1,5 ≈ 0,002.
    expect($ids([]))->toBe([$fresh->id, $unanswered->id, $old->id])
        ->and($ids(['status' => 'recentes']))->toBe([$unanswered->id, $fresh->id, $old->id, $ancient->id])
        // Une recherche ou un tag porte sur toutes les questions, pas seulement sur les 30 derniers jours.
        ->and($ids(['q' => '40 jours']))->toBe([$ancient->id]);
});

test('« En ce moment » : si rien n\'a été posé depuis 30 jours, le fil montre tout', function () {
    Question::factory()->count(2)->create(['created_at' => now()->subDays(45)]);

    expect(Livewire::test(Index::class)->instance()->questions()->total())->toBe(2);
});

test('pagination par 15, avec son adresse', function () {
    Question::factory()->count(16)->create();

    $this->get('/')->assertSee('16 questions')->assertSee('1 à 15 sur 16');
    $this->get('/?page=2')->assertOk()->assertSee('16 à 16 sur 16');
});

test('bandeau du visiteur : une ligne vers À propos, qu\'on peut fermer, sans bouton d\'inscription ; jamais pour un membre', function () {
    $html = $this->get('/')->getContent();
    $banner = substr($html, strpos($html, 'data-welcome'), strpos($html, 'Navigation principale') - strpos($html, 'data-welcome'));

    expect($banner)->toContain('Nouveau ici ?')
        ->toContain('DevKora, c\'est le point de rencontre des devs africains.')
        ->toContain('href="'.route('about').'"')->toContain('>Découvrir</a>')
        // Pas de doublon : le bouton d'inscription reste celui de la navigation.
        ->not->toContain(route('register'))->not->toContain(Copy::SIGNUP_CTA)
        // Fermeture gardée dans le navigateur, sans erreur si le stockage est refusé.
        ->toContain("localStorage.getItem('welcome')")->toContain("localStorage.setItem('welcome', 'closed')")
        ->and(substr_count($banner, 'try {'))->toBe(2)
        ->and($banner)->toContain('aria-label="Fermer ce message"');

    // Aucun bouton d'inscription dans le corps de la page : un seul sur l'écran, celui de la navigation.
    expect(mainOf($html))->not->toContain(Copy::SIGNUP_CTA)->not->toContain(route('register'))
        ->and(substr_count($html, '>'.Copy::SIGNUP_CTA.'</a>'))->toBe(1);

    // Le bandeau est celui de l'accueil : ni sur les autres pages, ni pour un membre.
    $this->get('/devs')->assertDontSee('data-welcome', false);
    $this->actingAs(User::factory()->withProfile()->create())->get('/')
        ->assertDontSee('data-welcome', false)->assertDontSee('Nouveau ici ?')->assertDontSee(Copy::SIGNUP_CTA);
});

test('« Pose ta question » : un seul bouton, pour tous ; le visiteur passe par la connexion', function () {
    Question::factory()->create();

    $html = $this->get('/')->getContent();
    expect(substr_count($html, 'Pose ta question'))->toBe(1)
        ->and($html)->toMatch('/href="'.preg_quote(route('questions.create'), '/').'"[^>]*data-cta="question"/');

    $this->get(route('questions.create'))->assertRedirect(route('login'));
    expect(session('url.intended'))->toBe(route('questions.create'));

    // Membre : le même bouton, une seule fois ; « Poser une question » reste dans le menu « Publier ».
    $member = $this->actingAs(User::factory()->withProfile()->create())->get('/')->getContent();
    expect(substr_count($member, 'Pose ta question'))->toBe(1)
        ->and(substr_count($member, 'Poser une question'))->toBe(1);
});

test('colonne latérale : tags locaux, prochains événements, annonces ouvertes et contributeurs du mois', function () {
    $wave = Skill::factory()->local()->create(['name' => 'Wave', 'slug' => 'wave']);
    $laravel = Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    Skill::factory()->local()->create(['name' => 'USSD', 'slug' => 'ussd']); // aucune question : pas affiché
    Question::factory()->create()->skills()->attach([$wave->id, $laravel->id]);

    Event::factory()->create(['title' => 'Meetup Laravel à Dakar', 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(2)]);
    Event::factory()->create(['title' => 'Atelier déjà passé', 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)->addHours(2)]);
    Listing::factory()->create(['title' => 'Mission ouverte de démonstration']);
    Listing::factory()->team()->create(['title' => 'Équipe ouverte de démonstration']);
    Listing::factory()->create(['title' => 'Mission annulée de démonstration', 'status' => 'cancelled']);

    $html = $this->get('/')->getContent();
    $aside = substr($html, strpos($html, 'data-feed-aside'), strpos($html, '</aside>') - strpos($html, 'data-feed-aside'));

    expect($aside)->toContain('Tags locaux')
        ->toContain('href="'.url('/').'?tag=wave"')
        ->not->toContain('?tag=laravel')->not->toContain('?tag=ussd')
        ->toContain('Prochains événements')->toContain('Meetup Laravel à Dakar')->not->toContain('Atelier déjà passé')
        ->toContain('Annonces ouvertes')->toContain('Mission ouverte de démonstration')
        ->toContain('Équipe ouverte de démonstration')->not->toContain('Mission annulée de démonstration');

    // Le filtre par tag du lien.
    Question::factory()->create(['title' => 'Question sans le tag Wave']);
    expect(mainOf($this->get('/?tag=wave')->getContent()))->toContain('1 question')->not->toContain('Question sans le tag Wave');
});

test('/questions mène à l\'accueil, avec ses filtres', function () {
    $this->get('/questions')->assertRedirect('/')->assertStatus(301);
    $this->get('/questions?statut=resolues&tag=wave&q=webhook')->assertStatus(301)
        ->assertRedirect(url('/').'?statut=resolues&tag=wave&q=webhook');
});

test('navigation : « Questions » mène à l\'accueil et y est actif ; « À propos » est dans la barre du haut', function () {
    $html = $this->get('/')->getContent();
    $nav = substr($html, strpos($html, 'Navigation principale'), strpos($html, '</nav>') - strpos($html, 'Navigation principale'));

    expect($nav)->toMatch('/href="'.preg_quote(url('/'), '/').'"\s+aria-current="page"[^>]*>\s*Questions/')
        ->toMatch('/href="'.preg_quote(route('about'), '/').'"[^>]*hidden xl:inline-block[^>]*>\s*À propos/')
        ->and(substr_count($nav, 'aria-current="page"'))->toBe(1);

    // Sur une question, « Questions » reste actif ; sur À propos, c'est « À propos ».
    $question = substr($this->get(route('questions.show', Question::factory()->create()))->getContent(), 0, 20000);
    expect($question)->toMatch('/aria-current="page"[^>]*>\s*Questions/');
    expect($this->get('/a-propos')->getContent())->toMatch('/aria-current="page"[^>]*>\s*À propos/');

    // Barre du bas mobile : 4 rubriques et « Moi » ; pied de page : « À propos » une seule fois.
    $bottom = substr($html, strpos($html, 'Navigation mobile'));
    $footer = substr($html, strpos($html, '<footer'), strpos($html, '</footer>') - strpos($html, '<footer'));
    expect($bottom)->toContain('Questions')->not->toContain('À propos')
        ->and(substr_count($footer, '>À propos</a>'))->toBe(1);
});
