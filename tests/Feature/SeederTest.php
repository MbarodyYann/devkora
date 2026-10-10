<?php

use App\Enums\ProposalStatus;
use App\Models\Answer;
use App\Models\Article;
use App\Models\Event;
use App\Models\Listing;
use App\Models\ModerationAction;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Proposal;
use App\Models\Question;
use App\Models\Report;
use App\Models\ReputationEvent;
use App\Models\Review;
use App\Models\Skill;
use App\Models\User;
use App\Models\Vote;
use App\Support\Demo;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoClock;
use Database\Seeders\DevSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

test('12 articles longs, de toutes les catégories, d\'auteurs de pays différents', function () {
    $articles = Article::with('user.profile')->get();

    expect($articles)->toHaveCount(12)
        ->and($articles->pluck('category')->unique())->toHaveCount(4)
        ->and($articles->pluck('user.profile.country')->unique()->count())->toBeGreaterThanOrEqual(8);

    foreach ($articles as $article) {
        $words = str_word_count(Str::ascii($article->body));
        expect($words)->toBeBetween(600, 1500)
            ->and($article->body_html)->toContain('<h2>')
            ->and(mb_strtolower($article->body))->not->toContain('lorem')
            ->and($article->score)->toBeGreaterThan(0)
            ->and($article->created_at->gte($article->user->created_at))->toBeTrue();
    }
});

test('les votes « Utile » de la démo donnent des événements article_useful', function () {
    $useful = Vote::where('votable_type', 'article')->count();

    expect($useful)->toBe((int) Article::sum('score'))
        ->and(ReputationEvent::where('reason', 'article_useful')->count())->toBe($useful);
});

test('annonces : 10 équipes et 15 missions à tous les stades', function () {
    $missions = Listing::where('type', 'mission')->get();

    expect(Listing::where('type', 'team')->count())->toBe(10)
        ->and(Listing::where('type', 'team')->where('status', 'closed')->count())->toBeGreaterThanOrEqual(1)
        ->and($missions)->toHaveCount(15)
        ->and($missions->pluck('status')->map->value->unique()->sort()->values()->all())->toBe(['cancelled', 'completed', 'in_progress', 'open'])
        ->and(Payment::where('status', 'held')->count())->toBe($missions->where('status.value', 'in_progress')->count())
        ->and(Payment::where('status', 'released')->count())->toBe($missions->where('status.value', 'completed')->count())
        ->and(Payment::where('status', 'refunded')->count())->toBe(1)
        ->and($missions->pluck('budget_fcfa')->min())->toBeGreaterThanOrEqual(5000);
});

test('Aïssatou : une mission ouverte avec 3 propositions dont Moussa, et une équipe de hackathon ; Moussa a un avis 5 étoiles', function () {
    $aissatou = User::where('email', Demo::AISSATOU_EMAIL)->sole();
    $moussa = User::where('email', Demo::MOUSSA_EMAIL)->sole();

    $open = Listing::where('user_id', $aissatou->id)->where('type', 'mission')->where('status', 'open')->sole();
    expect($open->proposals()->count())->toBe(3)
        ->and($open->proposals()->where('user_id', $moussa->id)->exists())->toBeTrue()
        ->and(Listing::where('user_id', $aissatou->id)->where('type', 'team')->where('title', 'like', '%ackathon%')->exists())->toBeTrue()
        ->and($moussa->receivedReviews()->where('rating', 5)->exists())->toBeTrue();
});

test('les cauris de missions et d\'avis viennent des événements', function () {
    $completed = Listing::where('status', 'completed')->count();

    expect(ReputationEvent::where('reason', 'mission_completed')->count())->toBe($completed)
        ->and(ReputationEvent::where('reason', 'review_bonus')->count())->toBe(Review::where('rating', '>=', 3)->count());
    $this->artisan('reputation:recalculate')->expectsOutput('Tous les cauris sont justes.');
});

test('chaque réponse à une annonce arrive après l\'annonce et après l\'inscription de son auteur', function () {
    foreach (Proposal::with(['listing', 'user'])->get() as $proposal) {
        expect($proposal->created_at->gte($proposal->listing->created_at))->toBeTrue()
            ->and($proposal->created_at->gte($proposal->user->created_at))->toBeTrue()
            ->and($proposal->user_id)->not->toBe($proposal->listing->user_id);
    }
});

test('événements : 8, dont des à venir qui restent dans le futur, et le hackathon lié à l\'équipe d\'Aïssatou', function () {
    expect(Event::count())->toBe(8)
        ->and(Event::where('starts_at', '>', now())->count())->toBeGreaterThanOrEqual(4)
        ->and(Event::where('ends_at', '<', now())->count())->toBeGreaterThanOrEqual(2);

    $hackathon = Event::where('type', 'hackathon')->where('starts_at', '>', now())->sole();
    $team = Listing::where('event_id', $hackathon->id)->sole();
    expect($team->user->email)->toBe(Demo::AISSATOU_EMAIL);

    foreach (Event::with(['user', 'participants'])->get() as $event) {
        expect($event->created_at->lte($event->starts_at))->toBeTrue()
            ->and($event->participants->pluck('id'))->not->toBeEmpty();
    }
});

test('les événements à venir restent dans le futur, quelle que soit la date du semis', function () {
    $this->travelTo('2026-12-15 23:30');
    User::query()->delete();
    Skill::query()->delete();
    $this->seed(DatabaseSeeder::class);

    expect(Event::where('starts_at', '>', now())->count())->toBe(5);
});

test('modération : 3 contenus signalés en attente que Koffi peut traiter, une action au journal', function () {
    $koffi = User::where('email', Demo::KOFFI_EMAIL)->sole();
    $pending = Report::whereNull('resolved_at')->with('reportable')->get()->unique(fn ($r) => $r->reportable_type.$r->reportable_id);

    expect($pending)->toHaveCount(3)
        ->and($koffi->reputation)->toBeGreaterThanOrEqual(250)
        ->and(ModerationAction::count())->toBe(1);

    foreach ($pending as $report) {
        expect(Gate::forUser($koffi)->allows('moderate-content', $report->reportable))->toBeTrue();
    }
});

test('notifications de démo : les comptes de démo ont des notifications non lues', function () {
    foreach (array_column(Demo::PERSONAS, 'email') as $email) {
        expect(User::where('email', $email)->sole()->unreadNotifications()->count())->toBeGreaterThanOrEqual(2);
    }
});

test('démo : l\'équipe du hackathon d\'Aïssatou réunit trois pays dès que Kwame est accepté', function () {
    $team = Listing::with(['user.profile', 'acceptedProposals.user.profile', 'proposals.user.profile'])
        ->where('title', 'like', 'Hackathon fintech d%')->sole();
    $countries = fn () => collect([$team->user->profile->country])
        ->merge($team->acceptedProposals->map(fn ($p) => $p->user->profile->country))->unique()->values()->all();

    expect($countries())->toBe(['CI', 'CM'])
        ->and($team->proposals->where('status', ProposalStatus::Pending)->map(fn ($p) => $p->user->profile->country)->values()->all())->toBe(['GH']);
});

test('en production, aucun membre de démo n\'a le mot de passe « password »', function () {
    $this->app['env'] = 'production';
    User::query()->delete();
    app(DevSeeder::class)->run();

    expect(User::count())->toBeGreaterThan(30)
        ->and(User::all()->contains(fn (User $user) => Hash::check('password', $user->password)))->toBeFalse();
});
