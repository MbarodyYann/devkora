<?php

use App\Models\Listing;
use App\Models\Profile;
use App\Models\Proposal;
use App\Models\Question;
use App\Models\Skill;
use App\Services\PulseService;
use Illuminate\Support\Facades\Cache;

function missionWith(Skill $skill, int $budget, array $prices = [], array $attributes = []): Listing
{
    $listing = Listing::factory()->create(['budget_fcfa' => $budget] + $attributes);
    $listing->skills()->attach($skill);
    foreach ($prices as $price) {
        Proposal::factory()->for($listing)->create(['proposed_fcfa' => $price]);
    }

    return $listing;
}

test('« Combien facturer ? » : médiane des budgets et des prix proposés, 3 montants au moins', function () {
    $laravel = Skill::factory()->create(['name' => 'Laravel']);
    $flutter = Skill::factory()->create(['name' => 'Flutter']);
    missionWith($laravel, 100000, [120000]);
    missionWith($laravel, 200000, [180000]);
    missionWith($laravel, 900000, [], ['status' => 'cancelled']); // une mission annulée ne compte pas son budget
    missionWith($flutter, 150000, [140000]);                        // 2 montants : pas assez

    $rates = app(PulseService::class)->rates();

    expect($rates)->toHaveCount(1)
        ->and($rates[0])->toMatchArray(['skill' => 'Laravel', 'count' => 4, 'median' => 150000])
        ->and($rates[0]['low'])->toBeLessThan($rates[0]['median'])
        ->and($rates[0]['high'])->toBeGreaterThan($rates[0]['median']);
});

test('compétences demandées, globalement et par pays ; devs par pays ; activité sur 30 jours', function () {
    $wave = Skill::factory()->create(['name' => 'Wave']);
    $django = Skill::factory()->create(['name' => 'Django']);
    missionWith($wave, 50000, [], ['country' => 'SN']);
    missionWith($wave, 60000, [], ['country' => 'SN']);
    $question = Question::factory()->create();
    $question->user->profile->update(['country' => 'CI']);
    $question->skills()->attach($django);

    $pulse = app(PulseService::class);
    expect(collect($pulse->demandedSkills())->pluck('skill')->all())->toBe(['Wave', 'Django'])
        ->and(collect($pulse->demandedSkills('CI'))->pluck('skill')->all())->toBe(['Django'])
        ->and($pulse->demandCountries())->toHaveKeys(['SN', 'CI'])
        ->and(collect($pulse->devsByCountry())->sum('count'))->toBe(Profile::count())
        ->and($pulse->activity())->toHaveKeys(['resolved', 'articles', 'teams', 'missions']);
});

test('la page /pouls : textes de 11, tableaux, sélecteur de pays, barres en CSS ; en cache 10 minutes', function () {
    $laravel = Skill::factory()->create(['name' => 'Laravel']);
    foreach ([100000, 150000, 200000] as $budget) {
        missionWith($laravel, $budget);
    }

    $this->get('/pouls')
        ->assertOk()
        ->assertSee('Combien facturer ?')
        ->assertSee('<em class="font-serif italic text-secondary-400">Le marché te répond.</em>', false)
        ->assertSee('Tarifs médians en FCFA, compétences recherchées et activité de la communauté, calculés à partir des échanges réels.')
        ->assertSee("150\u{202F}000\u{00A0}FCFA")
        ->assertSee('data-devs-by-country', false)
        ->assertSee('style="width:', false)
        ->assertDontSee('chart.js');

    $this->get('/pouls?pays=SN')->assertOk();
    $this->get('/pouls?pays=XX')->assertOk();

    expect(Cache::has('pulse.rates'))->toBeTrue()->and(PulseService::CACHE_SECONDS)->toBe(600);

    // En cache : une nouvelle mission n'apparaît qu'après expiration.
    missionWith($laravel, 1000000);
    expect(app(PulseService::class)->rates()[0]['count'])->toBe(3);
});

test('Le Pouls : dans la navigation à partir de 1 280 px, dans le pied de page et le pilier de l\'accueil', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toMatch('/hidden xl:inline-block[^>]*>\s*Le Pouls/')
        ->toContain('Voir le Pouls →')
        ->and(substr($html, strpos($html, '<footer')))->toContain('>Le Pouls<');
});
