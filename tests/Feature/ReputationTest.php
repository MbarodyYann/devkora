<?php

use App\Enums\Level;
use App\Enums\ReputationReason;
use App\Models\Answer;
use App\Models\ReputationEvent;
use App\Models\User;
use App\Services\ReputationService;

test('chaque variation passe par un événement ; users.reputation est leur somme', function () {
    $answer = Answer::factory()->create();
    $service = app(ReputationService::class);

    $service->award($answer->user, ReputationReason::AnswerUpvoted, $answer);
    $service->award($answer->user, ReputationReason::AnswerAccepted, $answer);
    $service->revokeOne($answer->user, ReputationReason::AnswerUpvoted, $answer);

    expect($answer->user->fresh()->reputation)->toBe(15)
        ->and((int) ReputationEvent::where('user_id', $answer->user_id)->sum('points'))->toBe(15);
});

test('reputation:recalculate corrige un total faussé', function () {
    $answer = Answer::factory()->create();
    app(ReputationService::class)->award($answer->user, ReputationReason::AnswerAccepted, $answer);
    $answer->user->forceFill(['reputation' => 999])->save();
    $other = User::factory()->create();
    $other->forceFill(['reputation' => 40])->save();

    $this->artisan('reputation:recalculate')->expectsOutput('2 membre(s) corrigé(s).')->assertSuccessful();
    $this->artisan('reputation:recalculate')->expectsOutput('Tous les cauris sont justes.');

    expect($answer->user->fresh()->reputation)->toBe(15)->and($other->fresh()->reputation)->toBe(0);
});

test('le niveau suit les vrais cauris', function () {
    $answer = Answer::factory()->create();
    foreach (range(1, 4) as $i) {
        app(ReputationService::class)->award($answer->user, ReputationReason::AnswerAccepted, $answer);
    }

    expect($answer->user->fresh()->level())->toBe(Level::Contributor);
});

test('étiquette « Nouveau » : moins de 14 jours', function () {
    expect(User::factory()->create(['created_at' => now()->subDays(13)])->isNewMember())->toBeTrue()
        ->and(User::factory()->create(['created_at' => now()->subDays(15)])->isNewMember())->toBeFalse();
});
