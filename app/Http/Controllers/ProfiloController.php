<?php

namespace App\Http\Controllers;

use App\Models\EventLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as RegolaPassword;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** "Il mio profilo": nome, email e password dell'utente collegato. */
class ProfiloController extends Controller
{
    public function show(Request $request): View
    {
        return view('profilo', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $dati = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password_attuale' => ['nullable', 'string'],
            'password' => ['nullable', 'confirmed', RegolaPassword::min(10)],
        ], [], ['password' => 'nuova password', 'password_attuale' => 'password attuale']);

        $cambiaEmail = $dati['email'] !== $user->email;
        $cambiaPassword = filled($dati['password'] ?? null);

        if (($cambiaEmail || $cambiaPassword) && ! Hash::check((string) ($dati['password_attuale'] ?? ''), $user->password)) {
            throw ValidationException::withMessages(['password_attuale' => 'Per cambiare email o password serve la password attuale (quella corretta).']);
        }

        $user->nome = $dati['nome'];
        $user->email = $dati['email'];
        if ($cambiaPassword) {
            $user->password = $dati['password'];
        }
        $user->save();

        if ($user->hotel_id) {
            EventLog::withoutGlobalScopes()->create([
                'hotel_id' => $user->hotel_id, 'user_id' => $user->id, 'tipo' => 'profilo_modificato',
                'payload' => ['email' => $cambiaEmail, 'password' => $cambiaPassword],
            ]);
        }

        return back()->with('ok', 'Profilo salvato.'.($cambiaPassword ? ' La nuova password vale dal prossimo accesso.' : ''));
    }
}
