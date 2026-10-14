<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aile_Batiment extends Model{

    use HasFactory;

    protected $primaryKey = 'id_aile_batiment';
    protected  $guarded= [];

    protected  $table= 'aile_batiment';

    public function batiment()
    {
        return $this->belongsTo(Batiment::class, 'code_batiment','code_batiment');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by','code');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by','code');
    }

    public function niveaux()
    {
        return $this->hasMany(Niveau_Aile_Batiment::class, 'code_aile', 'code_aile');
    }

    public function chambres()
    {
        return $this->hasMany(Chambre::class, 'code_aile', 'code_aile');
    }

// Accesseur pour le nombre de niveaux par aile
    public function getNombreNiveauxAttribute()
    {
        return $this->niveaux()->count();
    }

// Accesseur pour les statistiques de l'aile
    public function getStatistiquesAileAttribute()
    {
        return [
            'nombre_niveaux' => $this->nombre_niveaux,
            'total_chambres' => $this->chambres()->count(),
            'chambres_par_niveau' => $this->niveaux()
                ->withCount('chambres')
                ->get()
                ->mapWithKeys(function($niveau) {
                    return [$niveau->niveau_numero => $niveau->chambres_count];
                }),
            'capacite_totale' => $this->chambres()->sum('capacite_max'),
        ];
    }
}