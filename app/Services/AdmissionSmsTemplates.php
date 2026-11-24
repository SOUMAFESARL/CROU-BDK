<?php

namespace App\Services;

/** Corps des messages de résultat (texte brut), commun au SMS et à l'e-mail. */
class AdmissionSmsTemplates
{
    public const DEFINITIVE = 'definitive';
    public const ATTENTE = 'attente';
    public const NON_RETENUE = 'irrecevable_ou_refusee';

    public const LABELS = [
        self::DEFINITIVE => 'Liste définitive publiée',
        self::ATTENTE => 'Liste d’attente définitive publiée',
        self::NON_RETENUE => 'Demande irrecevable ou refusée',
    ];

    private const DEFAULTS = [
        self::DEFINITIVE => 'CROU : votre demande de logement est retenue. Consultez votre espace étudiant pour connaître la suite.',
        self::ATTENTE => 'CROU : votre demande de logement figure sur la liste d’attente définitive. Consultez votre espace étudiant pour suivre votre dossier.',
        self::NON_RETENUE => 'CROU : votre demande de logement n’a pas été retenue. Consultez votre espace étudiant pour connaître la décision et ses détails.',
    ];

    public static function resolve(array $saved): array
    {
        if (!isset($saved[self::NON_RETENUE]) && isset($saved['irrecevable']) && $saved['irrecevable'] === ($saved['refusee'] ?? null)) {
            $saved[self::NON_RETENUE] = $saved['irrecevable'];
        }

        return array_replace(self::DEFAULTS, array_intersect_key($saved, self::LABELS));
    }

    /** Mise en forme (HTML nettoyé) des messages enregistrés avec l'éditeur, utilisée par l'e-mail. */
    public static function formatted(array $saved): array
    {
        return array_intersect_key($saved['formatted'] ?? [], self::LABELS);
    }
}
