<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Type de sanction du paramétrage (`types_sanction`) : libellé, points retirés à la note de conduite et effet.
 * Le code est la valeur enregistrée dans `sanctions.type`. Géré par `App\Http\Services\CatalogueSanctions`.
 */
class TypeSanction extends Model
{
    protected $table = 'types_sanction';

    protected $primaryKey = 'id_type_sanction';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'points_retires' => 'float',
        'actif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn (TypeSanction $type) => $type->id_type_sanction ??= (string) Str::uuid());
    }

    public function sanctions()
    {
        return $this->hasMany(Sanction::class, 'type', 'code');
    }
}
