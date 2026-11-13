<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AcessoAdministrativo extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Acesso administrativo — CDA Digital')
            ->greeting('Olá, '.$notifiable->name)
            ->line('Foi criado para si um acesso à administração da plataforma CDA Digital.')
            ->line('Nível atribuído: '.($notifiable->nivelLabel() ?? $notifiable->nivel_acesso).'.')
            ->action('Definir a minha senha', route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->email,
            ]))
            ->line('A ligação é válida durante 72 horas.');
    }
}
