<?php

use App\Http\Controllers\Addetta\AddettaController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\StatisticheController;
use App\Http\Controllers\Admin\UtentiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ProfiloController;
use App\Http\Controllers\Reception\AddetteController;
use App\Http\Controllers\Reception\CamereController;
use App\Http\Controllers\Reception\FotoController;
use App\Http\Controllers\Reception\GiornataController;
use App\Http\Controllers\Reception\ImportaPrenotazioniController;
use App\Http\Controllers\Reception\PrenotazioniController;
use App\Http\Controllers\Reception\TabelloneController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', fn () => redirect('/login'));

// Service worker servito da Laravel e mai messo in cache (né dal browser né dalla
// CDN di Hostinger), così gli aggiornamenti arrivano subito ai telefoni.
Route::get('/sw.js', fn () => response()->file(public_path('sw.js'), [
    'Content-Type' => 'application/javascript; charset=utf-8',
    'Cache-Control' => 'no-cache, no-store, must-revalidate',
    'Service-Worker-Allowed' => '/s/',
]))->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])->name('sw');

// --- Reception e superadmin: login standard -------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Password dimenticata e link d'invito (validi anche da utente non collegato).
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/password/dimenticata', [PasswordController::class, 'richiesta'])->name('password.dimenticata');
    Route::post('/password/dimenticata', [PasswordController::class, 'inviaLink'])->name('password.invia');
    Route::get('/password/reimposta/{token}', [PasswordController::class, 'form'])->name('password.reimposta');
    Route::post('/password/reimposta', [PasswordController::class, 'reimposta'])->name('password.salva');
});

Route::middleware(['auth', 'ruolo:reception,superadmin'])->group(function () {
    Route::get('/profilo', [ProfiloController::class, 'show'])->name('profilo');
    Route::put('/profilo', [ProfiloController::class, 'update'])->name('profilo.update');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'ruolo:reception', 'hotel'])->prefix('reception')->name('reception.')->group(function () {
    Route::get('/', [TabelloneController::class, 'index'])->name('tabellone');
    Route::get('/dati', [TabelloneController::class, 'dati'])->name('dati');
    Route::post('/camera/{roomDay}/stato', [TabelloneController::class, 'stato'])->name('camera.stato');
    Route::post('/camera/{roomDay}', [TabelloneController::class, 'aggiorna'])->name('camera.aggiorna');
    Route::post('/segnalazione/{issue}', [TabelloneController::class, 'segnalazione'])->name('segnalazione');
    Route::get('/foto/{issue}', [FotoController::class, 'show'])->name('foto');

    Route::get('/camere', [CamereController::class, 'index'])->name('camere');
    Route::post('/camere', [CamereController::class, 'store'])->name('camere.store');
    Route::post('/camere/importa', [CamereController::class, 'importa'])->name('camere.importa');
    Route::put('/camere/{room}', [CamereController::class, 'update'])->name('camere.update');

    Route::get('/giornata', [GiornataController::class, 'index'])->name('giornata');
    Route::prefix('/giornata/{data}')->where(['data' => '\d{4}-\d{2}-\d{2}'])->name('giornata.')->group(function () {
        Route::post('/applica', [GiornataController::class, 'applica'])->name('applica');
        Route::post('/incolla', [GiornataController::class, 'incolla'])->name('incolla');
        Route::post('/distribuisci', [GiornataController::class, 'distribuisci'])->name('distribuisci');
        Route::post('/copia-ieri', [GiornataController::class, 'copiaIeri'])->name('copia-ieri');
        Route::post('/sostituisci', [GiornataController::class, 'sostituisci'])->name('sostituisci');
    });

    Route::get('/prenotazioni', [PrenotazioniController::class, 'index'])->name('prenotazioni');
    Route::post('/prenotazioni', [PrenotazioniController::class, 'store'])->name('prenotazioni.store');
    Route::get('/prenotazioni/importa', [ImportaPrenotazioniController::class, 'form'])->name('prenotazioni.importa');
    Route::post('/prenotazioni/importa', [ImportaPrenotazioniController::class, 'anteprima'])->name('prenotazioni.anteprima');
    Route::post('/prenotazioni/importa/conferma', [ImportaPrenotazioniController::class, 'conferma'])->name('prenotazioni.conferma');
    Route::put('/prenotazioni/{stay}', [PrenotazioniController::class, 'update'])->name('prenotazioni.update');
    Route::delete('/prenotazioni/{stay}', [PrenotazioniController::class, 'destroy'])->name('prenotazioni.destroy');

    Route::get('/addette', [AddetteController::class, 'index'])->name('addette');
    Route::post('/addette', [AddetteController::class, 'store'])->name('addette.store');
    Route::post('/addette/pin-hotel', [AddetteController::class, 'pinHotel'])->name('addette.pin-hotel');
    Route::put('/addette/{staff}', [AddetteController::class, 'update'])->name('addette.update');
    Route::post('/addette/{staff}/rigenera', [AddetteController::class, 'rigenera'])->name('addette.rigenera');
    Route::post('/addette/{staff}/pin', [AddetteController::class, 'pin'])->name('addette.pin');
    Route::get('/addette/{staff}/stampa', [AddetteController::class, 'stampa'])->name('addette.stampa');
});

Route::middleware(['auth', 'ruolo:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [HotelController::class, 'index'])->name('index');
    Route::get('/statistiche', [StatisticheController::class, 'index'])->name('statistiche');
    Route::get('/hotel/nuovo', [HotelController::class, 'create'])->name('hotel.create');
    Route::post('/hotel', [HotelController::class, 'store'])->name('hotel.store');
    Route::get('/hotel/{hotel}', [HotelController::class, 'show'])->name('hotel.show');
    Route::put('/hotel/{hotel}', [HotelController::class, 'update'])->name('hotel.update');
    Route::delete('/hotel/{hotel}', [HotelController::class, 'destroy'])->name('hotel.destroy');
    Route::post('/hotel/{hotel}/utenti', [UtentiController::class, 'store'])->name('utenti.store');
    Route::put('/utenti/{user}', [UtentiController::class, 'update'])->name('utenti.update');
    Route::post('/utenti/{user}/invito', [UtentiController::class, 'invito'])->name('utenti.invito');
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
