<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EquipementMouvement extends Model
{
    use HasFactory;

    protected $table = 'equipement_mouvement';
    protected $primaryKey = 'equipement_mouvement_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];


    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id','equipement_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by','code');
    }

    // Générer automatiquement un UUID
    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->equipement_mouvement_id) {
                $model->equipement_mouvement_id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
