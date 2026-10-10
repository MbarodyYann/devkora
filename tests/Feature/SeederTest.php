<?php

use App\Models\Profile;
use App\Models\Skill;
use App\Models\User;
use App\Support\Demo;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevSeeder;
use Illuminate\Support\Carbon;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

test('environ 40 devs de 12 pays ou plus, dont des pays non francophones', function () {
    $countries = Profile::distinct()->pluck('country');

    expect(User::count())->toBeBetween(38, 45)
        ->and(Profile::count())->toBe(User::count())
        ->and($countries->count())->toBeGreaterThanOrEqual(12)
        ->and($countries)->toContain('NG', 'GH', 'KE');
});

test('les comptes de démo : Moussa (Laravel, Dakar), Aïssatou (Flutter, Abidjan), Koffi (Lomé), actifs aujourd\'hui', function () {
    $personas = User::with(['profile', 'skills'])->whereIn('email', array_column(Demo::PERSONAS, 'email'))->get()->keyBy('email');

    expect($personas)->toHaveCount(3)
        ->and($personas[Demo::MOUSSA_EMAIL]->profile->city)->toBe('Dakar')
        ->and($personas[Demo::MOUSSA_EMAIL]->skills->first()->name)->toBe('Laravel')
        ->and($personas[Demo::AISSATOU_EMAIL]->profile->city)->toBe('Abidjan')
        ->and($personas[Demo::AISSATOU_EMAIL]->skills->first()->name)->toBe('Flutter')
        ->and($personas[Demo::KOFFI_EMAIL]->profile->city)->toBe('Lomé');

    foreach ($personas as $persona) {
        expect($persona->activityLabel())->toBe('Actif aujourd\'hui');
    }
});

test('juste après minuit, les comptes de démo restent actifs aujourd\'hui', function () {
    $this->travelTo(now()->startOfDay()->addMinutes(2));
    User::query()->delete();
    Skill::query()->delete();
    $this->seed(DevSeeder::class);

    expect(User::where('email', Demo::MOUSSA_EMAIL)->sole()->last_seen_at->isToday())->toBeTrue();
});

test('chaque membre a un titre, des compétences et des langues ; personne ne paraît actif avant son inscription', function () {
    foreach (User::with(['profile', 'skills'])->get() as $user) {
        expect($user->profile->headline)->not->toBeEmpty()
            ->and($user->skills)->not->toBeEmpty()
            ->and($user->profile->languages)->not->toBeEmpty()
            ->and($user->last_seen_at === null || $user->last_seen_at->gte($user->created_at))->toBeTrue()
            ->and($user->last_seen_at === null || $user->last_seen_at->lte(now()))->toBeTrue();
    }
});

test('les compétences locales sont marquées', function () {
    expect(Skill::where('is_local', true)->pluck('name')->sort()->values()->all())
        ->toBe(collect(DevSeeder::LOCAL_SKILLS)->sort()->values()->all());
});

test('les données sont en français, sans lorem ipsum', function () {
    $text = Profile::pluck('bio')->merge(Profile::pluck('headline'))->filter()->join(' ');

    expect(mb_strtolower($text))->not->toContain('lorem')->not->toContain('ipsum');
});

test('les inscriptions tiennent dans la fenêtre du concours, relative à la date du semis', function (string $date, string $oldestMonth) {
    $this->travelTo($date);
    User::query()->delete();
    Skill::query()->delete();
    $this->seed(DevSeeder::class);

    $start = Carbon::parse('2026-09-29')->max(now()->subDays(30));
    $users = User::with('profile')->get();

    foreach ($users as $user) {
        expect($user->created_at->gte($start->copy()->startOfDay()))->toBeTrue("{$user->name} inscrit avant la fenêtre")
            ->and($user->created_at->lte(now()))->toBeTrue()
            ->and($user->last_seen_at === null || $user->last_seen_at->gt($user->created_at))->toBeTrue();
    }

    expect($users->min('created_at')->translatedFormat('F Y'))->toBe($oldestMonth);
})->with([
    'au lancement' => ['2026-10-10 14:00', 'septembre 2026'],
    'pendant le vote du jury' => ['2026-11-01 09:00', 'octobre 2026'],
    'juste après minuit' => ['2026-10-25 00:03', 'septembre 2026'],
]);
