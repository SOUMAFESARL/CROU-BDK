<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Str;

class  Document extends  Model
{
    use HasFactory ;


    protected $table = 'document';
    protected $primaryKey = 'id_document';
    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'champs_requis' => 'boolean',
        'statut' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    protected static function booted()
    {
        // Avant la création, générer le slug si non fourni
        static::creating(function ($document) {
            if (empty($categorie->slug)) {
                $document->slug = self::generateUniqueSlug($document->name);
            }

            if (empty($model->{$document->getKeyName()})) {
                $document->{$document->getKeyName()} = (string) Str::uuid();
            }

            // Ajouter l'utilisateur connecté comme créateur si non défini
            if (empty($document->created_by)) {
                $document->created_by = getInfoAdmin()->users->code;
            }
        });

        // Avant la mise à jour, mettre à jour le slug si le nom change
        static::updating(function ($document) {
            if (empty($document->updated_by)) {
                $document->updated_by = getInfoAdmin()->users->code;    
            }
        });
    }

    public static function generateUniqueSlug($nom)
    {
        $slug = Str::slug($nom);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }
    public function getRequisBadgeAttribute()
    {
        return $this->is_confirme ? 'badge bg-success' : 'badge bg-danger';
    }

    public function getStatutBadgeAttribute()
    {
        return $this->is_confirme ? 'badge bg-success' : 'badge bg-danger';
    }


    public static function  getAllDocuments($statut=null){

        $document = $statut ==null ? Document::all() : Document::where('statut',$statut)->get();
        return $document;
    }

}