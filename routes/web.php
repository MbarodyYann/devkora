<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Livewire\Devs\Directory;
use App\Livewire\Questions\Index as QuestionIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

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
