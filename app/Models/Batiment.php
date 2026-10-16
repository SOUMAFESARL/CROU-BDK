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
    /**
     * Valeur de la colonne enum `niveau_aile_batiment.type_occupation` ('hommes', 'femmes', 'mixte').
     * Le formulaire envoie le nom normalisé du type de cité (« uniquement homme », « uniquement femme », « mixte »)
     * ou son identifiant : on le ramène à l'enum, sinon MySQL refuse l'insertion du niveau.
     */
    public static function typeOccupationNiveau($valeur, $batiment = null): string
    {
        $valeur = trim((string) $valeur);
        if ($valeur === '' && $batiment?->type_occupation_id) {
            $valeur = (string) $batiment->type_occupation_id;
        }
        if ($valeur !== '' && ($type = TypeCite::find($valeur))) {
            $valeur = (string) $type->name;
        }

        $valeur = mb_strtolower(Str::ascii($valeur));

        return match (true) {
            str_contains($valeur, 'mixte') => 'mixte',
            str_contains($valeur, 'femme'), str_contains($valeur, 'fille') => 'femmes',
            str_contains($valeur, 'homme'), str_contains($valeur, 'garcon') => 'hommes',
            default => 'mixte',
        };
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
                $typeOccupation = self::typeOccupationNiveau($etage['type_occupation'] ?? null, $batiment);

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
                    'type_occupation' => self::typeOccupationNiveau(null, $batiment),
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
                    'type_occupation' => self::typeOccupationNiveau(null, $batiment),
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
                        'type_occupation'  => self::typeOccupationNiveau($etageData['type_occupation'] ?? null, $batiment),
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
    // STRUCTURE EXISTANTE (formulaire de modification)
    // ========================

    /**
     * Ailes et niveaux d'un bâtiment existant, avec le nombre de chambres déjà créées et occupées par niveau
     * (le formulaire de modification ne permet ni d'en retirer, ni de changer l'occupation d'un niveau occupé).
     */
    public static function structureExistante($batiment): array
    {
        $ailes = Aile_Batiment::where('batiment_id', $batiment->id_batiment)->orderBy('numero')->get();
        $niveaux = Niveau_Aile_Batiment::whereIn('code_aile', $ailes->pluck('code_aile'))
            ->orderBy('niveau_numero')
            ->get()
            ->groupBy('code_aile');
        $chambres = self::chambresParNiveau($batiment);

        return $ailes->map(fn ($aile) => [
            'code' => $aile->code_aile,
            'nom' => $aile->name,
            'niveaux' => ($niveaux[$aile->code_aile] ?? collect())->map(fn ($niveau) => [
                'code' => $niveau->niveau_aile_code,
                'numero' => (int) $niveau->niveau_numero,
                'type_occupation' => $niveau->type_occupation,
                'chambres' => $chambres[$niveau->niveau_aile_code]['total'] ?? 0,
                'occupees' => $chambres[$niveau->niveau_aile_code]['occupees'] ?? 0,
            ])->values()->all(),
        ])->values()->all();
    }

    /** Chambres existantes (non supprimées) et chambres occupées, par code de niveau. */
    public static function chambresParNiveau($batiment): array
    {
        return Chambre::where('batiment_id', $batiment->id_batiment)
            ->selectRaw("niveau_aile_code, count(*) as total, sum(case when occupants_actuels > 0 or etat = 'occupee' then 1 else 0 end) as occupees")
            ->groupBy('niveau_aile_code')
            ->get()
            ->mapWithKeys(fn ($ligne) => [(string) $ligne->niveau_aile_code => ['total' => (int) $ligne->total, 'occupees' => (int) $ligne->occupees]])
            ->all();
    }

    /**
     * Applique la structure modifiée : renomme les ailes, retire les niveaux demandés (`niveaux_retires`), met à jour
     * l'occupation des niveaux, ajoute les chambres manquantes et les nouveaux niveaux/ailes.
     * Les contrôles (niveaux du bâtiment, retrait par le haut, pas de diminution, niveau occupé) sont faits par le contrôleur.
     */
    public static function appliquerStructureModifiee($batiment, array $ailesData): void
    {
        $admin = getInfoAdmin()->users->id_user ?? null;

        foreach (array_values($ailesData) as $aileData) {
            $aile = !empty($aileData['code'])
                ? Aile_Batiment::where('batiment_id', $batiment->id_batiment)->where('code_aile', $aileData['code'])->first()
                : null;
            $nom = trim((string) ($aileData['nom'] ?? ''));

            if (!$aile) {
                $numero = (int) Aile_Batiment::where('batiment_id', $batiment->id_batiment)->max('numero') + 1;
                $aile = Aile_Batiment::create([
                    'batiment_id' => $batiment->id_batiment,
                    'numero' => $numero,
                    'code_aile' => generate('aile_batiment', 'code_aile', 150, 'a-zA-Z0-9'),
                    'name' => $nom !== '' ? $nom : 'Aile ' . $numero,
                    'nombre_niveaux' => 0,
                    'total_chambres' => 0,
                    'ordre_display' => $numero,
                    'created_by' => $admin,
                ]);
            } elseif ($nom !== '' && $aile->name !== $nom) {
                $aile->update(['name' => $nom]);
            }

            // Niveaux retirés (contrôlés par le contrôleur : niveaux supérieurs sans occupant) : chambres supprimées (corbeille) puis niveau.
            $retires = array_values(array_filter((array) ($aileData['niveaux_retires'] ?? []), 'is_string'));
            if ($retires) {
                $codes = Niveau_Aile_Batiment::where('code_aile', $aile->code_aile)->whereIn('niveau_aile_code', $retires)->pluck('niveau_aile_code');
                Chambre::whereIn('niveau_aile_code', $codes)->get()->each->delete();
                Niveau_Aile_Batiment::whereIn('niveau_aile_code', $codes)->delete();
            }

            foreach ($aileData['niveaux'] ?? [] as $niveauData) {
                $voulues = (int) ($niveauData['chambres'] ?? 0);
                $type = self::typeOccupationNiveau($niveauData['type_occupation'] ?? null, $batiment);
                $niveau = !empty($niveauData['code'])
                    ? Niveau_Aile_Batiment::where('code_aile', $aile->code_aile)->where('niveau_aile_code', $niveauData['code'])->first()
                    : null;

                if ($niveau) {
                    $existantes = Chambre::where('niveau_aile_code', $niveau->niveau_aile_code)->count();
                    $niveau->update(['type_occupation' => $type, 'total_chambres' => max($voulues, $existantes)]);
                } else {
                    $existantes = 0;
                    $niveau = Niveau_Aile_Batiment::create([
                        'code_aile' => $aile->code_aile,
                        'niveau_aile_code' => generate('niveau_aile_batiment', 'niveau_aile_code', 150, 'a-zA-Z0-9'),
                        'niveau_numero' => (int) ($niveauData['numero'] ?? 0),
                        'type_occupation' => $type,
                        'total_chambres' => $voulues,
                        'created_by' => $admin,
                    ]);
                }

                // Chambres créées vides, comme à la création du bâtiment (numéro, type et lits configurés ensuite).
                for ($i = $existantes; $i < $voulues; $i++) {
                    Chambre::create([
                        'niveau_aile_code' => $niveau->niveau_aile_code,
                        'code_aile' => $aile->code_aile,
                        'batiment_id' => $batiment->id_batiment,
                        'cite_id' => $batiment->cite_id,
                        'type_occupation_id' => $batiment->type_occupation_id,
                        'etat' => 'disponible',
                        'capacite_max' => 1,
                        'occupants_actuels' => 0,
                        'created_by' => $admin,
                    ]);
                }
            }

            $aile->update([
                'nombre_niveaux' => Niveau_Aile_Batiment::where('code_aile', $aile->code_aile)->count(),
                'total_chambres' => Chambre::where('code_aile', $aile->code_aile)->count(),
            ]);
        }

        self::updateBatimentTotals($batiment);
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

