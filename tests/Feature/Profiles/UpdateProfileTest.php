<?php

use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function validProfile(array $overrides = []): array
{
    return array_merge([
        'name' => 'Moussa Diop',
        'headline' => 'Dev Laravel',
        'bio' => 'Je construis des API.',
        'country' => 'SN',
        'city' => 'Dakar',
        'languages' => ['wo', 'fr'],
        'availability' => 'missions',
        'skills' => 'Laravel, Wave, laravel, PostgreSQL',
        'github_url' => 'https://github.com/moussa',
        'portfolio_url' => '',
    ], $overrides);
}

test('un visiteur est renvoyé vers la connexion', function () {
    $user = User::factory()->withProfile()->create();

    $this->get(route('profile.edit', $user))->assertRedirect('/connexion');
    $this->put(route('profile.update', $user), validProfile())->assertRedirect('/connexion');
});

test('on ne modifie pas le profil d\'un autre (403)', function () {
    $owner = User::factory()->withProfile()->create();
    $other = User::factory()->withProfile()->create();

    $this->actingAs($other)->get(route('profile.edit', $owner))->assertForbidden();
    $this->actingAs($other)->put(route('profile.update', $owner), validProfile())->assertForbidden();

    expect($owner->fresh()->name)->not->toBe('Moussa Diop');
});

test('le formulaire tutoie et propose les compétences existantes, locales d\'abord', function () {
    $user = User::factory()->withProfile()->create();
    Skill::factory()->create(['name' => 'Angular']);
    Skill::factory()->local()->create(['name' => 'Wave']);

    $this->actingAs($user)->get(route('profile.edit', $user))
        ->assertOk()
        ->assertSee('Fais‑toi trouver par les bons devs.')
        ->assertSee('Enregistrer mon profil')
        ->assertSee('Choisir une photo')
        ->assertSeeInOrder(['<option value="Wave">', '<option value="Angular">'], false);
});

test('le propriétaire met à jour son profil ; compétences dédoublonnées, dans l\'ordre saisi', function () {
    $user = User::factory()->withProfile()->create();
    Skill::factory()->local()->create(['name' => 'Wave', 'slug' => 'wave']);

    $this->actingAs($user)->put(route('profile.update', $user), validProfile())
        ->assertRedirect(route('devs.show', $user))
        ->assertSessionHas('status', 'Profil mis à jour. Les autres devs te trouvent maintenant avec ces informations.');

    $user->refresh()->load('profile', 'skills');
    expect($user->name)->toBe('Moussa Diop')
        ->and($user->profile->headline)->toBe('Dev Laravel')
        ->and($user->profile->availability->value)->toBe('missions')
        // Langues rangées dans l'ordre de config/languages.php.
        ->and($user->profile->languages)->toBe(['fr', 'wo'])
        ->and($user->skills->pluck('name')->all())->toBe(['Laravel', 'Wave', 'PostgreSQL'])
        ->and(Skill::where('slug', 'wave')->count())->toBe(1)
        ->and(Skill::where('slug', 'wave')->value('is_local'))->toBeTrue();
});

test('validation : lien GitHub, langues, pays, disponibilité, compétences', function (array $data, string $field, string $message) {
    $user = User::factory()->withProfile()->create();

    $this->actingAs($user)->from(route('profile.edit', $user))
        ->put(route('profile.update', $user), validProfile($data))
        ->assertRedirect(route('profile.edit', $user))
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'GitHub' => [['github_url' => 'https://gitlab.com/moussa'], 'github_url', 'Le lien GitHub doit commencer par https://github.com/.'],
    'aucune langue' => [['languages' => []], 'languages', 'Choisis au moins une langue.'],
    'trop de langues' => [['languages' => ['fr', 'en', 'wo', 'bm', 'ln', 'sw', 'ar']], 'languages', 'Choisis 6 langues au plus.'],
    'pays hors du continent' => [['country' => 'FR'], 'country', 'Choisis ton pays dans la liste.'],
    'disponibilité inconnue' => [['availability' => 'busy'], 'availability', 'La valeur choisie pour disponibilité est invalide.'],
    'trop de compétences' => [['skills' => implode(', ', array_map(fn ($i) => "Techno $i", range(1, 13)))], 'skills', 'Garde tes 12 compétences principales.'],
    'compétence trop longue' => [['skills' => str_repeat('a', 41)], 'skills', 'Chaque compétence doit faire 40 caractères au plus.'],
    'portfolio' => [['portfolio_url' => 'javascript:alert(1)'], 'portfolio_url', 'Colle l\'adresse complète de ton portfolio, avec https://.'],
]);

test('les champs sensibles ne passent pas par le formulaire', function () {
    $user = User::factory()->withProfile()->create();

    $this->actingAs($user)->put(route('profile.update', $user), validProfile(['reputation' => 9000, 'is_admin' => true, 'user_id' => 999]));

    $user->refresh();
    expect($user->reputation)->toBe(0)->and($user->is_admin)->toBeFalse()->and($user->profile->user_id)->toBe($user->id);
});

test('la photo est recadrée en carré de 256 px, en WebP, et l\'ancienne est supprimée', function () {
    Storage::fake('public');
    $user = User::factory()->withProfile()->create();

    $this->actingAs($user)->put(route('profile.update', $user), validProfile(['avatar' => UploadedFile::fake()->image('moi.jpg', 800, 600)]));
    $first = $user->profile->fresh()->avatar_path;

    Storage::disk('public')->assertExists($first);
    expect($first)->toEndWith('.webp')
        ->and(array_slice(getimagesizefromstring(Storage::disk('public')->get($first)), 0, 3))->toBe([256, 256, IMAGETYPE_WEBP]);

    $this->actingAs($user)->put(route('profile.update', $user), validProfile(['avatar' => UploadedFile::fake()->image('moi2.png', 300, 300)]));
    Storage::disk('public')->assertMissing($first);

    $this->get(route('devs.show', $user))->assertSee(Storage::disk('public')->url($user->profile->fresh()->avatar_path));
});

test('une photo trop petite ou un autre fichier est refusé', function () {
    Storage::fake('public');
    $user = User::factory()->withProfile()->create();

    $this->actingAs($user)->put(route('profile.update', $user), validProfile(['avatar' => UploadedFile::fake()->image('mini.png', 20, 20)]))
        ->assertSessionHasErrors(['avatar' => 'La photo doit mesurer au moins 64 × 64 pixels.']);
    $this->actingAs($user)->put(route('profile.update', $user), validProfile(['avatar' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')]))
        ->assertSessionHasErrors('avatar');
});
