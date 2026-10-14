<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    public const TYPES = [
        'reglement_interieur' => 'Règlement intérieur',
        'guide_resident' => 'Guide du résident',
        'procedures_securite' => 'Procédures de sécurité',
        'calendrier_visites' => 'Calendrier des visites ou interventions',
    ];

    // Suggestions de circuit interne, ajustables pour chaque document.
    public const SUGGESTED_SIGNATORIES = [
        'reglement_interieur' => ['sous_dal', 'chef_logement', 'directeur_cite'],
        'guide_resident' => ['chef_logement', 'directeur_cite'],
        'procedures_securite' => ['directeur_cite', 'concierge_principal'],
        'calendrier_visites' => ['directeur_cite', 'concierge_principal'],
    ];

    public const OUTLINES = [
        'reglement_interieur' => "Objet et champ d’application\n\nConditions d’occupation\n\nVie collective et respect du voisinage\n\nEntretien des locaux\n\nVisites et accès\n\nSignalements et mesures applicables",
        'guide_resident' => "Bienvenue à {{etablissement}}\n\nInstallation dans la résidence\n\nServices et contacts utiles\n\nPaiement du loyer\n\nDemandes d’intervention\n\nPréparer son départ",
        'procedures_securite' => "Contacts d’urgence\n\nConsignes d’alerte\n\nÉvacuation et points de rassemblement\n\nAccès aux locaux\n\nSignalement des incidents\n\nResponsabilités des intervenants",
        'calendrier_visites' => "Objet des visites ou interventions\n\nInformation des résidents\n\nModalités d’accès et de présence\n\nContact pour demander un changement de créneau : {{contact}}",
    ];

    protected $guarded = ['id'];
    protected $casts = ['effective_date' => 'date', 'schedule' => 'array', 'signatory_roles' => 'array', 'lock_version' => 'integer'];

    public function versions() { return $this->hasMany(LegalDocumentVersion::class); }
}
