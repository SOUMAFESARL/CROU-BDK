<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentEtudiant extends Model
{
    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    protected $table = 'document_etudiant';

    /**
     * La clé primaire de la table.
     *
     * @var string
     */
    protected $primaryKey = 'id_document';

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

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'date_validation' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'taille_octets' => 'integer',
        'score_qualite' => 'float'
    ];

    /**
     * Les valeurs par défaut pour les attributs.
     *
     * @var array
     */
    protected $attributes = [
        'statut' => 'en_verification'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (empty($document->{$document->getKeyName()})) {
                $document->{$document->getKeyName()} = (string) Str::uuid();
            }

            if (empty($document->created_by)) {
                $document->created_by = getInfoAdmin()->users->code ?? null;
            }

            if (empty($document->statut)) {
                $document->statut = 'en_verification';
            }
        });
    }

    /**
     * Les statuts possibles
     */
    const STATUT_EN_VERIFICATION = 'en_verification';
    const STATUT_VALIDE = 'valide';
    const STATUT_REJETE = 'rejete';
    const STATUT_OBSOLETE = 'obsolete';

    /**
     * Récupère tous les statuts possibles
     *
     * @return array
     */
    public static function getStatuts(): array
    {
        return [
            self::STATUT_EN_VERIFICATION => 'En vérification',
            self::STATUT_VALIDE => 'Validé',
            self::STATUT_REJETE => 'Rejeté',
            self::STATUT_OBSOLETE => 'Obsolète'
        ];
    }

    /**
     * Récupère le libellé du statut
     *
     * @return string
     */
    public function getStatutLibelleAttribute(): string
    {
        return self::getStatuts()[$this->statut] ?? $this->statut;
    }

    /**
     * Vérifie si le document est en vérification
     *
     * @return bool
     */
    public function isEnVerification(): bool
    {
        return $this->statut === self::STATUT_EN_VERIFICATION;
    }

    /**
     * Vérifie si le document est validé
     *
     * @return bool
     */
    public function isValide(): bool
    {
        return $this->statut === self::STATUT_VALIDE;
    }

    /**
     * Vérifie si le document est rejeté
     *
     * @return bool
     */
    public function isRejete(): bool
    {
        return $this->statut === self::STATUT_REJETE;
    }

    // ==================== RELATIONS ====================

    /**
     * Relation avec l'étudiant
     */
    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'etudiant_id', 'id_etudiant');
    }

    /**
     * Relation avec le campus
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_code', 'code_campus');
    }

    /**
     * Relation avec le validateur
     */
    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par', 'id');
    }

    /**
     * Relation avec le document précédent (version antérieure)
     */
    public function documentPrecedent()
    {
        return $this->belongsTo(DocumentEtudiant::class, 'document_precedent_id', 'id_document');
    }

    /**
     * Relation avec les versions suivantes de ce document
     */
    public function versionsSuivantes()
    {
        return $this->hasMany(DocumentEtudiant::class, 'document_precedent_id', 'id_document');
    }

    /**
     * Relation avec l'utilisateur qui a créé
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour
     */
    public function moderateur()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope pour filtrer par statut
     */
    public function scopeStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope pour les documents en vérification
     */
    public function scopeEnVerification($query)
    {
        return $query->where('statut', self::STATUT_EN_VERIFICATION);
    }

    /**
     * Scope pour les documents validés
     */
    public function scopeValides($query)
    {
        return $query->where('statut', self::STATUT_VALIDE);
    }

    /**
     * Scope pour les documents rejetés
     */
    public function scopeRejetes($query)
    {
        return $query->where('statut', self::STATUT_REJETE);
    }

    /**
     * Scope pour filtrer par étudiant
     */
    public function scopeParEtudiant($query, $etudiantId)
    {
        return $query->where('etudiant_id', $etudiantId);
    }

    /**
     * Scope pour filtrer par type de document
     */
    public function scopeParTypeDocument($query, $type)
    {
        return $query->where('type_document', $type);
    }

    /**
     * Scope pour les documents récents (créés dans les X derniers jours)
     */
    public function scopeRecents($query, $jours = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($jours));
    }

    /**
     * Scope pour les documents validés récemment
     */
    public function scopeValidesRecents($query, $jours = 30)
    {
        return $query->where('statut', self::STATUT_VALIDE)
            ->where('date_validation', '>=', now()->subDays($jours));
    }

    // ==================== ACCESSEURS ====================

    /**
     * Formate la taille du fichier en Ko/Mo
     */
    public function getTailleFormateeAttribute(): string
    {
        $octets = $this->taille_octets;
        if ($octets >= 1048576) {
            return round($octets / 1048576, 2) . ' Mo';
        } elseif ($octets >= 1024) {
            return round($octets / 1024, 2) . ' Ko';
        }
        return $octets . ' octets';
    }

    /**
     * Récupère le nom du fichier sans le chemin
     */
    public function getNomFichierAttribute(): string
    {
        return basename($this->chemin_fichier);
    }

    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([
            "createur",
            "documentPrecedent",
            "campus",
            "etudiant",
            "validateur",
            "versionsSuivantes",
            "moderateur"
        ]);
    }
}