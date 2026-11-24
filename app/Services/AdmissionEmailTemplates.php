<?php

namespace App\Services;

use App\Models\ConfigApp;

/** E-mails des résultats : objet propre à l'e-mail, corps commun avec le SMS (`AdmissionSmsTemplates`). */
final class AdmissionEmailTemplates
{
    private const DEFAULTS = [
        AdmissionSmsTemplates::DEFINITIVE => 'Votre demande figure sur la liste définitive',
        AdmissionSmsTemplates::ATTENTE => 'Votre demande figure sur la liste d’attente définitive',
        AdmissionSmsTemplates::NON_RETENUE => 'CROU — Résultat de votre demande de logement',
    ];

    /** @return array<string, string> objet de l'e-mail par résultat */
    public static function subjects(array $saved): array
    {
        $subjects = self::DEFAULTS;
        foreach ($subjects as $key => $default) {
            $subjects[$key] = $saved['emails'][$key]['subject'] ?? $default;
        }

        return $subjects;
    }

    /** Les réclamations sont reçues à l'adresse de contact du CROU (Configurations système › Profil). */
    public static function reclamationEmail(?ConfigApp $config): ?string
    {
        return filter_var($config?->email, FILTER_VALIDATE_EMAIL) ?: null;
    }
}
