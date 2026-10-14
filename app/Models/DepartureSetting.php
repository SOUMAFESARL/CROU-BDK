<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartureSetting extends Model
{
    protected $guarded = [];
    protected $casts = ['damage_retention_grid'=>'array','late_notice_penalty'=>'decimal:2','unpaid_blocking_threshold'=>'decimal:2'];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'notice_days'=>30,
            'late_notice_penalty'=>0,
            'unpaid_blocking_threshold'=>0,
            'damage_retention_grid'=>[],
        ]);
    }
}
