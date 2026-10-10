<?php

use App\Models\Event;
use App\Models\Listing;
use App\Models\Question;
use App\Models\User;
use App\Support\Demo;
use Database\Seeders\DemoClock;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Schedule;

/*
| demo:reset vide et ressème la base : il ne peut pas tourner dans une transaction de test.
| Ces tests laissent la base semée ; les tests Feature suivants repartiront d'une base propre.
*/

afterAll(fn () => RefreshDatabaseState::$migrated = false);

test('demo:reset refuse de tourner sans APP_DEMO', function () {
    config(['devkora.demo' => false]);

    $this->artisan('demo:reset')
        ->expectsOutputToContain('Refusé : demo:reset ne tourne qu\'avec APP_DEMO=true')
        ->assertFailed();
});

test('demo:reset ressème une démo cohérente, relative à la date, à trois dates', function (string $date, string $oldestMonth) {
    config(['devkora.demo' => true]);
    $this->travelTo($date);

    $this->artisan('demo:reset')->assertSuccessful();

    $start = DemoClock::make()->start();
    expect(User::count())->toBeGreaterThan(38)
        ->and(User::min('created_at'))->toBeGreaterThanOrEqual($start->toDateTimeString())
        ->and(User::max('created_at'))->toBeLessThanOrEqual(now()->toDateTimeString())
        ->and(User::orderBy('created_at')->first()->created_at->translatedFormat('F Y'))->toBe($oldestMonth)
        ->and(Question::max('created_at'))->toBeLessThanOrEqual(now()->toDateTimeString())
        ->and(Listing::where('status', 'open')->exists())->toBeTrue()
        ->and(Event::where('starts_at', '>', now())->count())->toBe(5)
        ->and(User::where('email', Demo::KOFFI_EMAIL)->value('reputation'))->toBeGreaterThan(250);

    $this->artisan('reputation:recalculate')->expectsOutput('Tous les cauris sont justes.');
})->with([
    'au lancement' => ['2026-10-10 03:00', 'septembre 2026'],
    'pendant le vote du public' => ['2026-10-28 03:00', 'septembre 2026'],
    'pendant la notation du jury' => ['2026-11-01 03:00', 'octobre 2026'],
]);

test('demo:reset est planifié chaque nuit à 3 h, heure de Dakar', function () {
    $event = collect(Schedule::events())->first(fn ($e) => str_contains($e->command, 'demo:reset'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 3 * * *')
        ->and($event->timezone)->toBe('Africa/Dakar');
});
