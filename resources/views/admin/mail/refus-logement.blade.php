<?php


/**
 * resources/views/emails/refus-logement.blade.php
 */

@component('mail::message')

    # ❌ Votre demande d'hébergement

    Bonjour {{ $etudiant->name }},

    Suite à notre examen de votre demande d'hébergement, nous regrettons de vous informer que **celle-ci n'a pas pu être acceptée**.

    ## Motif du refus

    {{ $motif }}

    ## Que faire maintenant?

    - Consultez les critères d'éligibilité pour les prochaines campagnes
    - Contactez le service d'hébergement pour des conseils
    - Vous pouvez soumettre une nouvelle demande lors de la prochaine campagne

    ## Contact du service d'hébergement

    - **Email**: support@universite.ci
    - **Téléphone**: +225 XX XX XX XX
    - **Adresse**: [Adresse du service]
    - **Horaires**: Lundi - Vendredi, 8h00 - 17h00

    Nous restons à votre disposition pour toute question.

    Cordialement,
    **L'équipe du Service d'Hébergement**

@endcomponent