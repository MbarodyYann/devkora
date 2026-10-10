<?php

use App\Models\User;
use App\Support\Demo;
use Database\Seeders\DevSeeder;

beforeEach(fn () => $this->seed(DevSeeder::class));

test('sans APP_DEMO, ni panneau ni connexion en un clic (404)', function () {
    config(['devkora.demo' => false]);

    $this->get('/connexion')->assertDontSee('Entrer comme Moussa');
    $this->post('/demo/moussa')->assertNotFound();
    $this->assertGuest();
});

test('avec APP_DEMO, les trois comptes de démo sont proposés et connectent en un clic', function (string $persona, string $email) {
    config(['devkora.demo' => true]);

    $this->get('/connexion')
        ->assertSee('Explore DevKora sans compte')
        ->assertSeeInOrder(['Entrer comme Moussa', 'Entrer comme Aïssatou', 'Entrer comme Koffi']);

    $user = User::where('email', $email)->sole();
    $this->post("/demo/$persona")->assertRedirect(route('devs.show', $user));
    $this->assertAuthenticatedAs($user);

    $this->get('/devs')->assertSee('Tu explores DevKora avec le compte de démonstration de '.Demo::PERSONAS[$persona]['name'].'.');
})->with([
    ['moussa', Demo::MOUSSA_EMAIL],
    ['aissatou', Demo::AISSATOU_EMAIL],
    ['koffi', Demo::KOFFI_EMAIL],
]);

test('un compte de démo inconnu donne une 404', function () {
    config(['devkora.demo' => true]);

    $this->post('/demo/inconnu')->assertNotFound();
    $this->post('/demo/Moussa')->assertNotFound();
});

test('un membre déjà connecté ne passe pas par la démo', function () {
    config(['devkora.demo' => true]);

    $this->actingAs(User::where('email', Demo::AISSATOU_EMAIL)->sole())
        ->post('/demo/moussa')
        ->assertRedirect('/');

    $this->assertAuthenticatedAs(User::where('email', Demo::AISSATOU_EMAIL)->sole());
});

test('le bandeau de démo ne s\'affiche pas pour un membre ordinaire', function () {
    config(['devkora.demo' => true]);

    $this->actingAs(User::factory()->withProfile()->create())->get('/devs')->assertDontSee('data-demo-banner', false);
});
