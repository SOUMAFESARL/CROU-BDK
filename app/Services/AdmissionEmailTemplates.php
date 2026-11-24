<?php

namespace App\Services;

use App\Models\ConfigApp;

final class AdmissionEmailTemplates
{
    private const DEFAULTS = [
        AdmissionSmsTemplates::DEFINITIVE => [
            'subject' => 'CROU — Votre demande figure sur la liste définitive',
            'body' => "Bonjour,\n\nVotre demande de logement est retenue sur la liste définitive publiée. Consultez votre espace étudiant pour connaître la suite à donner à votre demande.",
        ],
        AdmissionSmsTemplates::ATTENTE => [
            'subject' => 'CROU — Votre demande figure sur la liste d’attente définitive',
            'body' => "Bonjour,\n\nVotre demande de logement figure sur la liste d’attente définitive publiée.",
        ],
        AdmissionSmsTemplates::NON_RETENUE => [
            'subject' => 'CROU — Résultat de votre demande de logement',
            'body' => "Bonjour,\n\nVotre demande de logement n’a pas été retenue à l’issue de l’examen de votre dossier.",
        ],
    ];

    public static function resolve(array $saved): array
    {
        $templates = self::DEFAULTS;
        foreach ($templates as $key => $defaults) {
            $templates[$key] = array_replace($defaults, array_intersect_key($saved['emails'][$key] ?? [], $defaults));
        }

        return $templates;
    }

    public static function reclamationEmail(?ConfigApp $config): ?string
    {
        foreach ([$config?->admission_sms_templates['reclamation_email'] ?? null, $config?->email] as $address) {
            if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return $address;
            }
        }

        return null;
    }
}
