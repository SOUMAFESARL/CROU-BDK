<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TypeChambre extends Model
{
    use HasFactory;

    protected $table = 'type_chambre';
    protected $primaryKey = 'id_type_chambre';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    // Générer automatiquement un UUID
    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id_type_chambre) {
                $model->id_type_chambre = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public static function capaciteChambre($id)
    {
        $type = TypeChambre::where('id_type_chambre', $id)->first();

        if (!$type) {
            throw new \Exception("Type de chambre non trouvé avec l'ID: {$id}");
        }

        return match($type->name) {
            'Individuelle' => 1,
            'Double' => 2,
            'Triple' => 3,
            default => 1 // valeur par défaut
        };
    }

    public static function montantChambre($id)
    {
        $type = TypeChambre::where('id_type_chambre', $id)->first();

        if (!$type) {
            throw new \Exception("Type de chambre non trouvé avec l'ID: {$id}");
        }

        return $type->montant;
    }
    public static function cautionChambre($id)
    {
        $type = TypeChambre::where('id_type_chambre', $id)->first();

        if (!$type) {
            throw new \Exception("Type de chambre non trouvé avec l'ID: {$id}");
        }

        return $type->caution;
    }
    public static function avanceChambre($id)
    {
        $type = TypeChambre::where('id_type_chambre', $id)->first();

        if (!$type) {
            throw new \Exception("Type de chambre non trouvé avec l'ID: {$id}");
        }

        return $type->avance;
    }
    public static function equipementChambre($id)
    {
        $type = TypeChambre::where('id_type_chambre', $id)->first();

        if (!$type) {
            throw new \Exception("Type de chambre non trouvé avec l'ID: {$id}");
        }

        return $type->equipement;
    }
    public static function superficieChambre($id)
    {
        $type = TypeChambre::where('id_type_chambre', $id)->first();

        if (!$type) {
            throw new \Exception("Type de chambre non trouvé avec l'ID: {$id}");
        }

        return $type->superficie;
    }
}
