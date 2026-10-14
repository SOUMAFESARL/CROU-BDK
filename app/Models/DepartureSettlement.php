<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class DepartureSettlement extends Model {
    protected $primaryKey='id_decompte'; public $incrementing=false; protected $keyType='string'; protected $guarded=[];
    protected $casts=['retenues_degradations'=>'array','penalite_preavis'=>'decimal:2','total_degradations'=>'decimal:2','total_retenues'=>'decimal:2','impute_caution'=>'decimal:2','caution_disponible'=>'decimal:2','caution_a_restituer'=>'decimal:2','caution_restituee'=>'decimal:2','solde_debiteur'=>'decimal:2','date_restitution'=>'datetime'];
    protected static function booted():void{static::creating(fn($m)=>$m->id_decompte??=(string)Str::uuid());}
}
