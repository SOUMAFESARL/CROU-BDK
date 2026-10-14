<?php

namespace App\Http\Services;

use App\Exceptions\OccupationIncompatibleException;
use App\Models\Batiment;
use App\Repositories\Interfaces\BatimentRepositoryInterface;
use App\Repositories\Interfaces\CiteRepositoryInterface;
use App\Repositories\Interfaces\OccupationServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BatimentService
{
    protected  $batimentRepository;
        protected  $citeRepository;
        protected    $occupationService;
        protected     $batimentModel;
    public function __construct(
         BatimentRepositoryInterface $batimentRepository,
         CiteRepositoryInterface      $citeRepository,
         OccupationServiceInterface   $occupationService,
         Batiment                     $batimentModel
    ) {
        $this->batimentRepository = $batimentRepository;
        $this->citeRepository      = $citeRepository;
        $this->occupationService = $occupationService;
        $this->batimentModel       = $batimentModel;

    }

    public function getAll()
    {
        return  $this->batimentRepository->getAll();
    }
    // -------------------------------------------------------------------------
    // Création
    // -------------------------------------------------------------------------

    /**
     * Créer un bâtiment avec sa structure (ailes + niveaux + chambres vides).
     *
     * @throws OccupationIncompatibleException
     */
    public function createBatiment(array $batimentData, ?\Illuminate\Http\UploadedFile $planFile = null): Batiment
    {
        return DB::transaction(function () use ($batimentData, $planFile) {

            // 1. Valider l'occupation
            $this->occupationService->validateBatimentOccupation(
                $batimentData['code_cite'],
                $batimentData['type_occupation']
            );

            // 2. Gérer le fichier plan
            if ($planFile) {
                $batimentData['plan_file'] = $this->storePlanFile($planFile);
            }

            // 3. Créer le bâtiment
            $batiment = $this->batimentRepository->create($batimentData);

            // 4. Créer la structure (ailes / niveaux)
            if ($batimentData['type_architecture'] === 'complexe' && isset($batimentData['structure_data']['ailes'])) {
                $this->batimentModel->createAilesAndNiveaux($batiment, $batimentData['structure_data']['ailes']);
            } else {
                $this->batimentModel->createSimpleStructure($batiment, $batimentData);
            }

            // 5. Créer les chambres vides
            $this->batimentModel->createChambres($batiment, $batimentData);

            // 6. Mettre à jour les totaux
            $this->batimentRepository->updateTotals($batiment->code_batiment);

            return $batiment->fresh();
        });
    }


    /**
     * Alias de createBatiment() — utilisé par storeBatiment() dans le contrôleur.
     * Reçoit le tableau JSON décodé depuis le formulaire JS.
     *
     * @throws OccupationIncompatibleException
     * @throws \InvalidArgumentException si le nom existe déjà dans la cité
     */
    public function createFromFormData(array $batimentData, ?\Illuminate\Http\UploadedFile $planFile = null): Batiment
    {
        // Vérifier doublon nom dans la cité
        if (
            !empty($batimentData['code_cite']) &&
            !empty($batimentData['name']) &&
            $this->batimentRepository->existsInCite($batimentData['code_cite'], $batimentData['name'])
        ) {
            throw new \InvalidArgumentException(
                "Un bâtiment nommé « {$batimentData['name']} » existe déjà dans cette cité."
            );
        }

        return $this->createBatiment($batimentData, $planFile);
    }

    // -------------------------------------------------------------------------
    // Mise à jour
    // -------------------------------------------------------------------------

    /**
     * Mettre à jour un bâtiment.
     * Si le type d'occupation change, propagation vers les chambres.
     *
     * @throws OccupationIncompatibleException
     */
    public function updateBatiment(string $code, array $data): Batiment
    {
        return DB::transaction(function () use ($code, $data) {

            $batiment    = $this->batimentRepository->findByCode($code);
            $ancienType  = $batiment->type_occupation;
            $nouveauType = $data['type_occupation'] ?? $ancienType;

            // Valider la compatibilité avec la cité
            $this->occupationService->validateBatimentOccupation(
                $batiment->code_cite,
                $nouveauType
            );

            $batiment = $this->batimentRepository->update($code, $data);

            // Propager si le type a changé et n'est plus mixte
            if ($ancienType !== $nouveauType) {
                $this->occupationService->propagateOccupationFromBatiment($code, $nouveauType);
            }

            return $batiment;
        });
    }

    // -------------------------------------------------------------------------
    // Suppression
    // -------------------------------------------------------------------------

    public function deleteBatiment(string $code): void
    {
        if ($this->batimentRepository->hasChambres($code)) {
            throw new \RuntimeException(
                'Impossible de supprimer ce bâtiment : il contient des chambres. '
                . 'Veuillez d\'abord supprimer toutes les chambres associées.'
            );
        }

        $this->batimentRepository->delete($code);
    }

    // -------------------------------------------------------------------------
    // Données pour les vues
    // -------------------------------------------------------------------------

    /**
     * Retourne les infos d'un bâtiment avec niveaux et chambres
     * pour le formulaire chambre.
     */
    public function getBatimentInfoForChambreForm(string $batimentCode): array
    {
        $batiment = $this->batimentRepository->findByCode($batimentCode);

        if (!$batiment) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Bâtiment introuvable : {$batimentCode}");
        }

        // Récupérer les niveaux avec chambres
        $niveaux = $this->buildNiveauxData($batiment);

        return [
            'success' => true,
            'batiment' => [
                'code'              => $batiment->code_batiment,
                'name'              => $batiment->name,
                'type_architecture' => $batiment->type_architecture,
                'type_occupation'   => $batiment->type_occupation,
                'total_chambres'    => $batiment->total_chambres,
                'total_etages'      => $batiment->total_etages,
                'total_ailes'       => $batiment->total_ailes,
            ],
            'etages'              => $niveaux,
            'types_occupation_autorises' => $this->occupationService->getAllowedTypesForChambre($batimentCode),
        ];
    }

    /**
     * Types d'occupation autorisés pour les chambres d'un bâtiment.
     */
    public function getAllowedChambreTypes(string $batimentCode): array
    {
        return $this->occupationService->getAllowedTypesForChambre($batimentCode);
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function storePlanFile(\Illuminate\Http\UploadedFile $file): string
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('public/batiments/plans', $fileName);
        return 'storage/batiments/plans/' . $fileName;
    }

    private function buildNiveauxData(Batiment $batiment): array
    {
        // Réutilise la logique existante du contrôleur
        $niveauxExistants = \App\Models\Niveau_Aile_Batiment::with(['aile' => function ($q) use ($batiment) {
            $q->where('code_batiment', $batiment->code_batiment);
        }])->whereHas('aile', function ($q) use ($batiment) {
            $q->where('code_batiment', $batiment->code_batiment);
        })->get();

        $chambresParNiveau = \App\Models\Chambre::where('code_batiment', $batiment->code_batiment)
            ->get()
            ->groupBy('niveau_aile_code');

        $niveaux = [];

        foreach ($niveauxExistants as $niveau) {
            $niveauNumero = $niveau->niveau_numero;
            $aile         = $niveau->aile;
            $aileName     = $aile ? $aile->name : "Aile {$niveau->code_aile}";

            $chambresNiveau = $chambresParNiveau[$niveau->niveau_aile_code] ?? collect();

            $niveaux[] = [
                'id_niveau_aile'   => $niveau->id_niveau_aile,
                'niveau_numero'    => (int) $niveauNumero,
                'niveau_aile_code' => $niveau->niveau_aile_code,
                'code_aile'        => $niveau->code_aile,
                'aile_name'        => $aileName,
                'aile_numero'      => $aile ? $aile->numero : null,
                'niveau'           => (int) $niveauNumero,
                'code'             => $niveau->niveau_aile_code,
                'label'            => $niveau->label ?? ($niveauNumero == 0 ? 'Rez-de-chaussée' : "Étage {$niveauNumero}"),
                'type_occupation'  => $niveau->type_occupation,
                'total_chambres'   => $niveau->total_chambres,
                'chambres_crees'   => $chambresNiveau->filter(fn($c) => !is_null($c->numero_chambre) && !is_null($c->type_chambre_code))->count(),
                'chambres_non_crees' => $chambresNiveau->filter(fn($c) => is_null($c->numero_chambre) || is_null($c->type_chambre_code))->count(),
            ];
        }

        usort($niveaux, fn($a, $b) => strcmp($a['code_aile'], $b['code_aile']) ?: ($a['niveau_numero'] - $b['niveau_numero']));

        return $niveaux;
    }


    public function findByCode(string $id): ? Batiment
    {
       return $this->batimentRepository->findByCode($id);
    }


}