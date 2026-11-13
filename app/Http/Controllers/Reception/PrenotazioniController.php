<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\Room;
use App\Models\Stay;
use App\Services\GeneratoreGiornate;
use App\Support\CurrentHotel;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Planning delle prenotazioni: da qui nascono Cambi e Riassetti. */
class PrenotazioniController extends Controller
{
    public const GIORNI = 14;

    public function __construct(private GeneratoreGiornate $generatore) {}

    public function index(Request $request, CurrentHotel $hotel): View
    {
        $dal = $this->dal($request->query('dal'), $hotel);

        return view('reception.prenotazioni', [
            'dal' => $dal,
            'oggi' => $hotel->get()->oggi(),
            'camere' => Room::where('attiva', true)->orderBy('piano')->orderByRaw('LENGTH(numero), numero')
                ->get(['id', 'numero', 'piano']),
            'soggiorni' => $this->soggiorni($dal),
            'frequenze' => Stay::FREQUENZE,
        ]);
    }

    public function store(Request $request, CurrentHotel $hotel): JsonResponse
    {
        $dati = $this->valida($request);
        $stay = Stay::create($dati + ['fonte' => 'manuale']);
        $this->generatore->sincronizza($hotel->get(), $stay->room_id, $dati['arrivo'], $dati['partenza']);
        EventLog::registra('prenotazione_creata', ['user_id' => $request->user()->id], ['stay_id' => $stay->id]);

        return $this->risposta($request, $hotel);
    }

    public function update(Request $request, Stay $stay, CurrentHotel $hotel): JsonResponse
    {
        $dati = $this->valida($request, $stay);
        $prima = ['room_id' => $stay->room_id, 'arrivo' => $stay->arrivo->toDateString(), 'partenza' => $stay->partenza->toDateString()];

        $stay->update($dati);

        // Riallinea sia il vecchio periodo/camera sia il nuovo.
        $this->generatore->sincronizza($hotel->get(), $prima['room_id'], $prima['arrivo'], $prima['partenza']);
        $this->generatore->sincronizza($hotel->get(), $stay->room_id, $dati['arrivo'], $dati['partenza']);
        EventLog::registra('prenotazione_modificata', ['user_id' => $request->user()->id], ['stay_id' => $stay->id, 'prima' => $prima]);

        return $this->risposta($request, $hotel);
    }

    public function destroy(Request $request, Stay $stay, CurrentHotel $hotel): JsonResponse
    {
        $this->generatore->sincronizza($hotel->get(), $stay->room_id, $stay->arrivo->toDateString(), $stay->partenza->toDateString(), escludi: $stay->id);
        EventLog::registra('prenotazione_cancellata', ['user_id' => $request->user()->id], $stay->only(['id', 'room_id', 'riferimento']) + [
            'arrivo' => $stay->arrivo->toDateString(), 'partenza' => $stay->partenza->toDateString(),
        ]);
        $stay->delete();

        return $this->risposta($request, $hotel);
    }

    private function valida(Request $request, ?Stay $stay = null): array
    {
        $dati = $request->validate([
            'room_id' => ['required', 'integer'],
            'arrivo' => ['required', 'date_format:Y-m-d'],
            'partenza' => ['required', 'date_format:Y-m-d', 'after:arrivo'],
            'riassetto_ogni' => ['required', 'integer', Rule::in(array_keys(Stay::FREQUENZE))],
            'riferimento' => ['nullable', 'string', 'max:100'],
            'pax' => ['nullable', 'integer', 'between:1,30'],
            'nota' => ['nullable', 'string', 'max:255'],
        ]);

        $room = Room::find($dati['room_id']);
        if (! $room) {
            throw ValidationException::withMessages(['room_id' => 'Camera non valida.']);
        }

        $conflitto = Stay::sovrapposti($room->id, $dati['arrivo'], $dati['partenza'], $stay?->id)->first();
        if ($conflitto) {
            throw ValidationException::withMessages(['arrivo' => sprintf(
                'La camera %s è già occupata dal %s al %s.',
                $room->numero, $conflitto->arrivo->format('d/m'), $conflitto->partenza->format('d/m'),
            )]);
        }

        return $dati;
    }

    private function risposta(Request $request, CurrentHotel $hotel): JsonResponse
    {
        return response()->json(['soggiorni' => $this->soggiorni($this->dal($request->input('dal'), $hotel))]);
    }

    private function soggiorni(string $dal): array
    {
        $al = CarbonImmutable::parse($dal)->addDays(self::GIORNI)->toDateString();

        return Stay::whereDate('arrivo', '<=', $al)->whereDate('partenza', '>=', $dal)
            ->orderBy('arrivo')->get()
            ->map(fn (Stay $s) => [
                'id' => $s->id,
                'room_id' => $s->room_id,
                'arrivo' => $s->arrivo->toDateString(),
                'partenza' => $s->partenza->toDateString(),
                'riassetto_ogni' => $s->riassetto_ogni,
                'riferimento' => $s->riferimento,
                'pax' => $s->pax,
                'nota' => $s->nota,
                'fonte' => $s->fonte,
            ])->values()->all();
    }

    private function dal(?string $dal, CurrentHotel $hotel): string
    {
        if ($dal && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dal) && strtotime($dal)) {
            return $dal;
        }

        // Da ieri, così si vedono anche le partenze di oggi in contesto.
        return CarbonImmutable::parse($hotel->get()->oggi())->subDay()->toDateString();
    }
}
