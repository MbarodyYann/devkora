<?php

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Livewire\Listings\Create;
use App\Livewire\Listings\Index;
use App\Models\Listing;
use App\Models\Proposal;
use App\Models\Skill;
use App\Models\User;
use App\Services\BudgetEstimator;
use App\Services\ListingService;
use Livewire\Livewire;

function poster(array $profile = []): User
{
    return User::factory()->withProfile($profile)->create();
}

test('la liste reprend l\'en-tête de 11 ; « Publier une annonce » pour les membres', function () {
    $this->get('/annonces')
        ->assertOk()
        ->assertSee('Construis avec les tiens,')
        ->assertSee('<em class="font-serif italic text-secondary-400">d&#039;un pays à l&#039;autre.</em>', false)
        ->assertSee('Trouve des coéquipiers pour ton projet ou confie une mission à un dev de confiance, payée en FCFA par Mobile Money.')
        ->assertDontSee('Publier une annonce');

    $html = $this->actingAs(poster())->get('/annonces')->getContent();
    expect(substr_count($html, 'Publier une annonce'))->toBe(2) // en-tête et menu « Publier »
        ->and(substr($html, strpos($html, 'data-publish-menu'), 6000))->toContain('Publier une annonce');
});

test('la navigation : Questions · Articles · Annonces · Devs', function () {
    $html = $this->get('/annonces')->getContent();
    preg_match_all('/>\s*(Questions|Articles|Annonces|Devs)\s*</', substr($html, strpos($html, 'aria-label="Navigation principale"'), 2500), $labels);

    expect($labels[1])->toBe(['Questions', 'Articles', 'Annonces', 'Devs']);
});

test('filtres : type, compétence, pays ou télétravail, recherche sans accents ; ouvertes d\'abord', function () {
    $laravel = Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    $mission = Listing::factory()->create(['title' => 'Intégration de la caisse Orange Money', 'country' => 'CI']);
    $mission->skills()->attach($laravel);
    $team = Listing::factory()->team()->create(['title' => 'Équipe pour le hackathon de Dakar', 'country' => null]);
    $closed = Listing::factory()->team()->create(['title' => 'Équipe déjà complète au Bénin', 'status' => ListingStatus::Closed, 'country' => 'BJ', 'created_at' => now()->addMinute()]);

    $titles = fn (array $filters) => Livewire::test(Index::class)->set($filters)->instance()->listings()->pluck('title')->all();

    expect($titles(['type' => 'mission']))->toBe([$mission->title])
        ->and($titles(['type' => 'team']))->toBe([$team->title, $closed->title])
        ->and($titles(['skill' => 'laravel']))->toBe([$mission->title])
        ->and($titles(['place' => 'teletravail']))->toBe([$team->title])
        ->and($titles(['place' => 'CI']))->toBe([$mission->title])
        ->and($titles(['q' => 'integration caisse']))->toBe([$mission->title])
        ->and($titles([])[2])->toBe($closed->title);
});

test('cartes : budget en Newsreader pour une mission, rôles et membres pour une équipe ; sans N+1', function () {
    Listing::factory()->create(['budget_fcfa' => 180000]);
    $team = Listing::factory()->team()->create(['roles_wanted' => 'Dev back-end, designer']);
    Proposal::factory()->for($team)->create(['status' => 'accepted', 'proposed_fcfa' => null, 'delivery_days' => null]);
    Listing::factory()->count(12)->create();

    Livewire::test(Index::class)
        ->assertSee("180\u{202F}000")
        ->assertSee('Dev back-end, designer')
        ->assertSeeHtml("2 dans l'équipe")
        ->assertSee('1 à 12 sur 14');
});

test('publier : le type d\'abord, puis les champs adaptés ; textes de 11', function () {
    $this->get('/annonces/publier')->assertRedirect('/connexion');

    Livewire::actingAs(poster())->test(Create::class)
        ->assertSee('Je cherche des coéquipiers')
        ->assertSee('Je confie une mission')
        ->set('type', 'mission')
        ->assertSee('Budget (FCFA)')
        ->assertDontSee('Rôles recherchés')
        ->set('type', 'team')
        ->assertSee('Rôles recherchés')
        ->assertSee('Publier mon annonce');
});

test('publier une mission et une équipe', function () {
    $user = poster(['country' => 'SN']);

    Livewire::actingAs($user)->test(Create::class)
        ->set(['type' => 'mission', 'title' => 'Module Orange Money pour une caisse', 'description' => str_repeat('Le contexte de la mission est détaillé ici. ', 3), 'skills' => 'Laravel, Orange Money', 'budget' => '180 000', 'deliveryDays' => '10'])
        ->call('publish')
        ->assertRedirect();

    Livewire::actingAs($user)->test(Create::class)
        ->set(['type' => 'team', 'title' => 'Hackathon fintech : il manque un dev', 'description' => str_repeat('Le contexte du projet est détaillé ici. ', 3), 'skills' => 'Flutter', 'rolesWanted' => 'Dev back-end', 'hoursPerWeek' => '8', 'remote' => true])
        ->call('publish');

    [$mission, $team] = Listing::with('skills')->orderBy('id')->get();
    expect($mission->type)->toBe(ListingType::Mission)->and($mission->budget_fcfa)->toBe(180000)->and($mission->country)->toBe('SN')
        ->and($mission->status)->toBe(ListingStatus::Open)->and($mission->user_id)->toBe($user->id)
        ->and($mission->skills->pluck('name')->all())->toBe(['Laravel', 'Orange Money'])
        ->and($team->type)->toBe(ListingType::Team)->and($team->country)->toBeNull()->and($team->budget_fcfa)->toBeNull()
        ->and($team->hours_per_week)->toBe(8);
});

test('publier : refus avec des messages qui disent quoi faire', function (array $data, string $field, string $message) {
    Livewire::actingAs(poster(['country' => 'SN']))->test(Create::class)
        ->set(array_merge(['type' => 'mission', 'title' => 'Un titre suffisamment long', 'description' => str_repeat('Contexte détaillé. ', 6), 'skills' => 'Laravel', 'budget' => '150000', 'deliveryDays' => '10'], $data))
        ->call('publish')
        ->assertHasErrors([$field])
        ->assertSee($message);

    expect(Listing::count())->toBe(0);
})->with([
    [['budget' => '3000'], 'budget', 'Le budget doit être d\'au moins 5 000 FCFA.'],
    [['budget' => ''], 'budget', 'Indique ton budget en FCFA.'],
    [['deliveryDays' => '400'], 'deliveryDays', 'Le délai doit être entre 1 et 180 jours.'],
    [['skills' => ', ,'], 'skills', 'Ajoute au moins une compétence, par exemple Laravel.'],
    [['title' => 'Court'], 'title', 'Ton titre est un peu court : dis ce que tu cherches en une phrase.'],
    [['type' => 'team', 'rolesWanted' => ''], 'rolesWanted', 'Indique les rôles recherchés, par exemple « Dev back-end, designer ».'],
]);

test('estimateur : médiane des missions de mêmes compétences, au moins 3, sinon rien', function () {
    $laravel = Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    foreach ([100000, 180000, 250000] as $budget) {
        Listing::factory()->create(['budget_fcfa' => $budget])->skills()->attach($laravel);
    }
    Listing::factory()->create(['budget_fcfa' => 900000, 'status' => ListingStatus::Cancelled])->skills()->attach($laravel);

    expect(app(BudgetEstimator::class)->estimate(['Laravel']))->toBe(['median' => 180000, 'count' => 3, 'label' => 'Laravel'])
        ->and(app(BudgetEstimator::class)->estimate(['Flutter']))->toBeNull();

    Livewire::actingAs(poster())->test(Create::class)
        ->set(['type' => 'mission', 'skills' => 'Laravel'])
        ->assertSee('Les missions similaires (Laravel) ont un budget médian de')
        ->assertSee("180\u{202F}000\u{00A0}FCFA");

    Listing::query()->latest('id')->first()->delete();
    Listing::query()->latest('id')->first()->delete();
    expect(app(BudgetEstimator::class)->estimate(['Laravel']))->toBeNull();
});

test('les titres gardent la typographie française (espace insécable avant « : »)', function () {
    $listing = app(ListingService::class)->publish(poster(), ListingType::Mission, [
        'title' => 'Je suis débordée : module Wave ?', 'description' => 'Contexte.', 'budget_fcfa' => 10000, 'delivery_days' => 3, 'country' => 'SN',
    ], ['Wave']);

    expect($listing->title)->toBe("Je suis débordée\u{00A0}: module Wave\u{00A0}?");
});

test('annonce masquée : 404 pour les autres, visible de son auteur', function () {
    $listing = Listing::factory()->create(['hidden_at' => now()]);

    $this->get(route('listings.show', $listing))->assertNotFound();
    $this->actingAs($listing->user)->get(route('listings.show', $listing))->assertOk();
});
