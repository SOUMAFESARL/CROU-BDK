<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Campus extends Model
{
    use HasFactory , SoftDeletes;

    protected $table = 'campus';
    protected $primaryKey = 'id_campus';

    public $incrementing = false;

    public $timestamps = true;
    protected $keyType = 'string';

    protected $guarded = [];


//    protected $fillable = [
//        'code_campus',
//        'name',
//        'description',
//        'adresse',
//        'ville_id',
//        'region_id',
//        'countrie_id',
//        'code',
//        'telephone1',
//        'telephone2',
//        'telephone3',
//        'email1',
//        'email2',
//        'email3',
//        'logo',
//        'slogan',
//        'devise',
//        'directeur_code',
//        'capacite_max',
//        'superficie',
//        'date_creation',
//        'statut',
//        'latitude',   // NOUVEAU
//        'longitude',  // NOUVEAU
//        'created_by',
//        'updated_by',
//        'deleted_by',
//    ];



    protected $casts = [
        'date_creation' => 'date',
        'capacite_max' => 'integer',
        'superficie' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'latitude' => 'float',    // NOUVEAU
        'longitude' => 'float',   // NOUVEAU
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campus) {
            if (empty($campus->{$campus->getKeyName()})) {
                $campus->{$campus->getKeyName()} = (string) Str::uuid();
            }

            if (empty($campus->created_by)) {
                $campus->created_by = getInfoAdmin()->users->code ?? null;
            }
            if (empty($demande->code)) {
                $campus->code = generate('campus','code',150,'a-zA-Z0-9') ;
            }
        });

        static::updating(function ($campus) {


            if (empty($campus->updated_by)) {
                $campus->updated_by = getInfoAdmin()->users->code ?? null;
            }

        });
    }

    /**
     * Relation avec la ville
     */
    public function ville()
    {
        return $this->belongsTo(Ville::class, 'ville_id');
    }

    /**
     * Relation avec la région
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Relation avec le pays
     */
    public function country()
    {
        return $this->belongsTo(Countrie::class, 'countrie_id');
    }

    /**
     * Relation avec les cités
     */
    public function cites()
    {
        return $this->hasMany(Cite::class, 'campus_id','id_campus');
    }

    /**
     * Relation avec le directeur
     */
    public function directeur()
    {
        return $this->belongsTo(User::class, 'directeur_code', 'id_user');
    }

    /**
     * Relation avec le créateur
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
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

    /**
     * Vérifier si le campus est actif
     */
    public function isActive(): bool
    {
        return $this->statut === 'actif';
    }

    /**
     * Scope pour les campus actifs
     */
    public function scopeActive($query)
    {
        return $query->where('statut', 'actif');
    }

    /**
     * Scope pour recherche
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('code_campus', 'like', "%{$term}%")
            ->orWhere('email1', 'like', "%{$term}%");
    }

    /**
     * Scope par région
     */
    public function scopeByRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Scope par ville
     */
    public function scopeByVille($query, $villeId)
    {
        return $query->where('ville_id', $villeId);
    }


    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Formater la latitude pour l'affichage (6 décimales)
     */
    public function getLatitudeFormattedAttribute()
    {
        return $this->latitude ? number_format($this->latitude, 6) : null;
    }

    /**
     * Formater la longitude pour l'affichage (6 décimales)
     */
    public function getLongitudeFormattedAttribute()
    {
        return $this->longitude ? number_format($this->longitude, 6) : null;
    }

    /**
     * Vérifier si le campus a des coordonnées
     */
    public function hasCoordinates()
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Obtenir les coordonnées au format array
     */
    public function getCoordinatesArray()
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * Obtenir les coordonnées au format string (Google Maps, Leaflet, etc.)
     */
    public function getCoordinatesString()
    {
        if (!$this->hasCoordinates()) {
            return null;
        }
        return "{$this->latitude},{$this->longitude}";
    }

    // ==================== SCOPES ====================

    /**
     * Scope : Récupérer uniquement les campus avec coordonnées
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    /**
     * Scope : Récupérer les campus sans coordonnées
     */
    public function scopeWithoutCoordinates($query)
    {
        return $query->whereNull('latitude')
            ->orWhereNull('longitude');
    }

    /**
     * Scope : Rechercher les campus proches d'une localisation
     * Utilise la formule Haversine pour calculer la distance
     *
     * @param float $latitude
     * @param float $longitude
     * @param float $radiusKm Distance en kilomètres
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 10)
    {
        // Formule Haversine (distance en km)
        // Rayon terrestre : 6371 km
        $earthRadiusKm = 6371;

        return $query->selectRaw(
            "*,
            ({$earthRadiusKm} * acos(
                cos(radians({$latitude})) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians({$longitude})) +
                sin(radians({$latitude})) *
                sin(radians(latitude))
            )) AS distance"
        )
            ->whereRaw(
                "({$earthRadiusKm} * acos(
                cos(radians({$latitude})) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians({$longitude})) +
                sin(radians({$latitude})) *
                sin(radians(latitude))
            )) <= ?",
                [$radiusKm]
            )
            ->orderBy('distance');
    }

    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([
            'ville',
            'region',
            'country',
            'cites',
            'directeur',
            'creator',
            'updator',
            'deletor'
        ]);
    }

}
