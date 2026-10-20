<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Identifiants de l'espace étudiant envoyés à l'adresse saisie dans la demande (au passage en « Éligible », ou dès l'inscription pour un brouillon). */
class EtudiantIdentifiantsNotification extends Notification
{
    public function __construct(
        private readonly string $email,
        private readonly string $motDePasse,
        private readonly string $urlConnexion,
        private readonly string $introduction = 'Votre demande d’hébergement a été jugée éligible. Connectez-vous à l’espace étudiant pour confirmer que vous souhaitez toujours être logé(e) (ou refuser) ; une chambre ne vous sera attribuée qu’après cette confirmation. Vos identifiants :',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vos identifiants – Espace étudiant '.systemAppName())
            ->greeting('Bonjour,')
            ->line($this->introduction)
            ->line("Identifiant : {$this->email}")
            ->line("Mot de passe : {$this->motDePasse}")
            ->action('Accéder à mon espace', $this->urlConnexion)
            ->line('Pour votre sécurité, changez ce mot de passe depuis votre profil après votre première connexion.')
            ->salutation(systemAppName());
    }
}
