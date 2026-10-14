<?php
namespace App\Http\Services;
use App\Models\HousingEntrySetting;
class ContractTerms
{
    public const DEFAULTS = [
        'duree' => '12 mois renouvelable',
        'charges_comprises' => 'Eau, Électricité, Internet, Entretien',
        'depot_garantie' => '50 000 FCFA',
        'jour_paiement' => 'Le 5 de chaque mois',
        'mode_paiement' => 'Virement bancaire / Mobile Money',
        'delai_retard' => '15',
        'delai_restituion' => '30',
        'heure_silence_debut' => '22h',
        'heure_silence_fin' => '7h',
        'plage_visites' => '8h à 22h',
        'nb_nuitees' => '3',
        'modalites_equipements' => 'Usage partagé, nettoyage après usage',
        'conditions_internet' => 'Wi-Fi gratuit disponible 24h/24',
        'fumeur_autorise' => 'NON, sauf zones extérieures autorisées',
        'animaux_autorises' => 'NON AUTORISÉS',
        'conditions_animaux' => 'Aucune dérogation possible',
        'dispositions_diverses' => 'Parking à disposition sur demande / Buanderie au sous-sol / Poubelles à sortir les mardis et vendredis',
        'mode_notification' => 'Lettre recommandée avec accusé de réception',
        'delai_mise_demeure' => '15 jours',
        'juridiction' => 'le tribunal de première instance de ',
        'dispositions_complementaires' => 'Le locataire s\'engage à respecter les règles de vie collective et à entretenir les espaces communs.',
        'lieu_signature' => 'Abidjan',
        'bailleur_representant' => 'Le Directeur Général',
    ];
    public function values(): array
    {
        return array_replace(self::DEFAULTS, array_intersect_key(HousingEntrySetting::options()['contract'] ?? [], self::DEFAULTS));
    }
}
