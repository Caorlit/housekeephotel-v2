<?php

use App\Http\Controllers\Addetta\AddettaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Reception\FotoController;
use App\Http\Controllers\Reception\TabelloneController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', fn () => redirect('/login'));

// --- Reception e superadmin: login standard -------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'ruolo:reception', 'hotel'])->prefix('reception')->name('reception.')->group(function () {
    Route::get('/', [TabelloneController::class, 'index'])->name('tabellone');
    Route::get('/foto/{issue}', [FotoController::class, 'show'])->name('foto');
});

Route::middleware(['auth', 'ruolo:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => view('admin.index'))->name('index');
});

// --- Addette: link personale /s/{token} -----------------------------------
// Niente sessione né CSRF: l'accesso è il token segreto nell'URL
// (così le azioni salvate offline possono partire anche ore dopo).
Route::prefix('s/{token}')
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
    ->middleware('throttle:addetta')
    ->name('addetta.')
    ->group(function () {
        Route::get('/', [AddettaController::class, 'home'])->middleware('addetta')->name('home');
        Route::post('/pin', [AddettaController::class, 'verificaPin'])->middleware('addetta:no')->name('pin');
        Route::get('/manifest.webmanifest', [AddettaController::class, 'manifest'])->middleware('addetta:no')->name('manifest');
        Route::get('/camere', [AddettaController::class, 'camere'])->middleware('addetta')->name('camere');
        Route::post('/azioni', [AddettaController::class, 'azioni'])->middleware('addetta')->name('azioni');
        Route::post('/segnalazioni', [AddettaController::class, 'segnalazione'])->middleware('addetta')->name('segnalazione');
    });
