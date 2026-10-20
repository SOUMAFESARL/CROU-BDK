<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EtudiantResetPasswordNotification extends Notification
{
    public function __construct(private readonly string $url, private readonly int $expireMinutes)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour,')
            ->line('Vous recevez cet e-mail car une réinitialisation du mot de passe de votre espace étudiant '.systemAppName().' a été demandée.')
            ->action('Réinitialiser mon mot de passe', $this->url)
            ->line("Ce lien expire dans {$this->expireMinutes} minutes.")
            ->line("Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet e-mail : votre mot de passe reste inchangé.")
            ->salutation(systemAppName());
    }
}
