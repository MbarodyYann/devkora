<?php

use App\Models\Answer;
use App\Models\Article;
use App\Models\Question;
use App\Models\Skill;
use App\Models\User;

function dev(array $profile = [], array $user = []): User
{
    return User::factory()->withProfile($profile)->create($user);
}

test('la page profil montre l\'identité, les langues, la disponibilité et les compétences dans l\'ordre choisi', function () {
    $user = dev(['headline' => 'Dev Laravel', 'country' => 'TG', 'city' => 'Lomé', 'languages' => ['fr', 'ee'], 'availability' => 'missions', 'bio' => 'Je construis des API.'], ['name' => 'Koffi Mensah', 'last_seen_at' => now()]);
    $user->skills()->attach([
        Skill::factory()->create(['name' => 'Laravel'])->id => ['position' => 0],
        Skill::factory()->create(['name' => 'Docker'])->id => ['position' => 1],
    ]);

    $this->get(route('devs.show', $user))
        ->assertOk()
        ->assertSee('<title>Koffi Mensah · DevKora</title>', false)
        ->assertSee('Dev Laravel')
        ->assertSee('Lomé, Togo')
        ->assertSee('Parle français, éwé')
        ->assertSee('Actif aujourd&#039;hui', false)
        ->assertSee('Ouvert aux missions')
        ->assertSee('Je construis des API.')
        ->assertSeeInOrder(['Laravel', 'Docker'])
        ->assertSee('Pas encore de cauris : chaque réponse utile, chaque article et chaque mission en rapportent.')
        ->assertSee('Nouveau')
        ->assertDontSee('Compléter mon profil')
        ->assertDontSee('data-edit-profile', false);
});

test('les cauris s\'affichent en grand quand le membre en a', function () {
    $user = dev();
    $user->forceFill(['reputation' => 1250])->save();

    $this->get(route('devs.show', $user))
        ->assertSee("1\u{202F}250")
        ->assertSee('Référent')
        ->assertDontSee('Pas encore de cauris');
});

test('le propriétaire sans compétences est invité à compléter son profil', function () {
    $user = dev();

    $this->actingAs($user)->get(route('devs.show', $user))
        ->assertSee('Ajoute tes compétences pour apparaître dans les recherches des autres devs.')
        ->assertSee('Compléter mon profil')
        ->assertDontSee('data-edit-profile', false);
});

test('le propriétaire avec des compétences voit « Modifier mon profil »', function () {
    $user = dev();
    $user->skills()->attach(Skill::factory()->create());

    $this->actingAs($user)->get(route('devs.show', $user))
        ->assertSee('data-edit-profile', false)
        ->assertDontSee('data-profile-prompt', false);
});

test('un autre membre ne voit pas l\'invitation', function () {
    $this->actingAs(dev())->get(route('devs.show', dev()))
        ->assertDontSee('Compléter mon profil')
        ->assertDontSee('data-edit-profile', false);
});

test('les liens externes sont en nofollow et noopener', function () {
    $user = dev(['github_url' => 'https://github.com/koffi', 'portfolio_url' => 'https://koffi.dev']);

    $this->get(route('devs.show', $user))
        ->assertSee('href="https://github.com/koffi" rel="nofollow ugc noopener noreferrer"', false)
        ->assertSee('href="https://koffi.dev" rel="nofollow ugc noopener noreferrer"', false);
});

test('profil inexistant ou sans fiche : 404 en français', function () {
    $this->get('/devs/999999')->assertNotFound()->assertSee("Cette page s'est perdue en route.", false);
    $this->get('/devs/abc')->assertNotFound();
    $this->get(route('devs.show', User::factory()->create()))->assertNotFound();
});

test('le texte d\'un membre est échappé', function () {
    $user = dev(['bio' => '<script>alert(1)</script>', 'headline' => '<b>gras</b>']);

    $this->get(route('devs.show', $user))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

test('« Actif cette semaine », puis plus rien au-delà de 7 jours', function () {
    $user = dev(user: ['last_seen_at' => now()->subDays(3)]);
    expect($user->activityLabel())->toBe('Actif cette semaine');

    $user->last_seen_at = now()->subDays(9);
    expect($user->activityLabel())->toBeNull();
});

test('la dernière activité est mise à jour au plus toutes les 5 minutes', function () {
    $user = dev(user: ['last_seen_at' => now()->subHour()]);

    $this->actingAs($user)->get('/');
    expect($user->fresh()->last_seen_at->diffInSeconds(now()))->toBeLessThan(5);

    $this->travel(2)->minutes();
    $before = $user->fresh()->last_seen_at;
    $this->actingAs($user->fresh())->get('/');
    expect($user->fresh()->last_seen_at->equalTo($before))->toBeTrue();
});

test('« Ce que … partage » : réponses acceptées puis questions, avant le reste', function () {
    $user = dev(user: ['name' => 'Koffi Mensah']);
    $question = Question::factory()->create(['title' => 'Comment vérifier un webhook Wave ?']);
    $accepted = Answer::factory()->for($question)->for($user)->create();
    $question->forceFill(['accepted_answer_id' => $accepted->id])->save();
    Answer::factory()->for($user)->create(); // non acceptée : n'apparaît pas
    Question::factory()->for($user)->create(['title' => 'Quelle base pour un service USSD ?']);

    $this->get(route('devs.show', $user))
        ->assertSeeInOrder(['Ce que Koffi partage', 'Réponses acceptées', 'Comment vérifier un webhook Wave ?', 'Questions', 'Quelle base pour un service USSD ?', 'À propos']);

    $this->actingAs($user)->get(route('devs.show', $user))->assertSee('Ce que tu partages');
});

test('rien de partagé : une invitation pour le propriétaire', function () {
    $user = dev(user: ['name' => 'Awa Sarr']);

    $this->get(route('devs.show', $user))->assertSee('Awa n&#039;a encore rien partagé.', false);
    $this->actingAs($user)->get(route('devs.show', $user))->assertSee('Une réponse acceptée te rapporte +15 cauris.');
});

test('« Ce que … partage » inclut les articles, en premier', function () {
    $user = dev(user: ['name' => 'Moussa Diop']);
    Article::factory()->for($user)->create(['title' => 'Intégrer Wave de bout en bout', 'score' => 4]);
    Question::factory()->for($user)->create(['title' => 'Quelle base pour un service USSD ?']);

    $this->get(route('devs.show', $user))
        ->assertSeeInOrder(['Ce que Moussa partage', 'Articles', 'Intégrer Wave de bout en bout', '4 × Utile', 'Questions', 'Quelle base pour un service USSD ?']);
});
