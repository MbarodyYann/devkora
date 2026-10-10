<?php

use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Devs\Directory;
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
