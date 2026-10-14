<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TypeCite extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    protected $table = 'type_cite';

    /**
     * La clé primaire associée à la table.
     *
     * @var string
     */
    protected $primaryKey = 'id_type_cite';

    /**
     * Indique si la clé primaire est un incrément automatique.
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
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected  $guarded= [];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id_type_cite' => 'string', // Ajouter ce cast
        'is_active' => 'boolean',
        'duree_min_sejour' => 'integer',
        'duree_max_sejour' => 'integer',
        'age_min' => 'integer',
        'age_max' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les valeurs par défaut des attributs.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'public_cible' => 'etudiant',
        'is_active' => true,
        'age_min' => 0,
        'age_max' => 0,
    ];

    // ... Le reste de vos méthodes (scopes, accesseurs, etc.) reste inchangé ...

    /**
     * Événements du modèle.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($typeCite) {
            // Générer un UUID si l'id n'est pas déjà défini
            if (empty($typeCite->{$typeCite->getKeyName()})) {
                $typeCite->{$typeCite->getKeyName()} = (string) Str::uuid();
            }

            // Générer un code_type si non défini
            if (empty($typeCite->code_type)) {
                $typeCite->code_type = 'CITE_' . strtoupper(Str::random(6));
            }

            if (auth()->check()) {
                $typeCite->created_by = auth()->user()->email ?? auth()->user()->name;
            }
        });

        static::updating(function ($typeCite) {
            if (auth()->check()) {
                $typeCite->updated_by = auth()->user()->email ?? auth()->user()->name;
            }
        });
    }
}
