<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Login minimale per reception e superadmin (email + password). */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $dati = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $chiave = 'login:'.Str::lower($dati['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($chiave, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Troppi tentativi. Riprova tra '.RateLimiter::availableIn($chiave).' secondi.',
            ]);
        }

        if (! Auth::attempt($dati, $request->boolean('ricordami'))) {
            RateLimiter::hit($chiave, 60);
            throw ValidationException::withMessages(['email' => 'Email o password non corretti.']);
        }

        RateLimiter::clear($chiave);
        $request->session()->regenerate();

        $user = $request->user();
        if (! $user->attivo) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Il tuo accesso è stato disattivato.']);
        }
        if (! $user->isSuperadmin() && ! $user->hotel?->attivo) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Il tuo hotel non è attivo.']);
        }

        return redirect()->intended($user->isSuperadmin() ? '/admin' : '/reception');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
