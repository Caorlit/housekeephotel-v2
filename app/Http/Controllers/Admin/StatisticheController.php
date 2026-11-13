<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Services\Statistiche;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatisticheController extends Controller
{
    public function index(Request $request, Statistiche $statistiche): View
    {
        $hotels = Hotel::orderByDesc('attivo')->orderBy('nome')->get();
        $hotel = $hotels->firstWhere('id', (int) $request->query('hotel')) ?? $hotels->first();

        $al = $this->data($request->query('al')) ?? CarbonImmutable::now($hotel?->fuso_orario ?? 'Europe/Rome')->toDateString();
        $dal = $this->data($request->query('dal')) ?? CarbonImmutable::parse($al)->subDays(29)->toDateString();
        if ($dal > $al) {
            [$dal, $al] = [$al, $dal];
        }

        return view('admin.statistiche', [
            'hotels' => $hotels,
            'hotel' => $hotel,
            'dal' => $dal,
            'al' => $al,
            's' => $hotel ? $statistiche->calcola($hotel, $dal, $al) : null,
        ]);
    }

    private function data(?string $d): ?string
    {
        return $d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) ? $d : null;
    }
}
