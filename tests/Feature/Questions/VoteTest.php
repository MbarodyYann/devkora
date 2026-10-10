<?php

use App\Livewire\Vote;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

function voter(int $reputation = 0): User
{
    $user = User::factory()->withProfile()->create();
    $user->forceFill(['reputation' => $reputation])->save();

    return $user;
}

test('voter +1 pour une réponse : score +1 et +10 cauris pour l\'auteur ; revoter annule', function () {
    $answer = Answer::factory()->create();

    Livewire::actingAs(voter())->test(Vote::class, ['votable' => $answer])
        ->call('up')->assertSet('score', 1)->assertSet('myVote', 1)
        ->call('up')->assertSet('score', 0)->assertSet('myVote', null);

    expect($answer->user->fresh()->reputation)->toBe(0);

    Livewire::actingAs(voter())->test(Vote::class, ['votable' => $answer])->call('up');
    expect($answer->fresh()->score)->toBe(1)->and($answer->user->fresh()->reputation)->toBe(10);
});

test('vote −1 : à partir de 50 cauris, −2 pour l\'auteur ; inverser son vote remplace les cauris', function () {
    $answer = Answer::factory()->create();

    Livewire::actingAs(voter(49))->test(Vote::class, ['votable' => $answer])
        ->call('down')->assertSet('score', 0)->assertSee('Le vote négatif se débloque à 50 cauris.');

    $expert = voter(50);
    Livewire::actingAs($expert)->test(Vote::class, ['votable' => $answer])
        ->call('down')->assertSet('score', -1)->assertSet('myVote', -1);
    expect($answer->user->fresh()->reputation)->toBe(-2);

    Livewire::actingAs($expert)->test(Vote::class, ['votable' => $answer->fresh()])
        ->call('up')->assertSet('score', 1);
    expect($answer->user->fresh()->reputation)->toBe(10);
});

test('on ne vote pas pour son propre contenu', function () {
    $answer = Answer::factory()->create();

    Livewire::actingAs($answer->user)->test(Vote::class, ['votable' => $answer])
        ->call('up')->assertSet('score', 0)->assertSee('Tu ne peux pas voter pour ton propre contenu.');
});

test('voter pour une question change son score, sans cauris', function () {
    $question = Question::factory()->create();

    Livewire::actingAs(voter())->test(Vote::class, ['votable' => $question])->call('up')->assertSet('score', 1);

    expect($question->fresh()->score)->toBe(1)->and($question->user->fresh()->reputation)->toBe(0);
});

test('un visiteur qui vote est envoyé vers la connexion', function () {
    Livewire::test(Vote::class, ['votable' => Answer::factory()->create()])->call('up')->assertRedirect(route('login'));
});

test('le vote est limité à 30 par minute', function () {
    $user = voter();
    $answers = Answer::factory()->count(31)->create();

    foreach ($answers->take(30) as $answer) {
        Livewire::actingAs($user)->test(Vote::class, ['votable' => $answer])->call('up');
    }

    Livewire::actingAs($user)->test(Vote::class, ['votable' => $answers->last()])
        ->call('up')->assertSet('score', 0)->assertSee('Doucement : réessaie dans une minute.');
});

test('un contenu masqué ne se vote pas', function () {
    $answer = Answer::factory()->create(['hidden_at' => now()]);

    Livewire::actingAs(voter())->test(Vote::class, ['votable' => $answer])->call('up')->assertSet('score', 0);
});
