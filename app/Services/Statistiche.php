<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Issue;
use App\Models\RoomDay;
use Carbon\CarbonImmutable;

/**
 * Numeri di un hotel in un periodo (date comprese, nel fuso dell'hotel).
 * Calcolati in PHP per funzionare uguale su MySQL e SQLite: i volumi sono piccoli.
 */
class Statistiche
{
    /** Durate fuori da questo intervallo (minuti) sono quasi sempre tocchi dimenticati: escluse dalle medie. */
    public const MIN_MINUTI = 1;

    public const MAX_MINUTI = 240;

    public function calcola(Hotel $hotel, string $dal, string $al): array
    {
        $giorni = RoomDay::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->whereDate('data', '>=', $dal)->whereDate('data', '<=', $al)
            ->with(['staff' => fn ($q) => $q->withoutGlobalScopes()])
            ->get();

        $pronte = $giorni->where('stato', RoomDay::PRONTA);
        $durata = function (RoomDay $g): ?float {
            if (! $g->iniziata_alle || ! $g->finita_alle) {
                return null;
            }
            $m = $g->iniziata_alle->diffInSeconds($g->finita_alle) / 60;

            return $m >= self::MIN_MINUTI && $m <= self::MAX_MINUTI ? $m : null;
        };
        $media = fn ($righe) => ($v = $righe->map($durata)->filter(fn ($m) => $m !== null))->isEmpty() ? null : (int) round($v->avg());

        $perAddetta = $pronte->groupBy(fn ($g) => $g->staff?->nome ?? '— non assegnata —')
            ->map(fn ($righe, $nome) => [
                'nome' => $nome,
                'pulite' => $righe->count(),
                'cambi' => $righe->where('tipo', 'partenza')->count(),
                'riassetti' => $righe->where('tipo', 'fermata')->count(),
                'media_cambio' => $media($righe->where('tipo', 'partenza')),
                'media_riassetto' => $media($righe->where('tipo', 'fermata')),
            ])->sortByDesc('pulite')->values()->all();

        $fuso = $hotel->fuso_orario;
        $inizio = CarbonImmutable::parse($dal, $fuso)->startOfDay()->utc();
        $fine = CarbonImmutable::parse($al, $fuso)->endOfDay()->utc();
        $segnalazioni = Issue::withoutGlobalScopes()->where('hotel_id', $hotel->id)->whereBetween('created_at', [$inizio, $fine])->get();
        $risolte = $segnalazioni->where('stato', Issue::RISOLTA)->filter(fn ($i) => $i->risolta_alle);

        return [
            'dal' => $dal,
            'al' => $al,
            'camere_previste' => $giorni->count(),
            'pulite' => $pronte->count(),
            'cambi' => $pronte->where('tipo', 'partenza')->count(),
            'riassetti' => $pronte->where('tipo', 'fermata')->count(),
            'extra' => $pronte->where('tipo', 'extra')->count(),
            'media_cambio' => $media($pronte->where('tipo', 'partenza')),
            'media_riassetto' => $media($pronte->where('tipo', 'fermata')),
            'per_addetta' => $perAddetta,
            'segnalazioni' => $segnalazioni->count(),
            'segnalazioni_risolte' => $risolte->count(),
            'segnalazioni_aperte' => $segnalazioni->where('stato', '!=', Issue::RISOLTA)->count(),
            'ore_risoluzione' => $risolte->isEmpty() ? null : round($risolte->avg(fn ($i) => $i->created_at->diffInMinutes($i->risolta_alle) / 60), 1),
        ];
    }

    /** Una riga di testo per email: "Hotel X: 312 camere pulite (…)". */
    public function riga(Hotel $hotel, array $s): string
    {
        $tempi = array_filter([
            $s['media_cambio'] !== null ? "cambio {$s['media_cambio']} min" : null,
            $s['media_riassetto'] !== null ? "riassetto {$s['media_riassetto']} min" : null,
        ]);

        return sprintf(
            '%s: %d camere pulite (%d cambi, %d riassetti)%s, %d segnalazioni (%d risolte).',
            $hotel->nome, $s['pulite'], $s['cambi'], $s['riassetti'],
            $tempi ? ', tempo medio '.implode(' · ', $tempi) : '',
            $s['segnalazioni'], $s['segnalazioni_risolte'],
        );
    }
}
