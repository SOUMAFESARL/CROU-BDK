<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Region extends Model
{
    protected  $guarded= [];

    protected  $table= 'regions';

    public function villes()
    {
        return $this->hasMany(Ville::class, 'region_id');
    }
}