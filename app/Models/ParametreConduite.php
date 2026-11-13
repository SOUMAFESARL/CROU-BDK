<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Paramètres de la note de conduite (ligne unique) : note initiale attribuée à chaque étudiant. */
class ParametreConduite extends Model
{
    public const NOTE_INITIALE_DEFAUT = 20;

    protected $table = 'parametres_conduite';

    protected $guarded = [];

    protected $casts = ['note_initiale' => 'float'];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['note_initiale' => self::NOTE_INITIALE_DEFAUT]);
    }
}
