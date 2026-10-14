<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Equipement extends Model
{
    use HasFactory;

    protected $table = 'equipement';
    protected $primaryKey = 'equipement_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];


    public function type_equipement()
    {
        return $this->belongsTo(TypeEquipement::class, 'type_equipement_id','id_type_equipement');
    }
    public function mouvements()
    {
        return $this->hasMany(EquipementMouvement::class, 'equipement_id','equipement_id');
    }

    public function affectations()
    {
        return $this->hasMany(EquipementAffectation::class, 'equipement_id', 'equipement_id');
    }


    // Calculs de stock
    public function getStockActuelAttribute(): float
    {
        $movements = $this->relationLoaded('mouvements') ? $this->mouvements : $this->mouvements()->get();
        return $movements->isEmpty() ? (float) ($this->attributes['stock_initial'] ?? 0)
            : (float) ($movements->where('type', 'entry')->sum('quantity') - $movements->where('type', 'exit')->sum('quantity'));
    }

    public function updateStockActuel(): void
    {
        $total_entrees = $this->mouvements()->where('type', 'entry')->sum('quantity');
        $total_sorties = $this->mouvements()->where('type', 'exit')->sum('quantity');

        $this->attributes['stock_actuel'] = $total_entrees - $total_sorties;
        $this->unsetRelation('mouvements');
        $this->saveQuietly(); // Sauvegarde sans déclencher d'events
    }


    // Générer automatiquement un UUID
    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->equipement_id) {
                $model->equipement_id = (string) \Illuminate\Support\Str::uuid();
            }
            $model->reference = self::generateReference($model->name);
        });
    }

    public function setStockStatus(): void
    {
        $this->stock_status = match(true) {
            $this->stock_actuel <= 0 => [
                'class' => 'text-danger',
                'icon' => 'fas fa-times-circle',
                'text' => 'Rupture'
            ],
            $this->stock_actuel <= $this->threshold => [
                'class' => 'text-warning',
                'icon' => 'fas fa-exclamation-triangle',
                'text' => 'Stock faible'
            ],
            default => [
                'class' => 'text-success',
                'icon' => 'fas fa-check-circle',
                'text' => 'Disponible'
            ]
        };
    }
    public static function generateReference($name)
    {
        // 1. Nettoyage de la chaîne
        $cleaned = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);

        // 2. Extraction des mots clés
        $words = explode(' ', $cleaned);
        $acronym = '';

        // 3. Génération de l'acronyme (3 premières lettres de chaque mot)
        foreach ($words as $word) {
            $acronym .= strtoupper(substr($word, 0, 3)) . '-';
        }

        // 4. Extraction des nombres (ex: 15 dans "15mm")
        preg_match('/\d+/', $name, $matches);
        $numbers = $matches[0] ?? '';

        // 5. Construction finale
        $reference = rtrim($acronym, '-') . ($numbers ? '-' . $numbers : '');

        // 6. Garantie d'unicité
        $baseRef = $reference;
        $count = 1;

        while (self::where('reference', $reference)->exists()) {
            $reference = $baseRef . '-' . $count;
            $count++;
        }

        return $reference;
    }
}
