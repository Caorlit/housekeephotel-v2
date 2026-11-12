<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\RoomDay;
use App\Support\CurrentHotel;
use Illuminate\View\View;

/**
 * Tabellone della reception.
 * VERSIONE PROVVISORIA (Fase 3 da completare): elenco delle camere di oggi
 * che si ricarica da solo, utile per provare il pilota delle addette.
 */
class TabelloneController extends Controller
{
    public function index(CurrentHotel $current): View
    {
        $oggi = $current->get()->oggi();

        return view('reception.tabellone', [
            'oggi' => $oggi,
            'camere' => RoomDay::delGiorno($oggi)->ordinate()->with(['room', 'staff'])->get(),
            'segnalazioni' => Issue::where('stato', '!=', Issue::RISOLTA)->with(['roomDay.room', 'staff'])->latest()->get(),
        ]);
    }
}
