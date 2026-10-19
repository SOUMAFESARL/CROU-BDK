<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Chambre extends Model
{
    public function equipementAffectations()
    {
        return $this->hasMany(EquipementAffectation::class, 'chambre_id', 'id_chambre');
    }
    public const ETATS = [
        'disponible' => 'Disponible',
        'occupee' => 'Occupée',
        'reservee' => 'Réservée',
        'maintenance' => 'En maintenance',
        'hors_service' => 'Hors service',
        'fermee' => 'Fermée',
    ];

    use HasFactory , SoftDeletes;
    protected $primaryKey = 'id_chambre';
    protected  $guarded= [];
    public $incrementing = false;
    protected  $table= 'chambre';
    public $timestamps = true;
    protected $keyType = 'string';


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($chambre) {
            if (empty($chambre->{$chambre->getKeyName()})) {
                $chambre->{$chambre->getKeyName()} = (string) Str::uuid();
            }

            if (empty($chambre->created_by)) {
                $chambre->created_by = getInfoAdmin()->users->id_user ?? null;
            }

            if (empty($chambre->campus_id)) {
                $chambre->campus_id = getInfoAdmin()->users->campus_id ?? null;
            }

        });

        static::updating(function ($cite) {
            if ($cite->isDirty('capacite_max') && (int) $cite->capacite_max < (int) $cite->getOriginal('capacite_max')) {
                $active = $cite->assignations()->mobilisantPlace()->with('lit')->get();
                if ($active->count() > (int) $cite->capacite_max
                    || (int) $active->max('lit.numero') > (int) $cite->capacite_max) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'capacite_max' => 'Un lit concerné est occupé ou réservé. Réaffectez son occupant avant de réduire la capacité.',
                    ]);
                }
            }
            if (empty($cite->updated_by)) {
                $cite->updated_by = getInfoAdmin()->users->id_user ?? null;
            }

        });

        static::saved(function ($room) {
            if ($room->numero_chambre && (int) $room->capacite_max > 0
                && ($room->wasRecentlyCreated || $room->wasChanged('capacite_max'))) {
                app(\App\Http\Services\LitService::class)->synchroniser($room);
            }
        });
    }


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by','code');
    }
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by','code');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by','code');
    }
    public function batiment()
    {
        return $this->belongsTo(Batiment::class, 'batiment_id','id_batiment');
    }

    public function aile()
    {
        return $this->belongsTo(Aile_Batiment::class, 'code_aile','code_aile');
    }

    public function cite()
    {
        return $this->belongsTo(Cite::class, 'cite_id','id_cite');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id','id_campus');
    }


    public function niveau_aile()
    {
        // ✅ Utilisez le bon nom de modèle
        return $this->belongsTo(Niveau_Aile_Batiment::class, 'niveau_aile_code', 'niveau_aile_code');
    }
    public function typeOccupation()
    {
        return $this->belongsTo(TypeCite::class, 'type_occupation_id', 'id_type_cite');
    }
    public function typeChambre()
    {
        return $this->belongsTo(TypeChambre::class, 'type_chambre_id', 'id_type_chambre');
    }
    /**
     * Relation avec les assignations
     */
    public function lits()
    {
        return $this->hasMany(Lit::class, 'chambre_id', 'id_chambre')->orderBy('numero');
    }

    public function assignations()
    {
        return $this->hasMany(AssignationChambre::class, 'chambre_id', 'id_chambre');
    }
    /**
     * Obtenir le nombre de places occupées
     */
    public function getNombrePlacesOccupees(): int
    {
        $query = $this->assignations()->actives();
        return $this->getConnection()->transactionLevel() > 0
            ? $query->lockForUpdate()->get()->count() : $query->count();
    }

    /**
     * Obtenir le nombre de places disponibles
     */
    public function getNombrePlacesDisponibles(): int
    {

        return max(0, (int) $this->capacite_max - $this->assignations()->mobilisantPlace()->count());
    }
    /**
     * Vérifier si la chambre peut accueillir un étudiant supplémentaire
     */
    public function peutAccueillir(): bool
    {
        return $this->estActivePourAttribution()
            && $this->etat === 'disponible' && $this->getNombrePlacesDisponibles() > 0;
    }

    /**
     * Vérifier si la chambre est pleine
     */
    public function estActivePourAttribution(): bool
    {
        return $this->statut === 'actif'
            && $this->batiment?->statut === 'actif'
            && $this->cite?->estActive()
            && (string) $this->batiment->cite_id === (string) $this->cite_id;
    }

    public function actualiserOccupation(): void
    {
        $occupants = $this->getNombrePlacesOccupees();
        $data = ['occupants_actuels' => $occupants];
        // Préserver les réservations manuelles et les blocages techniques.
        if (in_array($this->etat, ['disponible', 'occupee'], true)) {
            $data['etat'] = $occupants >= (int) $this->capacite_max ? 'occupee' : 'disponible';
        }
        $this->update($data);
    }

    public function estPleine(): bool
    {
        return $this->getNombrePlacesOccupees() >= $this->capacite_max;
    }

    /** À appeler après la clôture du séjour, sous le verrou de chambre. */
    public function appliquerEtatApresDepart(string $etat): void
    {
        if (!in_array($etat, ['disponible', 'maintenance', 'hors_service'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'etat_chambre_sortie' => 'État de sortie invalide.',
            ]);
        }
        if ($etat !== 'disponible' && !in_array($this->etat, ['hors_service', 'fermee'], true)) {
            $this->update(['etat' => $etat]);
        }
        $this->actualiserOccupation();
    }

    /**
     * Obtenir le pourcentage d'occupation
     */
    public function getPourcentageOccupation(): float
    {
        if ($this->capacite_max == 0) {
            return 0;
        }

        return round(($this->getNombrePlacesOccupees() / $this->capacite_max) * 100, 2);
    }

    /**
     * Scope: chambres réellement configurées (numéro et type renseignés), comme `ChambreRepository::getAll()`.
     * Base commune des statistiques du parc immobilier et de son reporting.
     */
    public function scopeConfigurees($query)
    {
        return $query->whereNotNull('numero_chambre')->whereNotNull('type_chambre_id');
    }

    /**
     * Scope: Chambres disponibles
     * @param $query
     * @param $campagne - Objet Campagne
     */
    public function scopeDisponibles($query, $campagneId)
    {


        // Si c'est un UUID string
        if (is_string($campagneId)) {
            $campagne = Campagne::where('id_campagne', $campagneId)->first(); // ou 'uuid' selon votre colonne
        }else{
            $campagne = $campagneId; // ou 'uuid' selon votre colonne
        }

       // dd($campagne);

        if (!$campagne) {
            return $query->whereRaw('1 = 0');
        }

//dd($campagne->residencesCampagne()->pluck('code_cite'));
        $citesCampagne = $campagne->residencesCampagne()->pluck('cite_id');

        //dd($citesCampagne,'je suis dans le model');
        return $query
            ->whereIn('cite_id', $citesCampagne)
            ->whereNotNull('numero_chambre')
            ->where('etat', 'disponible')
            ->where('statut', 'actif')
            ->whereHas('batiment', fn ($q) => $q->where('statut', 'actif'))
            ->whereHas('cite', fn ($q) => $q->whereIn('statut', Cite::STATUTS_ACTIFS));
    }
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([
            'cite',
            'campus',
            'typeChambre',
            'batiment',
            'niveau_aile',
            'typeOccupation',
            'responsable'
        ]);
    }
    /**
     * Scope: Par type de chambre
     * @param $query
     * @param $type - Type de chambre
     * @param $campagne - Objet Campagne (optionnel)
     */
    public function scopeParType($query, $type, $campagne = null)
    {
        $query->where('type_chambre_id', $type);

        if ($campagne) {
            $citesCampagne = $campagne->residencesCampagne()->pluck('cite_id');
            $query->whereIn('cite_id', $citesCampagne);
        }

        return $query;
    }

    /**
     * Scope: Par bâtiment
     * @param $query
     * @param $codeBatiment
     * @param $campagne - Objet Campagne (optionnel)
     */
    public function scopeParBatiment($query, $codeBatiment, $campagne = null)
    {
        $query->where('batiment_id', $codeBatiment);

        if ($campagne) {
            $citesCampagne = $campagne->residencesCampagne()->pluck('cite_id');
            $query->whereIn('cite_id', $citesCampagne);
        }

        return $query;
    }

    /**
     * Scope: Par cité
     * @param $query
     * @param $codeCite
     * @param $campagne - Objet Campagne (optionnel)
     */
    public function scopeParCite($query, $codeCite, $campagne = null)
    {
        $query->where('cite_id', $codeCite);

        if ($campagne) {
            $citesCampagne = $campagne->residencesCampagne()->pluck('cite_id');
            $query->whereIn('cite_id', $citesCampagne);
        }

        return $query;
    }

    /**
     * Obtenir le label du type de chambre en français
     */
    public function getLabelType(): string
    {
        $types = [
            'individuelle' => 'Chambre Individuelle',
            'double' => 'Chambre Double',
            'triple' => 'Chambre Triple',
            'quad' => 'Chambre Quad (4 lits)',
        ];

        return $types[$this->type_chambre_id] ?? ucfirst($this->type_chambre_id);
    }

    /**
     * Obtenir l'état en français
     */
    public function getLabelEtat(): string
    {
        return self::ETATS[$this->etat] ?? ucfirst($this->etat);
    }

    /**
     * Obtenir les infos clés de la chambre
     */
    public function getInfos(): array
    {

        return [
            'id' => $this->id_chambre,
            'numero' => $this->numero_chambre,
            'code' => $this->code_chambre,
            'type' => $this->type_chambre_id,
            'label_type' => $this->getLabelType(),
            'capacite' => $this->capacite_max,
            'occupes' => $this->getNombrePlacesOccupees(),
            'disponibles' => $this->getNombrePlacesDisponibles(),
            'pourcentage' => $this->getPourcentageOccupation(),
            'prix' => $this->prix,
            'etat' => $this->etat,
            'batiment' => $this->batiment?->name,
        ];
    }
}
