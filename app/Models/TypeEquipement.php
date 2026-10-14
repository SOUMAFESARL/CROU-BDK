<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TypeEquipement extends Model
{
    use HasFactory;

    /**
     * Nom de la table associée au modèle
     *
     * @var string
     */
    protected $table = 'type_equipement';

    /**
     * Clé primaire de la table
     *
     * @var string
     */
    protected $primaryKey = 'id_type_equipement';

    /**
     * Indique si les IDs sont auto-incrémentés
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Type de la clé primaire
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Les attributs qui sont mass assignable
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description'
        // Seulement les champs que vous utilisez
    ];

    /**
     * Les attributs qui doivent être convertis
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id_type_equipement' => 'string',
        'est_consommable' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Les valeurs par défaut pour les attributs
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'cout_moyen' => 0.00,
        'cout_maintenance' => 0.00,
        'est_consommable' => false,
        'is_active' => true
    ];

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Générer un UUID si l'id n'est pas déjà défini
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Appliquer les valeurs par défaut pour éviter l'erreur SQL
            $model->cout_moyen = $model->cout_moyen ?? 0.00;
            $model->cout_maintenance = $model->cout_maintenance ?? 0.00;
            $model->est_consommable = $model->est_consommable ?? false;
            $model->is_active = $model->is_active ?? true;
        });
    }

}