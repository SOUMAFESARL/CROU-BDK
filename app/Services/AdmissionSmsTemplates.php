<?php

namespace App\Services;

use App\Models\ConfigApp;

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

    /** Rempli seulement à l'envoi : le mot de passe n'est jamais enregistré en clair. */
    public const PASSWORD = '{{password_étudiant}}';

    private const VARIABLES_COMMUNES = ['{{Nom_etudiant}}', '{{libelle_campagne}}', '{{annee_campagne}}'];

    /** Informations issues de la base de données, obligatoires dans chaque message. */
    public const VARIABLES = [
        self::DEFINITIVE => [...self::VARIABLES_COMMUNES, '{{lien_plateforme}}', '{{Email_etudiant}}', self::PASSWORD],
        self::ATTENTE => self::VARIABLES_COMMUNES,
        self::NON_RETENUE => self::VARIABLES_COMMUNES,
    ];

    private const DEFAULTS = [
        self::DEFINITIVE => "Bonjour cher étudiant {{Nom_etudiant}},\nVous êtes retenu(e) pour l’attribution de la campagne {{libelle_campagne}}-{{annee_campagne}}.\n\nVoici les informations pour accéder à votre espace :\nLien : {{lien_plateforme}}\nLogin : {{Email_etudiant}}\nMot de passe : {{password_étudiant}}",
        self::ATTENTE => "Bonjour cher étudiant {{Nom_etudiant}},\nVous n’êtes pas retenu(e) pour l’attribution de la campagne {{libelle_campagne}}-{{annee_campagne}}. Cependant, vous êtes sur la liste d’attente : vous serez contacté(e) en cas de disponibilité.\n\nVous pouvez faire une réclamation au sein du CROU ou sur la plateforme.",
        self::NON_RETENUE => "Bonjour cher étudiant {{Nom_etudiant}},\nVous n’êtes pas retenu(e) pour l’attribution de la campagne {{libelle_campagne}}-{{annee_campagne}}.\n\nVous pouvez faire une réclamation au sein du CROU ou sur la plateforme.",
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

    /** @return array<int, string> informations entre accolades présentes dans un texte, une seule fois chacune */
    public static function placeholders(string $text): array
    {
        preg_match_all('/\{\{[^{}]*\}\}/u', $text, $matches);

        return array_values(array_unique($matches[0]));
    }

    /** Remplace les informations entre accolades par leur valeur (échappée pour un message HTML). */
    public static function fill(string $text, array $values, bool $html = false): string
    {
        return strtr($text, $html ? array_map(fn (string $value) => e($value), $values) : $values);
    }

    /** Lien de la plateforme : « Site web » de Configurations système › Profil, à défaut la connexion étudiant. */
    public static function platformUrl(?ConfigApp $config): string
    {
        return trim((string) $config?->website) ?: route('etudiant.login');
    }

    /**
     * Messages dont l'envoi est activé (par défaut : tous).
     *
     * @return array<string, bool>
     */
    public static function enabled(array $saved): array
    {
        return array_map(
            fn ($enabled) => (bool) $enabled,
            array_replace(array_fill_keys(array_keys(self::LABELS), true), array_intersect_key($saved['enabled'] ?? [], self::LABELS)),
        );
    }
}
