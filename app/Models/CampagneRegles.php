<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Version du jeu de critères d'un volet (`volet` : admission | readmission) d'une campagne (règles + places par cité).
 * Géré uniquement par `App\Http\Services\Eligibilite\CriteresCampagne`.
 */
class CampagneRegles extends Model
{
    public const BROUILLON = 'brouillon';

    public const EN_VIGUEUR = 'en_vigueur';

    public const ARCHIVEE = 'archivee';

    protected $table = 'campagne_regles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'version' => 'integer',
        'parametres' => 'array',
        'places' => 'array',
        'applique_le' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CampagneRegles $regles) {
            $regles->id ??= (string) Str::uuid();
        });
    }

    public function campagne()
    {
        return $this->belongsTo(Campagne::class, 'campagne_id', 'id_campagne');
    }
}
