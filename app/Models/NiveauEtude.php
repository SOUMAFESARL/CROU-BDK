<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class NiveauEtude extends Model
{
    protected  $guarded= [];
    protected $primaryKey = 'id_niveau_etude';

    protected  $table= 'niveau_etude';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($niveau) {
            if (empty($niveau->{$niveau->getKeyName()})) {
                $niveau->{$niveau->getKeyName()} = (string) Str::uuid();
            }

            if (empty($niveau->created_by)) {
                $niveau->created_by = getInfoAdmin()->users->id_user ?? null;
            }

        });

        static::creating(function ($niveau) {


            if (empty($niveau->updated_by)) {
                $niveau->updated_by = getInfoAdmin()->users->id_user ?? null;
            }

        });
    }
}