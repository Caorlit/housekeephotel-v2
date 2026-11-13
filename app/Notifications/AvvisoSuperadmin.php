<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Avviso generico per i superadmin (nuovi hotel/utenti, riepiloghi, inattività, errori). */
class AvvisoSuperadmin extends Notification
{
    /** @param list<string> $righe */
    public function __construct(
        public string $oggetto,
        public array $righe,
        public ?string $link = null,
        public ?string $etichetta = null,
        public bool $errore = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = (new MailMessage)->subject('Housekeep: '.$this->oggetto)->greeting($this->oggetto)->salutation('Housekeep Hotel');
        if ($this->errore) {
            $m->error();
        }
        foreach ($this->righe as $riga) {
            $m->line($riga);
        }
        if ($this->link) {
            $m->action($this->etichetta ?? 'Apri Housekeep', $this->link);
        }

        return $m;
    }
}
