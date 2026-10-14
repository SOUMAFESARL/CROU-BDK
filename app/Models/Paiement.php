<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Paiement extends Model
{
    use SoftDeletes;

    protected $table = 'paiements';
    protected $primaryKey = 'id_paiement';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_paiement',
        'numero_paiement',
        'resident_id',
        'contrat_id',
        'chambre_id',
        'batiment_id',
        'cite_id',
        'type_paiement',
        'mois',
        'montant',
        'montant_du',
        'loyer_id',
        'devise',
        'methode_paiement',
        'reference_transaction',
        'statut_paiement',
        'date_paiement',
        'date_echeance',
        'date_creation',
        'date_mise_a_jour',
        'notes',
        'justificatif',
        'valide_par',
        'transaction_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_du' => 'decimal:2',
        'date_paiement' => 'datetime',
        'date_echeance' => 'date',
        'date_creation' => 'datetime',
        'date_mise_a_jour' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'mois' => 'array'
    ];

    protected $dates = [
        'date_paiement',
        'date_echeance',
        'date_creation',
        'date_mise_a_jour',
        'deleted_at'
    ];


   

     public const STATUTPAIEMENT = [
       'en_attente','valide','echoue','annule','rembourse'
    ];

    public const TYPEPAIEMENT = [
         'loyer','caution','penalite','autre'
    ];

    public const METHODEPAIEMENT = [
         'orange_money','mtn_money','moov_money','carte_bancaire','virement','especes','autre'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id_paiement) {
                $model->id_paiement = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = getInfoAdmin()->users->id_user ?? 'system';

            }
            $model->numero_paiement = self::genererNumeroPaiement();
            $model->reference_transaction = self::genererId();
            $model->created_at = now();
        });
        static::updating(function ($model) {


            if (empty($model->updated_by)) {
                $model->updated_by = getInfoAdmin()->users->id_user ?? 'system';
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

    // ============================================
    // RELATIONS
    // ============================================

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'resident_id', 'id_etudiant');
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(AssignationChambre::class, 'contrat_id', 'id_assignation');
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class, 'chambre_id', 'id_chambre');
    }

    public function batiment(): BelongsTo
    {
        return $this->belongsTo(Batiment::class, 'batiment_id', 'id_batiment');
    }

    public function cite(): BelongsTo
    {
        return $this->belongsTo(Cite::class, 'cite_id', 'id_cite');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par', 'id_user');
    }

    public function echeance(): HasOne
    {
        return $this->hasOne(Echeance::class, 'paiement_id', 'id_paiements');
    }
    public function createdBy(): BelongsTo
      
    {
        return $this->belongsTo(User::class, 'created_bd', 'id_user');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeValide($query)
    {
        return $query->where('statut_paiement', 'valide');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut_paiement', 'en_attente');
    }

    public function scopeParMois($query, $mois)
    {
        return $query->where('mois', $mois);
    }

    public function scopeParResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type_paiement', $type);
    }

    // ============================================
    // ACCESSORS & MUTATORS
    // ============================================

    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }

    public function getStatutLabelAttribute(): string
    {
        $labels = [
            'en_attente' => 'En attente',
            'valide' => 'Validé',
            'echoue' => 'Échoué',
            'annule' => 'Annulé',
            'rembourse' => 'Remboursé'
        ];
        return $labels[$this->statut_paiement] ?? $this->statut_paiement;
    }

    public function getStatutBadgeAttribute(): string
    {
        $badges = [
            'en_attente' => 'warning',
            'valide' => 'success',
            'echoue' => 'danger',
            'annule' => 'secondary',
            'rembourse' => 'info'
        ];
        return $badges[$this->statut_paiement] ?? 'secondary';
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'loyer' => 'Loyer',
            'caution' => 'Caution',
            'penalite' => 'Pénalité',
            'autre' => 'Autre'
        ];
        return $labels[$this->type_paiement] ?? $this->type_paiement;
    }

    public function getMethodeLabelAttribute(): string
    {
        $labels = [
            'orange_money' => 'Orange Money',
            'mtn_money' => 'MTN Money',
            'moov_money' => 'Moov Money',
            'carte_bancaire' => 'Carte bancaire',
            'virement' => 'Virement',
            'especes' => 'Espèces',
            'autre' => 'Autre'
        ];
        return $labels[$this->methode_paiement] ?? $this->methode_paiement;
    }
/*
    // ============================================
    // MÉTHODES
    // ============================================
     */
public static function genererNumeroPaiement(): string
{
    $prefix = 'PAY';
    $date = now()->format('dmy'); // 24072026
    
    // Récupérer le nombre de paiements du jour
    $count = self::whereDate('created_at', now()->toDateString())
        ->count() + 1;
    
    // Formater le compteur sur 4 chiffres
    $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
    
    // Format: PAY-24072026-0001
    $numero = $prefix . '-' . $date . '-' . $sequence;
    
    // Vérifier si le numéro existe déjà (sécurité)
    $exists = self::where('numero_paiement', $numero)->exists();
    if ($exists) {
        // En cas de collision, incrémenter jusqu'à trouver un numéro libre
        $counter = 1;
        while ($exists && $counter < 100) {
            $count = self::whereDate('created_at', now()->toDateString())->count() + $counter + 1;
            $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
            $numero = $prefix . '-' . $date . '-' . $sequence;
            $exists = self::where('numero_paiement', $numero)->exists();
            $counter++;
        }
    }
    
    return $numero;
}

    /**
     * Version séquentielle avec verrouillage (alternative)
     */
    public static function genererNumeroPaiementSequentiel(): string
    {
        $prefix = 'PAY';
        $date = now()->format('ymd');
        
        return DB::transaction(function () use ($prefix, $date) {
            // Récupérer le dernier numéro de la journée
            $last = self::where('numero_paiement', 'LIKE', $prefix . $date . '%')
                ->orderBy('numero_paiement', 'desc')
                ->lockForUpdate()
                ->first();

            if ($last) {
                // Extraire le numéro séquentiel
                preg_match('/' . $prefix . $date . '(\d{4})/', $last->numero_paiement, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }

            $numero = $prefix . $date . $newNumber;
            
            // Vérifier et incrémenter en cas de collision
            $exists = self::where('numero_paiement', $numero)->exists();
            $attempts = 0;
            
            while ($exists && $attempts < 100) {
                $newNumber = str_pad(intval($newNumber) + 1, 4, '0', STR_PAD_LEFT);
                $numero = $prefix . $date . $newNumber;
                $exists = self::where('numero_paiement', $numero)->exists();
                $attempts++;
            }
            
            if ($attempts >= 100) {
                // Fallback : utiliser microtime
                $numero = $prefix . $date . now()->format('His') . strtoupper(substr(Str::uuid()->toString(), 0, 6));
            }

            return $numero;
        });
    }


    public static function genererId(): string
    {
        return 'PAY-' . uniqid() . '-' . time();
    }

     /**
     * Relation avec les répartitions du paiement
     */
    public function repartitions()
    {
        return $this->hasMany(PaiementRepartition::class, 'paiement_id', 'id_paiement');
    }

    /**
     * Relation avec les répartitions de type arriéré
     */
    public function repartitionsArriere()
    {
        return $this->hasMany(PaiementRepartition::class, 'paiement_id', 'id_paiement')
            ->where('type', PaiementRepartition::TYPE_ARRIERE);
    }

    /**
     * Relation avec les répartitions de type mois en cours
     */
    public function repartitionsMoisCours()
    {
        return $this->hasMany(PaiementRepartition::class, 'paiement_id', 'id_paiement')
            ->where('type', PaiementRepartition::TYPE_MOIS_COURS);
    }

    /**
     * Relation avec les répartitions de type avance
     */
    public function repartitionsAvance()
    {
        return $this->hasMany(PaiementRepartition::class, 'paiement_id', 'id_paiement')
            ->where('type', PaiementRepartition::TYPE_AVANCE);
    }

}