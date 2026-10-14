<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AssignationChambre extends Model
{
    protected $table = 'assignation_chambres';
    protected $primaryKey = 'id_assignation';
    public $incrementing = false;
    /**
     * Le type de la clé primaire.
     *
     * @var string
     */
    protected $keyType = 'string';

    protected $fillable = [
        'etudiant_id', 'chambre_id', 'campagne_id', 'lit_id',
        'date_assignation', 'date_fin', 'statut',
        'type_assignation', 'commentaires'
    ];

    protected $casts = [
        'date_assignation' => 'datetime',
        'date_fin' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'étudiant
     */
    public function dossier()
    {
        return $this->hasOne(DemandeEtudiant::class, 'assignation_id', 'id_assignation');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'etudiant_id', 'id_etudiant');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($demande) {
            if (empty($demande->{$demande->getKeyName()})) {
                $demande->{$demande->getKeyName()} = (string) Str::uuid();
            }

            if (empty($demande->created_by)) {
                $demande->created_by = getInfoAdmin()->users->code ?? null;
            }

        });
    }

    /**
     * Relation avec la chambre
     */
    public function lit()
    {
        return $this->belongsTo(Lit::class, 'lit_id');
    }

    public function chambre()
    {
        return $this->belongsTo(Chambre::class, 'chambre_id', 'id_chambre');
    }

    /**
     * Relation avec la campagne
     */
    public function campagne()
    {
        return $this->belongsTo(Campagne::class, 'campagne_id', 'id_campagne');
    } 

   /**
     * Tous les loyers associés à cette assignation (historique)
     */
    public function loyers()
    {
        return $this->hasMany(Loyer::class,  'assignation_id','id_assignation');
    }

    /**
     * Le loyer actif actuel
     */
    public function loyer()
    {
        return $this->hasOne(Loyer::class, 'assignation_id','id_assignation')
            ->whereIn('statut', ['actif', 'en_retard'])
            ->latest();
    }

    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([
            'campagne',
            'chambre',
            'chambre.typeChambre',
            'chambre.niveau_aile',
            'chambre.batiment',
            'chambre.campus',
            'chambre.cite',


        ]);
    }
    /**
     * Vérifier si l'assignation est active
     */
    public function estActive(): bool
    {
        return $this->statut === 'confirmee' &&
            (!$this->date_fin || $this->date_fin->isFuture());
    }

    /**
     * Confirmer une assignation
     */
    public function confirmer(): bool
    {
        $this->statut = 'confirmee';
        $this->date_assignation = now();
        return $this->save();
    }

    /**
     * Annuler une assignation
     */
    public function annuler($motif = null): bool
    {
        $this->statut = 'annulee';
        $this->date_fin = now();
        if ($motif) {
            $this->commentaires = $motif;
        }
        return $this->save();
    }

    /**
     * Scope: Assignations confirmées
     */
    public function scopeConfirmees($query)
    {
        return $query->where('statut', 'confirmee'); //'confirmee'
    }

    /**
     * Scope: Assignations en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope: Assignations actives
     */
    public function scopeMobilisantPlace($query)
    {
        // Une proposition non expiree reserve deja une place, meme avant l'entree.
        return $query->whereIn('statut', ['en_attente', 'confirmee'])
            ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>', now()));
    }

    public function scopeActives($query)
    {
        return $query->where('statut', 'confirmee')
            ->where('date_assignation', '<=', now())
            ->where(function ($q) {
                $q->whereNull('date_fin')
                    ->orWhere('date_fin', '>', now());
            });
    }

    /**
     * Scope: Par campagne
     */
    public function scopeParCampagne($query, $campagneId)
    {
        return $query->where('campagne_id', $campagneId);
    }

    /**
     * Scope: Par chambre
     */
    public function scopeParChambre($query, $chambreId)
    {
        return $query->where('chambre_id', $chambreId);
    }

    /**
     * Scope: Par étudiant
     */
    public function scopeParEtudiant($query, $etudiantId)
    {
        return $query->where('etudiant_id', $etudiantId);
    }

    /**
     * Obtenir le label du statut
     */
    public function getLabelStatut(): string
    {
        $statuts = [
            'en_attente' => 'En Attente',
            'confirmee' => 'Confirmée',
            'annulee' => 'Annulée',
            'expulsion' => 'Expulsion',
        ];

        return $statuts[$this->statut] ?? ucfirst($this->statut);
    }

    /**
     * Obtenir le type d'assignation en français
     */
    public function getLabelType(): string
    {
        $types = [
            'automatique' => 'Assignation Automatique',
            'manuelle' => 'Assignation Manuelle',
            'transfert' => 'Transfert',
            'reaffectation' => 'Réaffectation',
        ];

        return $types[$this->type_assignation] ?? ucfirst($this->type_assignation);
    }
}
