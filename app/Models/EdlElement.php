<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EdlElement extends Model
{
    protected $table      = 'edl_element';
    protected $primaryKey = 'edl_element_id';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'edl_element_id',
        'id_edl_piece',
        'name_element',
        'etat',
        'observation',
        'photo',
        'ordre',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return \App\Http\Services\EdlPhotoService::url($this->photo);
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
    /*  Scopes                                                              */
    /* ------------------------------------------------------------------ */

    public function scopeBon($query)    { return $query->where('etat', 'bon');     }
    public function scopeMoyen($query)  { return $query->where('etat', 'moyen');   }
    public function scopeMauvais($query){ return $query->where('etat', 'mauvais'); }
}
