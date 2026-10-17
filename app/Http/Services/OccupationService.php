<?php

namespace App\Http\Services;


use App\Exceptions\OccupationIncompatibleException;
use App\Models\Batiment;
use App\Models\Chambre;
use App\Repositories\Interfaces\BatimentRepositoryInterface;
use App\Repositories\Interfaces\ChambreRepositoryInterface;
use App\Repositories\Interfaces\CiteRepositoryInterface;
use App\Repositories\Interfaces\OccupationServiceInterface;
use Illuminate\Support\Facades\DB;

class OccupationService implements OccupationServiceInterface
{
    /**
     * Map des types autorisés selon le parent.
     *  - mixte  → tout est permis pour l'enfant
     *  - hommes → l'enfant doit être hommes
     *  - femmes → l'enfant doit être femmes
     */
    private const ALLOWED_MAP = [
        'mixte'  => ['mixte', 'hommes', 'femmes'],
        'hommes' => ['hommes'],
        'femmes' => ['femmes'],
    ];

    protected      $citeRepository;
        protected  $batimentRepository;
        protected   $chambreRepository;
        protected  $batimentModel;
        protected   $chambreModel;

    public function __construct(
         CiteRepositoryInterface     $citeRepository,
         BatimentRepositoryInterface $batimentRepository,
         ChambreRepositoryInterface  $chambreRepository,
         Batiment                    $batimentModel,
         Chambre                     $chambreModel
    ) {
        $this->citeRepository = $citeRepository;
        $this->batimentRepository = $batimentRepository;
        $this->chambreRepository = $chambreRepository;
        $this->batimentModel = $batimentModel;
        $this->chambreModel = $chambreModel;

    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * Valide que le type d'occupation du bâtiment est compatible avec sa cité.
     *
     * @throws OccupationIncompatibleException
     */
    public function validateBatimentOccupation(string $citeCode, string $typeOccupationBatiment): void
    {
        $typeCite  = $this->citeRepository->getTypeOccupation($citeCode);
        $autorisés = self::ALLOWED_MAP[$typeCite] ?? ['mixte', 'hommes', 'femmes'];

        if (!in_array($typeOccupationBatiment, $autorisés, true)) {
            throw new OccupationIncompatibleException(
                typeParent:   $typeCite,
                typeEnfant:   $typeOccupationBatiment,
                niveauParent: 'cite'
            );
        }
    }

    /**
     * Valide que le type d'occupation d'une chambre est compatible avec son bâtiment.
     *
     * @throws OccupationIncompatibleException
     */
    public function validateChambreOccupation(string $batimentCode, string $typeOccupationChambre): void
    {
        $batiment      = $this->batimentModel->where('code_batiment', $batimentCode)->first();
        $typeBatiment  = $batiment?->type_occupation ?? 'mixte';
        $autorisés     = self::ALLOWED_MAP[$typeBatiment] ?? ['mixte', 'hommes', 'femmes'];

        if (!in_array($typeOccupationChambre, $autorisés, true)) {
            throw new OccupationIncompatibleException(
                typeParent:   $typeBatiment,
                typeEnfant:   $typeOccupationChambre,
                niveauParent: 'batiment'
            );
        }
    }

    // -------------------------------------------------------------------------
    // Listes autorisées
    // -------------------------------------------------------------------------

    public function getAllowedTypesForBatiment(string $citeCode): array
    {
        $typeCite = $this->citeRepository->getTypeOccupation($citeCode);
        return self::ALLOWED_MAP[$typeCite] ?? ['mixte', 'hommes', 'femmes'];
    }

    public function getAllowedTypesForChambre(string $batimentCode): array
    {
        $batiment     = $this->batimentModel->where('code_batiment', $batimentCode)->first();
        $typeBatiment = $batiment?->type_occupation ?? 'mixte';
        return self::ALLOWED_MAP[$typeBatiment] ?? ['mixte', 'hommes', 'femmes'];
    }

    // -------------------------------------------------------------------------
    // Propagation en cascade
    // -------------------------------------------------------------------------

    /**
     * Quand une cité change de type (ex: de mixte → hommes), on propage
     * vers tous ses bâtiments et toutes leurs chambres.
     */
    public function propagateOccupationFromCite(string $citeCode, string $nouveauType): void
    {
        DB::transaction(function () use ($citeCode, $nouveauType) {

            // 1. Mettre à jour tous les bâtiments de la cité
            $batiments = $this->batimentModel
                ->where('cite_id', $citeCode)
                ->get();

            foreach ($batiments as $batiment) {
              //  $typeForce = $this->forceType($batiment->type_occupation_id, $nouveauType);

                $batiment->update([
                    'type_occupation_id' => $nouveauType,
                    'updated_by'      => getInfoAdmin()->users->id_user,
                ]);

                // 2. Propager aux chambres de ce bâtiment
                $this->propagateOccupationFromBatiment($batiment->id_batiment, $nouveauType);
            }
        });
    }

    /**
     * Quand un bâtiment change de type, on propage vers toutes ses chambres.
     */
    public function propagateOccupationFromBatiment(string $batimentCode, string $nouveauType): void
    {
        DB::transaction(function () use ($batimentCode, $nouveauType) {

            // Si le bâtiment n'est pas mixte, forcer TOUTES les chambres
            //if ($nouveauType !== 'mixte') {
                $this->chambreModel
                    ->where('batiment_id', $batimentCode)
                    ->update([
                        'type_occupation_id' => $nouveauType,
                        'updated_by'      => getInfoAdmin()->users->id_user,
                    ]);
        //    }
            // Si mixte → on ne touche pas les chambres (elles gardent leur type)
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Détermine le type forcé selon la règle parent → enfant.
     *  - Parent mixte  → l'enfant conserve son type actuel
     *  - Parent hommes → l'enfant passe à hommes
     *  - Parent femmes → l'enfant passe à femmes
     */
    private function forceType(string $typeActuelEnfant, string $typeParent): string
    {
        if ($typeParent === 'mixte') {
            return $typeActuelEnfant; // pas de contrainte, on conserve
        }
        return $typeParent; // on force le type du parent
    }

    /**
     * Résumé lisible des règles pour l'affichage côté vue.
     */
    public function getRulesDescription(): array
    {
        return [
            [
                'type_cite'      => 'mixte',
                'label_cite'     => 'Mixte',
                'types_batiment' => ['mixte', 'hommes', 'femmes'],
                'description'    => 'La cité mixte autorise des bâtiments de tout type.',
            ],
            [
                'type_cite'      => 'hommes',
                'label_cite'     => 'Hommes uniquement',
                'types_batiment' => ['hommes'],
                'description'    => 'La cité hommes impose des bâtiments hommes uniquement.',
            ],
            [
                'type_cite'      => 'femmes',
                'label_cite'     => 'Femmes uniquement',
                'types_batiment' => ['femmes'],
                'description'    => 'La cité femmes impose des bâtiments femmes uniquement.',
            ],
        ];
    }
}