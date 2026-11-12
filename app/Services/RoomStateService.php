<?php

namespace App\Services;

use App\Models\EventLog;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto in cui cambia lo stato di una camera.
 *
 * Regole (CLAUDE.md §7):
 * - vince l'ultima azione, ma ogni cambio finisce in "events";
 * - le azioni offline portano l'orario del dispositivo: si applicano solo
 *   se più recenti dell'ultimo cambio registrato;
 * - ogni azione dal telefono ha un client_action_id: i reinvii non duplicano.
 */
class RoomStateService
{
    public const ESITO_APPLICATA = 'applicata';

    public const ESITO_DUPLICATA = 'duplicata';

    public const ESITO_SUPERATA = 'superata';

    public const ESITO_INVARIATA = 'invariata';

    /**
     * @param  CarbonImmutable|null  $quando  orario dell'azione (dal dispositivo); null = adesso
     */
    public function cambiaStato(
        RoomDay $roomDay,
        string $nuovoStato,
        Staff|User|null $autore = null,
        ?CarbonImmutable $quando = null,
        ?string $clientActionId = null,
    ): string {
        if (! in_array($nuovoStato, RoomDay::STATI, true)) {
            throw new \InvalidArgumentException("Stato non valido: {$nuovoStato}");
        }

        $adesso = CarbonImmutable::now();
        // Un orologio del telefono avanti non deve "bloccare" le azioni future.
        $quando = $quando === null || $quando->greaterThan($adesso) ? $adesso : $quando;

        try {
            return $this->applica($roomDay, $nuovoStato, $autore, $quando, $clientActionId);
        } catch (UniqueConstraintViolationException) {
            // Due invii identici arrivati insieme: il secondo è un duplicato.
            return self::ESITO_DUPLICATA;
        }
    }

    private function applica(RoomDay $roomDay, string $nuovoStato, Staff|User|null $autore, CarbonImmutable $quando, ?string $clientActionId): string
    {
        return DB::transaction(function () use ($roomDay, $nuovoStato, $autore, $quando, $clientActionId) {
            if ($clientActionId !== null && EventLog::withoutGlobalScopes()->where('client_action_id', $clientActionId)->exists()) {
                return self::ESITO_DUPLICATA;
            }

            /** @var RoomDay $rd */
            $rd = RoomDay::whereKey($roomDay->getKey())->lockForUpdate()->firstOrFail();
            $precedente = $rd->stato;

            $autoreAttr = [
                'room_day_id' => $rd->id,
                'staff_id' => $autore instanceof Staff ? $autore->id : null,
                'user_id' => $autore instanceof User ? $autore->id : null,
                'client_action_id' => $clientActionId,
            ];
            $payload = ['da' => $precedente, 'a' => $nuovoStato, 'quando' => $quando->toIso8601String()];

            if ($rd->stato_cambiato_alle !== null && $quando->lessThan($rd->stato_cambiato_alle)) {
                // Azione vecchia arrivata tardi: la registriamo ma non la applichiamo.
                EventLog::registra('azione_superata', $autoreAttr, $payload);

                return self::ESITO_SUPERATA;
            }

            if ($precedente === $nuovoStato) {
                EventLog::registra('stato_invariato', $autoreAttr, $payload);

                return self::ESITO_INVARIATA;
            }

            $rd->stato = $nuovoStato;
            $rd->stato_cambiato_alle = $quando;

            match ($nuovoStato) {
                RoomDay::DA_FARE => [$rd->iniziata_alle, $rd->finita_alle] = [null, null],
                RoomDay::IN_PULIZIA => (function () use ($rd, $quando, $precedente) {
                    // Da "pronta" a "in pulizia" = camera riaperta: si tiene l'inizio originale.
                    if ($precedente === RoomDay::DA_FARE || $rd->iniziata_alle === null) {
                        $rd->iniziata_alle = $quando;
                    }
                    $rd->finita_alle = null;
                })(),
                RoomDay::PRONTA => (function () use ($rd, $quando) {
                    $rd->iniziata_alle ??= $quando;
                    $rd->finita_alle = $quando;
                })(),
            };

            $rd->save();

            $tipo = $precedente === RoomDay::PRONTA && $nuovoStato !== RoomDay::PRONTA ? 'camera_riaperta' : 'stato_cambiato';
            EventLog::registra($tipo, $autoreAttr, $payload);

            $roomDay->setRawAttributes($rd->getAttributes(), true);

            return self::ESITO_APPLICATA;
        });
    }
}
