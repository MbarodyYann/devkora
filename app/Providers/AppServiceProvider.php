<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
    }

    public function boot(): void
    {
        // Aucune requête N+1 hors production : un chargement paresseux lève une exception.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Les identifiants sont numériques : /devs/abc donne une 404, pas une erreur PostgreSQL.
        Route::pattern('user', '[0-9]+');

        // Limites anti-abus (03, « Sécurité ») : chaque route POST en a une.
        $byMember = fn (Request $request) => $request->user()?->id ?: $request->ip();
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('profile', fn (Request $request) => Limit::perMinute(10)->by($byMember($request)));
        RateLimiter::for('demo', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        // Requêtes Livewire (filtres de l'annuaire) : limite large.
        RateLimiter::for('livewire', fn (Request $request) => Limit::perMinute(120)->by($byMember($request)));

        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)->middleware(['web', 'throttle:livewire']));

        Carbon::setLocale(config('app.locale'));
    }
}
