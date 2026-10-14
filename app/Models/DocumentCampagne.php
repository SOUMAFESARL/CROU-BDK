<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentCampagne extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_document_campagne';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $table = 'document_campagne';

    protected $casts = [

        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    protected static function booted()
    {
        // Avant la création, générer le slug si non fourni
        static::creating(function ($documentCampagne) {


            if (empty($model->{$documentCampagne->getKeyName()})) {
                $documentCampagne->{$documentCampagne->getKeyName()} = (string) Str::uuid();
            }

            // Ajouter l'utilisateur connecté comme créateur si non défini

        });

        // Avant la mise à jour, mettre à jour le slug si le nom change

    }
    public function documents()
    {
        return $this->hasMany(Document::class, 'id_document','document_id');
    }
}