<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReimpostaPassword extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Housekeep: reimposta la password')
            ->greeting('Ciao '.$notifiable->nome.',')
            ->line('abbiamo ricevuto una richiesta per reimpostare la password del tuo accesso a Housekeep.')
            ->action('Scegli una nuova password', route('password.reimposta', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line('Il link vale 60 minuti. Se non hai chiesto tu il cambio, ignora questa email: la password resta quella di prima.')
            ->salutation('Housekeep Hotel');
    }
}
