<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EdlPiece extends Model
{
    protected $table      = 'edl_piece';
    protected $primaryKey = 'edl_piece_id';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'edl_piece_id',
        'id_etat_des_lieux',
        'name_piece',
        'ordre',
        'created_by',
        'updated_by',
    ];

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

    public function etatDesLieux()
    {
        return $this->belongsTo(EtatDesLieux::class, 'id_etat_des_lieux', 'etat_des_lieux_id');
    }

    public function elements()
    {
        return $this->hasMany(EdlElement::class, 'id_edl_piece', 'edl_piece_id')
            ->orderBy('ordre');
    }

    public function images()
    {
        return $this->hasMany(EdlImage::class, 'id_edl_piece', 'edl_piece_id');
    }
}
