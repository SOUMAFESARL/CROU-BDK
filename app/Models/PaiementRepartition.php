<?php
// app/Models/PaiementRepartition.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class PaiementRepartition extends Model
{
    use HasUuids;

    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    protected $table = 'paiement_repartitions';

    /**
     * Le nom de la clé primaire.
     *
     * @var string
     */
    protected $primaryKey = 'id_repartition';

    /**
     * Indique si l'ID est auto-incrémenté.
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
    protected $fillable = [
        'id_repartition',
        'paiement_id',
        'type',
        'montant',
        'mois',
        'annee',
        'description',
        'created_at',
        'updated_at'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les types de répartition disponibles.
     */
    public const TYPE_ARRIERE = 'arriere';
    public const TYPE_MOIS_COURS = 'mois_cours';
    public const TYPE_AVANCE = 'avance';

    /**
     * Les labels des types de répartition.
     */
    public const TYPE_LABELS = [
        self::TYPE_ARRIERE => 'Arriérés',
        self::TYPE_MOIS_COURS => 'Mois en cours',
        self::TYPE_AVANCE => 'Avance',
    ];

    /**
     * Les couleurs des types de répartition.
     */
    public const TYPE_COLORS = [
        self::TYPE_ARRIERE => 'danger',
        self::TYPE_MOIS_COURS => 'success',
        self::TYPE_AVANCE => 'info',
    ];

    /**
     * Les icônes des types de répartition.
     */
    public const TYPE_ICONS = [
        self::TYPE_ARRIERE => '⚠️',
        self::TYPE_MOIS_COURS => '📆',
        self::TYPE_AVANCE => '💰',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    /**
     * Relation avec le paiement.
     */
    public function paiement()
    {
        return $this->belongsTo(Paiement::class, 'paiement_id', 'id_paiement');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope pour filtrer par type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour les arriérés.
     */
    public function scopeArriere($query)
    {
        return $query->where('type', self::TYPE_ARRIERE);
    }

    /**
     * Scope pour le mois en cours.
     */
    public function scopeMoisCours($query)
    {
        return $query->where('type', self::TYPE_MOIS_COURS);
    }

    /**
     * Scope pour les avances.
     */
    public function scopeAvance($query)
    {
        return $query->where('type', self::TYPE_AVANCE);
    }

    /**
     * Scope pour filtrer par mois et année.
     */
    public function scopeForPeriode($query, string $mois, string $annee)
    {
        return $query->where('mois', $mois)->where('annee', $annee);
    }

    /**
     * Scope pour filtrer par paiement.
     */
    public function scopeForPaiement($query, string $paiementId)
    {
        return $query->where('paiement_id', $paiementId);
    }

    // ============================================================
    // MÉTHODES D'INSTANCE
    // ============================================================

    /**
     * Récupère le label du type.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /**
     * Récupère la couleur du type.
     */
    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'secondary';
    }

    /**
     * Récupère l'icône du type.
     */
    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? '📌';
    }

    /**
     * Récupère le mois formaté.
     */
    public function getMoisFormateAttribute(): string
    {
        if (!$this->mois || !$this->annee) {
            return '-';
        }

        $mois = [
            '01' => 'Janvier',
            '02' => 'Février',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Août',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre',
        ];

        return ($mois[$this->mois] ?? $this->mois) . ' ' . $this->annee;
    }

    /**
     * Vérifie si c'est un arriéré.
     */
    public function isArriere(): bool
    {
        return $this->type === self::TYPE_ARRIERE;
    }

    /**
     * Vérifie si c'est un mois en cours.
     */
    public function isMoisCours(): bool
    {
        return $this->type === self::TYPE_MOIS_COURS;
    }

    /**
     * Vérifie si c'est une avance.
     */
    public function isAvance(): bool
    {
        return $this->type === self::TYPE_AVANCE;
    }

    // ============================================================
    // MÉTHODES STATIQUES
    // ============================================================

    /**
     * Récupère tous les types disponibles.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_ARRIERE,
            self::TYPE_MOIS_COURS,
            self::TYPE_AVANCE,
        ];
    }

    /**
     * Récupère tous les types avec leurs labels.
     */
    public static function getTypesWithLabels(): array
    {
        return self::TYPE_LABELS;
    }

    /**
     * Crée une répartition pour un paiement.
     */
    public static function createRepartition(
        string $paiementId,
        string $type,
        float $montant,
        ?string $mois = null,
        ?string $annee = null,
        ?string $description = null
    ): self {
        return self::create([
            'id_repartition' => (string) Str::uuid(),
            'paiement_id' => $paiementId,
            'type' => $type,
            'montant' => $montant,
            'mois' => $mois,
            'annee' => $annee,
            'description' => $description,
        ]);
    }

    /**
     * Récupère le total des répartitions pour un paiement.
     */
    public static function getTotalForPaiement(string $paiementId): float
    {
        return self::where('paiement_id', $paiementId)->sum('montant');
    }

    /**
     * Récupère le total par type pour un paiement.
     */
    public static function getTotalByTypeForPaiement(string $paiementId, string $type): float
    {
        return self::where('paiement_id', $paiementId)
            ->where('type', $type)
            ->sum('montant');
    }

    // ============================================================
    // EVENTS
    // ============================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id_repartition)) {
                $model->id_repartition = (string) Str::uuid();
            }
        });
    }
}