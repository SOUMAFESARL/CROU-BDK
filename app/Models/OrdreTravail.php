<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrdreTravail extends Model
{
    protected $table = 'ordres_travail';
    protected $primaryKey = 'id_ordres_travail';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_ordres_travail',
        'numero_ot',
        'cite_id',
        'batiment_id',
        'chambre_id',
        'etudiant_id',
        'equipement_id',
        'type_intervention',
        'categorie',
        'priorite',
        'titre',
        'description',
        'cause',
        'solution_apportee',
        'date_disponibilite',
        'periode_disponibilite',
        'date_debut_intervention',
        'date_fin_intervention',
        'date_echeance',
        'date_resolution',
        'instructions',
        'statut',
        'assigne_a',
        'assigne_par',
        'cout_estime',
        'cout_reel',
        'temps_passe',
        'photos',
        'pieces_jointes',
        'notification_envoyee',
        'taux_satisfaction',
        'commentaire_satisfaction',
        'date_mise_a_jour',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'cout_estime' => 'decimal:2',
        'cout_reel' => 'decimal:2',
        'temps_passe' => 'integer',
        'taux_satisfaction' => 'integer',
        'date_disponibilite' => 'datetime',
        'date_debut_intervention' => 'datetime',
        'date_fin_intervention' => 'datetime',
        'date_echeance' => 'date',
        'date_resolution' => 'datetime',
        'date_mise_a_jour' => 'datetime',
        'photos' => 'array',
        'pieces_jointes' => 'array',
        'notification_envoyee' => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // RELATIONS
    // ============================================

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id_ordres_travail) {
                $model->id_ordres_travail = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = getInfoAdmin()->users->id_user ?? null;

            }
             $model->numero_ot  = self::genererNumeroOT();

            $model->created_at = now();
        });
        static::updating(function ($model) {


            if (empty($model->updated_by)) {
                $model->updated_by = getInfoAdmin()->users->id_user ?? null;
            }
            $model->updated_at = now();

        });
        static::deleting(function ($model) {


            if (empty($model->deleted_by)) {
                $model->deleted_by = getInfoAdmin()->users->id_user ?? null;
            }
            $model->deleted_at = now();
        });
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Cite::class, 'cite_id', 'id_cite');
    }

    public function batiment(): BelongsTo
    {
        return $this->belongsTo(Batiment::class, 'batiment_id', 'id_batiment');
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class, 'chambre_id', 'id_chambre');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'etudiant_id', 'id_etudiant');
    }
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'created_by', 'id_etudiant');
    }
    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class, 'equipement_id', 'equipement_id');
    }

    public function assigneA(): BelongsTo
    {
        return $this->belongsTo(Maintenancier::class, 'assigne_a', 'id_maintenancier');
    }

    public function assignePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigne_par', 'id_user');
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class, 'id_ordres_travail', 'id_ordres_travail');
    }

    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([

            'chambre',
            'interventions',
            'assignePar',
            'assigneA',
            'equipement',
            'resident',
            'batiment',
            'residence',
            'createdBy'

        ]);
    }
    public function piecesDetachees(): BelongsToMany
    {
        return $this->belongsToMany(PieceDetachee::class, 'ot_pieces_utilisees', 'ot_id', 'piece_id')
            ->withPivot('quantite', 'prix_unitaire', 'date_utilisation')
            ->withTimestamps();

    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeEnCours($query)
    {
        return $query->whereIn('statut', ['en_attente', 'en_cours', 'en_attente_pieces']);
    }

    public function scopeTermine($query)
    {
        return $query->where('statut', 'termine');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priorite', 'critique');
    }

    public function scopeParCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeParResidence($query, $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    public function scopeParStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeParPriorite($query, $priorite)
    {
        return $query->where('priorite', $priorite);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getStatutLabelAttribute(): string
    {
        $labels = [
            'en_attente' => 'En attente',
            'en_cours' => 'En cours',
            'en_attente_pieces' => 'En attente pièces',
            'termine' => 'Terminé',
            'annule' => 'Annulé',
            'reporte' => 'Reporté'
        ];
        return $labels[$this->statut] ?? $this->statut;
    }

    public function getStatutBadgeAttribute(): string
    {
        $badges = [
            'en_attente' => 'danger',
            'en_cours' => 'warning',
            'en_attente_pieces' => 'info',
            'termine' => 'success',
            'annule' => 'secondary',
            'reporte' => 'orange'
        ];
        return $badges[$this->statut] ?? 'secondary';
    }

    public function getPrioriteLabelAttribute(): string
    {
        $labels = [
            'critique' => 'Critique',
            'elevee' => 'Élevée',
            'moyenne' => 'Moyenne',
            'faible' => 'Faible'
        ];
        return $labels[$this->priorite] ?? $this->priorite;
    }

    public function getCategorieLabelAttribute(): string
    {
        $labels = [
            'electricite' => '⚡ Électricité',
            'eau' => '💧 Eau',
            'plomberie' => '💧Plomberie',
            'sanitaire' => '🚽 Sanitaire',
            'mobilier' => '🪑 Mobilier',
            'securite' => '🔒 Sécurité',

            'climatisation' => '❄️ Climatisation',
            'batiment' => '🏗️ Bâtiment',
            'autre' => '🔧 Autre'


        ];
        return $labels[$this->categorie] ?? $this->categorie;
    }

    public function getTypeInterventionLabelAttribute(): string
    {
        $labels = [
            'corrective' => 'Corrective',
            'preventive' => 'Préventive',
            'urgente' => 'Urgente'
        ];
        return $labels[$this->type_intervention] ?? $this->type_intervention;
    }

    public function getDureeAttribute(): ?string
    {
        if (!$this->date_debut_intervention || !$this->date_fin_intervention) {
            return null;
        }
        $diff = $this->date_fin_intervention->diff($this->date_debut_intervention);
        return $diff->format('%Hh %Im');
    }

    public function getDelaiResolutionAttribute(): ?int
    {
        if (!$this->date_creation || !$this->date_resolution) {
            return null;
        }
        return $this->date_creation->diffInDays($this->date_resolution);
    }

    // ============================================
    // MÉTHODES
    // ============================================

    public static function genererNumeroOT(): string
    {
        $prefix = 'OT';
        $date = now()->format('Y');
        $last = self::where('numero_ot', 'LIKE', $prefix . $date . '%')
            ->orderBy('numero_ot', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->numero_ot, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . '-' . $newNumber;
    }

    public function assigner($userId): void
    {
        $this->assigne_a = $userId;
        $this->statut = 'en_cours';
        $this->date_debut_intervention = now();
        $this->save();
    }

    public function terminer($solution = null): void
    {
        $this->getConnection()->transaction(function () use ($solution) {
            if ($this->statut !== 'termine') {
                $this->date_resolution = now();
                $this->date_fin_intervention = now();
            }
            $this->statut = 'termine';
            if ($solution) {
                $this->solution_apportee = $solution;
            }
            $this->save();
            $this->actualiserChambreApresMaintenance();
        });
    }

    public function actualiserChambreApresMaintenance(): void
    {
        if ($this->statut !== 'termine' || !$this->chambre_id) {
            return;
        }

        $chambre = Chambre::whereKey($this->chambre_id)->lockForUpdate()->first();
        if (!$chambre || $chambre->etat !== 'maintenance') {
            return;
        }

        $autreOrdreOuvert = static::where('chambre_id', $this->chambre_id)
            ->where('id_ordres_travail', '!=', $this->getKey())
            ->whereIn('statut', ['en_attente', 'en_cours', 'en_attente_pieces', 'reporte'])
            ->exists();

        if ($autreOrdreOuvert) {
            return;
        }

        // Le statut administratif (actif/desactive) reste independant de l'etat.
        $chambre->etat = $chambre->estPleine() ? 'occupee' : 'disponible';
        $chambre->save();
    }

    public function ajouterIntervention($data): Intervention
    {
        return $this->interventions()->create($data);
    }

    public const CATEGORIES = [
        'electricite', 'eau', 'sanitaire', 'mobilier','plomberie' ,
        'securite',  'climatisation', 'batiment', 'autre'
    ];

    public const STATUTS = [
        'en_attente', 'en_cours', 'en_attente_pieces', 'termine', 'annule', 'reporte'
    ];

    public const PRIORITES = [
        'critique', 'elevee', 'moyenne', 'faible'
    ];

    public const TYPE_INTERVENTIONS = [
        'corrective', 'preventive', 'urgente'
    ];

    public const CATEGORIES_INTERVENTIONS = [

            'electricite' => '⚡ Électricité',
            'eau' => '💧 Eau',
            'plomberie' => '💧Plomberie',
            'sanitaire' => '🚽 Sanitaire',
            'mobilier' => '🪑 Mobilier',
            'securite' => '🔒 Sécurité',

            'climatisation' => '❄️ Climatisation',
            'batiment' => '🏗️ Bâtiment',
            'autre' => '🔧 Autre'



    ];

    // Ajouter une méthode pour obtenir les catégories avec libellés
    public static function getCategorieLabels(): array
    {
        return [
            'electricite' => 'Électricité',
            'eau' => 'Eau',
            'sanitaire' => 'Sanitaire',
            'mobilier' => 'Mobilier',
            'securite' => 'Sécurité',
            'chauffage' => 'Chauffage',
            'climatisation' => 'Climatisation',
            'batiment' => 'Bâtiment',
            'autre' => 'Autre'
        ];
    }

    public static function getCategorieLabel(string $categorie): string
    {
        return self::getCategorieLabels()[$categorie] ?? $categorie;
    }


}