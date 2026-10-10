<?php

use App\Livewire\Questions\Index;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Skill;
use App\Models\User;
use App\Support\Copy;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function member(array $attributes = []): User
{
    return User::factory()->withProfile()->create($attributes);
}

test('la liste reprend l\'en-tête de la charte ; le membre a « Poser une question », pas le visiteur', function () {
    $this->get('/questions')
        ->assertOk()
        ->assertSee('Ta question ne se perdra plus')
        ->assertSee('<em class="font-serif italic text-secondary-400">dans un groupe WhatsApp.</em>', false)
        ->assertSee('Pose-la en français avec les bons tags : la communauté répond et vote, la meilleure réponse reste trouvable par tous.')
        ->assertDontSee('Poser une question');

    $html = $this->actingAs(member())->get('/questions')->getContent();
    expect(substr_count($html, 'Poser une question'))->toBe(1);
});

test('« Questions » apparaît dans la navigation', function () {
    $this->get('/devs')->assertSee('>Questions<', false);
});

test('filtres : récentes, sans réponse, résolues, par tag', function () {
    $wave = Skill::factory()->local()->create(['name' => 'Wave', 'slug' => 'wave']);
    $open = Question::factory()->create(['title' => 'Question ouverte sans réponse ici']);
    $answered = Question::factory()->create(['title' => 'Question avec une réponse ici', 'answers_count' => 1]);
    $resolved = Question::factory()->create(['title' => 'Question résolue avec Wave ici', 'answers_count' => 1]);
    $resolved->forceFill(['accepted_answer_id' => Answer::factory()->for($resolved)->create()->id])->save();
    $resolved->skills()->attach($wave);

    $titles = fn (array $filters) => Livewire::test(Index::class)->set($filters)->instance()->questions()->pluck('title')->sort()->values()->all();

    expect($titles([]))->toHaveCount(3)
        ->and($titles(['status' => 'sans-reponse']))->toBe([$open->title])
        ->and($titles(['status' => 'resolues']))->toBe([$resolved->title])
        ->and($titles(['tag' => 'wave']))->toBe([$resolved->title]);
});

test('recherche plein texte en français : pluriels et conjugaisons', function () {
    Question::factory()->create(['title' => 'Paiement Wave refusé en production', 'body' => 'Le webhook ne passe pas la vérification de signature.']);
    Question::factory()->create(['title' => 'Flutter hors ligne', 'body' => 'Synchroniser les ventes quand le réseau revient.']);

    $search = fn (string $q) => Livewire::test(Index::class)->set('q', $q)->instance()->questions()->pluck('title')->all();

    expect($search('paiements wave'))->toBe(['Paiement Wave refusé en production'])
        ->and($search('synchronisation'))->toBe(['Flutter hors ligne'])
        ->and($search('"signature" -flutter'))->toBe(['Paiement Wave refusé en production'])
        ->and($search('kubernetes'))->toBe([]);
});

test('recherche sans accents : « cafe » trouve « café », « deploiement » trouve « déploiement »', function () {
    Question::factory()->create(['title' => 'Mon café en ligne refuse les paiements', 'body' => 'Une boutique de café à Abidjan.']);
    Question::factory()->create(['title' => 'Déploiement Laravel sur un VPS', 'body' => 'Nginx et PHP-FPM.']);

    $search = fn (string $q) => Livewire::test(Index::class)->set('q', $q)->instance()->questions()->pluck('title')->all();

    expect($search('cafe'))->toBe(['Mon café en ligne refuse les paiements'])
        ->and($search('deploiement'))->toBe(['Déploiement Laravel sur un VPS'])
        ->and($search('Déploiements'))->toBe(['Déploiement Laravel sur un VPS']);
});

test('la recherche passe par l\'index GIN', function () {
    Question::factory()->count(3)->create();
    DB::statement('set enable_seqscan = off');

    $vector = (new Question)->searchVector();
    $plan = collect(DB::select("explain select id from questions where $vector @@ websearch_to_tsquery('french', f_unaccent('wave'))"))->pluck('QUERY PLAN')->join(' ');

    expect($plan)->toContain('questions_search_index');
});

test('aucune question : une invitation, et pagination par 15 sans N+1', function () {
    Livewire::test(Index::class)->set('q', 'introuvable')->assertSee('Personne n\'a encore posé cette question.');

    Question::factory()->count(16)->create()->each(fn (Question $q) => $q->skills()->attach(Skill::factory()->create()));
    Livewire::test(Index::class)->assertSee('16 questions')->assertSee('1 à 15 sur 16')->call('nextPage')->assertSee('16 à 16 sur 16');
});

test('poser une question : réservé aux membres, textes de 11', function () {
    $this->get('/questions/poser')->assertRedirect('/connexion');

    $this->actingAs(member())->get('/questions/poser')
        ->assertOk()
        ->assertSee('Résume ton problème en une phrase, comme si tu le cherchais sur Google.')
        ->assertSee('Publier ma question');
});

test('publier une question : Markdown rendu, extrait, tags dans l\'ordre, redirection vers la fiche', function () {
    $user = member();

    $response = $this->actingAs($user)->post('/questions', [
        'title' => '  Le webhook Wave renvoie 401 en production ',
        'body' => "Mon middleware refuse tout.\n\n```php\nabort(401);\n```",
        'tags' => 'Wave, Laravel, wave',
    ]);

    $question = Question::sole();
    $response->assertRedirect(route('questions.show', $question))
        ->assertSessionHas('status', 'Question publiée. Les réponses de la communauté apparaîtront juste en dessous.');

    expect($question->user_id)->toBe($user->id)
        ->and($question->title)->toBe('Le webhook Wave renvoie 401 en production')
        ->and($question->body_html)->toContain('class="code-block"')
        ->and($question->excerpt)->toBe('Mon middleware refuse tout.')
        ->and($question->skills->pluck('name')->all())->toBe(['Wave', 'Laravel']);
});

test('publier une question : refus avec des messages qui disent quoi faire', function (array $data, string $field, string $message) {
    $this->actingAs(member())->post('/questions', array_merge([
        'title' => 'Un titre suffisamment long', 'body' => str_repeat('Détail utile. ', 5), 'tags' => 'Laravel',
    ], $data))->assertSessionHasErrors([$field => $message]);

    expect(Question::count())->toBe(0);
})->with([
    [['title' => 'Court'], 'title', 'Ton titre est un peu court : résume ton problème en une phrase.'],
    [['body' => ''], 'body', 'Explique ton problème : ce que tu as essayé, ce qui se passe.'],
    [['tags' => ''], 'tags', 'Ajoute au moins un tag, par exemple Laravel.'],
    [['tags' => ' , ,'], 'tags', 'Ajoute au moins un tag, par exemple Laravel.'],
    [['tags' => 'a, b, c, d, e, f'], 'tags', 'Garde 5 tags au plus : les plus précis.'],
]);

test('les champs sensibles ne passent pas par le formulaire', function () {
    $user = member();
    $this->actingAs($user)->post('/questions', [
        'title' => 'Un titre suffisamment long', 'body' => str_repeat('Détail utile. ', 5), 'tags' => 'Laravel',
        'user_id' => 999, 'score' => 100, 'accepted_answer_id' => 1, 'body_html' => '<script>x</script>',
    ]);

    $question = Question::sole();
    expect($question->user_id)->toBe($user->id)->and($question->score)->toBe(0)->and($question->body_html)->not->toContain('<script');
});

test('la fiche : réponse acceptée d\'abord, puis par score ; boîte d\'action du visiteur', function () {
    $question = Question::factory()->create();
    $low = Answer::factory()->for($question)->create(['body_html' => '<p>Réponse faible</p>', 'score' => 1]);
    $high = Answer::factory()->for($question)->create(['body_html' => '<p>Réponse forte</p>', 'score' => 9]);
    $accepted = Answer::factory()->for($question)->create(['body_html' => '<p>Réponse acceptée ici</p>', 'score' => 0]);
    $question->forceFill(['accepted_answer_id' => $accepted->id])->save();

    $this->get(route('questions.show', $question))
        ->assertOk()
        ->assertSeeInOrder(['Réponse acceptée ici', 'Réponse forte', 'Réponse faible'])
        ->assertSee('Tu connais la réponse ?')
        ->assertSee(Copy::SIGNUP_REASSURANCE)
        ->assertDontSee('Publier ma réponse');
});

test('le membre voit le formulaire de réponse et la réassurance de 11', function () {
    $this->actingAs(member())->get(route('questions.show', Question::factory()->create()))
        ->assertSee('Publier ma réponse')
        ->assertSee('Une réponse votée te rapporte +10 cauris, une réponse acceptée +15.')
        ->assertDontSee('data-action-box', false);
});

test('question masquée : 404 pour les autres, absente de la liste', function () {
    $question = Question::factory()->create(['hidden_at' => now(), 'title' => 'Question masquée par la modération']);

    $this->get(route('questions.show', $question))->assertNotFound();
    $this->actingAs(member())->get(route('questions.show', $question))->assertNotFound();
    $this->actingAs($question->user)->get(route('questions.show', $question))->assertOk();
    Livewire::test(Index::class)->assertDontSee('Question masquée par la modération');
});

test('identifiant non numérique : 404', function () {
    $this->get('/questions/abc')->assertNotFound();
});
