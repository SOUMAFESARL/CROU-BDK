<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Filiere extends Model
{
    protected  $guarded= [];
    protected $primaryKey = 'id_filiere';

    protected  $table= 'filiere';


    public function departement()
    {
        return $this->belongsTo(Departement::class, 'departement_code','code_departement');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by','code');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by','code');
    }
}