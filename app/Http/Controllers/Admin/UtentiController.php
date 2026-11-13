<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\User;
use App\Support\Avvisi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Superadmin: utenti della reception di un hotel, con invito via email. */
class UtentiController extends Controller
{
    public function store(Request $request, Hotel $hotel): RedirectResponse
    {
        $dati = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
        ]);

        // Password casuale che nessuno conosce: la sceglie l'utente dal link dell'invito.
        $user = User::create($dati + ['hotel_id' => $hotel->id, 'ruolo' => User::RUOLO_RECEPTION, 'password' => Str::random(40)]);

        Avvisi::superadmin("nuovo utente per {$hotel->nome}", [
            "{$user->nome} ({$user->email}) ora può accedere alla reception di {$hotel->nome}.",
            'Creato da '.$request->user()->nome.'.',
        ], route('admin.hotel.show', $hotel), 'Apri l\'hotel');

        return back()->with('ok', $this->invita($user)
            ? "Utente creato. Abbiamo mandato a {$user->email} l'email per scegliere la password (il link vale 7 giorni)."
            : "Utente creato, ma l'email d'invito NON è partita: controlla le impostazioni email e usa \"Reinvia invito\".");
    }

    public function invito(User $user): RedirectResponse
    {
        abort_if($user->isSuperadmin(), 404);

        return back()->with('ok', $this->invita($user)
            ? "Invito rimandato a {$user->email}."
            : "L'email non è partita: controlla le impostazioni email (MAIL_USERNAME e MAIL_PASSWORD nel file .env).");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isSuperadmin(), 404);
        $dati = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);
        $user->update($dati + ['attivo' => $request->boolean('attivo')]);

        return back()->with('ok', "{$user->nome} salvato.".($user->attivo ? '' : ' Non può più entrare.'));
    }

    private function invita(User $user): bool
    {
        try {
            $user->inviaInvito();

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
