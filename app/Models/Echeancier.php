<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Echeancier extends Model
{
    protected $table = 'echeanciers';
    protected $primaryKey = 'id_echeancier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_echeancier',
        'contrat_id',
        'resident_id',
        'periodicite',
        'date_debut',
        'date_fin',
        'montant_par_echeance',
        'nombre_echeances',
        'echeances_effectuees',
        'statut',
        'date_creation',
        'date_mise_a_jour',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'montant_par_echeance' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_creation' => 'datetime',
        'date_mise_a_jour' => 'datetime',
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
            if (!$model->id_echeancier) {
                $model->id_echeancier = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = getInfoAdmin()->users->id_user ?? null;

            }
           // $model->numero_paiement = self::genererNumeroPaiement();
          //  $model->reference_transaction = self::genererId();
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

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class, 'contrat_id', 'id_contrat');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'resident_id', 'id_etudiant');
    }

    public function echeances(): HasMany
    {
        return $this->hasMany(Echeance::class, 'echeancier_id', 'id_echeanciers');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeParResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getStatutLabelAttribute(): string
    {
        $labels = [
            'actif' => 'Actif',
            'termine' => 'Terminé',
            'suspendu' => 'Suspendu',
            'annule' => 'Annulé'
        ];
        return $labels[$this->statut] ?? $this->statut;
    }

    public function getPeriodiciteLabelAttribute(): string
    {
        $labels = [
            'mensuel' => 'Mensuel',
            'trimestriel' => 'Trimestriel',
            'semestriel' => 'Semestriel',
            'annuel' => 'Annuel'
        ];
        return $labels[$this->periodicite] ?? $this->periodicite;
    }

    public function getProgressionAttribute(): float
    {
        if ($this->nombre_echeances == 0) {
            return 0;
        }
        return round(($this->echeances_effectuees / $this->nombre_echeances) * 100, 2);
    }

    // ============================================
    // MÉTHODES
    // ============================================

    public static function genererId(): string
    {
        return 'ECH-' . uniqid() . '-' . time();
    }
}