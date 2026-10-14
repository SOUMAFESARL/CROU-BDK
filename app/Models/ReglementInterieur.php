<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class ReglementInterieur extends Model
{
    protected  $guarded= [];

    protected  $table= 'reglement_interieur';
    protected $primaryKey = 'id_reglement';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reglement) {
            if (empty($reglement->{$reglement->getKeyName()})) {
                $reglement->{$reglement->getKeyName()} = (string) Str::uuid();
            }

            if (empty($reglement->created_by)) {
                $reglement->created_by = getInfoAdmin()->users->id_user ?? null;
            }

        });

        static::creating(function ($reglement) {


            if (empty($reglement->updated_by)) {
                $reglement->updated_by = getInfoAdmin()->users->id_user ?? null;
            }

        });
    }
}