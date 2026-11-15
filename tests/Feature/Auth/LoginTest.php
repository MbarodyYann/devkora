<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('la page de connexion tutoie', function () {
    $this->get('/connexion')
        ->assertOk()
        ->assertSee('Content de te revoir.')
        ->assertSee('Me connecter')
        ->assertDontSee('Pas encore membre ?') // le CTA de la navigation suffit
        ->assertDontSee('data-demo-panel', false);
});

test('connexion avec les bons identifiants', function () {
    $user = User::factory()->withProfile()->create(['email' => 'fatou@exemple.sn']);

    $this->post('/connexion', ['email' => 'fatou@exemple.sn', 'password' => 'password'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('connexion refusée avec un mauvais mot de passe, message en français', function () {
    User::factory()->withProfile()->create(['email' => 'fatou@exemple.sn']);

    $this->from('/connexion')->post('/connexion', ['email' => 'fatou@exemple.sn', 'password' => 'faux'])
        ->assertRedirect('/connexion')
        ->assertSessionHasErrors(['email' => 'Cette adresse e-mail et ce mot de passe ne correspondent à aucun compte.']);

    $this->assertGuest();
});

test('après 5 essais, la connexion est bloquée un moment', function () {
    User::factory()->withProfile()->create(['email' => 'fatou@exemple.sn']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/connexion', ['email' => 'fatou@exemple.sn', 'password' => 'faux']);
    }

    $this->post('/connexion', ['email' => 'fatou@exemple.sn', 'password' => 'password'])->assertStatus(429);
    $this->assertGuest();
});

test('déconnexion', function () {
    $this->actingAs(User::factory()->withProfile()->create())
        ->post('/deconnexion')
        ->assertRedirect('/');

    $this->assertGuest();
});

test('mot de passe oublié : lien envoyé, page de nouveau mot de passe, changement', function () {
    Notification::fake();
    $user = User::factory()->withProfile()->create(['email' => 'fatou@exemple.sn']);

    $this->get('/mot-de-passe-oublie')->assertOk()->assertSee('Ça arrive à tout le monde.')->assertSee('Recevoir le lien');

    $this->post('/mot-de-passe-oublie', ['email' => 'fatou@exemple.sn'])
        ->assertSessionHas('status', 'C\'est envoyé : ouvre l\'e-mail et clique sur le lien pour choisir un nouveau mot de passe.');

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->get('/nouveau-mot-de-passe/'.$notification->token.'?email='.$user->email)
            ->assertOk()
            ->assertSee('Choisis ton nouveau mot de passe.');

        $this->post('/nouveau-mot-de-passe', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'nouveau-solide',
            'password_confirmation' => 'nouveau-solide',
        ])->assertSessionHasNoErrors()->assertRedirect('/connexion');

        return true;
    });

    $this->post('/connexion', ['email' => 'fatou@exemple.sn', 'password' => 'nouveau-solide']);
    $this->assertAuthenticated();
});

test('un lien de réinitialisation invalide est refusé', function () {
    User::factory()->withProfile()->create(['email' => 'fatou@exemple.sn']);

    $this->post('/nouveau-mot-de-passe', [
        'token' => 'faux', 'email' => 'fatou@exemple.sn',
        'password' => 'nouveau-solide', 'password_confirmation' => 'nouveau-solide',
    ])->assertSessionHasErrors(['email' => 'Ce lien n\'est plus valide. Demande un nouveau lien.']);
});

test('la confirmation du mot de passe a sa page', function () {
    $this->actingAs(User::factory()->withProfile()->create())
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee('Confirme ton mot de passe.');
});
