<?php

use App\Models\User;
use App\Support\Copy;

test('la page d\'inscription tutoie et reprend les textes de la charte', function () {
    $this->get('/inscription')
        ->assertOk()
        ->assertSee('Ton profil en ligne en 2 minutes.')
        ->assertSee('Nom, e-mail, pays, ville et mot de passe. Tes compétences viendront ensuite.')
        ->assertSee(Copy::SIGNUP_CTA)
        ->assertSee(Copy::SIGNUP_REASSURANCE)
        ->assertDontSee('Déjà membre ?'); // la navigation propose déjà « Connexion »
});

test('le CTA d\'inscription n\'apparaît qu\'une fois sur la page d\'inscription', function () {
    $html = $this->get('/inscription')->getContent();

    expect(substr_count($html, '>'.Copy::SIGNUP_CTA.'<'))->toBe(1)
        ->and($html)->not->toContain('data-nav-cta');
});

test('l\'inscription crée le compte et le profil, puis mène au profil avec la bienvenue', function () {
    $response = $this->post('/inscription', [
        'name' => '  Aminata   Sall ',
        'email' => 'Aminata@Exemple.SN',
        'country' => 'SN',
        'city' => 'Dakar',
        'password' => 'motdepasse-solide',
        'password_confirmation' => 'motdepasse-solide',
    ]);

    $user = User::sole();
    $response->assertRedirect(route('devs.show', $user));
    $this->assertAuthenticatedAs($user);

    expect($user->name)->toBe('Aminata Sall')
        ->and($user->email)->toBe('aminata@exemple.sn')
        ->and($user->reputation)->toBe(0)
        ->and($user->is_admin)->toBeFalse()
        ->and($user->profile->country)->toBe('SN')
        ->and($user->profile->city)->toBe('Dakar')
        ->and($user->profile->languages)->toBe(['fr']);

    $this->get(route('devs.show', $user))
        ->assertSee('Bienvenue !')
        ->assertSee('Ajoute tes compétences pour que les autres devs te trouvent.')
        ->assertSee('Compléter mon profil');
});

test('l\'inscription refuse les champs invalides, avec des messages en français', function () {
    User::factory()->create(['email' => 'pris@exemple.sn']);

    $this->from('/inscription')->post('/inscription', [
        'name' => '',
        'email' => 'pris@exemple.sn',
        'country' => 'FR',
        'city' => '',
        'password' => 'court',
        'password_confirmation' => 'autre',
    ])
        ->assertRedirect('/inscription')
        ->assertSessionHasErrors([
            'name' => 'Le champ nom est obligatoire.',
            'email' => 'Un compte existe déjà avec cette adresse e-mail.',
            'country' => 'Choisis ton pays dans la liste.',
            'city' => 'Le champ ville est obligatoire.',
        ])
        ->assertSessionHasErrors('password');

    expect(User::count())->toBe(1);
});

test('l\'inscription ignore les champs sensibles (réputation, administrateur)', function () {
    $this->post('/inscription', [
        'name' => 'Pirate', 'email' => 'pirate@exemple.sn', 'country' => 'SN', 'city' => 'Dakar',
        'password' => 'motdepasse-solide', 'password_confirmation' => 'motdepasse-solide',
        'reputation' => 5000, 'is_admin' => true,
    ]);

    $user = User::sole();
    expect($user->reputation)->toBe(0)->and($user->is_admin)->toBeFalse();
});

test('un membre connecté qui ouvre l\'inscription revient à l\'accueil', function () {
    $this->actingAs(User::factory()->withProfile()->create())
        ->get('/inscription')
        ->assertRedirect('/');
});
