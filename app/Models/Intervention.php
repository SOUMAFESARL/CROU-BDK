<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intervention extends Model
{
    protected $table = 'interventions';
    protected $primaryKey = 'id_intervention';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_intervention',
        'id_ordres_travail',
        'date_intervention',
        'duree',
        'description',
        'technicien_id',
        'equipe',
        'materiel_utilise',
        'pieces_detachees',
        'resultat',
        'remarques',
        'photos_avant',
        'photos_apres',
        'date_creation',
        'date_mise_a_jour',
        'taux_satisfaction',
        'commentaire_satisfaction',
        'date_satisfaction',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'date_intervention' => 'datetime',
        'date_creation' => 'datetime',
        'date_mise_a_jour' => 'datetime',
        'materiel_utilise' => 'array',
        'pieces_detachees' => 'array',
        'photos_avant' => 'array',
        'photos_apres' => 'array',
        'duree' => 'integer',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
         'taux_satisfaction' => 'integer',
    'date_satisfaction' => 'datetime',
    ];

    // ============================================
    // RELATIONS
    // ============================================

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id_intervention) {
                $model->id_intervention = (string) \Illuminate\Support\Str::uuid();
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

    public function ordreTravail(): BelongsTo
    {
        return $this->belongsTo(OrdreTravail::class, 'id_ordres_travail', 'id_ordres_travail');
    }

    public function technicien(): BelongsTo
    {
        return $this->belongsTo(Maintenancier::class, 'technicien_id', 'id_maintenancier');
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getResultatLabelAttribute(): string
    {
        $labels = [
            'resolu' => 'Résolu',
            'partiellement_resolu' => 'Partiellement résolu',
            'non_resolu' => 'Non résolu',
            'necessite_suivi' => 'Nécessite un suivi'
        ];
        return $labels[$this->resultat] ?? $this->resultat;
    }

    public function getResultatBadgeAttribute(): string
    {
        $badges = [
            'resolu' => 'success',
            'partiellement_resolu' => 'warning',
            'non_resolu' => 'danger',
            'necessite_suivi' => 'info'
        ];
        return $badges[$this->resultat] ?? 'secondary';
    }
}
