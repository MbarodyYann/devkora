<?php

use App\Livewire\Devs\Directory;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $laravel = Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    $flutter = Skill::factory()->create(['name' => 'Flutter', 'slug' => 'flutter']);

    $this->moussa = User::factory()->withProfile(['country' => 'SN', 'city' => 'Dakar', 'languages' => ['fr', 'wo'], 'availability' => 'missions'])->create(['name' => 'Moussa Diop']);
    $this->moussa->skills()->attach($laravel);

    $this->aissatou = User::factory()->withProfile(['country' => 'CI', 'city' => 'Abidjan', 'languages' => ['fr', 'dyu'], 'availability' => 'collab'])->create(['name' => 'Aïssatou Koné']);
    $this->aissatou->skills()->attach($flutter);

    $this->kwame = User::factory()->withProfile(['country' => 'GH', 'city' => 'Accra', 'languages' => ['en'], 'availability' => 'unavailable'])->create(['name' => 'Kwame Asante']);
    $this->kwame->skills()->attach([$laravel->id, $flutter->id]);
});

test('l\'annuaire affiche l\'en-tête de la charte et des cartes de devs', function () {
    $this->get('/devs')
        ->assertOk()
        ->assertSee("Le bon dev est peut\u{2011}être")
        ->assertSee('<em class="font-serif italic text-secondary-400">dans ta ville.</em>', false)
        ->assertSee('Filtre par compétence, pays et langue : les cauris montrent qui aide la communauté et qui a déjà livré.')
        ->assertSee('3 devs')
        ->assertSee('Moussa Diop')
        ->assertSee('Dakar, Sénégal')
        ->assertSee('Ouvert aux missions')
        ->assertSee('Français, wolof');
});

test('un membre sans profil n\'apparaît pas', function () {
    User::factory()->create(['name' => 'Sans Profil']);

    $this->get('/devs')->assertDontSee('Sans Profil');
});

test('filtres : compétence, pays, langue, disponibilité, nom', function (array $filters, array $visible) {
    $component = Livewire::test(Directory::class);
    foreach ($filters as $property => $value) {
        $component->set($property, $value);
    }

    $names = $component->instance()->devs()->pluck('name')->sort()->values()->all();
    expect($names)->toBe(collect($visible)->sort()->values()->all());
})->with([
    'compétence' => [['skill' => 'laravel'], ['Moussa Diop', 'Kwame Asante']],
    'pays' => [['country' => 'CI'], ['Aïssatou Koné']],
    'langue' => [['language' => 'en'], ['Kwame Asante']],
    'disponibilité' => [['availability' => 'collab'], ['Aïssatou Koné']],
    'nom, sans tenir compte de la casse' => [['q' => 'moussa'], ['Moussa Diop']],
    'combinés' => [['skill' => 'flutter', 'country' => 'GH'], ['Kwame Asante']],
    'valeurs inconnues ignorées' => [['country' => 'XX', 'language' => 'zz', 'availability' => 'busy'], ['Moussa Diop', 'Aïssatou Koné', 'Kwame Asante']],
    'caractères spéciaux de LIKE échappés' => [['q' => '%'], []],
]);

test('les filtres sont dans l\'adresse en français', function () {
    $this->get('/devs?competence=flutter&pays=GH')
        ->assertSee('Kwame Asante')
        ->assertDontSee('Moussa Diop')
        ->assertDontSee('Aïssatou Koné');
});

test('les listes de filtres ne proposent que des valeurs présentes', function () {
    $component = Livewire::test(Directory::class)->instance();

    expect(array_keys($component->countryOptions()))->toBe(['CI', 'SN', 'GH'])
        ->and($component->languageOptions())->toBe(['fr' => 'Français', 'en' => 'Anglais', 'dyu' => 'Dioula', 'wo' => 'Wolof'])
        ->and($component->skillOptions()->pluck('slug')->all())->toBe(['flutter', 'laravel']);
});

test('aucun résultat : une invitation à élargir, et on efface les filtres', function () {
    Livewire::test(Directory::class)
        ->set('q', 'personne')
        ->assertSee('Personne avec ces critères, pour l\'instant.')
        ->assertSee('Effacer les filtres')
        ->call('resetFilters')
        ->assertSet('q', '')
        ->assertSee('Moussa Diop');
});

test('pagination par 12, sans requête N+1', function () {
    User::factory()->count(12)->withProfile()->create()->each(fn (User $u) => $u->skills()->attach(Skill::factory()->create()));

    // preventLazyLoading est actif : un chargement paresseux ferait échouer le rendu.
    Livewire::test(Directory::class)
        ->assertSee('15 devs')
        ->assertSee('1 à 12 sur 15')
        ->call('nextPage')
        ->assertSee('13 à 15 sur 15');
});

test('« peut‑être » ne se coupe pas au trait d\'union (U+2011)', function () {
    $this->get('/devs')->assertSee("Le bon dev est peut\u{2011}être")->assertDontSee('peut-être ancien');
});
