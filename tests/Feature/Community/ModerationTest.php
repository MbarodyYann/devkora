<?php

use App\Enums\ModerationActionType;
use App\Enums\ReportReason;
use App\Models\Answer;
use App\Models\Article;
use App\Models\Listing;
use App\Models\ModerationAction;
use App\Models\Question;
use App\Models\Report;
use App\Models\Skill;
use App\Models\User;

function withCauris(int $points): User
{
    $user = User::factory()->withProfile()->create();
    $user->forceFill(['reputation' => $points])->save();

    return $user;
}

function reportOf(User $reporter, $content, string $reason = 'spam'): void
{
    test()->actingAs($reporter)->post(route('reports.store'), ['type' => $content->getMorphClass(), 'id' => $content->id, 'reason' => $reason]);
}

test('signaler : un par membre et par contenu, jamais son propre contenu, raison requise', function () {
    $question = Question::factory()->create();
    $reporter = withCauris(0);

    $this->post(route('reports.store'), ['type' => 'question', 'id' => $question->id, 'reason' => 'spam'])->assertRedirect('/connexion');
    $this->actingAs($reporter)->post(route('reports.store'), ['type' => 'question', 'id' => $question->id])->assertSessionHasErrors(['reason' => 'Choisis la raison du signalement.']);
    $this->actingAs($reporter)->post(route('reports.store'), ['type' => 'user', 'id' => $question->user_id, 'reason' => 'spam'])->assertSessionHasErrors('type');

    $this->actingAs($reporter)->post(route('reports.store'), ['type' => 'question', 'id' => $question->id, 'reason' => 'off_topic'])
        ->assertSessionHas('status', 'Merci : un modérateur de la communauté va regarder ce contenu.');
    $this->actingAs($reporter)->post(route('reports.store'), ['type' => 'question', 'id' => $question->id, 'reason' => 'spam'])
        ->assertSessionHas('status', 'Tu as déjà signalé ce contenu : un modérateur va le regarder.');
    $this->actingAs($question->user)->post(route('reports.store'), ['type' => 'question', 'id' => $question->id, 'reason' => 'spam'])
        ->assertSessionHas('status', 'C\'est ton propre contenu : tu peux le modifier, pas le signaler.');

    expect(Report::count())->toBe(1)->and(Report::sole()->reason)->toBe(ReportReason::OffTopic);
});

test('le bouton « Signaler » est sur les questions, réponses, articles, annonces et événements, pas pour l\'auteur', function () {
    $question = Question::factory()->create();
    Answer::factory()->for($question)->create();
    $viewer = withCauris(0);

    expect(substr_count($this->actingAs($viewer)->get(route('questions.show', $question))->getContent(), 'data-report'))->toBe(2)
        ->and($this->actingAs($viewer)->get(route('articles.show', Article::factory()->create()))->getContent())->toContain('data-report')
        ->and($this->actingAs($viewer)->get(route('listings.show', Listing::factory()->create()))->getContent())->toContain('data-report')
        ->and($this->actingAs($question->user)->get(route('questions.show', $question))->getContent())->not->toContain('name="type" value="question"');
    auth()->logout();
    $this->get(route('questions.show', $question))->assertDontSee('data-report', false);
});

test('la file de modération : à partir de 250 cauris', function () {
    $this->actingAs(withCauris(249))->get('/moderation')->assertForbidden();
    $this->actingAs(withCauris(250))->get('/moderation')->assertOk()->assertSee('Garde les échanges utiles et sains.');
});

test('masquer : contenu en 404 pour les autres, visible du modérateur, signalements classés, action journalisée', function () {
    $question = Question::factory()->create(['title' => 'Qui vend un téléphone pas cher ?']);
    reportOf(withCauris(0), $question, 'off_topic');
    $moderator = withCauris(300);

    $this->actingAs($moderator)->get('/moderation')->assertSee('Qui vend un téléphone pas cher ?')->assertSee('Hors sujet');
    $this->actingAs($moderator)->post(route('moderation.hide', ['question', $question->id]), ['note' => 'Petite annonce hors sujet.'])
        ->assertSessionHas('status');

    expect($question->fresh()->hidden_at)->not->toBeNull()
        ->and(Report::sole()->resolved_at)->not->toBeNull()
        ->and(ModerationAction::sole()->only(['moderator_id', 'action', 'note']))->toEqual(['moderator_id' => $moderator->id, 'action' => ModerationActionType::Hide, 'note' => 'Petite annonce hors sujet.']);

    auth()->logout();
    $this->get(route('questions.show', $question))->assertNotFound();
    $this->actingAs(withCauris(10))->get(route('questions.show', $question))->assertNotFound();
    $this->actingAs($moderator)->get(route('questions.show', $question))->assertOk()->assertSee('Masquée');

    // Journal visible, et action réversible.
    $this->actingAs($moderator)->get('/moderation')->assertSee('a masqué')->assertSee('Petite annonce hors sujet.')->assertSee('Rétablir');
    $this->actingAs($moderator)->post(route('moderation.restore', ['question', $question->id]));
    expect($question->fresh()->hidden_at)->toBeNull()->and(ModerationAction::count())->toBe(2);
});

test('classer sans suite : signalements résolus, contenu visible', function () {
    $listing = Listing::factory()->create();
    reportOf(withCauris(0), $listing, 'other');

    $this->actingAs(withCauris(300))->post(route('moderation.dismiss', ['listing', $listing->id]));

    expect($listing->fresh()->hidden_at)->toBeNull()->and(Report::sole()->resolved_at)->not->toBeNull()
        ->and(ModerationAction::sole()->action->value)->toBe('dismiss');
});

test('jamais sur son propre contenu, ni sur un contenu qu\'on a signalé ; refus sous 250', function () {
    $moderator = withCauris(400);
    $own = Question::factory()->for($moderator)->create();
    reportOf(withCauris(0), $own);
    $reportedByMe = Article::factory()->create();
    reportOf($moderator, $reportedByMe);

    $this->actingAs($moderator)->post(route('moderation.hide', ['question', $own->id]))->assertForbidden();
    $this->actingAs($moderator)->post(route('moderation.hide', ['article', $reportedByMe->id]))->assertForbidden();
    $this->actingAs(withCauris(100))->post(route('moderation.hide', ['article', $reportedByMe->id]))->assertForbidden();

    $this->actingAs($moderator)->get('/moderation')
        ->assertSee('Tu ne modères pas ton propre contenu : un autre modérateur s&#039;en charge.', false)
        ->assertSee('Tu as signalé ce contenu : un autre modérateur s&#039;en charge.', false);

    expect($own->fresh()->hidden_at)->toBeNull()->and(ModerationAction::count())->toBe(0);
});

test('modifier les tags d\'une question d\'autrui : à partir de 500 cauris, journalisé', function () {
    $question = Question::factory()->create();
    $question->skills()->attach(Skill::factory()->create(['name' => 'Divers']));

    $this->actingAs(withCauris(499))->put(route('questions.retag', $question), ['tags' => 'Wave, Laravel'])->assertForbidden();
    $this->actingAs($question->user)->put(route('questions.retag', $question), ['tags' => 'Wave'])->assertForbidden();
    $this->actingAs(withCauris(499))->get(route('questions.show', $question))->assertDontSee('data-retag', false);

    $expert = withCauris(500);
    $this->actingAs($expert)->get(route('questions.show', $question))->assertSee('Modifier les tags');
    $this->actingAs($expert)->put(route('questions.retag', $question), ['tags' => 'Wave, Laravel'])->assertSessionHas('status');

    expect($question->fresh()->skills->pluck('name')->all())->toBe(['Wave', 'Laravel'])
        ->and(ModerationAction::sole()->note)->toBe('Divers → Wave, Laravel');
});

test('page des privilèges : paliers de 11 et progression du membre', function () {
    $this->get('/privileges')->assertOk()
        ->assertSee('Plus tu aides,')->assertSee('plus tu comptes.')
        ->assertSee('Tes cauris débloquent de nouveaux rôles : la communauté se gère elle-même.')
        ->assertSeeInOrder(['Vote négatif', '50', 'Modération communautaire', '250', 'Modifier les tags', '500']);

    $this->actingAs(withCauris(120))->get('/privileges')->assertSee('Encore 130 cauris pour débloquer « Modération communautaire ».');
    expect(config('privileges'))->toBe(['downvote' => 50, 'moderate' => 250, 'retag' => 500]);
});
