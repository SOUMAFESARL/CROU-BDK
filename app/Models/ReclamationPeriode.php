<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Période pendant laquelle les étudiants peuvent déposer une réclamation sur leur demande d'une campagne. */
class ReclamationPeriode extends Model
{
    protected $table = 'reclamation_periodes';

    protected $primaryKey = 'id_periode';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $periode) {
            if (empty($periode->id_periode)) {
                $periode->id_periode = (string) Str::uuid();
            }
        });
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(Campagne::class, 'campagne_id', 'id_campagne');
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(Reclamation::class, 'periode_id', 'id_periode');
    }

    /** Périodes ouvertes aujourd'hui (bornes comprises) dont la campagne existe toujours. */
    public function scopeOuvertes($query)
    {
        return $query->whereDate('date_debut', '<=', today())
            ->whereDate('date_fin', '>=', today())
            ->whereHas('campagne');
    }

    /** `ouverte`, `a_venir` ou `terminee`, selon la date du jour. */
    public function etat(): string
    {
        return match (true) {
            today()->lt($this->date_debut) => 'a_venir',
            today()->gt($this->date_fin) => 'terminee',
            default => 'ouverte',
        };
    }
}
