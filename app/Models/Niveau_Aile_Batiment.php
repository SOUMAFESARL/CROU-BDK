<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Niveau_Aile_Batiment extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_niveau_aile';
    protected $guarded = [];
    protected $table = 'niveau_aile_batiment';

    /**
     * Relation avec le bâtiment via l'aile
     */
    public function batiment()
    {
        return $this->belongsTo(Batiment::class, 'code_batiment', 'code_batiment');
    }

    /**
     * Relation avec l'aile
     */
    public function aile()
    {
        return $this->belongsTo(Aile_Batiment::class, 'code_aile', 'code_aile');
    }

    /**
     * Relation avec l'utilisateur qui a créé
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'code');
    }

    /**
     * Relation avec l'utilisateur qui a modifié
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'code');
    }

    /**
     * Relation avec les chambres
     */
    public function chambres()
    {
        return $this->hasMany(Chambre::class, 'niveau_aile_code', 'niveau_aile_code');
    }

    /**
     * Accesseur pour le nombre de chambres du niveau
     */
    public function getNombreChambresAttribute()
    {
        return $this->chambres()->count();
    }

    /**
     * Accesseur pour les chambres disponibles
     */
    public function getChambresDisponiblesAttribute()
    {
        return $this->chambres()
            ->where('etat', 'disponible')
            ->count();
    }

    /**
     * Accesseur pour le taux d'occupation du niveau
     */
    public function getTauxOccupationNiveauAttribute()
    {
        $totalChambres = $this->chambres()->count();
        if ($totalChambres === 0) return 0;

        $chambresOccupees = $this->chambres()
            ->where('etat', 'occupee')
            ->count();

        return round(($chambresOccupees / $totalChambres) * 100, 2);
    }

    /**
     * Statistiques complètes du niveau
     */
    public function getStatistiquesNiveauAttribute()
    {
        return [
            'total_chambres' => $this->nombre_chambres,
            'chambres_disponibles' => $this->chambres_disponibles,
            'chambres_occupees' => $this->chambres()->where('etat', 'occupee')->count(),
            'chambres_maintenance' => $this->chambres()->where('etat', 'maintenance')->count(),
            'chambres_reservees' => $this->chambres()->where('etat', 'reservee')->count(),
            'taux_occupation' => $this->taux_occupation_niveau,
            'capacite_totale' => $this->chambres()->sum('capacite_max'),
            'occupants_actuels' => $this->chambres()->sum('occupants_actuels'),
            'chambres_par_orientation' => $this->chambres()
                ->selectRaw('cote_orientation, count(*) as total')
                ->whereNotNull('cote_orientation')
                ->groupBy('cote_orientation')
                ->pluck('total', 'cote_orientation'),
        ];
    }
}