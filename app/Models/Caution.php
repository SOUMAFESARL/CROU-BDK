<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Caution extends Model
{
    protected $table = 'cautions';
    protected $primaryKey = 'id_caution';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_caution',
        'resident_id',
        'contrat_id',
        'chambre_id',
        'batiment_id',
        'cite_id',
        'montant_initial',
        'montant_retenu',
        'montant_restitue',
        'solde',
        'statut',
        'paiement_encaissement_id',
        'paiement_restitution_id',
        'date_encaissement',
        'date_restitution',
        'date_creation',
        'date_mise_a_jour',
        'details_retenues',
        'observations',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'montant_initial' => 'decimal:2',
        'montant_retenu' => 'decimal:2',
        'montant_restitue' => 'decimal:2',
        'solde' => 'decimal:2',
        'date_encaissement' => 'date',
        'date_restitution' => 'date',
        'date_creation' => 'datetime',
        'date_mise_a_jour' => 'datetime',
        'details_retenues' => 'array',
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

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'resident_id', 'id_residents');
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class, 'contrat_id', 'id_contrats');
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class, 'chambre_id', 'id_chambres');
    }

    public function batiment(): BelongsTo
    {
        return $this->belongsTo(Batiment::class, 'batiment_id', 'id_batiments');
    }

    public function cite(): BelongsTo
    {
        return $this->belongsTo(Cite::class, 'cite_id', 'id_cites');
    }

    public function paiementEncaissement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class, 'paiement_encaissement_id', 'id_paiements');
    }

    public function paiementRestitution(): BelongsTo
    {
        return $this->belongsTo(Paiement::class, 'paiement_restitution_id', 'id_paiements');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeEncaissement($query)
    {
        return $query->where('statut', 'encaissement');
    }

    public function scopeRestitue($query)
    {
        return $query->where('statut', 'restitue');
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
            'encaissement' => 'Encaissement',
            'retenue_partielle' => 'Retenue partielle',
            'restitue' => 'Restituée',
            'solde_debiteur' => 'Solde débiteur'
        ];
        return $labels[$this->statut] ?? $this->statut;
    }

    public function getStatutBadgeAttribute(): string
    {
        $badges = [
            'encaissement' => 'info',
            'retenue_partielle' => 'warning',
            'restitue' => 'success',
            'solde_debiteur' => 'danger'
        ];
        return $badges[$this->statut] ?? 'secondary';
    }

    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant_initial, 0, ',', ' ') . ' FCFA';
    }

    public function getSoldeFormateAttribute(): string
    {
        return number_format($this->solde, 0, ',', ' ') . ' FCFA';
    }

    // ============================================
    // MÉTHODES
    // ============================================

    public static function genererId(): string
    {
        return 'CAU-' . uniqid() . '-' . time();
    }

    public function appliquerRetenue($montant, $details = null): void
    {
        $this->montant_retenu += $montant;

        if ($details) {
            $currentDetails = $this->details_retenues ?? [];
            $currentDetails[] = $details;
            $this->details_retenues = $currentDetails;
        }

        if ($this->solde == 0) {
            $this->statut = 'restitue';
        } elseif ($this->solde > 0) {
            $this->statut = 'retenue_partielle';
        }

        $this->save();
    }

    public function restituer($montant, $paiementId = null): void
    {
        $this->montant_restitue = $montant;
        $this->date_restitution = now();
        $this->paiement_restitution_id = $paiementId;

        if ($this->solde == 0) {
            $this->statut = 'restitue';
        } elseif ($this->solde < 0) {
            $this->statut = 'solde_debiteur';
        }

        $this->save();
    }
}