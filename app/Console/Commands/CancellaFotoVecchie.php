<?php

namespace App\Console\Commands;

use App\Models\EventLog;
use App\Models\Issue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Privacy: le foto delle segnalazioni si cancellano dopo 90 giorni. */
#[Signature('hk:cancella-foto {--giorni=90}')]
#[Description('Cancella le foto delle segnalazioni più vecchie di N giorni')]
class CancellaFotoVecchie extends Command
{
    public function handle(): int
    {
        $limite = now()->subDays((int) $this->option('giorni'));
        $totale = 0;

        Issue::withoutGlobalScopes()
            ->whereNotNull('foto_path')
            ->where('created_at', '<', $limite)
            ->chunkById(200, function ($issues) use (&$totale) {
                foreach ($issues as $issue) {
                    Storage::disk('local')->delete($issue->foto_path);
                    EventLog::withoutGlobalScopes()->create([
                        'hotel_id' => $issue->hotel_id,
                        'room_day_id' => $issue->room_day_id,
                        'tipo' => 'foto_cancellata',
                        'payload' => ['issue_id' => $issue->id],
                    ]);
                    $issue->forceFill(['foto_path' => null])->saveQuietly();
                    $totale++;
                }
            });

        $this->info("Foto cancellate: {$totale}");

        return self::SUCCESS;
    }
}
