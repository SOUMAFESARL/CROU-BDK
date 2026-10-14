<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionDerogation extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['eligibility' => 'array', 'decided_at' => 'datetime'];
}
