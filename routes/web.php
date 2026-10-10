<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ListingStatusController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\PulseController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionTagController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use App\Livewire\Articles\Create as ArticleCreate;
use App\Livewire\Articles\Index as ArticleIndex;
use App\Livewire\Devs\Directory;
use App\Livewire\Events\Create as EventCreate;
use App\Livewire\Events\Index as EventIndex;
use App\Livewire\Listings\Create as ListingCreate;
use App\Livewire\Listings\Index as ListingIndex;
use App\Livewire\Questions\Index as QuestionIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// SEO : plan du site et consignes aux robots (dynamiques, pour donner l'adresse absolue du plan)
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// À propos : identité, équipe, licences, usage de l'IA
Route::view('/a-propos', 'about')->name('about');

// Villes : devs, événements, annonces et questions d'une ville
Route::get('/villes/{slug}', CityController::class)->where('slug', '[a-z0-9-]+')->name('cities.show');

// Le Pouls : les chiffres réels de la communauté
Route::get('/pouls', PulseController::class)->name('pulse');

// Démo : connexion en un clic (404 si APP_DEMO=false)
Route::post('/demo/{persona}', DemoLoginController::class)
    ->middleware(['guest', 'throttle:demo'])
    ->where('persona', '[a-z]+')
    ->name('demo.login');

// Devs : annuaire et profils
Route::get('/devs', Directory::class)->name('devs.index');
Route::get('/devs/{user}', [ProfileController::class, 'show'])->name('devs.show');

Route::middleware('auth')->group(function () {
    Route::get('/devs/{user}/modifier', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/devs/{user}', [ProfileController::class, 'update'])->middleware('throttle:profile')->name('profile.update');
});

// Entraide : questions-réponses
Route::get('/questions', QuestionIndex::class)->name('questions.index');
Route::get('/questions/{question}', [QuestionController::class, 'show'])->name('questions.show');

Route::middleware('auth')->group(function () {
    Route::get('/questions/poser', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions', [QuestionController::class, 'store'])->middleware('throttle:questions')->name('questions.store');
    Route::post('/questions/{question}/reponses', [AnswerController::class, 'store'])->middleware('throttle:answers')->name('answers.store');
    Route::post('/questions/{question}/reponses/{answer}/accepter', [AnswerController::class, 'accept'])->middleware('throttle:actions')->name('answers.accept');
});

// Entraide : articles
Route::get('/articles', ArticleIndex::class)->name('articles.index');
Route::get('/articles/ecrire', ArticleCreate::class)->middleware('auth')->name('articles.create');
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

// Communauté : annonces entre devs (équipes et missions)
Route::get('/annonces', ListingIndex::class)->name('listings.index');
Route::get('/annonces/{listing}', [ListingController::class, 'show'])->name('listings.show');

Route::middleware('auth')->prefix('annonces')->group(function () {
    Route::get('/publier', ListingCreate::class)->name('listings.create');
    Route::post('/{listing}/reponses', [ProposalController::class, 'store'])->middleware('throttle:proposals')->name('proposals.store');
    Route::post('/{listing}/reponses/{proposal}/accepter', [ProposalController::class, 'accept'])->middleware('throttle:actions')->name('proposals.accept');
    Route::post('/{listing}/reponses/{proposal}/refuser', [ProposalController::class, 'decline'])->middleware('throttle:actions')->name('proposals.decline');
    // Retenir un dev = sécuriser les fonds (simulation), en deux étapes.
    Route::get('/{listing}/reponses/{proposal}/paiement', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/{listing}/reponses/{proposal}/paiement', [PaymentController::class, 'store'])->middleware('throttle:payments')->name('payments.store');
    Route::get('/{listing}/paiement/confirmation', [PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::post('/{listing}/paiement/confirmation', [PaymentController::class, 'verify'])->middleware('throttle:payments')->name('payments.verify');
    Route::post('/{listing}/fermer', [ListingStatusController::class, 'close'])->middleware('throttle:actions')->name('listings.close');
    Route::post('/{listing}/terminer', [ListingStatusController::class, 'complete'])->middleware('throttle:actions')->name('listings.complete');
    Route::post('/{listing}/annuler', [ListingStatusController::class, 'cancel'])->middleware('throttle:actions')->name('listings.cancel');
    Route::post('/{listing}/avis', [ReviewController::class, 'store'])->middleware('throttle:actions')->name('reviews.store');
});

// Notifications (cloche)
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/lire', [NotificationController::class, 'readAll'])->middleware('throttle:actions')->name('notifications.read-all');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->whereUuid('notification')->name('notifications.open');
});

// Par les devs : privilèges, signalement, modération communautaire
Route::view('/privileges', 'privileges')->name('privileges');

Route::middleware('auth')->group(function () {
    Route::post('/signalements', [ReportController::class, 'store'])->middleware('throttle:reports')->name('reports.store');
    Route::put('/questions/{question}/tags', [QuestionTagController::class, 'update'])->middleware('throttle:actions')->name('questions.retag');

    Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
    Route::middleware('throttle:actions')->prefix('moderation/{type}/{id}')->whereIn('type', ['question', 'answer', 'article', 'listing', 'event'])->whereNumber('id')->group(function () {
        Route::post('/masquer', [ModerationController::class, 'hide'])->name('moderation.hide');
        Route::post('/retablir', [ModerationController::class, 'restore'])->name('moderation.restore');
        Route::post('/classer', [ModerationController::class, 'dismiss'])->name('moderation.dismiss');
    });
});

// Communauté : événements
Route::get('/evenements', EventIndex::class)->name('events.index');
Route::get('/evenements/{event}', [EventController::class, 'show'])->name('events.show');
Route::middleware('auth')->group(function () {
    Route::get('/evenements/proposer', EventCreate::class)->name('events.create');
    Route::post('/evenements/{event}/participer', [EventController::class, 'participate'])->middleware('throttle:actions')->name('events.participate');
});
