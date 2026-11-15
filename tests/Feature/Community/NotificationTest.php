<?php

use App\Enums\PaymentProvider;
use App\Livewire\Useful;
use App\Models\Article;
use App\Models\Listing;
use App\Models\Proposal;
use App\Models\Question;
use App\Models\User;
use App\Notifications\Activity;
use App\Services\AnswerService;
use App\Services\ListingService;
use App\Services\PaymentService;
use App\Services\ReviewService;
use Livewire\Livewire;

function member5(): User
{
    return User::factory()->withProfile()->create();
}

function kinds(User $user): array
{
    return $user->fresh()->notifications->pluck('data.kind')->sort()->values()->all();
}

test('nouvelle réponse et réponse acceptée : l\'auteur concerné est prévenu, jamais soi-même', function () {
    $question = Question::factory()->create(['title' => 'Webhook Wave refusé en production']);
    $answerer = member5();

    $answer = app(AnswerService::class)->publish($answerer, $question, 'Vérifie la signature sur le corps brut.');
    app(AnswerService::class)->publish($question->user, $question, 'Merci, je précise ce qui a marché pour moi.');
    app(AnswerService::class)->accept($question, $answer);

    $notification = $question->user->fresh()->notifications->sole();
    expect($notification->data['message'])->toBe($answerer->name.' a répondu à ta question « Webhook Wave refusé en production ».')
        ->and($notification->data['url'])->toEndWith('#reponse-'.$answer->id)
        ->and(kinds($answerer))->toBe(['answer_accepted']);
});

test('votes « Utile » regroupés : une seule notification non lue, mise à jour', function () {
    $article = Article::factory()->create(['title' => 'Intégrer Wave de bout en bout']);

    foreach (User::factory()->count(3)->withProfile()->create() as $voter) {
        Livewire::actingAs($voter)->test(Useful::class, ['article' => $article->fresh()])->call('toggle');
    }

    $notification = $article->user->fresh()->notifications->sole();
    expect($notification->data['message'])->toBe('3 devs ont trouvé ton article « Intégrer Wave de bout en bout » utile.')
        ->and($notification->data['count'])->toBe(3);

    // Une fois lue, le vote suivant ouvre une nouvelle notification.
    $notification->markAsRead();
    Livewire::actingAs(member5())->test(Useful::class, ['article' => $article->fresh()])->call('toggle');
    expect($article->user->fresh()->unreadNotifications)->toHaveCount(1);
});

test('annonces : proposition reçue, membre accepté, dev retenu, autres prévenus, fin de mission, avis', function () {
    $team = Listing::factory()->team()->create();
    $member = member5();
    $request = app(ListingService::class)->propose($member, $team, 'Je veux rejoindre l\'équipe pour le hackathon.');
    app(ListingService::class)->decideTeamRequest($request, true);

    expect(kinds($team->user))->toBe(['proposal_received'])->and(kinds($member))->toBe(['team_accepted']);

    $mission = Listing::factory()->create();
    [$chosen, $other] = [member5(), member5()];
    $p1 = app(ListingService::class)->propose($chosen, $mission, 'Je peux livrer en une semaine.', 100000, 7);
    app(ListingService::class)->propose($other, $mission, 'Je peux livrer en dix jours.', 90000, 10);
    app(PaymentService::class)->secure($mission, $p1, PaymentProvider::Wave, '+221771234567');
    app(ListingService::class)->complete($mission->fresh());
    app(ReviewService::class)->create($mission->fresh(), ['rating' => 5, 'communication' => 5, 'quality' => 5, 'timeliness' => 5, 'comment' => null]);

    expect(kinds($mission->user))->toBe(['proposal_received', 'proposal_received'])
        ->and(kinds($chosen))->toBe(['mission_completed', 'proposal_retained', 'review_received'])
        ->and(kinds($other))->toBe(['proposal_declined'])
        ->and($other->fresh()->notifications->sole()->data['message'])->toContain('a retenu un autre dev');
});

test('cloche : compteur, 8 dernières, « Tout marquer comme lu » ; menu « Moi » sur mobile', function () {
    $user = member5();
    $question = Question::factory()->for($user)->create();
    foreach (range(1, 10) as $i) {
        app(AnswerService::class)->publish(member5(), $question, "Réponse numéro $i, assez longue pour passer.");
    }

    $html = $this->actingAs($user)->get('/devs')->getContent();
    $bell = substr($html, strpos($html, 'data-bell'), 12000);
    expect($bell)->toContain('aria-label="Notifications, 10 non lues"')->toContain('>9+<')
        ->and(substr_count($bell, 'notifications/'))->toBeGreaterThanOrEqual(8)
        ->and($html)->toContain('aria-label="Moi, 10 notifications non lues"');

    $this->actingAs($user)->post(route('notifications.read-all'))->assertSessionHas('status', 'Toutes tes notifications sont marquées comme lues.');
    expect($user->fresh()->unreadNotifications)->toHaveCount(0);
});

test('ouvrir une notification la marque comme lue et mène au contenu ; jamais celle d\'un autre', function () {
    $question = Question::factory()->create();
    app(AnswerService::class)->publish(member5(), $question, 'Une réponse assez longue pour passer.');
    $notification = $question->user->notifications->sole();

    $this->actingAs(member5())->get(route('notifications.open', $notification->id))->assertNotFound();
    $this->actingAs($question->user)->get(route('notifications.open', $notification->id))->assertRedirect($notification->data['url']);
    expect($notification->fresh()->read_at)->not->toBeNull();

    $this->actingAs($question->user)->get('/notifications')->assertOk()->assertSee("Ce qui s'est passé pour toi.", false);
    auth()->logout();
    $this->get('/notifications')->assertRedirect('/connexion');
});

test('une notification ne redirige jamais hors du site', function () {
    $user = member5();
    $user->notify(new Activity('test', 'Message', 'https://exemple-malveillant.com'));

    $this->actingAs($user)->get(route('notifications.open', $user->notifications->sole()->id))->assertRedirect(route('notifications.index'));
});

test('les textes de 11 annoncent les notifications', function () {
    $this->actingAs(member5())->post('/questions', ['title' => 'Un titre suffisamment long ici', 'body' => str_repeat('Détail utile. ', 5), 'tags' => 'Laravel'])
        ->assertSessionHas('status', 'Question publiée. Tu seras prévenu dès la première réponse.');

    $mission = Listing::factory()->create();
    Proposal::factory()->for($mission)->create();
    $this->actingAs($mission->user)->get(route('listings.show', $mission))->assertSee('Quand tu retiens un dev, les autres sont prévenus.');
});
