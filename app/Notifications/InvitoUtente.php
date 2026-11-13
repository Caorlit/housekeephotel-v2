<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Benvenuto a un nuovo utente della reception: link per scegliere la password (7 giorni). */
class InvitoUtente extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hotel = $notifiable->hotel?->nome;

        return (new MailMessage)
            ->subject('Benvenuto in Housekeep'.($hotel ? " – {$hotel}" : ''))
            ->greeting('Ciao '.$notifiable->nome.',')
            ->line('è stato creato il tuo accesso a Housekeep'.($hotel ? " per {$hotel}" : '').': da qui la reception segue le pulizie delle camere in tempo reale.')
            ->line('Per entrare scegli una password con il pulsante qui sotto. Il tuo nome utente è questo indirizzo email: '.$notifiable->email)
            ->action('Scegli la password', route('password.reimposta', ['token' => $this->token, 'email' => $notifiable->email, 'invito' => 1]))
            ->line('Il link vale 7 giorni. Se è scaduto, chiedi un nuovo invito a chi ti ha registrato.')
            ->salutation('Housekeep Hotel');
    }
}
