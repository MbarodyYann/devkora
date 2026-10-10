<?php

use App\Livewire\Questions\SimilarTitle;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

test('questions similaires pendant la saisie : 4 caractères au moins, sans accents, 3 au plus, sans bloquer', function () {
    Question::factory()->create(['title' => 'Webhook Wave : vérifier la signature', 'body' => 'Signature HMAC.']);
    Question::factory()->create(['title' => 'Déploiement Laravel sur un VPS', 'body' => 'Nginx.']);
    Question::factory()->count(4)->create(['title' => 'Wave en production', 'body' => 'Paiement Wave.']);

    Livewire::test(SimilarTitle::class)
        ->set('title', 'Wav')->assertDontSee('Ta question a peut-être déjà une réponse')
        ->set('title', 'deploiement laravel')->assertSee('Ta question a peut-être déjà une réponse')->assertSee('Déploiement Laravel sur un VPS')
        ->set('title', 'paiement wave')->assertSee('Rien ne correspond ? Continue : ta question sera publiée.');

    expect(Livewire::test(SimilarTitle::class)->set('title', 'paiement wave')->instance()->similar())->toHaveCount(3);
});

test('le champ titre garde son nom et sa valeur : la publication n\'est jamais bloquée', function () {
    $user = User::factory()->withProfile()->create();

    $this->actingAs($user)->get('/questions/poser')->assertSee('name="title"', false)->assertSee('wire:model.live.debounce.400ms="title"', false);
    $this->actingAs($user)->post('/questions', ['title' => 'Webhook Wave : vérifier la signature', 'body' => str_repeat('Détail utile. ', 5), 'tags' => 'Wave'])
        ->assertRedirect();
});
