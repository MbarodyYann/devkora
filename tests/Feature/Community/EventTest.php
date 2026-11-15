<?php

use App\Enums\EventType;
use App\Livewire\Events\Create;
use App\Livewire\Events\Index;
use App\Livewire\Listings\Create as ListingCreate;
use App\Models\Event;
use App\Models\Listing;
use App\Models\User;
use App\Support\Navigation;
use Livewire\Livewire;

function attendee(): User
{
    return User::factory()->withProfile(['city' => 'Dakar', 'country' => 'SN'])->create();
}

test('la liste : à venir d\'abord (du plus proche au plus lointain), puis passés ; filtres', function () {
    $later = Event::factory()->create(['title' => 'Atelier dans dix jours', 'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHours(2), 'type' => EventType::Workshop]);
    $soon = Event::factory()->create(['title' => 'Meetup dans deux jours', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(2), 'city' => 'Lomé', 'country' => 'TG']);
    $past = Event::factory()->create(['title' => 'Meetup passé', 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)->addHours(2)]);
    $online = Event::factory()->create(['title' => 'Atelier en ligne', 'type' => EventType::Workshop, 'online' => true, 'city' => null, 'country' => null, 'starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHours(2)]);

    $component = Livewire::test(Index::class);
    expect($component->instance()->upcoming()->pluck('title')->all())->toBe([$soon->title, $online->title, $later->title])
        ->and($component->instance()->past()->pluck('title')->all())->toBe([$past->title]);

    expect(Livewire::test(Index::class)->set('place', 'en-ligne')->instance()->upcoming()->pluck('title')->all())->toBe([$online->title])
        ->and(Livewire::test(Index::class)->set('place', 'Lomé')->instance()->upcoming()->pluck('title')->all())->toBe([$soon->title])
        ->and(Livewire::test(Index::class)->set('type', 'workshop')->instance()->upcoming()->pluck('title')->all())->toBe([$online->title, $later->title]);

    $this->get('/evenements')->assertOk()->assertSee('Rencontre les devs de ta ville,')->assertSeeInOrder(['À venir', 'Passés']);
});

test('« J\'y participe » / « Je n\'y vais plus », compteur et avatars ; pas après la fin', function () {
    $event = Event::factory()->create();
    $user = attendee();

    $this->post(route('events.participate', $event))->assertRedirect('/connexion');
    $this->actingAs($user)->post(route('events.participate', $event))->assertSessionHas('status', 'C\'est noté : tu participes. À bientôt !');
    $this->actingAs($user)->get(route('events.show', $event))->assertSee('Je n&#039;y vais plus', false)->assertSee('1 <span', false);
    $this->actingAs($user)->post(route('events.participate', $event))->assertSessionHas('status', 'C\'est noté : tu n\'y vas plus.');
    expect($event->participants()->count())->toBe(0);

    $past = Event::factory()->create(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHour()]);
    $this->actingAs($user)->post(route('events.participate', $past))->assertForbidden();
});

test('proposer un événement : textes, validation, lien d\'inscription en https, publication', function () {
    $this->get('/evenements/proposer')->assertRedirect('/connexion');

    Livewire::actingAs(attendee())->test(Create::class)
        ->set(['type' => 'meetup', 'title' => 'Court', 'description' => 'Trop court', 'date' => now()->subDay()->toDateString(), 'registrationUrl' => 'http://exemple.sn'])
        ->call('publish')
        ->assertHasErrors(['title', 'description', 'date', 'registrationUrl']);

    Livewire::actingAs(attendee())->test(Create::class)
        ->set(['type' => 'meetup', 'title' => 'Meetup Laravel Dakar', 'description' => str_repeat('Programme détaillé de la soirée. ', 3), 'date' => now()->addDays(3)->toDateString(), 'startTime' => '18:30', 'endTime' => '18:00'])
        ->call('publish')
        ->assertHasErrors(['endTime']);

    Livewire::actingAs(attendee())->test(Create::class)
        ->set(['type' => 'meetup', 'title' => 'Meetup Laravel Dakar : déployer sur un VPS', 'description' => str_repeat('Programme détaillé de la soirée. ', 3), 'date' => now()->addDays(3)->toDateString(), 'registrationUrl' => 'https://exemple.sn/inscription'])
        ->call('publish')
        ->assertRedirect();

    $event = Event::sole();
    expect($event->city)->toBe('Dakar')->and($event->title)->toBe("Meetup Laravel Dakar\u{00A0}: déployer sur un VPS")
        ->and($event->starts_at->format('H:i'))->toBe('18:30');

    $this->get(route('events.show', $event))->assertSee('rel="nofollow noopener noreferrer"', false)->assertSee('https://exemple.sn/inscription');
});

test('hackathon : « Chercher des coéquipiers pour cet événement » crée une annonce d\'équipe liée, listée sur la fiche', function () {
    $hackathon = Event::factory()->create(['type' => EventType::Hackathon, 'title' => 'Hackathon fintech']);
    $user = attendee();

    $this->actingAs($user)->get(route('events.show', $hackathon))
        ->assertSee('Chercher des coéquipiers pour cet événement')
        ->assertSee(route('listings.create', ['type' => 'team', 'evenement' => $hackathon->id]));

    Livewire::actingAs($user)->withQueryParams(['type' => 'team', 'evenement' => (string) $hackathon->id])->test(ListingCreate::class)
        ->assertSet('type', 'team')
        ->assertSee('Ton équipe sera liée au hackathon')
        ->set(['description' => str_repeat('Nous cherchons un dev back-end pour le hackathon. ', 3), 'skills' => 'Laravel', 'rolesWanted' => 'Dev back-end', 'hoursPerWeek' => '6'])
        ->call('publish')
        ->assertHasNoErrors();

    $listing = Listing::sole();
    expect($listing->event_id)->toBe($hackathon->id);
    $this->get(route('events.show', $hackathon))->assertSee('Les équipes qui se forment')->assertSee($listing->title);
    $this->get(route('listings.show', $listing))->assertSee('Pour Hackathon fintech');
});

test('un événement se signale ; masqué, il est en 404', function () {
    $event = Event::factory()->create(['hidden_at' => now()]);
    $this->get(route('events.show', $event))->assertNotFound();
    $this->actingAs(attendee())->get(route('events.show', Event::factory()->create()))->assertSee('data-report', false);
});

test('navigation : Événements sur ordinateur, pas dans la barre du bas ; lien depuis /annonces et le menu « Moi »', function () {
    $html = $this->actingAs(attendee())->get('/annonces')->getContent();
    preg_match_all('/>\s*(Questions|Articles|Annonces|Événements|Devs)\s*</', substr($html, strpos($html, 'aria-label="Navigation principale"'), 3000), $top);
    $bottom = substr($html, strpos($html, 'aria-label="Navigation mobile"'), 6000);

    expect($top[1])->toBe(['Questions', 'Articles', 'Annonces', 'Événements', 'Devs'])
        ->and(substr_count($bottom, 'flex-1'))->toBe(5)
        ->and($html)->toContain('data-events-link')
        ->and(Navigation::bottom())->toHaveCount(4);
});
