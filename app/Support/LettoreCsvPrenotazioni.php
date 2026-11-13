<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Legge un CSV di prenotazioni esportato dal gestionale.
 *
 * Formato di riferimento (Hotel Esperia):
 *   Arrivo;Partenza;Cliente;Codice;Pax;Subtotale;PMS
 *   10/10/2026;11/10/2026;ROSSI;GF7IB3;2;88;207-M+S-B
 *
 * - separatore ; , o tabulazione (riconosciuto da solo);
 * - date gg/mm/aaaa (anche gg.mm.aaaa, gg-mm-aaaa, aaaa-mm-gg);
 * - camera = la parte prima del primo trattino o spazio ("207-M+S-B" → "207");
 * - Cliente, prezzi e qualsiasi altra colonna vengono IGNORATI (niente dati personali).
 */
class LettoreCsvPrenotazioni
{
    /** Nomi di colonna riconosciuti (minuscolo, senza spazi/punti). */
    private const COLONNE = [
        'arrivo' => ['arrivo', 'dataarrivo', 'checkin', 'arrival', 'dal'],
        'partenza' => ['partenza', 'datapartenza', 'checkout', 'departure', 'al'],
        'camera' => ['pms', 'camera', 'camere', 'stanza', 'room', 'ncamera', 'numerocamera'],
        'riferimento' => ['codice', 'riferimento', 'prenotazione', 'nprenotazione', 'codiceprenotazione', 'booking', 'id'],
        'pax' => ['pax', 'persone', 'ospiti', 'adulti', 'guests'],
    ];

    /**
     * @return array{righe: list<array>, errore: ?string}
     *                                                    ogni riga: riga, camera (numero), arrivo, partenza, riferimento, pax, errori[]
     */
    public function leggi(string $contenuto): array
    {
        $contenuto = $this->inUtf8($contenuto);
        $linee = preg_split('/\r\n|\r|\n/', $contenuto);
        $intestazione = array_shift($linee) ?? '';
        $sep = $this->separatore($intestazione);

        $mappa = $this->mappa(str_getcsv($intestazione, $sep, '"', ''));
        $mancanti = array_diff(['arrivo', 'partenza', 'camera'], array_keys($mappa));
        if ($mancanti) {
            return ['righe' => [], 'errore' => 'Nel file mancano le colonne: '.implode(', ', $mancanti)
                .'. Servono almeno Arrivo, Partenza e la camera (colonna "PMS" o "Camera").'];
        }

        $righe = [];
        foreach ($linee as $i => $linea) {
            if (trim($linea) === '' || trim($linea, " ;,\t") === '') {
                continue;
            }
            $c = str_getcsv($linea, $sep, '"', '');
            $val = fn (string $k) => isset($mappa[$k]) ? trim((string) ($c[$mappa[$k]] ?? '')) : '';

            $riga = [
                'riga' => $i + 2,
                'camera' => $this->numeroCamera($val('camera')),
                'arrivo' => $this->data($val('arrivo')),
                'partenza' => $this->data($val('partenza')),
                'riferimento' => mb_substr($val('riferimento'), 0, 100) ?: null,
                'pax' => ctype_digit($val('pax')) ? min(30, (int) $val('pax')) : null,
                'errori' => [],
            ];

            if ($riga['camera'] === '') {
                $riga['errori'][] = 'camera mancante';
            }
            if (! $riga['arrivo'] || ! $riga['partenza']) {
                $riga['errori'][] = 'date non valide';
            } elseif ($riga['partenza'] <= $riga['arrivo']) {
                $riga['errori'][] = 'la partenza non è dopo l\'arrivo';
            }

            $righe[] = $riga;
        }

        return ['righe' => $righe, 'errore' => $righe ? null : 'Il file non contiene prenotazioni.'];
    }

    public function numeroCamera(string $valore): string
    {
        return trim(preg_split('/[-\s\/]/', trim($valore))[0] ?? '');
    }

    public function data(string $valore): ?string
    {
        $valore = trim($valore);
        foreach (['d/m/Y', 'd.m.Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $formato) {
            try {
                $d = CarbonImmutable::createFromFormat('!'.$formato, $valore);
            } catch (\Throwable) {
                continue;
            }
            if ($d && $d->format($formato) === $valore) {
                return $d->toDateString();
            }
        }

        return null;
    }

    private function mappa(array $intestazioni): array
    {
        $mappa = [];
        foreach ($intestazioni as $indice => $nome) {
            $chiave = preg_replace('/[^a-z]/', '', mb_strtolower(trim($nome)));
            foreach (self::COLONNE as $campo => $sinonimi) {
                if (! isset($mappa[$campo]) && in_array($chiave, $sinonimi, true)) {
                    $mappa[$campo] = $indice;
                }
            }
        }

        return $mappa;
    }

    private function separatore(string $intestazione): string
    {
        $conteggi = [';' => substr_count($intestazione, ';'), ',' => substr_count($intestazione, ','), "\t" => substr_count($intestazione, "\t")];
        arsort($conteggi);

        return array_key_first($conteggi);
    }

    private function inUtf8(string $s): string
    {
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s); // BOM
        if (! mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
        }

        return $s;
    }
}
