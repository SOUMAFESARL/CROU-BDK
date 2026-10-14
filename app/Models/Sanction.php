<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Sanction extends Model
{
    protected $primaryKey = 'id_sanction';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    protected $casts = [
        'date_decision' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sanction $sanction) {
            $sanction->id_sanction ??= (string) Str::uuid();
            $sanction->numero_sanction ??= 'SAN-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        });
    }

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'etudiant_id', 'id_etudiant');
    }

    public function cite()
    {
        return $this->belongsTo(Cite::class, 'cite_id', 'id_cite');
    }

    public function batiment()
    {
        return $this->belongsTo(Batiment::class, 'batiment_id', 'id_batiment');
    }

    public function chambre()
    {
        return $this->belongsTo(Chambre::class, 'chambre_id', 'id_chambre');
    }

    public function historique()
    {
        return $this->hasMany(SanctionHistory::class, 'sanction_id', 'id_sanction')->orderByDesc('created_at');
    }
}
