<?php

use Illuminate\Support\Facades\Route;
use MeuCandidato\Identity\Http\Controllers\FollowController;

Route::view('/', 'welcome')->name('home');

Route::livewire('politicos', 'politicos.index')->name('politicos');
Route::livewire('politicos/{id}', 'politicos.show')->name('politicos.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::post('/politicos/{id}/follow', [FollowController::class, 'store'])->name('politicos.follow');
    Route::delete('/politicos/{id}/follow', [FollowController::class, 'destroy'])->name('politicos.unfollow');
});

require __DIR__.'/settings.php';
