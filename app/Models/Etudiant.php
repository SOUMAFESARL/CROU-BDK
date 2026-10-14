<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Etudiant extends Authenticatable
{

    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    protected $table = 'etudiants';

    /**
     * La clé primaire de la table.
     *
     * @var string
     */
        protected $primaryKey = 'id_etudiant';

    /**
     * Indique si la clé primaire est auto-incrémentée.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Le type de la clé primaire.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
   protected $guarded = [];
   protected $hidden = ['password', 'remember_token'];

    /**
     * L'e-mail de connexion de l'étudiant est « email1 » (utilisé par le broker de réinitialisation du mot de passe).
     */
    public function getEmailForPasswordReset(): string
    {
        return (string) $this->email1;
    }

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_naissance' => 'date',
        'date_delivrance' => 'date',
        'date_expiration' => 'date',
        'date_validation' => 'date',
        'date_inscription' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'allergies' => 'boolean',
        'maladies_chroniques' => 'boolean',
        'autiste' => 'boolean',
        'pmr_confirme' => 'boolean',
        'pmr_confirme_at' => 'datetime',
        'boursier' => 'boolean',
        'is_validation' => 'boolean',
        'is_rejet' => 'boolean',
        'genre_id' => 'integer',
        'countrie_id' => 'integer',
        'regions_id' => 'integer',
        'ville_id' => 'integer',
    ];

    /**
     * Les attributs pour lesquels null est autorisé.
     *
     * @var array<int, string>
     */
    protected $nullable = [
        'email2',
        'telephone2',
        'departement_code',
        'numero_piece',
        'date_delivrance',
        'date_expiration',
        'type_bourse',
        'photo_identite',
        'piece_identite_recto',
        'piece_identite_verso',
        'certificat_scolarite',
        'users_validation',
        'users_rejet',
        'motif_rejet',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Les valeurs par défaut des attributs.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'nationalite' => 'IVOIRIENNE',
        'statut_inscription' => 'en_attente',
        'is_validation' => false,
        'is_rejet' => false,
        'allergies' => false,
        'maladies_chroniques' => false,
        'autiste' => false,
        'boursier' => false,
    ];

    /**
     * Boot du modèle pour générer l'ID automatiquement
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($etudiant) {
            if (empty($etudiant->{$etudiant->getKeyName()})) {
                $etudiant->{$etudiant->getKeyName()} = (string) Str::uuid();
            }

            if (empty($etudiant->created_by)) {
                $etudiant->created_by = getInfoAdmin()->users->id_user ?? null;
            }

            if (empty($etudiant->statut_inscription)) {
                $etudiant->statut_inscription = 'demande';
            }
        });

        static::creating(function ($etudiant) {


            if (empty($etudiant->updated_by)) {
                $etudiant->updated_by = getInfoAdmin()->users->id_user ?? null;
            }

        });
    }

    /**
     * Génère un ID unique pour l'étudiant
     *
     * @return string
     */
    protected static function generateEtudiantId(): string
    {
        $prefix = 'ETU';
        $year = date('Y');
        $lastEtudiant = static::where('id_etudiant', 'LIKE', "{$prefix}{$year}%")
            ->orderBy('id_etudiant', 'desc')
            ->first();

        if ($lastEtudiant) {
            $lastNumber = intval(substr($lastEtudiant->id_etudiant, -6));
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return "{$prefix}{$year}{$newNumber}";
    }

    public function getStatutDisciplinaireAttribute(): string
    {
        return app(\App\Http\Services\DisciplinaryImpactService::class)->statut($this->getKey());
    }

    // ==================== RELATIONS ====================

    public function maintenance()
    {
        return $this->hasMany(OrdreTravail::class, 'etudiant_id', 'id_etudiant');
    }

    /**
     * Relation avec la demande
     */
    public function demandes()
    {
        return $this->hasMany(DemandeEtudiant::class, 'etudiant_id', 'id_etudiant');
    }
    public function derniereDemande()
    {
        return $this->hasOne(DemandeEtudiant::class, 'etudiant_id', 'id_etudiant')
            ->ofMany('created_at', 'max');
    }
    public function typePiece()
    {
        return $this->hasOne(TypePiece::class, 'id', 'type_piece_id');
    }
    public function groupeSanguin()
    {
        return $this->hasOne(groupeSanguin::class, 'id', 'groupe_sanguin_id');
    }

//    public function edl_entree()
//    {
//        return $this->hasMany(EtatDesLieux::class,'id_etudiant', 'etudiant_id')->where('type_etat','entree');
//    }
//    public function edl_sortie()
//    {
//        return $this->hasMany(EtatDesLieux::class, 'id_etudiant', 'etudiant_id')->where('type_etat','sortie');
//    }

    public function edl_entree()
    {
        return $this->hasOne(EtatDesLieux::class, 'id_etudiant', 'id_etudiant')
            ->where('type_etat', 'entree')
           ->with(['pieces.elements', 'pieces.images'])
            ->latestOfMany('date_etat');
    }

// Relation vers l'EDL de sortie le plus récent
    public function edl_sortie()
    {
        return $this->hasOne(EtatDesLieux::class, 'id_etudiant', 'id_etudiant')
            ->where('type_etat', 'sortie')
            ->with(['pieces.elements', 'pieces.images']);
           // ->latestOfMany('date_etat');
    }
    public function civilite()
    {
        return $this->hasOne(Genre::class, 'id', 'genre_id');
    }
    public function documents()
    {
        return $this->hasMany(DocumentEtudiant::class, 'etudiant_id', 'id_etudiant');
    }



    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([

            'region',
            'ville',
            'demandes',
            'documents',
            'derniereDemande',
            'civilite',
            'typePiece',
            'groupeSanguin',
            'demandes.niveauEtudiantPrecedent',
            'derniereDemande.niveauEtudiantPrecedent',
            'derniereDemande.niveauEtudiantActuel',
            'demandes.niveauEtudiantActuel',
            'assignationsChambre',
            'assignationsChambre.chambre',
            'assignationsChambre.chambre.typeChambre',
            'assignationsChambre.chambre.niveau_aile',
            'assignationsChambre.chambre.batiment',
            'assignationsChambre.chambre.campus',
            'assignationsChambre.chambre.cite',
            'edl_entree',
            'edl_sortie',
            'maintenance',
            'assignationActive',
             'assignationActive.chambre.typeChambre',
            'assignationActive.chambre.niveau_aile',
            'assignationActive.chambre.batiment',
            'assignationActive.chambre.batiment.responsable',
            'assignationActive.chambre.campus',
            'assignationActive.chambre.cite',
            'assignationActive.chambre.cite.responsable',

        ]);
    }
    /**
     * Relation avec le genre
     */
    public function genre()
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }

    /**
     * Relation avec le pays
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'countrie_id');
    }

    /**
     * Relation avec la région
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'regions_id');
    }

    /**
     * Relation avec la ville
     */
    public function ville()
    {
        return $this->belongsTo(Ville::class, 'ville_id');
    }

    /**
     * Relation avec le niveau d'étude précédent
     */
    public function niveauPrecedent()
    {
        return $this->belongsTo(NiveauEtude::class, 'niveau_etude_precedent_code', 'code');
    }

    /**
     * Relation avec le niveau d'étude actuel
     */
    public function niveauActuel()
    {
        return $this->belongsTo(NiveauEtude::class, 'niveau_etude_actuel_code', 'code');
    }

    /**
     * Relation avec la filière
     */
    public function filiere()
    {
        return $this->belongsTo(Filiere::class, 'filiere_code', 'code');
    }

    /**
     * Relation avec le département
     */
    public function departement()
    {
        return $this->belongsTo(Departement::class, 'departement_code', 'code');
    }

    /**
     * Relation avec l'utilisateur qui a créé l'enregistrement
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'name');
    }

    /**
     * Relation avec l'utilisateur qui a modifié l'enregistrement
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'name');
    }

    /**
     * Relation avec l'utilisateur qui a supprimé l'enregistrement
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'name');
    }

    /**
     * Relation avec l'utilisateur qui a validé l'inscription
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'users_validation', 'name');
    }

    /**
     * Relation avec l'utilisateur qui a rejeté l'inscription
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'users_rejet', 'name');
    }

    /**
     * Relation avec les contrats de location
     */
    public function contrats()
    {
        return $this->hasMany(AssignationChambre::class, 'etudiant_id', 'id_etudiant');
    }

    /**
     * Relation avec les paiements (via contrats)
     */
    public function paiements()
    {
        return $this->hasManyThrough(Paiement::class, AssignationChambre::class, 'etudiant_id', 'id_assignation ', 'id_etudiant');
    }

    /**
     * Relation avec les documents
     */
   

    /**
     * Relation avec les réclamations
     */
    public function reclamations()
    {
        return $this->hasMany(Reclamation::class, 'etudiant_id', 'id_etudiant');
    }

    /**
     * Relation avec les contacts d'urgence
     */
    public function contactsUrgence()
    {
        return $this->hasMany(ContactUrgence::class, 'etudiant_id', 'id_etudiant');
    }

    /**
     * Relation avec l'historique d'occupation
     */
    public function historiqueOccupation()
    {
        return $this->hasMany(HistoriqueOccupation::class, 'etudiant_id', 'id_etudiant');
    }

    /**
     * Relation avec la chambre actuelle (via contrat actif)
     */
      public function chambreActuelle(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Chambre::class,              // Modèle final (Chambre)
            AssignationChambre::class,   // Modèle intermédiaire (AssignationChambre)
            'etudiant_id',               // Clé étrangère sur AssignationChambre (vers Etudiant)
            'id_chambre',                // Clé primaire de Chambre
            'id_etudiant',               // Clé primaire de Etudiant
            'chambre_id'                 // Clé étrangère sur AssignationChambre (vers Chambre)
        )->where('assignation_chambres.statut', 'confirmee');
    }
      /**
     * Alternative avec HasOne si la structure est différente
     */
    public function assignationActive()
    {
        return $this->hasOne(AssignationChambre::class, 'etudiant_id', 'id_etudiant')
            ->where('statut', 'confirmee');
    }

    /**
     * Récupère la chambre via l'assignation active (méthode alternative)
     */
    public function getChambreActuelleAttribute()
    {
        return $this->assignationActive?->chambre;
    }

    // ==================== SCOPES ====================

    /**
     * Scope pour les étudiants en attente de validation
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut_inscription', 'en_attente');
    }

    /**
     * Scope pour les étudiants validés
     */
    public function scopeValide($query)
    {
        return $query->where('statut_inscription', 'validee')
            ->where('is_validation', true);
    }

    /**
     * Scope pour les étudiants rejetés
     */
    public function scopeRejete($query)
    {
        return $query->where('statut_inscription', 'rejetee')
            ->where('is_rejet', true);
    }

    /**
     * Scope pour les étudiants archivés
     */
    public function scopeArchive($query)
    {
        return $query->where('statut_inscription', 'archivee');
    }

    /**
     * Scope pour les étudiants boursiers
     */
    public function scopeBoursier($query)
    {
        return $query->where('boursier', true);
    }

    /**
     * Scope pour les étudiants avec besoins spécifiques
     */
    public function scopeBesoinsSpecifiques($query)
    {
        return $query->where(function($q) {
            $q->where('allergies', true)
                ->orWhere('maladies_chroniques', true)
                ->orWhere('autiste', true);
        });
    }

    /**
     * Scope pour les étudiants d'une filière donnée
     */
    public function scopeFiliere($query, $filiereCode)
    {
        return $query->where('filiere_code', $filiereCode);
    }

    /**
     * Scope pour les étudiants d'un niveau donné
     */
    public function scopeNiveau($query, $niveauCode)
    {
        return $query->where('niveau_etude_actuel_code', $niveauCode);
    }

    /**
     * Scope pour les étudiants d'une année académique
     */
    public function scopeAnneeAcademique($query, $annee)
    {
        $anneeActive = $this->annee_academique_actuelle;
        return $query->where('annee_academique_actuel', $anneeActive);
    }

    // ==================== ATTRIBUTS ====================

    /**
     * Obtenir le nom complet (déjà présent comme 'name')
     */
    public function getNomCompletAttribute(): string
    {
        return $this->name;
    }

    /**
     * Obtenir l'âge de l'étudiant
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->date_naissance) {
            return $this->date_naissance->age;
        }
        return null;
    }

    /**
     * Obtenir le contact principal
     */
    public function getContactPrincipalAttribute(): ?string
    {
        return $this->telephone1 ?? $this->telephone2;
    }

    /**
     * Obtenir l'email principal
     */
    public function getEmailPrincipalAttribute(): ?string
    {
        return $this->email1 ?? $this->email2;
    }

    /**
     * Vérifier si l'étudiant a des besoins médicaux
     */
    public function getABesoinMedicalAttribute(): bool
    {
        return $this->allergies || $this->maladies_chroniques || $this->autiste;
    }

    /**
     * Obtenir le statut d'inscription formaté
     */
    public function getStatutInscriptionLibelleAttribute(): string
    {
        $statuts = [
            'en_attente' => 'En attente',
            'validee' => 'Validée',
            'rejetee' => 'Rejetée',
            'archivee' => 'Archivée'
        ];
        return $statuts[$this->statut_inscription] ?? $this->statut_inscription;
    }

    /**
     * Obtenir la pièce d'identité formatée
     */
    public function getPieceIdentiteCompletAttribute(): string
    {
        return $this->type_piece_identite . ' N° ' . ($this->numero_piece ?? 'N/A');
    }

    // ==================== MÉTHODES ====================

    /**
     * Valider l'inscription d'un étudiant
     */
    public function validerInscription(string $validateur): bool
    {
        $this->statut_inscription = 'validee';
        $this->is_validation = true;
        $this->users_validation = $validateur;
        $this->date_validation = now();
        return $this->save();
    }

    /**
     * Rejeter l'inscription d'un étudiant
     */
    public function rejeterInscription(string $validateur, string $motif): bool
    {
        $this->statut_inscription = 'rejetee';
        $this->is_rejet = true;
        $this->users_rejet = $validateur;
        $this->motif_rejet = $motif;
        return $this->save();
    }

    /**
     * Archiver l'étudiant
     */
    public function archiver(): bool
    {
        $this->statut_inscription = 'archivee';
        return $this->save();
    }

    /**
     * Vérifier si l'étudiant a un contrat actif
     */
    public function aContratActif(): bool
    {
        return $this->contrats()->where('statut', 'actif')->exists();
    }

    /**
     * Obtenir le contrat actif
     */
    public function getContratActif()
    {
        return $this->contrats()->where('statut', 'actif')->first();
    }

    /**
     * Relation avec les assignations de chambres
     */
    public function assignationsChambre()
    {
        return $this->hasMany(AssignationChambre::class, 'etudiant_id', 'id_etudiant');
    }


    public function campagneActive()
    {
        return $this->belongsTo(Campagne::class, 'id_campagne', 'id_campagne')
            ->current();
    }
    public function getAnneeAcademiqueActuelleAttribute()
    {
        if ($this->campagneActive) {
            return $this->campagneActive->annee_campagne;
        }

        // La demande de l'étudiant prévaut, même si les admissions sont closes.
        $campagne = $this->derniereDemande?->campagne
            ?? Campagne::current()->orderByDesc('created_at')->first()
            ?? Campagne::orderByDesc('annee_debut')->orderByDesc('created_at')->first();

        return $campagne?->annee_campagne;
    }
    /**
     * Obtenir la demande actuelle de l'étudiant
     */
    public function demandeActuelle()
    {
        $anneeActive = $this->annee_academique_actuelle;

        return $this->demandes()
            ->where('annee_academique_actuel', $anneeActive)
            ->latest()
            ->first();
    }

    /**
     * Obtenir l'année d'étude (L1, L2, L3, M1, M2, etc.)
     */
    public function getAnneeEtude(): ?string
    {
        $demande = $this->demandeActuelle();

        if (!$demande) {
            return null;
        }

        // Extraire l'année du code (ex: "L1", "M1", "D3")
        return $demande->niveau_etude_actuel_code;
    }

    /**
     * Obtenir le niveau numérique (1, 2, 3, 4, 5, 6, 7, 8, 9)
     */
    public function getNiveauNumeric(): int
    {
        $code = $this->getAnneeEtude();

        if (!$code) {
            return 1;
        }

        // Extraire le chiffre du code (L1=1, L2=2, M1=4, M2=5, D1=7, etc.)
        if (preg_match('/\d+/', $code, $matches)) {
            return (int)$matches[0];
        }

        return 1;
    }

    /**
     * Déterminer les types de chambres éligibles
     */
    public function getTypesChambresEligibles(): array
    {

        $niveau = $this->getNiveauNumeric();

        // Années 1-3: double ou triple
        if ($niveau <= 3) {
            return ['double', 'triple'];
        }

        // Années 4+: individuelle prioritaire
        return ['individuelle', 'double', 'triple'];
    }
    /**
     * Obtenir la priorité des types de chambres
     */
    public function getPrioriteChambres(): array
    {
        $niveau = $this->getNiveauNumeric();

        if ($niveau <= 3) {
            return ['double', 'triple'];
        }

        // Années 4+: individuelle prioritaire
        return ['individuelle', 'double', 'triple'];
    }
    /**
     * Vérifier si l'étudiant est éligible pour une chambre
     */
    public function estEligiblePourChambre(Chambre $chambre): bool
    {
       // dd($chambre);
        $types = $this->getTypesChambresEligibles();

        return in_array($chambre->capacite_max, $types);
    }

    /**
     * Vérifier si l'étudiant a déjà une chambre assignée dans une campagne
     */
    public function aUneChambre($campagneId): bool
    {
        return $this->assignationsChambre()
            ->where('campagne_id', $campagneId)
            ->where('statut', '!=', 'annulee')
            ->exists();
    }
    /**
     * Obtenir sa chambre actuelle
     */
    public function getChambreActuelle($campagneId)
    {
        return $this->assignationsChambre()
            ->where('campagne_id', $campagneId)
            ->where('statut', 'confirmee')
            ->latest('created_at')
            ->first();
    }
    /**
     * Scope: Étudiants sans chambre pour une campagne
     */
    public function scopeSansChambre($query, $campagneId)
    {
        return $query->whereDoesntHave('assignationsChambre', function ($q) use ($campagneId) {
            $q->where('campagne_id', $campagneId)
              //  ->where('statut_inscription', '!=', 'annuler')
                ->mobilisantPlace();
        });
    }

    /**
     * Scope: Par année d'étude
     */
    public function scopeParAnneeEtude($query, $anneeCode)
    {
        return $query->whereHas('demandes', function ($q) use ($anneeCode) {
            $q->where('niveau_etude_actuel_code', $anneeCode)
                ->latest('created_at');
        });
    }

}
