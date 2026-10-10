<?php

use App\Enums\ListingStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProposalStatus;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Proposal;
use App\Models\ReputationEvent;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function helper(): User
{
    return User::factory()->withProfile()->create();
}

/** Parcours complet de sécurisation des fonds par l'auteur. */
function hire(Listing $mission, Proposal $proposal, string $provider = 'wave'): void
{
    test()->actingAs($mission->user)->post(route('payments.store', [$mission, $proposal]), ['provider' => $provider, 'phone' => '+225 07 07 07 07 07']);
    $code = session("payment.$mission->id")['code'];
    test()->actingAs($mission->user)->post(route('payments.verify', $mission), ['code' => $code]);
}

test('proposer son aide : prix, délai et message ; une seule proposition ; jamais sur sa propre mission', function () {
    $mission = Listing::factory()->create();
    $dev = helper();

    $this->actingAs($dev)->post(route('proposals.store', $mission), ['message' => 'J\'ai déjà intégré Orange Money pour une boutique.', 'proposed_fcfa' => '3 000', 'delivery_days' => 5])
        ->assertSessionHasErrors(['proposed_fcfa' => 'Le prix doit être d\'au moins 5 000 FCFA.']);

    $this->actingAs($dev)->post(route('proposals.store', $mission), ['message' => 'J\'ai déjà intégré Orange Money pour une boutique.', 'proposed_fcfa' => '140 000', 'delivery_days' => 8])
        ->assertSessionHas('status', 'Proposition envoyée. L\'auteur compare les propositions puis retient un dev.');

    $this->actingAs($dev)->post(route('proposals.store', $mission), ['message' => 'Une deuxième proposition, ce qui est refusé.', 'proposed_fcfa' => 100000, 'delivery_days' => 5])->assertForbidden();
    $this->actingAs($mission->user)->post(route('proposals.store', $mission), ['message' => 'Je propose sur ma propre mission, refusé.', 'proposed_fcfa' => 100000, 'delivery_days' => 5])->assertForbidden();

    $proposal = Proposal::sole();
    expect($proposal->proposed_fcfa)->toBe(140000)->and($proposal->delivery_days)->toBe(8)->and($proposal->status)->toBe(ProposalStatus::Pending);
});

test('« Choisis ton dev » : l\'auteur compare prix, délai, cauris, niveau, langues et activité', function () {
    $mission = Listing::factory()->create();
    $proposal = Proposal::factory()->for($mission)->create(['proposed_fcfa' => 165000, 'delivery_days' => 8]);
    $proposal->user->profile->update(['languages' => ['fr', 'wo']]);
    $proposal->user->forceFill(['reputation' => 425, 'last_seen_at' => now()])->save();

    $this->actingAs($mission->user)->get(route('listings.show', $mission))
        ->assertSee('Choisis ton dev')
        ->assertSee("165\u{202F}000")
        ->assertSee('8 <span', false)
        ->assertSee('425')
        ->assertSee('Confirmé')
        ->assertSee('Parle français, wolof')
        ->assertSee('Actif aujourd&#039;hui', false)
        ->assertSee('Retenir ce dev');

    // Un autre membre ne voit pas les propositions des autres.
    $this->actingAs(helper())->get(route('listings.show', $mission))->assertDontSee('Choisis ton dev')->assertSee('Proposer mon aide');
});

test('retenir un dev : opérateur, numéro masqué, code simulé, fonds sécurisés, les autres refusés', function () {
    $mission = Listing::factory()->create();
    [$chosen, $other] = Proposal::factory()->for($mission)->count(2)->create();

    $this->actingAs($mission->user)->get(route('payments.create', [$mission, $chosen]))
        ->assertOk()
        ->assertSee("Simulation : aucun argent réel n'est débité.", false)
        ->assertSee('Wave')->assertSee('Orange Money')->assertSee('Free Money')->assertSee('MTN MoMo')->assertSee('Moov Money');

    $this->actingAs($mission->user)->post(route('payments.store', [$mission, $chosen]), ['provider' => 'paypal', 'phone' => 'abc'])
        ->assertSessionHasErrors(['provider' => 'Choisis ton opérateur Mobile Money.', 'phone']);

    $this->actingAs($mission->user)->post(route('payments.store', [$mission, $chosen]), ['provider' => 'orange_money', 'phone' => '+225 07 07 07 07 07'])
        ->assertRedirect(route('payments.confirm', $mission));
    $code = session("payment.$mission->id")['code'];

    $this->actingAs($mission->user)->get(route('payments.confirm', $mission))
        ->assertSee($code)->assertSee('+225 07 ••• •• 07');

    $this->actingAs($mission->user)->post(route('payments.verify', $mission), ['code' => '000000'])
        ->assertSessionHasErrors(['code' => 'Ce code ne correspond pas. Vérifie les 6 chiffres affichés.']);
    expect(Payment::count())->toBe(0);

    $this->actingAs($mission->user)->post(route('payments.verify', $mission), ['code' => $code])->assertRedirect(route('listings.show', $mission));

    $payment = Payment::sole();
    expect($payment->status)->toBe(PaymentStatus::Held)
        ->and($payment->amount_fcfa)->toBe($chosen->proposed_fcfa)
        ->and($payment->reference)->toMatch('/^DK-[A-Z0-9]{6}$/')
        ->and($payment->phone)->toBe('+2250707070707')
        ->and($mission->fresh()->status)->toBe(ListingStatus::InProgress)
        ->and($chosen->fresh()->status)->toBe(ProposalStatus::Accepted)
        ->and($other->fresh()->status)->toBe(ProposalStatus::Declined);

    // Le refusé le voit sur la fiche.
    $this->actingAs($other->user)->get(route('listings.show', $mission))->assertSee('Non retenue');
    $this->actingAs($chosen->user)->get(route('listings.show', $mission))->assertSee('Tu es retenu');
});

test('seul l\'auteur retient un dev, et seulement sur une mission ouverte', function () {
    $mission = Listing::factory()->create();
    $proposal = Proposal::factory()->for($mission)->create();

    $this->actingAs(helper())->get(route('payments.create', [$mission, $proposal]))->assertForbidden();
    $this->actingAs($proposal->user)->post(route('payments.store', [$mission, $proposal]), ['provider' => 'wave', 'phone' => '+221771234567'])->assertForbidden();

    hire($mission, $proposal);
    $late = Proposal::factory()->for($mission)->create();
    $this->actingAs($mission->user)->get(route('payments.create', [$mission, $late]))->assertForbidden();
});

test('fin de mission : paiement libéré, +20 cauris pour le dev ; seul l\'auteur termine', function () {
    $mission = Listing::factory()->create();
    $proposal = Proposal::factory()->for($mission)->create();

    $this->actingAs($mission->user)->post(route('listings.complete', $mission))->assertForbidden(); // pas encore en cours
    hire($mission, $proposal);

    $this->actingAs($proposal->user)->post(route('listings.complete', $mission))->assertForbidden();
    $this->actingAs($mission->user)->post(route('listings.complete', $mission))
        ->assertSessionHas('status', 'Mission terminée : le paiement est libéré et le dev reçoit +20 cauris. Laisse-lui un avis.');

    expect($mission->fresh()->status)->toBe(ListingStatus::Completed)
        ->and($mission->fresh()->completed_at)->not->toBeNull()
        ->and(Payment::sole()->status)->toBe(PaymentStatus::Released)
        ->and($proposal->user->fresh()->reputation)->toBe(20)
        ->and(ReputationEvent::where('reason', 'mission_completed')->count())->toBe(1);

    // Terminée : ni annulation, ni seconde fin.
    $this->actingAs($mission->user)->post(route('listings.cancel', $mission))->assertForbidden();
    $this->actingAs($mission->user)->post(route('listings.complete', $mission))->assertForbidden();
});

test('annuler : ouverte ou en cours, remboursement simulé ; seul l\'auteur', function () {
    $open = Listing::factory()->create();
    $pending = Proposal::factory()->for($open)->create();
    $this->actingAs(helper())->post(route('listings.cancel', $open))->assertForbidden();
    $this->actingAs($open->user)->post(route('listings.cancel', $open))->assertSessionHas('status', 'Mission annulée.');
    expect($open->fresh()->status)->toBe(ListingStatus::Cancelled)->and($pending->fresh()->status)->toBe(ProposalStatus::Declined);

    $running = Listing::factory()->create();
    hire($running, Proposal::factory()->for($running)->create());
    $this->actingAs($running->user)->post(route('listings.cancel', $running))
        ->assertSessionHas('status', 'Mission annulée : les fonds sécurisés te sont remboursés (simulation).');
    expect(Payment::where('listing_id', $running->id)->sole()->status)->toBe(PaymentStatus::Refunded);

    $this->actingAs($running->user)->post(route('listings.cancel', $running))->assertForbidden();
});

test('avis : une seule fois, par l\'auteur, après la fin ; bonus de cauris selon la note', function (int $rating, int $bonus) {
    $mission = Listing::factory()->create();
    $proposal = Proposal::factory()->for($mission)->create();
    hire($mission, $proposal);

    $data = ['rating' => $rating, 'communication' => 5, 'quality' => 4, 'timeliness' => 5, 'comment' => 'Livré dans les temps.'];
    $this->actingAs($mission->user)->post(route('reviews.store', $mission), $data)->assertForbidden(); // pas terminée

    $this->actingAs($mission->user)->post(route('listings.complete', $mission));
    $this->actingAs($proposal->user)->post(route('reviews.store', $mission), $data)->assertForbidden();
    $this->actingAs($mission->user)->post(route('reviews.store', $mission), $data)->assertSessionHas('status');
    $this->actingAs($mission->user)->post(route('reviews.store', $mission), $data)->assertForbidden();

    expect(Review::count())->toBe(1)
        ->and(Review::sole()->reviewee_id)->toBe($proposal->user_id)
        ->and($proposal->user->fresh()->reputation)->toBe(20 + $bonus);
})->with([[5, 30], [4, 20], [3, 10], [2, 0]]);

test('avis : chaque critère est requis et entre 1 et 5', function () {
    $mission = Listing::factory()->create();
    hire($mission, Proposal::factory()->for($mission)->create());
    $this->actingAs($mission->user)->post(route('listings.complete', $mission));

    $this->actingAs($mission->user)->post(route('reviews.store', $mission), ['rating' => 6, 'quality' => 0])
        ->assertSessionHasErrors(['rating', 'communication' => 'Note la communication, de 1 à 5.', 'quality', 'timeliness']);
});

test('« Missions réalisées » et avis sur le profil du dev', function () {
    $mission = Listing::factory()->create(['title' => 'Intégration MTN MoMo pour une billetterie']);
    $proposal = Proposal::factory()->for($mission)->create();
    hire($mission, $proposal);
    $this->actingAs($mission->user)->post(route('listings.complete', $mission));
    $this->actingAs($mission->user)->post(route('reviews.store', $mission), ['rating' => 5, 'communication' => 5, 'quality' => 5, 'timeliness' => 5, 'comment' => 'Travail impeccable.']);
    auth()->logout();

    $this->get(route('devs.show', $proposal->user))
        ->assertSeeInOrder(['Missions réalisées', '5,0 · 1 avis', 'Intégration MTN MoMo pour une billetterie', 'Travail impeccable.', 'À propos']);
});

test('barre d\'action collante sur mobile : une seule action principale selon le visiteur', function () {
    $mission = Listing::factory()->create();
    Proposal::factory()->for($mission)->count(2)->create();

    $bar = fn (string $html) => substr($html, strpos($html, 'data-action-bar'), 2500);

    expect($bar($this->get(route('listings.show', $mission))->getContent()))->toContain('Décroche cette mission')->toContain('href="#agir"')
        ->and($bar($this->actingAs(helper())->get(route('listings.show', $mission))->getContent()))->toContain('Proposer mon aide')
        ->and($bar($this->actingAs($mission->user)->get(route('listings.show', $mission))->getContent()))->toContain('Choisis ton dev (2)')->toContain('href="#propositions"');
});

test('le paiement ne contacte aucun service externe', function () {
    Http::fake();
    $mission = Listing::factory()->create();
    hire($mission, Proposal::factory()->for($mission)->create());
    $this->actingAs($mission->user)->post(route('listings.complete', $mission));

    Http::assertNothingSent();
});
