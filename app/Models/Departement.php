<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Departement extends Model
{
    protected  $guarded= [];
    protected $primaryKey = 'id_departement';

    protected  $table= 'departement';


    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_code','code');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by','code');
    }
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by','code');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by','code');
    }
}