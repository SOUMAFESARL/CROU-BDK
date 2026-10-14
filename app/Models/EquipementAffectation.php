<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EquipementAffectation extends Model
{
    use HasUuids;
    protected $table = 'equipement_affectations';
    protected $guarded = [];
    protected $casts = ['returned_at' => 'datetime', 'quantite' => 'integer'];
    public function equipement() { return $this->belongsTo(Equipement::class, 'equipement_id', 'equipement_id'); }
    public function cite() { return $this->belongsTo(Cite::class, 'cite_id', 'id_cite'); }
    public function batiment() { return $this->belongsTo(Batiment::class, 'batiment_id', 'id_batiment'); }
    public function chambre() { return $this->belongsTo(Chambre::class, 'chambre_id', 'id_chambre'); }
}
