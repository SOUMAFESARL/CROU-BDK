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

    /** Ordre d'affichage défini dans le paramétrage (flèches), puis le nom pour départager. */
    public function scopeOrdonnes($query)
    {
        return $query->orderBy('ordre')->orderBy('name');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($niveau) {
            if (empty($niveau->{$niveau->getKeyName()})) {
                $niveau->{$niveau->getKeyName()} = (string) Str::uuid();
            }

            // Un nouveau niveau se place en dernier.
            if (empty($niveau->ordre)) {
                $niveau->ordre = (int) static::query()->max('ordre') + 1;
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