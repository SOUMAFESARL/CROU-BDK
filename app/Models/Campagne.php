<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Campagne extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id_campagne';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $table = 'campagne';

    protected $casts = [
        'annee_campagne' => 'string',
        'annee_debut' => 'integer',
        'annee_fin' => 'integer',
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'date_echeance_demande' => 'date',
        'date_echeance_contrat' => 'date',
        'admission' => 'boolean',
        //'old_bachelier' => 'boolean',
        'readmission' => 'boolean',
        'pmr' => 'boolean',
        'internationnaux' => 'boolean',
        'moyenne_min' => 'float',
        'redoublement' => 'boolean',
        'distance_min' => 'float',

        'age_L1_max'  => 'integer',
        'age_L2_max'  => 'integer',
        'age_L3_max'  => 'integer',
        'age_M1_max'  => 'integer',
        'age_M2_max'  => 'integer',
        'age_D1_max'   => 'integer',
        'age_D2_max'  => 'integer',
        'age_D3_max'  => 'integer' ,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $fillable = [
        'id_campagne',
        'description',
        'annee_campagne',
        'annee_debut',
        'annee_fin',
        'periode_debut',
        'periode_fin',
        'date_echeance_demande',
        'date_echeance_contrat',
        'statut',
        'admission',
       // 'old_bachelier',
        'readmission',
        'pmr',
        'is_discipline',
        'is_participate',
        'pourcentage_validate',
        'internationnaux',
        'moyenne_min',
        'redoublement',
        'distance_min',
        'age_L1_max',
        'age_L1_max',
        'age_L2_max',
        'age_L3_max',
        'age_M1_max',
        'age_M2_max',
        'age_D1_max' ,
        'age_D2_max',
        'age_D3_max' ,
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    /**
     * Boot method pour initialiser les événements
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campagne) {
            // Génération de l'UUID si non défini
            if (empty($campagne->{$campagne->getKeyName()})) {
                $campagne->{$campagne->getKeyName()} = (string) Str::uuid();
            }

            // Définition du created_by si non défini
            if (empty($campagne->created_by)) {
                $user = getInfoAdmin()->users->id_user ?? null;
                $campagne->created_by = $user ?: auth()->user()->id_user ?? null;
            }
        });

        static::updating(function ($campagne) {
            // Mise à jour du updated_by
            $user = getInfoAdmin()->users->id_user ?? null;
            $campagne->updated_by = $user ?: auth()->user()->id_user ?? null;
        });
    }

    /**
     * Setter pour l'année académique
     */
    public function setAnneeCampagneAttribute($value)
    {
        $this->attributes['annee_campagne'] = $value;

        // Extraire les années du format "2025-2026"
        if (preg_match('/(\d{4})-(\d{4})/', $value, $matches)) {
            $this->attributes['annee_debut'] = (int) $matches[1];
            $this->attributes['annee_fin'] = (int) $matches[2];
        }
    }

    /**
     * Relations
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
    public function residencesCampagne()
    {
        return $this->belongsTo(ResidenceCampagne::class, 'id_campagne', 'campagne_id');
    }
    public function scopeByYear($query, $year)
    {
        return $query->where('annee_debut', $year)
            ->orWhere('annee_fin', $year);
    }

    public function scopeCurrent($query)
    {
        return $query->where('annee_campagne', getAnneeUniversitaire());
    }

    /** Campagnes dont la période d'admission est réellement ouverte. */
    public function scopeAdmissionsOuvertes($query)
    {
        return $query
            ->where('statut', 'active')
            ->whereDate('periode_debut', '<=', today())
            ->whereDate('periode_fin', '>=', today());
    }

    /**
     * Vérifier si la campagne est en cours
     */
    public function isCurrent(): bool
    {
        $now = now();
        return $now->between($this->periode_debut, $this->periode_fin);
    }

    /**
     * Obtenir l'année académique complète
     */
    public function getAcademicYear(): string
    {
        return $this->annee_debut . '-' . $this->annee_fin;
    }


    public function residences()
    {
        return $this->hasMany(ResidenceCampagne::class, 'campagne_id', 'id_campagne');
    }

    public function documentCampagnes()
    {
        return $this->hasMany(DocumentCampagne::class, 'id_campagne','id_campagne');
    }

    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([
            'residences',
            'residences.cites',
            'documentCampagnes',
            'documentCampagnes.documents'
        ]);
    }
    /**
     * Relation avec les demandes
     */
    public function demandes()
    {
        return $this->hasMany(DemandeEtudiant::class, 'campagne_id', 'id_campagne');
    }

    /**
     * Relation avec les assignations
     */
    public function assignations()
    {
        return $this->hasMany(AssignationChambre::class, 'campagne_id', 'id_campagne');
    }

    /**
     * Vérifier si la campagne est actuelle
     */
    public function estActive(): bool
    {
        return $this->statut === 'active' ||
            (now()->between($this->periode_debut, $this->periode_fin));
    }

    /**
     * Obtenir le nombre d'étudiants avec demande
     */
    public function getNombreEtudiants(): int
    {
        return $this->demandes()->distinct('etudiant_id')->count();
    }

    /**
     * Obtenir le nombre d'assignations confirmées
     */
    public function getNombreAssignations(): int
    {
        return $this->assignations()->where('statut', 'confirmee')->count();
    }

    /**
     * Obtenir le taux d'allocation
     */
    public function getTauxAllocation(): float
    {
        $total = $this->getNombreEtudiants();
        if ($total == 0) {
            return 0;
        }

        $assignes = $this->getNombreAssignations();
        return round(($assignes / $total) * 100, 2);
    }

    /**
     * Scope: Campagnes actives
     */
    public function scopeActives($query)
    {
        return $query->where('statut', 'active');
    }

    /**
     * Scope: Par année
     */
    public function scopeParAnnee($query, $annee)
    {
        return $query->where('annee_campagne', $annee);
    }

    /**
     * Obtenir le label du statut
     */
    public function getLabelStatut(): string
    {
        $statuts = [
            'active' => 'Active',
            'planifiee' => 'Planifiée',
            'terminee' => 'Terminée',
            'annulee' => 'Annulée',
        ];

        return $statuts[$this->statut] ?? ucfirst($this->statut);
    }

    /**
     * Obtenir les informations clés
     */
    public function getInfos(): array
    {
        return [
            'id' => $this->id_campagne,
            'annee' => $this->annee_campagne,
            'description' => $this->description,
            'etudiants' => $this->getNombreEtudiants(),
            'assignations' => $this->getNombreAssignations(),
            'taux' => $this->getTauxAllocation(),
            'statut' => $this->statut,
            'label_statut' => $this->getLabelStatut(),
        ];
    }
    protected $appends = ['statut_actuel'];
    public function getStatutActuelAttribute(): string
    {
        if (empty($this->periode_debut) || empty($this->periode_fin)) {
            return 'brouillon';
        }

        $aujourdhui = Carbon::now();
        $debut = Carbon::parse($this->periode_debut);
        $fin   = Carbon::parse($this->periode_fin);

        if ($debut->lte($aujourdhui) && $fin->gte($aujourdhui)) {
            return 'active';
        } elseif ($fin->lt($aujourdhui)) {
            return 'terminer';
        } elseif ($debut->gt($aujourdhui)) {
            return 'planifier';
        }

        return 'brouillon';
    }
}
