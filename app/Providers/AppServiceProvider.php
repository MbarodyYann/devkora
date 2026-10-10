<?php

namespace App\Providers;

use App\Models\Answer;
use App\Models\Article;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Question;
use App\Models\Review;
use App\Models\User;
use App\Policies\ModerationPolicy;
use App\Policies\VotePolicy;
use App\Services\MarkdownRenderer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn () => ImageManager::gd());
        $this->app->singleton(MarkdownRenderer::class);
    }

    public function boot(): void
    {
        // Aucune requête N+1 hors production : un chargement paresseux lève une exception.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Les identifiants sont numériques : /devs/abc donne une 404, pas une erreur PostgreSQL.
        Route::pattern('user', '[0-9]+');
        Route::pattern('question', '[0-9]+');
        Route::pattern('answer', '[0-9]+');
        Route::pattern('listing', '[0-9]+');
        Route::pattern('proposal', '[0-9]+');
        Route::pattern('event', '[0-9]+');

        // Noms stables dans les colonnes polymorphes (votes, événements de réputation).
        Relation::enforceMorphMap([
            'user' => User::class,
            'question' => Question::class,
            'answer' => Answer::class,
            'article' => Article::class,
            'listing' => Listing::class,
            'review' => Review::class,
            'event' => Event::class,
        ]);

        // Le vote porte sur une question OU une réponse : la policy est déclarée explicitement.
        Gate::define('vote', [VotePolicy::class, 'vote']);

        // Privilèges débloqués par les cauris (config/privileges.php).
        Gate::define('moderate', fn (User $user) => ModerationPolicy::canModerate($user));
        Gate::define('moderate-content', [ModerationPolicy::class, 'act']);
        Gate::define('retag', fn (User $user, Question $question) => ModerationPolicy::canRetag($user) && $question->user_id !== $user->id);

        // Limites anti-abus (03, « Sécurité ») : chaque route POST en a une.
        $byMember = fn (Request $request) => $request->user()?->id ?: $request->ip();
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('profile', fn (Request $request) => Limit::perMinute(10)->by($byMember($request)));
        RateLimiter::for('questions', fn (Request $request) => Limit::perHour(10)->by($byMember($request)));
        RateLimiter::for('answers', fn (Request $request) => Limit::perHour(30)->by($byMember($request)));
        RateLimiter::for('actions', fn (Request $request) => Limit::perMinute(30)->by($byMember($request)));
        RateLimiter::for('articles', fn (Request $request) => Limit::perHour(5)->by($byMember($request)));
        RateLimiter::for('listings', fn (Request $request) => Limit::perHour(10)->by($byMember($request)));
        RateLimiter::for('proposals', fn (Request $request) => Limit::perHour(30)->by($byMember($request)));
        RateLimiter::for('payments', fn (Request $request) => Limit::perMinute(10)->by($byMember($request)));
        RateLimiter::for('reports', fn (Request $request) => Limit::perHour(20)->by($byMember($request)));
        RateLimiter::for('events', fn (Request $request) => Limit::perDay(5)->by($byMember($request)));
        RateLimiter::for('demo', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        // Requêtes Livewire (filtres de l'annuaire) : limite large.
        RateLimiter::for('livewire', fn (Request $request) => Limit::perMinute(120)->by($byMember($request)));

        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)->middleware(['web', 'throttle:livewire']));

        Carbon::setLocale(config('app.locale'));
    }
}
