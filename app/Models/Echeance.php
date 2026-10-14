<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Echeance extends Model
{
    protected $table = 'echeances';
    protected $primaryKey = 'id_echeance';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_echeance',
        'echeancier_id',
        'numero_echeance',
        'mois',
        'date_echeance',
        'montant_du',
        'statut',
        'paiement_id',
        'date_paiement',
        'date_rappel1',
        'date_rappel2',
        'date_mise_en_demeure',
        'date_creation',
        'date_mise_a_jour',
        'penalite',
        'penalite_appliquee',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'montant_du' => 'decimal:2',
        'penalite' => 'decimal:2',
        'date_echeance' => 'date',
        'date_paiement' => 'datetime',
        'date_rappel1' => 'datetime',
        'date_rappel2' => 'datetime',
        'date_mise_en_demeure' => 'datetime',
        'date_creation' => 'datetime',
        'date_mise_a_jour' => 'datetime',
        'penalite_appliquee' => 'boolean',
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
            if (!$model->id_echeance) {
                $model->id_echeance = (string) \Illuminate\Support\Str::uuid();
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

    public function echeancier(): BelongsTo
    {
        return $this->belongsTo(Echeancier::class, 'echeancier_id', 'id_echeanciers');
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class, 'paiement_id', 'id_paiements');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeAPayer($query)
    {
        return $query->where('statut', 'a_payer');
    }

    public function scopePaye($query)
    {
        return $query->where('statut', 'paye');
    }

    public function scopeEnRetard($query)
    {
        return $query->where('statut', 'en_retard');
    }

    public function scopeParMois($query, $mois)
    {
        return $query->where('mois', $mois);
    }

    public function scopeEcheanceAvant($query, $date)
    {
        return $query->where('date_echeance', '<=', $date)
            ->where('statut', '!=', 'paye');
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getStatutLabelAttribute(): string
    {
        $labels = [
            'a_payer' => 'À payer',
            'paye' => 'Payé',
            'en_retard' => 'En retard',
            'annule' => 'Annulé'
        ];
        return $labels[$this->statut] ?? $this->statut;
    }

    public function getStatutBadgeAttribute(): string
    {
        $badges = [
            'a_payer' => 'info',
            'paye' => 'success',
            'en_retard' => 'danger',
            'annule' => 'secondary'
        ];
        return $badges[$this->statut] ?? 'secondary';
    }

    public function getMontantTotalAttribute(): float
    {
        return $this->montant_du + $this->penalite;
    }

    // ============================================
    // MÉTHODES
    // ============================================

    public function marquerPaye($paiementId = null): void
    {
        $this->statut = 'paye';
        $this->date_paiement = now();
        $this->paiement_id = $paiementId;
        $this->save();

        // Mettre à jour l'échéancier
        $this->echeancier->increment('echeances_effectuees');
    }

    public function marquerEnRetard(): void
    {
        $this->statut = 'en_retard';
        $this->save();
    }
}