<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Cite extends Model
{
    /** Valeurs de l'enum `cite.statut` utilisées par l'activation / désactivation. */
    public const STATUT_OUVERT = 'ouvert';
    public const STATUT_FERME = 'ferme';

    /** Statuts d'une cité en service (« actif » : valeur historique encore présente dans certaines données). */
    public const STATUTS_ACTIFS = [self::STATUT_OUVERT, 'actif'];

    public function estActive(): bool
    {
        return in_array($this->statut, self::STATUTS_ACTIFS, true);
    }

    public function equipementAffectations()
    {
        return $this->hasMany(EquipementAffectation::class, 'cite_id', 'id_cite');
    }
    use HasFactory , SoftDeletes;

    protected $table = 'cite';
    protected $primaryKey = 'id_cite';
    public $incrementing = false;

    public $timestamps = true;
    protected $keyType = 'string';
    protected $fillable = [
        'code_cite',
        'name',
        'type_cite_id',
        'campus_id',
        'adresse',
        'capacite_max',
        'nombre_batiments',
        'superficie',
        'annee_construction',
        'responsable_id',
        'telephone1',
        'telephone2',
        'telephone3',
        'email1',
        'email2',
        'longitude',
        'latitude',
        'reglement_interieur_id',
        'statut',
        'wifi_gratuit',
        'salle_etude',
        'infirmerie',
        'restaurant',
        'plan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'capacite_max' => 'integer',
        'nombre_batiments' => 'integer',
        'superficie' => 'decimal:2',
        'annee_construction' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'wifi_gratuit' => 'boolean',
        'salle_etude' => 'boolean',
        'infirmerie' => 'boolean',
        'restaurant' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cite) {
            if (empty($campus->{$cite->getKeyName()})) {
                $cite->{$cite->getKeyName()} = (string) Str::uuid();
            }

            if (empty($cite->created_by)) {
                $cite->created_by = getInfoAdmin()->users->id_user ?? null;
            }

        });

        static::updating(function ($cite) {


            if (empty($cite->updated_by)) {
                $cite->updated_by = getInfoAdmin()->users->id_user ?? null;
            }

        });
    }

    /**
     * Relation avec le campus
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id', 'id_campus');
    }
    public function typeCite()
    {
        return $this->belongsTo(TypeCite::class, 'type_cite_id', 'id_type_cite');
    }
    public function reglementInterieur()
    {
        return $this->belongsTo(ReglementInterieur::class, 'reglement_interieur_id', 'id_reglement');
    }
    /**
     * Relation avec le responsable
     */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id', 'id_user');
    }

    /**
     * Relation avec le créateur
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function batiment()
    {
        return $this->hasMany(Batiment::class, 'cite_id', 'id_cite');
    }

    /**
     * Relation avec l'updater
     */
    public function updator()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id_user');
    }

    /**
     * Relation avec le deleter
     */
    public function deletor()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id_user');
    }
    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([
            'creator',
            'responsable',
            'typeCite',
            'campus',
            'updator',
            'deletor',
            'batiment',
            'batiment.chambres',
            'batiment.chambres.assignations',
            'batiment.chambresDisponibles',
            'reglementInterieur'
        ]);
    }
    /**
     * Vérifier si la cité est active
     */
    public function isActive(): bool
    {
        return $this->statut === 'actif';
    }

    /**
     * Vérifier si le wifi est gratuit
     */
    public function hasWifiGratuit(): bool
    {
        return $this->wifi_gratuit === true;
    }


    /**
     * Vérifier les services disponibles
     */
    public function hasServices(): array
    {
        return [
            'wifi_gratuit' => $this->wifi_gratuit,
            'salle_etude' => $this->salle_etude,
            'infirmerie' => $this->infirmerie,
            'restaurant' => $this->restaurant,
        ];
    }

    /**
     * Scope pour les cités actives
     */
    public function scopeActive($query)
    {
        return $query->where('statut', 'actif');
    }

    /**
     * Scope par campus
     */
    public function scopeByCampus($query, $campusCode)
    {
        return $query->where('campus_id', $campusCode);
    }

    /**
     * Scope pour recherche
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('code_cite', 'like', "%{$term}%")
            ->orWhere('email1', 'like', "%{$term}%");
    }

    /**
     * Scope avec wifi gratuit
     */
    public function scopeWithWifiGratuit($query)
    {
        return $query->where('wifi_gratuit', true);
    }

    /**
     * Scope avec services
     */
    public function scopeWithServices($query, array $services)
    {
        foreach ($services as $service) {
            $query->where($service, true);
        }
        return $query;
    }
}
