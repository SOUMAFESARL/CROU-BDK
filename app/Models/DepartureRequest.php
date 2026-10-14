<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class DepartureRequest extends Model {
    protected $primaryKey='id_demande_sortie'; public $incrementing=false; protected $keyType='string'; protected $guarded=[];
    protected $casts=['date_demande'=>'date','date_sortie_prevue'=>'date','date_decision'=>'datetime'];
    protected static function booted():void{static::creating(fn($m)=>$m->id_demande_sortie??=(string)Str::uuid());}

    public function etudiant(){return $this->belongsTo(Etudiant::class,'etudiant_id','id_etudiant');}
    public function assignation(){return $this->belongsTo(AssignationChambre::class,'assignation_id','id_assignation');}
    public function chambre(){return $this->belongsTo(Chambre::class,'chambre_id','id_chambre');}
    public function settlement(){return $this->hasOne(DepartureSettlement::class,'demande_sortie_id','id_demande_sortie');}
}
