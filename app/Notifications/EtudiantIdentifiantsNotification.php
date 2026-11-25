<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Identifiants de l'espace étudiant envoyés à l'adresse saisie dans la demande (brouillon ; envoi après paiement à venir). */
class EtudiantIdentifiantsNotification extends Notification
{
    public function __construct(
        private readonly string $email,
        private readonly string $motDePasse,
        private readonly string $urlConnexion,
        private readonly string $introduction = 'Voici vos identifiants pour accéder à l’espace étudiant :',
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
