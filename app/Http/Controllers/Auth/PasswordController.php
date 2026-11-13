<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as RegolaPassword;
use Illuminate\View\View;

/** "Password dimenticata" e scelta della password (anche dal link d'invito). */
class PasswordController extends Controller
{
    public function richiesta(): View
    {
        return view('auth.password-dimenticata');
    }

    public function inviaLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Stessa risposta che l'email esista o no: non si rivela chi è registrato.
        $user = User::where('email', $request->input('email'))->where('attivo', true)->first();
        if ($user) {
            try {
                Password::broker('users')->sendResetLink(['email' => $user->email]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('ok', 'Se l\'indirizzo è registrato, tra poco riceverai un\'email con il link per scegliere una nuova password.');
    }

    public function form(Request $request, string $token): View
    {
        return view('auth.password-reimposta', [
            'token' => $token,
            'email' => (string) $request->query('email'),
            'invito' => $request->boolean('invito'),
        ]);
    }

    public function reimposta(Request $request): RedirectResponse
    {
        $dati = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', RegolaPassword::min(10)],
        ], [], ['password' => 'nuova password']);

        $broker = $request->boolean('invito') ? 'inviti' : 'users';
        $utente = null;
        $esito = Password::broker($broker)->reset($dati, function (User $user, string $password) use (&$utente) {
            $user->forceFill(['password' => $password])->setRememberToken(null);
            $user->save();
            $utente = $user;
        });

        if ($esito !== Password::PASSWORD_RESET || ! $utente?->attivo) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Il link non è valido o è scaduto. '.($broker === 'inviti'
                    ? 'Chiedi un nuovo invito a chi ti ha registrato.'
                    : 'Richiedi un nuovo link da "Password dimenticata".'),
            ]);
        }

        Auth::login($utente);
        $request->session()->regenerate();

        return redirect($utente->isSuperadmin() ? '/admin' : '/reception')->with('ok', 'Password salvata. Benvenuto!');
    }
}
