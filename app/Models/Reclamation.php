<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Réclamation d'un étudiant sur sa demande d'hébergement (formulaire public ouvert pendant une `ReclamationPeriode`). */
class Reclamation extends Model
{
    public const EN_ATTENTE = 'en_attente';

    public const OUVERTE = 'ouverte';

    public const ACCEPTEE = 'acceptee';

    public const REJETEE = 'rejetee';

    public const STATUTS = [
        self::EN_ATTENTE => 'En attente',
        self::OUVERTE => 'Ouverte',
        self::ACCEPTEE => 'Acceptée',
        self::REJETEE => 'Rejetée',
    ];

    /** Statuts choisis par l'administration (« En attente » est aussi le statut de dépôt). */
    public const STATUTS_TRAITEMENT = [self::OUVERTE, self::EN_ATTENTE, self::ACCEPTEE, self::REJETEE];

    /** Types de demande (volets d'une campagne, `CriteresCampagne::VOLETS`). */
    public const TYPES = ['admission' => 'Admission', 'readmission' => 'Réadmission'];

    protected $table = 'reclamations';

    protected $primaryKey = 'id_reclamation';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['traite_at' => 'datetime'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $reclamation) {
            if (empty($reclamation->id_reclamation)) {
                $reclamation->id_reclamation = (string) Str::uuid();
            }
            if (empty($reclamation->numero)) {
                do {
                    $numero = sprintf('REC-%s-%05d', date('Y'), random_int(0, 99999));
                } while (self::query()->where('numero', $numero)->exists());
                $reclamation->numero = $numero;
            }
        });
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(ReclamationPeriode::class, 'periode_id', 'id_periode');
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(Campagne::class, 'campagne_id', 'id_campagne')->withTrashed();
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeEtudiant::class, 'demande_id', 'id_demande');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'etudiant_id', 'id_etudiant');
    }

    /** Réclamations pas encore tranchées. */
    public function scopeNonTraitees($query)
    {
        return $query->whereIn('statut', [self::EN_ATTENTE, self::OUVERTE]);
    }
}
