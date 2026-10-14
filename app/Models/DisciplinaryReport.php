<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DisciplinaryReport extends Model
{
    protected $primaryKey = 'id_rapport';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = ['date_incident' => 'datetime', 'transmis_at' => 'datetime', 'pris_en_charge_at' => 'datetime', 'decision_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function ($report) {
            $report->id_rapport ??= (string) Str::uuid();
            $report->numero_rapport ??= 'RDI-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        });
    }

    public function sanction() { return $this->hasOne(Sanction::class, 'rapport_id', 'id_rapport'); }

    public function etudiant() { return $this->belongsTo(Etudiant::class, 'etudiant_id', 'id_etudiant'); }
    public function cite() { return $this->belongsTo(Cite::class, 'cite_id', 'id_cite'); }
    public function batiment() { return $this->belongsTo(Batiment::class, 'batiment_id', 'id_batiment'); }
    public function chambre() { return $this->belongsTo(Chambre::class, 'chambre_id', 'id_chambre'); }
    public function historique() { return $this->hasMany(DisciplinaryReportHistory::class, 'rapport_id', 'id_rapport')->orderByDesc('created_at'); }
}
