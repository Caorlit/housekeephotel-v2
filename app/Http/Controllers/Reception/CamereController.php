<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\Room;
use App\Support\CurrentHotel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Anagrafica delle camere dell'hotel. */
class CamereController extends Controller
{
    public function index(): View
    {
        $camere = Room::query()
            ->orderBy('piano')
            ->orderByRaw('LENGTH(numero), numero')
            ->get();

        return view('reception.camere', [
            'perPiano' => $camere->groupBy('piano'),
            'totale' => $camere->where('attiva', true)->count(),
        ]);
    }

    public function store(Request $request, CurrentHotel $hotel): RedirectResponse
    {
        $dati = $request->validate([
            'numero' => ['required', 'string', 'max:20', Rule::unique('rooms')->where('hotel_id', $hotel->id())],
            'piano' => ['required', 'integer', 'between:-5,200'],
            'note_fisse' => ['nullable', 'string', 'max:255'],
        ]);
        $dati['numero'] = trim($dati['numero']);

        $room = Room::create($dati);
        EventLog::registra('camera_creata', ['user_id' => $request->user()->id], ['room_id' => $room->id]);

        return back()->with('ok', "Camera {$room->numero} aggiunta.");
    }

    /** Importazione rapida: "da 101 a 120, piano 1". */
    public function importa(Request $request): RedirectResponse
    {
        $dati = $request->validate([
            'da' => ['required', 'integer', 'min:0', 'max:99999'],
            'a' => ['required', 'integer', 'gte:da', 'max:99999'],
            'piano' => ['required', 'integer', 'between:-5,200'],
        ]);

        if ($dati['a'] - $dati['da'] >= 300) {
            return back()->withErrors(['a' => 'Al massimo 300 camere per volta.'])->withInput();
        }

        $esistenti = Room::pluck('numero')->flip();
        $create = 0;
        foreach (range($dati['da'], $dati['a']) as $n) {
            if (! $esistenti->has((string) $n)) {
                Room::create(['numero' => (string) $n, 'piano' => $dati['piano']]);
                $create++;
            }
        }
        $gia = ($dati['a'] - $dati['da'] + 1) - $create;

        EventLog::registra('camere_importate', ['user_id' => $request->user()->id], $dati + ['create' => $create]);

        return back()->with('ok', "Aggiunte {$create} camere al piano {$dati['piano']}.".($gia ? " {$gia} c'erano già." : ''));
    }

    public function update(Request $request, Room $room, CurrentHotel $hotel): RedirectResponse
    {
        $dati = $request->validate([
            'numero' => ['required', 'string', 'max:20', Rule::unique('rooms')->where('hotel_id', $hotel->id())->ignore($room->id)],
            'piano' => ['required', 'integer', 'between:-5,200'],
            'note_fisse' => ['nullable', 'string', 'max:255'],
            'attiva' => ['nullable', 'boolean'],
        ]);
        $dati['numero'] = trim($dati['numero']);
        $dati['attiva'] = $request->boolean('attiva');

        $room->update($dati);

        return back()->with('ok', "Camera {$room->numero} salvata.");
    }
}
