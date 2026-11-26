<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class TypePiece extends Model
{
    protected  $guarded= [];


    protected  $table= 'type_piece';

    /** La table n'a pas de colonnes created_at / updated_at. */
    public $timestamps = false;



}