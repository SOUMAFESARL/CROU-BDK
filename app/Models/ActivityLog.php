<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    protected $guarded = [];
    protected $casts = ['context' => 'array'];
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (ActivityLog $log) {
            $log->id ??= (string) Str::uuid();
        });
    }
}
