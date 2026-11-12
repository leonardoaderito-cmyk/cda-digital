<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CredenciaisIniciais extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Acesso à Área do Membro — CDA')
            ->greeting('Olá, '.$notifiable->name)
            ->line('A Câmara dos Despachantes Aduaneiros criou o seu acesso à Área do Membro.')
            ->line('O seu número de membro é '.$notifiable->carteira.'.')
            ->action('Definir a minha senha', $url)
            ->line('Esta ligação é válida durante 72 horas. Depois de definir a senha, poderá entrar com o seu número de membro.')
            ->salutation('Câmara dos Despachantes Aduaneiros de Moçambique');
    }
}
