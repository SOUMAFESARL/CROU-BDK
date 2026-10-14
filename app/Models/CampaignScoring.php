<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CampaignScoring extends Model {
    protected $guarded=[];
    protected $casts=['weights'=>'array','points'=>'array','minimum_score'=>'float','pmr_first'=>'boolean','active'=>'boolean'];
}
