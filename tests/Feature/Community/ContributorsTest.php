<?php

use App\Enums\ReputationReason;
use App\Livewire\Devs\Directory;
use App\Models\Answer;
use App\Services\Contributors;
use App\Services\ReputationService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

test('contributeurs du mois : cauris gagnés sur 30 jours, les 5 premiers, en cache 10 minutes', function () {
    $answers = Answer::factory()->count(6)->create();
    foreach ($answers as $i => $answer) {
        foreach (range(0, $i) as $n) {
            app(ReputationService::class)->award($answer->user, ReputationReason::AnswerUpvoted, $answer);
        }
    }
    // Un gain ancien ne compte pas.
    $this->travel(-40)->days();
    app(ReputationService::class)->award($answers[0]->user, ReputationReason::AnswerAccepted, $answers[0]);
    app(ReputationService::class)->award($answers[0]->user, ReputationReason::AnswerAccepted, $answers[0]);
    $this->travelBack();

    $top = app(Contributors::class)->ofMonth(5);
    expect($top)->toHaveCount(5)
        ->and($top->first()->id)->toBe($answers[5]->user_id)
        ->and($top->first()->month_points)->toBe(60)
        ->and($top->pluck('id'))->not->toContain($answers[0]->user_id)
        ->and(Cache::has('contributors.month.5'))->toBeTrue()
        ->and(Contributors::CACHE_SECONDS)->toBe(600);
});

test('sur l\'accueil (H2 de 11) et en tête de /devs, sans filtre', function () {
    $answer = Answer::factory()->create();
    app(ReputationService::class)->award($answer->user, ReputationReason::AnswerAccepted, $answer);

    $this->get('/')->assertSee('Ils ont fait avancer la communauté ce mois-ci.')->assertSee('+15');
    Livewire::test(Directory::class)->assertSee('Les plus utiles ce mois-ci')
        ->set('q', 'personne')->assertDontSee('Les plus utiles ce mois-ci');
});
