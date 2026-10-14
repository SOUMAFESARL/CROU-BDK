<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Ville extends Model
{
    protected  $guarded= [];

    protected  $table= 'villes';

    public function regions()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

}