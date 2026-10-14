<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EtatDesLieux extends Model
{
    protected $table      = 'etat_des_lieux';
    protected $primaryKey = 'etat_des_lieux_id';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'etat_des_lieux_id',
        'id_cite',
        'id_batiment',
        'id_chambre',
        'id_etudiant',
        'type_etat',
        'date_etat',
        'nbre_cle_recus',
        'observation_generale',
        'signature_crou',
        'signature_etudiant',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_etat'  => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Auto-génération de l'UUID avant création
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = getInfoAdmin()->users->id_user ?? null;
            }
        });

        static::updating(function ($model) {


            if (empty($model->updated_by)) {
                $model->updated_by = getInfoAdmin()->users->id_user ?? null;
            }

        });
    }

    /* ------------------------------------------------------------------ */
    /*  Relations                                                           */
    /* ------------------------------------------------------------------ */

    public function pieces()
    {
        return $this->hasMany(EdlPiece::class, 'id_etat_des_lieux', 'etat_des_lieux_id')
            ->orderBy('ordre');
    }

    public function cite()
    {
        return $this->belongsTo(Cite::class, 'id_cite', 'id_cite');
    }

    public function batiment()
    {
        return $this->belongsTo(Batiment::class, 'id_batiment', 'id_batiment');
    }

    public function chambre()
    {
        return $this->belongsTo(Chambre::class, 'id_chambre', 'id_chambre');
    }

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'id_etudiant', 'id_etudiant');
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                              */
    /* ------------------------------------------------------------------ */

    public function scopeEntree($query)
    {
        return $query->where('type_etat', 'entree');
    }

    public function scopeSortie($query)
    {
        return $query->where('type_etat', 'sortie');
    }

    public function scopePourChambre($query, string $idChambre)
    {
        return $query->where('chambre_id', $idChambre);
    }

    public function scopePourEtudiant($query, string $idEtudiant)
    {
        return $query->where('etudiant_id', $idEtudiant);
    }

    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([
            'pieces',
            'cite',
            'batiment',
            'chambre',
            'etudiant',
            'pieces.elements',
            'pieces.images',
        ]);
    }
}
