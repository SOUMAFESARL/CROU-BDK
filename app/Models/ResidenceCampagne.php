<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ResidenceCampagne extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_residence_campagne';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $table = 'residence_campagne';

    protected $casts = [

        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    protected static function booted()
    {
        // Avant la création, générer le slug si non fourni
        static::creating(function ($residenceCampagne) {


            if (empty($model->{$residenceCampagne->getKeyName()})) {
                $residenceCampagne->{$residenceCampagne->getKeyName()} = (string) Str::uuid();
            }

            // Ajouter l'utilisateur connecté comme créateur si non défini

        });

        // Avant la mise à jour, mettre à jour le slug si le nom change

    }
    public function cites()
    {
        return $this->hasMany(Cite::class,'id_cite','cite_id');
    }
}