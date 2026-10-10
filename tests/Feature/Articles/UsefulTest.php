<?php

use App\Livewire\Useful;
use App\Models\Article;
use App\Models\ReputationEvent;
use App\Models\User;
use App\Services\VoteService;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

function reader(): User
{
    return User::factory()->withProfile()->create();
}

test('« Utile » : +1, +5 cauris pour l\'auteur ; revoter l\'annule', function () {
    $article = Article::factory()->create();
    $reader = reader();

    Livewire::actingAs($reader)->test(Useful::class, ['article' => $article])
        ->call('toggle')->assertSet('score', 1)->assertSet('voted', true)->assertSeeHtml('aria-pressed="true"');

    expect($article->user->fresh()->reputation)->toBe(5)
        ->and(ReputationEvent::where('reason', 'article_useful')->count())->toBe(1);

    Livewire::actingAs($reader)->test(Useful::class, ['article' => $article->fresh()])
        ->call('toggle')->assertSet('score', 0)->assertSet('voted', false);

    expect($article->user->fresh()->reputation)->toBe(0)->and(ReputationEvent::count())->toBe(0);
});

test('jamais sur son propre article', function () {
    $article = Article::factory()->create();

    Livewire::actingAs($article->user)->test(Useful::class, ['article' => $article])
        ->call('toggle')->assertSet('score', 0)->assertSee('Tu ne peux pas voter pour ton propre contenu.');
});

test('pas de vote négatif sur un article, même à 1 000 cauris', function () {
    $article = Article::factory()->create();
    $expert = reader();
    $expert->forceFill(['reputation' => 1000])->save();

    expect(Gate::forUser($expert)->inspect('vote', [$article, -1])->allowed())->toBeFalse()
        ->and(Gate::forUser($expert)->inspect('vote', [$article, 1])->allowed())->toBeTrue();
});

test('un visiteur est envoyé vers la connexion ; un article masqué ne se vote pas', function () {
    Livewire::test(Useful::class, ['article' => Article::factory()->create()])->call('toggle')->assertRedirect(route('login'));

    Livewire::actingAs(reader())->test(Useful::class, ['article' => Article::factory()->create(['hidden_at' => now()])])
        ->call('toggle')->assertSet('score', 0);
});

test('le score d\'un article reste le nombre de votes « Utile »', function () {
    $article = Article::factory()->create();
    foreach (User::factory()->count(3)->withProfile()->create() as $voter) {
        app(VoteService::class)->vote($voter, $article, 1);
    }

    expect($article->fresh()->score)->toBe(3)->and($article->user->fresh()->reputation)->toBe(15);
});
