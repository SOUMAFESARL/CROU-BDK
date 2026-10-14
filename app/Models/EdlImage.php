<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EdlImage extends Model
{
    protected $table      = 'edl_image';
    protected $primaryKey = 'edl_image_id';
    public    $incrementing = false;
    protected $keyType    = 'string';
    protected $appends = ['url'];

    protected $fillable = [
        'edl_image_id',
        'id_edl_piece',
        'chemin_fichier',
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

    public function piece()
    {
        return $this->belongsTo(EdlPiece::class, 'id_edl_piece', 'edl_piece_id');
    }

    /* ------------------------------------------------------------------ */
    /*  Accesseur : URL publique de l'image                                 */
    /* ------------------------------------------------------------------ */

    public function getUrlAttribute(): ?string
    {
        return \App\Http\Services\EdlPhotoService::url($this->chemin_fichier);
    }
}
