<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SanctionHistory extends Model
{
    protected $table = 'sanction_histories';
    protected $primaryKey = 'id_historique';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(fn (SanctionHistory $history) => $history->id_historique ??= (string) Str::uuid());
    }

    public function sanction()
    {
        return $this->belongsTo(Sanction::class, 'sanction_id', 'id_sanction');
    }
}
