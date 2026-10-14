<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DisciplinaryReportHistory extends Model
{
    protected $table = 'disciplinary_report_histories';
    protected $primaryKey = 'id_historique';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['created_at' => 'datetime'];
    protected static function booted(): void { static::creating(fn ($item) => $item->id_historique ??= (string) Str::uuid()); }
}
