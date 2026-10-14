<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Maintenancier extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'maintenanciers';
    protected $primaryKey = 'id_maintenancier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'tel',
        'email',
        'specialite',
        'cite_id',
        'notes',
        'adresse',
        'statut',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'statut' => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id_maintenancier) {
                $model->id_maintenancier = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = getInfoAdmin()->users->id_user ?? null;

            }
            // $model->numero_paiement = self::genererNumeroPaiement();
            //  $model->reference_transaction = self::genererId();
            $model->created_at = now();
        });
        static::updating(function ($model) {


            if (empty($model->updated_by)) {
                $model->updated_by = getInfoAdmin()->users->id_user ?? null;
            }
            $model->updated_at = now();

        });
        static::deleting(function ($model) {


            if (empty($model->deleted_by)) {
                $model->deleted_by = getInfoAdmin()->users->id_user ?? null;
            }
            $model->deleted_at = now();
        });
    }
    public function cite()
    {
        return $this->belongsTo(Cite::class, 'cite_id', 'id_cite');
    }
    public const SPECIALITE = [
        'electricite','plomberie','menuiserie','climatisation','maconnerie','peinture','multiservices'
    ];

    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([

            'cite',


        ]);
    }
}