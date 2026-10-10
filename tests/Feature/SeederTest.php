<?php

use App\Models\Answer;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Skill;
use App\Models\User;
use App\Models\Vote;
use App\Support\Demo;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoClock;
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

test('entraide : environ 35 questions et 90 réponses, des résolues et des sans réponse', function () {
    expect(Question::count())->toBeBetween(35, 40)
        ->and(Answer::count())->toBeBetween(85, 100)
        ->and(Question::whereNotNull('accepted_answer_id')->count())->toBeGreaterThan(20)
        ->and(Question::where('answers_count', 0)->count())->toBeGreaterThanOrEqual(2)
        ->and(Question::sum('answers_count'))->toEqual(Answer::count());
});

test('les cauris viennent des événements, et Koffi dépasse 250', function () {
    foreach (User::all() as $user) {
        expect($user->reputation)->toBe((int) $user->reputationEvents()->sum('points'));
    }

    expect(User::where('email', Demo::KOFFI_EMAIL)->value('reputation'))->toBeGreaterThan(250);
    $this->artisan('reputation:recalculate')->expectsOutput('Tous les cauris sont justes.');
});

test('chaque contenu et chaque vote arrivent après l\'inscription de leur auteur, dans la fenêtre', function () {
    $start = DemoClock::make()->start();

    foreach ([Question::with('user')->get(), Answer::with(['user', 'question'])->get(), Vote::with('user')->get()] as $items) {
        foreach ($items as $item) {
            expect($item->created_at->gte($item->user->created_at))->toBeTrue()
                ->and($item->created_at->gte($start))->toBeTrue()
                ->and($item->created_at->lte(now()))->toBeTrue();
        }
    }

    foreach (Answer::with('question')->get() as $answer) {
        expect($answer->created_at->gte($answer->question->created_at))->toBeTrue();
    }

    // Personne ne paraît absent après avoir publié.
    foreach (User::with(['answers', 'questions'])->whereNotNull('last_seen_at')->get() as $user) {
        $latest = $user->answers->merge($user->questions)->max('created_at');
        expect($latest === null || $user->last_seen_at->gte($latest))->toBeTrue();
    }
});

test('aucun vote pour son propre contenu dans la démo', function () {
    foreach (Vote::with('votable')->get() as $vote) {
        expect($vote->user_id)->not->toBe($vote->votable->user_id);
    }
});

test('le contenu de démo est en français, sans lorem, avec du code', function () {
    $text = Question::pluck('body')->merge(Answer::pluck('body'))->join("\n");

    expect(mb_strtolower($text))->not->toContain('lorem')
        ->and(substr_count($text, '```'))->toBeGreaterThan(40)
        ->and(Question::pluck('title')->filter(fn ($t) => preg_match('/\b(the|how to|with)\b/i', $t))->all())->toBe([]);
});
