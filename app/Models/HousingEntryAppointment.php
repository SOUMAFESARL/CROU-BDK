<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HousingEntryAppointment extends Model {
    protected $guarded=[];
    protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime'];
    public function student(){return $this->belongsTo(Etudiant::class,'student_id','id_etudiant');}
}
