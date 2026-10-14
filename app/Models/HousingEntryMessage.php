<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HousingEntryMessage extends Model {
    protected $guarded=[];
    protected $casts=['details'=>'array','sent_at'=>'datetime','read_at'=>'datetime'];
}
