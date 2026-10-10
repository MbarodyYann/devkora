<?php

use App\Enums\ListingStatus;
use App\Enums\ProposalStatus;
use App\Models\Listing;
use App\Models\Proposal;
use App\Models\User;

function teammate(): User
{
    return User::factory()->withProfile()->create();
}

test('rejoindre une équipe : message requis, une seule demande, jamais sur sa propre annonce', function () {
    $team = Listing::factory()->team()->create();
    $dev = teammate();

    $this->actingAs($dev)->post(route('proposals.store', $team), ['message' => 'Court'])
        ->assertSessionHasErrors(['message' => 'Ton message est un peu court : dis ce que tu as déjà fait de proche.']);

    $this->actingAs($dev)->post(route('proposals.store', $team), ['message' => 'Je fais du back-end Laravel depuis trois ans, dispo le soir.'])
        ->assertSessionHas('status', 'Demande envoyée. L\'auteur voit ton profil et ton message, puis t\'ajoute à l\'équipe.');

    $this->actingAs($dev)->post(route('proposals.store', $team), ['message' => 'Je fais du back-end Laravel depuis trois ans, dispo le soir.'])
        ->assertForbidden()->assertSee('Tu as déjà répondu à cette annonce.');

    $this->actingAs($team->user)->post(route('proposals.store', $team), ['message' => 'Je réponds à ma propre annonce, ce qui est refusé.'])
        ->assertForbidden();

    expect(Proposal::count())->toBe(1)->and(Proposal::sole()->proposed_fcfa)->toBeNull();
});

test('une équipe n\'accepte pas de prix', function () {
    $this->actingAs(teammate())->post(route('proposals.store', Listing::factory()->team()->create()), [
        'message' => 'Je fais du back-end Laravel depuis trois ans, dispo le soir.', 'proposed_fcfa' => 50000,
    ])->assertSessionHasErrors('proposed_fcfa');
});

test('l\'auteur accepte plusieurs membres, en refuse, puis ferme l\'annonce', function () {
    $team = Listing::factory()->team()->create();
    [$a, $b, $c] = Proposal::factory()->for($team)->count(3)->create(['proposed_fcfa' => null, 'delivery_days' => null]);

    $this->actingAs(teammate())->post(route('proposals.accept', [$team, $a]))->assertForbidden();

    $this->actingAs($team->user)->post(route('proposals.accept', [$team, $a]))->assertSessionHas('status');
    $this->actingAs($team->user)->post(route('proposals.accept', [$team, $b]));
    $this->actingAs($team->user)->post(route('listings.close', $team))
        ->assertSessionHas('status', 'Annonce fermée : ton équipe est complète. Bonne aventure !');

    expect($a->fresh()->status)->toBe(ProposalStatus::Accepted)
        ->and($b->fresh()->status)->toBe(ProposalStatus::Accepted)
        ->and($c->fresh()->status)->toBe(ProposalStatus::Declined)
        ->and($team->fresh()->status)->toBe(ListingStatus::Closed);

    // Fermée : plus de réponse, plus de décision.
    $this->actingAs(teammate())->post(route('proposals.store', $team), ['message' => 'Je voudrais rejoindre cette équipe complète.'])->assertForbidden();
    $this->actingAs($team->user)->post(route('proposals.decline', [$team, $c]))->assertForbidden();
});

test('la fiche montre l\'équipe : porteur du projet et membres acceptés', function () {
    $team = Listing::factory()->team()->create();
    $member = Proposal::factory()->for($team)->create(['status' => 'accepted', 'proposed_fcfa' => null, 'delivery_days' => null]);
    Proposal::factory()->for($team)->create(['proposed_fcfa' => null, 'delivery_days' => null]);

    $this->get(route('listings.show', $team))
        ->assertSee("L'équipe", false)
        ->assertSeeInOrder([$team->user->name, 'Porteur du projet', $member->user->name])
        ->assertSee('Rejoins cette équipe')
        ->assertDontSee('Demandes pour rejoindre');

    $this->actingAs($team->user)->get(route('listings.show', $team))
        ->assertSee("Demandes pour rejoindre l'équipe", false)
        ->assertSee("Accepter dans l'équipe", false)
        ->assertSee('Équipe complète');
});

test('« Projets et équipes » sur les profils du porteur et des membres', function () {
    $team = Listing::factory()->team()->create(['title' => 'Kit d\'interface pour le paiement mobile']);
    $member = Proposal::factory()->for($team)->create(['status' => 'accepted', 'proposed_fcfa' => null, 'delivery_days' => null]);

    $this->get(route('devs.show', $team->user))->assertSee('Projets et équipes')->assertSee('Porteur du projet');
    $this->get(route('devs.show', $member->user))->assertSee('Kit d&#039;interface pour le paiement mobile', false)->assertSee('Membre de l&#039;équipe', false);
});
