<?php

use App\Models\Answer;
use App\Models\Question;
use App\Models\ReputationEvent;
use App\Models\User;

function asker(): User
{
    return User::factory()->withProfile()->create();
}

test('répondre : réservé aux membres, compteur mis à jour, Markdown sécurisé', function () {
    $question = Question::factory()->create();
    $this->post(route('answers.store', $question), ['body' => 'Une réponse assez longue pour passer.'])->assertRedirect('/connexion');

    $user = asker();
    $response = $this->actingAs($user)->post(route('answers.store', $question), ['body' => "Utilise un middleware.\n\n<script>alert(1)</script>"]);

    $answer = Answer::sole();
    $response->assertRedirect(route('questions.show', $question).'#reponse-'.$answer->id)
        ->assertSessionHas('status', 'Réponse publiée. Chaque vote positif te rapporte +10 cauris.');

    expect($answer->user_id)->toBe($user->id)
        ->and($answer->body_html)->not->toContain('<script>')
        ->and($question->fresh()->answers_count)->toBe(1);
});

test('répondre : refus d\'une réponse trop courte ou à une question masquée', function () {
    $question = Question::factory()->create();
    $this->actingAs(asker())->post(route('answers.store', $question), ['body' => 'Court'])
        ->assertSessionHasErrors(['body' => 'Ta réponse est un peu courte : explique le pourquoi, pas seulement le quoi.']);

    $hidden = Question::factory()->create(['hidden_at' => now()]);
    $this->actingAs(asker())->post(route('answers.store', $hidden), ['body' => 'Une réponse assez longue pour passer.'])->assertForbidden();

    expect(Answer::count())->toBe(0);
});

test('seul l\'auteur de la question accepte une réponse (+15 cauris)', function () {
    $question = Question::factory()->create();
    $answer = Answer::factory()->for($question)->create();

    $this->post(route('answers.accept', [$question, $answer]))->assertRedirect('/connexion');
    $this->actingAs(asker())->post(route('answers.accept', [$question, $answer]))->assertForbidden();

    $this->actingAs($question->user)->post(route('answers.accept', [$question, $answer]))
        ->assertSessionHas('status', 'Réponse acceptée : elle s\'affiche en premier pour tous ceux qui chercheront la même chose.');

    expect($question->fresh()->accepted_answer_id)->toBe($answer->id)
        ->and($answer->user->fresh()->reputation)->toBe(15)
        ->and(ReputationEvent::where('reason', 'answer_accepted')->count())->toBe(1);
});

test('changer de réponse acceptée retire les +15 de l\'ancienne', function () {
    $question = Question::factory()->create();
    [$first, $second] = Answer::factory()->for($question)->count(2)->create();

    $this->actingAs($question->user)->post(route('answers.accept', [$question, $first]));
    $this->actingAs($question->user)->post(route('answers.accept', [$question, $second]));

    expect($first->user->fresh()->reputation)->toBe(0)
        ->and($second->user->fresh()->reputation)->toBe(15)
        ->and($question->fresh()->accepted_answer_id)->toBe($second->id);
});

test('accepter à nouveau la même réponse la retire', function () {
    $question = Question::factory()->create();
    $answer = Answer::factory()->for($question)->create();

    $this->actingAs($question->user)->post(route('answers.accept', [$question, $answer]));
    $this->actingAs($question->user)->post(route('answers.accept', [$question, $answer]))
        ->assertSessionHas('status', 'Réponse retirée. Ta question est de nouveau ouverte.');

    expect($question->fresh()->accepted_answer_id)->toBeNull()->and($answer->user->fresh()->reputation)->toBe(0);
});

test('accepter sa propre réponse ne rapporte pas de cauris', function () {
    $question = Question::factory()->create();
    $answer = Answer::factory()->for($question)->for($question->user)->create();

    $this->actingAs($question->user)->post(route('answers.accept', [$question, $answer]));

    expect($question->fresh()->accepted_answer_id)->toBe($answer->id)->and($question->user->fresh()->reputation)->toBe(0);
});

test('une réponse d\'une autre question donne une 404', function () {
    $question = Question::factory()->create();
    $other = Answer::factory()->create();

    $this->actingAs($question->user)->post(route('answers.accept', [$question, $other]))->assertNotFound();
});
