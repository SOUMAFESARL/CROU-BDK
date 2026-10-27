<?php
// app/Models/demande.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Nette\Utils\Random;

class DemandeEtudiant extends Model
{
  //  use SoftDeletes; // Si vous utilisez le soft delete (champ deleted_at)

    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    protected $table = 'demande';

    /**
     * La clé primaire de la table.
     *
     * @var string
     */
    protected $primaryKey = 'id_demande';

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
     * Indique si le modèle doit utiliser les timestamps.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $guarded = [];
    public bool $workflowDraft = false;

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'date_demande' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Les valeurs par défaut pour les attributs.
     *
     * @var array
     */
    protected $attributes = [
        'statut_demande' => 'en_attente'
    ];
    protected $dates = ['date_demande', 'created_at', 'updated_at'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($demande) {
            $demande->etat_dossier = $demande->workflowDraft ? 'brouillon' : 'soumis';
            $demande->assignation_id = null;
        });
        static::created(function ($demande) {
            \Illuminate\Support\Facades\DB::table('dossier_transitions')->insert([
                'demande_id'=>$demande->getKey(),'ancien_etat'=>null,'nouvel_etat'=>$demande->etat_dossier,
                'acteur_type'=>auth('admin')->check()?'admin':'etudiant',
                'acteur_id'=>auth('admin')->id() ?? auth('etudiant')->id() ?? $demande->etudiant_id,
                'motif'=>'Dossier déposé.','created_at'=>now(),
            ]);
            // Dossier déposé par l'étudiant : décision automatique selon le barème (pas pour une saisie admin).
            if ($demande->etat_dossier === 'soumis' && !auth('admin')->check()) {
                \Illuminate\Support\Facades\DB::afterCommit(fn () => app(\App\Http\Services\DecisionAutomatiqueDossier::class)->decider($demande->getKey()));
            }
        });
        static::updating(function ($demande) {
            if ($demande->isDirty(['etat_dossier','statut_demande','assignation_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'etat_dossier'=>'Utilisez les transitions contrôlées du dossier pour modifier son état.',
                ]);
            }
        });

        static::creating(function ($demande) {
            if (empty($demande->{$demande->getKeyName()})) {
                $demande->{$demande->getKeyName()} = (string) Str::uuid();
            }

            if (empty($demande->created_by)) {
                $demande->created_by = getInfoAdmin()->users->code ?? null;
            }
            if (empty($demande->numero_demande)) {
                $demande->numero_demande = self::generate() ;
            }
        });
    }


    /**
     * Les statuts possibles pour une demande
     */
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_EN_COURS = 'en_cours';
    const STATUT_VALIDEE = 'validee';
    const STATUT_REJETEE = 'rejetee';
    const STATUT_ANNULEE = 'annulee';

    /** Crédits d'une année universitaire : `credit_valider` est un nombre de crédits (0 à 60), pas un pourcentage. */
    const CREDITS_PAR_AN = 60;

    /** Part des crédits de l'année validés, en %, comparée au seuil `pourcentage_validate` de la campagne. */
    public static function pourcentageCredits(int|float|string $credits): float
    {
        return round((float) $credits / self::CREDITS_PAR_AN * 100, 2);
    }

    /**
     * Récupère tous les statuts possibles
     *
     * @return array
     */
    public static function getStatuts(): array
    {
        return [
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_EN_COURS => 'En cours',
            self::STATUT_VALIDEE => 'Validée',
            self::STATUT_REJETEE => 'Rejetée',
            self::STATUT_ANNULEE => 'Annulée'
        ];
    }

    /**
     * Récupère le libellé du statut
     *
     * @return string
     */
    public function getStatutLibelleAttribute(): string
    {
        return self::getStatuts()[$this->statut_demande] ?? $this->statut_demande;
    }

    /**
     * Vérifie si la demande est en attente
     *
     * @return bool
     */
    public function isEnAttente(): bool
    {
        return $this->statut_demande === self::STATUT_EN_ATTENTE;
    }

    /**
     * Vérifie si la demande est validée
     *
     * @return bool
     */
    public function isValidee(): bool
    {
        return $this->statut_demande === self::STATUT_VALIDEE;
    }

    /**
     * Vérifie si la demande est rejetée
     *
     * @return bool
     */
    public function isRejetee(): bool
    {
        return $this->statut_demande === self::STATUT_REJETEE;
    }

    // ==================== RELATIONS ====================

    /**
     * Relation avec l'étudiant
     */
    public function transitions()
    {
        return $this->hasMany(\App\Models\DossierTransition::class, 'demande_id', 'id_demande')->orderBy('id');
    }
    public function proposition()
    {
        return $this->belongsTo(AssignationChambre::class, 'assignation_id', 'id_assignation');
    }
    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'etudiant_id', 'id_etudiant');
    }

    /**
     * Relation avec les documents de l'étudiant
     */
    public function documents()
    {
        return $this->hasMany(DocumentEtudiant::class, 'etudiant_id', 'etudiant_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé la demande
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    /**
     * Relation avec la campagne
     */
    public function campagne(): BelongsTo
    {
        return $this->belongsTo(Campagne::class, 'campagne_id', 'id_campagne');
    }
    /**
     * Relation avec l'utilisateur qui a mis à jour la demande
     */
    public function moderateur()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Relation avec l'utilisateur qui a supprimé la demande
     */
    public function supprimeur()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope pour filtrer par statut
     */
    public function scopeStatut($query, $statut)
    {
        return $query->where('statut_demande', $statut);
    }

    /**
     * Scope pour les demandes en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut_demande', self::STATUT_EN_ATTENTE);
    }

    /**
     * Scope pour les demandes validées
     */
    public function scopeValidees($query)
    {
        return $query->where('statut_demande', self::STATUT_VALIDEE);
    }

    /**
     * Scope pour les demandes rejetées
     */
    public function scopeRejetees($query)
    {
        return $query->where('statut_demande', self::STATUT_REJETEE);
    }

    /**
     * Scope pour filtrer par étudiant
     */
    public function scopeParEtudiant($query, $etudiantId)
    {
        return $query->where('etudiant_id', $etudiantId);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopeParPeriode($query, $periode)
    {
        return $query->where('periode', 'like', '%' . $periode . '%');
    }

    /**
     * Scope pour les demandes récentes
     */
    public function scopeRecentes($query, $jours = 30)
    {
        return $query->where('date_demande', '>=', now()->subDays($jours));
    }

    // ==================== ACCESSEURS ====================

    /**
     * Formate la date de demande
     */
    public function getDateDemandeFormatteeAttribute(): string
    {
        return $this->date_demande ? $this->date_demande->format('d/m/Y H:i') : '';
    }

    /**
     * Récupère le type d'hébergement formaté
     */
    public function getTypeHebergementFormatteAttribute(): string
    {
        $types = [
            'chambre_individuelle' => 'Chambre individuelle',
            'chambre_partagee' => 'Chambre partagée',
            'studio' => 'Studio',
            'appartement' => 'Appartement'
        ];

        return $types[$this->type_hebergement_souhaite] ?? $this->type_hebergement_souhaite;
    }

    // ==================== MUTATEURS ====================

    /**
     * Met en forme le commentaire avant l'enregistrement
     */
    public function setCommentairesAttribute($value)
    {
        $this->attributes['commentaires'] = $value ? trim($value) : null;
    }


    public static function generate()
    {

        $result=   Random::generate(5,'0-9');

        $val = 'CROU-'.date('Y').'-'.$result;


        if (!empty(DemandeEtudiant::where('numero_demande', $val)->first())) {
            // récursion sans $this
            return self::generate();
        }

        // dd($val);
        return $val;
    }

    /**
     * Relation avec l'utilisateur qui a supprimé la demande
     */
    public function niveauEtudiantPrecedent()
    {
        return $this->belongsTo(NiveauEtude::class, 'niveau_etude_precedent_code', 'id_niveau_etude');
    }

    public function niveauEtudiantActuel()
    {
        return $this->belongsTo(NiveauEtude::class, 'niveau_etude_actuel_code', 'id_niveau_etude');
    }
    /**
     * Vérifier si la demande est acceptée
     */
    public function estAcceptee(): bool
    {
        return $this->statut_demande === 'acceptee';
    }


    /**
     * Scope: Demandes acceptées
     */
    public function scopeAcceptees($query)
    {
        return $query->where('statut_demande', 'acceptee');
    }

    /**
     * Scope: Par campagne
     */
    public function scopeParCampagne($query, $campagneId)
    {
        return $query->where('campagne_id', $campagneId);
    }

    /**
     * Obtenir le label du statut
     */
    public function getLabelStatut(): string
    {
        $statuts = [
            'en_attente' => 'En Attente',
            'acceptee' => 'Acceptée',
            'rejetee' => 'Rejetée',
            'traitee' => 'Traitée',
        ];

        return $statuts[$this->statut_demande] ?? ucfirst($this->statut_demande);
    }
}
