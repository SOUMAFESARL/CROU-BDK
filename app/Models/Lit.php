<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lit extends Model
{
    protected $table = 'lits';
    protected $guarded = [];

    public function chambre()
    {
        return $this->belongsTo(Chambre::class, 'chambre_id', 'id_chambre');
    }

    public function assignations()
    {
        return $this->hasMany(AssignationChambre::class, 'lit_id');
    }
}
