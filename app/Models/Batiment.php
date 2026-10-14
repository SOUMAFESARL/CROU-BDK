<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Batiment extends Model
{
    public function equipementAffectations()
    {
        return $this->hasMany(EquipementAffectation::class, 'batiment_id', 'id_batiment');
    }
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id_batiment';
    protected $guarded    = [];
    protected $table      = 'batiment';


    public $incrementing = false;

    public $timestamps = true;
    protected $keyType = 'string';

    // ========================
    // RELATIONS
    // ========================
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batiment) {
            if (empty($batiment->{$batiment->getKeyName()})) {
                $batiment->{$batiment->getKeyName()} = (string) Str::uuid();
            }

            if (empty($batiment->created_by)) {
                $batiment->created_by = getInfoAdmin()->users->id_user ?? null;
            }

            if (empty($batiment->campus_id)) {
                $batiment->campus_id = getInfoAdmin()->users->campus_id ?? null;
            }


        });

        static::updating(function ($batiment) {


            if (empty($batiment->updated_by)) {
                $batiment->updated_by = getInfoAdmin()->users->id_user ?? null;
            }

        });
    }
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id_user');
    }

    public function cite()
    {
        return $this->belongsTo(Cite::class, 'cite_id', 'id_cite');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }
    public function typeOccupation()
    {
        return $this->belongsTo(TypeCite::class, 'type_occupation_id');
    }

    public function chambres(): HasMany
    {
        return $this->hasMany(Chambre::class, 'batiment_id', 'id_batiment')->whereNotNull('numero_chambre')
            ->whereNotNull('type_chambre_id');
    }
    public function chambresDisponibles()
    {
        return $this->hasMany(Chambre::class, 'batiment_id', 'id_batiment')
            ->where('etat', 'disponible')
            ->whereNotNull('numero_chambre');
    }
    public static function withDefaultRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return static::with([

            'chambres',
            'campus',
            'cite',
            'updatedBy',
            'createdBy',
            'deletedBy',
            'responsable',
            'typeOccupation',
            'chambresDisponibles'
        ]);
    }
    // ========================
    // CRÉATION STRUCTURE SIMPLE
    // ========================

    public static function createSimpleStructure($batiment, $batimentData)
    {
        $structureData = $batimentData['structure_data'] ?? [];
        $etages        = $structureData['etages'] ?? [];

        // Calculer le nombre total de niveaux (RDC + étages)
        $nombreEtages = $structureData['nombre_etages'] ?? 1;
        $totalNiveaux = count($etages) > 0 ? count($etages) : ($nombreEtages + 1);

        // ✅ Calculer total_chambres depuis les étages reçus
        $totalChambresEtages = 0;
        foreach ($etages as $etage) {
            $totalChambresEtages += (int)($etage['chambres'] ?? 0);
        }

        $aile = Aile_Batiment::firstOrCreate(
            [
                'batiment_id' => $batiment->id_batiment,
                'numero'        => 1,
            ],
            [
                'code_aile'      => generate('aile_batiment', 'code_aile', 150, 'a-zA-Z0-9'),
                'name'           => 'Aile Principale',
                'nombre_niveaux' => $totalNiveaux,
                'total_chambres' => $totalChambresEtages, // ✅ valeur réelle
                'ordre_display'  => 1,
                'created_by'     => getInfoAdmin()->users->code,
            ]
        );

        // Supprimer les anciens niveaux
        Niveau_Aile_Batiment::where('code_aile', $aile->code_aile)->delete();

        $totalChambresNiveaux = 0;

        // ✅ Cas 1 : données d'étages détaillées envoyées par le frontend
        if (!empty($etages)) {
            foreach ($etages as $etage) {
                $niveauNumero   = (int)($etage['etage_numero']   ?? 0);
                $chambresNiveau = (int)($etage['chambres']        ?? 0);  // clé envoyée par le frontend
                $typeOccupation = $etage['type_occupation']        ?? $batiment->type_occupation;

                Niveau_Aile_Batiment::create([
                    'code_aile'       => $aile->code_aile,
                    'niveau_aile_code'=> generate('niveau_aile_batiment', 'niveau_aile_code', 150, 'a-zA-Z0-9'),
                    'niveau_numero'   => $niveauNumero,
                    'type_occupation' => $typeOccupation,
                    'total_chambres'  => $chambresNiveau, // ✅ stocké en BDD pour createChambresSimple
                    'created_by'      => getInfoAdmin()->users->id_user,
                ]);

                $totalChambresNiveaux += $chambresNiveau;
            }
        }
        // ✅ Cas 2 : fallback — répartition automatique
        else {
            $niveauxCount  = (int)($batimentData['total_niveaux'] ?? 1);
            $totalChambres = (int)($batimentData['total_chambres'] ?? 0);
            $chambresRDC   = (int)($batimentData['rdc_chambres']   ?? 0);

            // RDC
            if ($chambresRDC > 0) {
                Niveau_Aile_Batiment::create([
                    'code_aile'       => $aile->code_aile,
                    'niveau_aile_code'=> generate('niveau_aile_batiment', 'niveau_aile_code', 150, 'a-zA-Z0-9'),
                    'niveau_numero'   => 0,
                    'type_occupation' => $batiment->type_occupation,
                    'total_chambres'  => $chambresRDC,
                    'created_by'      => getInfoAdmin()->users->id_user,
                ]);
                $totalChambresNiveaux += $chambresRDC;
            }

            // Étages
            $chambresRestantes = $totalChambres - $chambresRDC;
            $chambresParEtage  = $niveauxCount > 0 ? floor($chambresRestantes / $niveauxCount) : 0;
            $reste             = $niveauxCount > 0 ? $chambresRestantes % $niveauxCount : 0;

            for ($i = 1; $i <= $niveauxCount; $i++) {
                $chambresPourCetEtage = $chambresParEtage + ($i <= $reste ? 1 : 0);

                Niveau_Aile_Batiment::create([
                    'code_aile'       => $aile->code_aile,
                    'niveau_aile_code'=> generate('niveau_aile_batiment', 'niveau_aile_code', 150, 'a-zA-Z0-9'),
                    'niveau_numero'   => $i,
                    'type_occupation' => $batiment->type_occupation,
                    'total_chambres'  => $chambresPourCetEtage,
                    'created_by'      => getInfoAdmin()->users->id_user,
                ]);

                $totalChambresNiveaux += $chambresPourCetEtage;
            }
        }

        // Mettre à jour le total de l'aile si nécessaire
        if ($aile->total_chambres != $totalChambresNiveaux) {
            $aile->update(['total_chambres' => $totalChambresNiveaux]);
        }
    }

    // ========================
    // CRÉATION AILES ET NIVEAUX (complexe)
    // ========================

    public function createAilesAndNiveaux($batiment, $ailesData)
    {
        foreach ($ailesData as $aileIndex => $aileData) {
            $aile = Aile_Batiment::firstOrCreate(
                [
                    'batiment_id' => $batiment->id_batiment,
                    'numero'        => $aileData['aile_numero'] ?? ($aileIndex + 1),
                ],
                [
                    'code_aile'      => generate('aile_batiment', 'code_aile', 150, 'a-zA-Z0-9'),
                    'name'           => $aileData['aile_nom'] ?? 'Aile ' . ($aileData['aile_numero'] ?? ($aileIndex + 1)),
                    'nombre_niveaux' => $aileData['nombre_niveaux'] ?? 0,
                    'total_chambres' => $aileData['total_chambres'] ?? 0,
                    'ordre_display'  => $aileData['aile_numero'] ?? ($aileIndex + 1),
                    'created_by'     => getInfoAdmin()->users->id_user,
                ]
            );

            Niveau_Aile_Batiment::where('code_aile', $aile->code_aile)->delete();

            if (isset($aileData['etages']) && is_array($aileData['etages'])) {
                foreach ($aileData['etages'] as $etageData) {
                    Niveau_Aile_Batiment::create([
                        'code_aile'        => $aile->code_aile,
                        'niveau_aile_code' => generate('niveau_aile_batiment', 'niveau_aile_code', 150, 'a-zA-Z0-9'),
                        'niveau_numero'    => $etageData['niveau_numero']  ?? 0,
                        'type_occupation'  => $etageData['type_occupation'] ?? $batiment->type_occupation,
                        'total_chambres'   => $etageData['total_chambres'] ?? 0, // ✅ clé correcte pour complexe
                        'chambres_nord'    => $etageData['chambres_par_cote']['nord']  ?? 0,
                        'chambres_sud'     => $etageData['chambres_par_cote']['sud']   ?? 0,
                        'chambres_est'     => $etageData['chambres_par_cote']['est']   ?? 0,
                        'chambres_ouest'   => $etageData['chambres_par_cote']['ouest'] ?? 0,
                        'created_by'       => getInfoAdmin()->users->id_user,
                    ]);
                }
            }
        }
    }

    // ========================
    // CRÉATION DES CHAMBRES
    // ========================

    public static function createChambres($batiment, $batimentData)
    {
        if ($batiment->type_architecture === 'complexe') {
            self::createChambresComplexe($batiment, $batimentData);
        } else {
            self::createChambresSimple($batiment, $batimentData);
        }
    }

    /**
     * Chambres pour structure SIMPLE
     * Lit total_chambres depuis la BDD (niveaux créés par createSimpleStructure)
     */
    public static function createChambresSimple($batiment, $batimentData)
    {
        $aile = Aile_Batiment::where('batiment_id', $batiment->id_batiment)->first();
        if (!$aile) return;

        $niveaux = Niveau_Aile_Batiment::where('code_aile', $aile->code_aile)
            ->orderBy('niveau_numero')
            ->get();

        if ($niveaux->isEmpty()) return;

        $totalChambresCreees = 0;

        foreach ($niveaux as $niveau) {
            $chambresACreer = (int)$niveau->total_chambres; // ✅ lu depuis la BDD

            for ($i = 1; $i <= $chambresACreer; $i++) {
                Chambre::create([
                    'niveau_aile_code' => $niveau->niveau_aile_code,
                //    'code_chambre'     => generate('chambre', 'code_chambre', 150, 'a-zA-Z0-9'),
                    'code_aile'        => $niveau->code_aile,
                    'batiment_id'    => $batiment->id_batiment ,
                    'cite_id'        => $batiment->cite_id,

                    'etat'             => 'disponible',
                    'capacite_max'     => 1,
                    'occupants_actuels'=> 0,
                    'created_by'       => getInfoAdmin()->users->code,
                ]);
            }

            $totalChambresCreees += $chambresACreer;
        }

        if ($aile->total_chambres != $totalChambresCreees) {
            $aile->update(['total_chambres' => $totalChambresCreees]);
        }
    }

    /**
     * Chambres pour structure COMPLEXE
     */
    public static function createChambresComplexe($batiment, $batimentData)
    {
        if (!isset($batimentData['structure_data']['ailes'])) return;

        foreach ($batimentData['structure_data']['ailes'] as $aileData) {
            $aile = Aile_Batiment::where('batiment_id', $batiment->id_batiment)
                ->where('numero', $aileData['aile_numero'])
                ->first();

            if (!$aile || !isset($aileData['etages'])) continue;

            foreach ($aileData['etages'] as $niveauData) {
                $niveau = Niveau_Aile_Batiment::where('code_aile', $aile->code_aile)
                    ->where('niveau_numero', $niveauData['niveau_numero'])
                    ->first();

                if (!$niveau) continue;

                self::createChambresForNiveau($batiment, $niveau, $aileData['aile_numero'], $niveauData);
            }
        }
    }

    /**
     * Chambres pour un niveau spécifique (complexe)
     * ✅ Lit total_chambres depuis $niveauData (envoyé par le frontend)
     */
    public static function createChambresForNiveau($batiment, $niveau, $aileNumero, $niveauData)
    {
        $totalChambres = (int)($niveauData['total_chambres'] ?? 0);

        if ($totalChambres <= 0) return;

        for ($i = 1; $i <= $totalChambres; $i++) {
            Chambre::create([
                'niveau_aile_code' => $niveau->niveau_aile_code,
               // 'code_chambre'     => generate('chambre', 'code_chambre', 150, 'a-zA-Z0-9'),
                'code_aile'        => $niveau->code_aile,
                'batiment_id'    => $batiment->id_batiment ,
                'cite_id'        => $batiment->cite_id,
                'type_occupation_id' =>  $batiment->type_occupation_id ?? $niveauData['structure_data']['type_occupation'] ?? $niveauData['structure_data']['type'] ?? null,
                'etat'             => 'disponible',
                'capacite_max'     => 1,
                'occupants_actuels'=> 0,
                'created_by'       => getInfoAdmin()->users->id_user,
            ]);
        }
    }

    // ========================
    // MISE À JOUR DES TOTAUX
    // ========================

    public static function updateBatimentTotals($batiment)
    {
        $totalChambres = Chambre::where('batiment_id', $batiment->id_batiment)->count();

        $totalEtages = Niveau_Aile_Batiment::whereIn('code_aile', function ($query) use ($batiment) {
            $query->select('code_aile')
                ->from('aile_batiment')
                ->where('batiment_id', $batiment->id_batiment);
        })->distinct('niveau_numero')->count();

        $batiment->update([
            'total_chambres' => $totalChambres,
            'total_ailes'    => Aile_Batiment::where('batiment_id', $batiment->id_batiment)->count(),
            'total_etages'   => $totalEtages,
        ]);
    }

    // ========================
    // HISTORIQUE
    // ========================

    public static function saveToHistorique($batiment, $actionType, $data)
    {
        // À implémenter selon votre modèle Historique
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeValides($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeParCampus($query, $codeCampus)
    {
        return $query->where('code_campus', $codeCampus);
    }

    public function scopeParCite($query, $codeCite)
    {
        return $query->where('code_cite', $codeCite);
    }

    // ========================
    // ACCESSEURS / MÉTHODES
    // ========================

    public function getNombreChambresTotal(): int
    {
        return $this->chambres()->count();
    }

    public function getNombrechambresDisponibles(): int
    {
        return $this->chambres()->where('etat', 'disponible')->count();
    }



    public function getTauxOccupation(): float
    {
        $total   = $this->chambres()->sum('capacite_max');
        $occupes = $this->chambres()->sum('occupants_actuels');

        if ($total == 0) return 0;

        return round(($occupes / $total) * 100, 2);
    }

    public function getLabelEtat(): string
    {
        $etats = [
            'nouveau'        => 'Nouveau',
            'bon'            => 'Bon État',
            'moyen'          => 'État Moyen',
            'mauvais'        => 'Mauvais État',
            'renovation'     => 'En Rénovation',
        ];

        return $etats[$this->etat_initial] ?? ucfirst($this->etat_initial);
    }

    public function getInfos(): array
    {
        return [
            'id'              => $this->id_batiment,
          //  'code'            => $this->code_batiment,
            'name'            => $this->name,
            'chambres'        => $this->getNombreChambresTotal(),
            'disponibles'     => $this->getNombrechambresDisponibles(),
            'etages'          => $this->total_etages,
            'ailes'           => $this->total_ailes,
            'taux_occupation' => $this->getTauxOccupation(),
            'etat'            => $this->getLabelEtat(),
        ];
    }

    // ========================
    // GÉNÉRATION NUMÉRO CHAMBRE
    // ========================

    public static function generateNumeroChambre($batiment, $aileNumero, $niveauNumero, $chambreIndex)
    {
        return sprintf('%s-%d-%02d-%03d',
            $batiment->id_batiment,
            $aileNumero,
            $niveauNumero,
            $chambreIndex
        );
    }
}

