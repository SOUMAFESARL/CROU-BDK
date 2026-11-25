<?php

namespace App\Models;

use App\Http\Services\Eligibilite\ResultatsAdmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Réclamation d'un étudiant sur sa demande d'hébergement (formulaire public ouvert pendant une `ReclamationPeriode`). */
class Reclamation extends Model
{
    public const EN_ATTENTE = 'en_attente';

    public const ACCEPTEE = 'acceptee';

    public const REJETEE = 'rejetee';

    public const STATUTS = [
        self::EN_ATTENTE => 'En attente',
        self::ACCEPTEE => 'Favorable',
        self::REJETEE => 'Non recevable',
    ];

    /** Décisions de l'administration (« En attente », statut de dépôt, reste tant qu'aucune n'est prise). */
    public const STATUTS_TRAITEMENT = [self::ACCEPTEE, self::REJETEE];

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

    /** Avec le résultat publié de la demande (`resultat_demande`, null s'il n'est pas publié). */
    public function scopeAvecResultat($query)
    {
        return app(ResultatsAdmission::class)->disponible()
            ? $query->addSelect(['reclamations.*', 'resultat_demande' => DB::table(ResultatsAdmission::TABLE)->select('resultat')
                ->whereColumn(ResultatsAdmission::TABLE.'.demande_id', 'reclamations.demande_id')->limit(1)])
            : $query;
    }

    /** Réclamations pas encore tranchées. */
    public function scopeNonTraitees($query)
    {
        return $query->where('statut', self::EN_ATTENTE);
    }
}
