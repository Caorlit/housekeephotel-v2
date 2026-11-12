<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Lanciato quando un'addetta crea una segnalazione.
 * Fase 2: un listener potrà inoltrarla su WhatsApp.
 */
class IssueCreated
{
    use Dispatchable;

    public function __construct(public Issue $issue) {}
}
