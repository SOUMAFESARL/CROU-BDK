<?php

namespace App\Http\Services;

use App\Repositories\ChambreRepository;
use App\Models\Chambre;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChambreService
{
    protected  $chambreRepository;
    public function __construct(
         ChambreRepository $chambreRepository
    ) {
        $this->chambreRepository = $chambreRepository;
    }

    /**
     * Récupérer les chambres d'un bâtiment avec la structure par niveau.
     */
    public function getByBatiment(string $batimentCode): array
    {
        $chambres    = $this->chambreRepository->getByBatiment($batimentCode);
        $parNiveau   = $chambres->groupBy('niveau_aile_code');

        $batiment = \App\Models\Batiment::where('code_batiment', $batimentCode)->first();

        $niveauxExistants = \App\Models\Niveau_Aile_Batiment::whereHas('aile', fn($q) =>
        $q->where('code_batiment', $batimentCode)
        )->get();

        $niveaux = $niveauxExistants->map(function ($niveau) use ($parNiveau, $batiment) {
            $aile           = $niveau->aile;
            $chambresNiveau = $parNiveau[$niveau->niveau_aile_code] ?? collect();

            return [
                'id_niveau_aile'   => $niveau->id_niveau_aile,
                'niveau_numero'    => (int) $niveau->niveau_numero,
                'niveau_aile_code' => $niveau->niveau_aile_code,
                'code_aile'        => $niveau->code_aile,
                'aile_name'        => $aile?->name ?? "Aile {$niveau->code_aile}",
                'aile_numero'      => $aile?->numero,
                'label'            => $niveau->label ?? ($niveau->niveau_numero == 0 ? 'Rez-de-chaussée' : "Étage {$niveau->niveau_numero}"),
                'type_occupation'  => $niveau->type_occupation,
                'total_chambres'   => $niveau->total_chambres,
                'chambres_crees'   => $chambresNiveau->filter(fn($c) => !is_null($c->numero_chambre) && !is_null($c->type_chambre_id))->count(),
                'chambres_non_crees' => $chambresNiveau->filter(fn($c) => is_null($c->numero_chambre) || is_null($c->type_chambre_id))->count(),
            ];
        })->sortBy([['code_aile', 'asc'], ['niveau_numero', 'asc']])->values()->toArray();

        return [
            'batiment'         => $batiment ? [
                'code'              => $batiment->code_batiment,
                'name'              => $batiment->name,
                'type_architecture' => $batiment->type_architecture,
                'total_chambres'    => $batiment->total_chambres ?? $chambres->count(),
                'total_etages'      => $batiment->total_etages ?? count($niveaux),
                'total_ailes'       => $batiment->total_ailes ?? 0,
            ] : null,
            'etages'           => $niveaux,
            'chambres_par_etage' => $parNiveau,
            'chambres'         => $chambres->map(fn($c) => [
                'code'              => $c->code_chambre,
                'numero_chambre'    => $c->numero_chambre,
                'niveau_code'       => $c->niveau_aile_code,
                'type_chambre_id' => $c->type_chambre_id?? null,
                'type_chambre_nom'  => $c->typeChambre?->name ?? 'Non défini',
                'statut'            => $c->etat,
                'est_creee'         => !is_null($c->numero_chambre) && !is_null($c->type_chambre_id),
            ]),
        ];
    }

    /**
     * Récupérer les chambres créées / non créées d'un niveau.
     */
    public function getByNiveau(string $niveauCode, string $batimentCode): array
    {
        return $this->chambreRepository->getByNiveau($niveauCode, $batimentCode);
    }

    /**
     * Création rapide d'une chambre (remplissage numero + type).
     *
     * @throws ValidationException
     */
    public function quickCreate(string $code, string $numeroChambre, string $typeChambreCode): Chambre
    {
        $chambre = $this->chambreRepository->findByCode($code);

        if (!$chambre) {
            throw new \Exception('Chambre introuvable.');
        }

        if ($this->chambreRepository->existsInNiveau(
            $chambre->code_batiment,
            $chambre->niveau_aile_code,
            $numeroChambre,
            $code
        )) {
            throw ValidationException::withMessages([
                'numero_chambre' => ['Ce numéro de chambre existe déjà pour ce niveau.']
            ]);
        }

        DB::beginTransaction();
        try {
            $result = $this->chambreRepository->quickCreate($code, $numeroChambre, $typeChambreCode);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mise à jour d'une chambre (via storeChambre — validation du numéro unique).
     *
     * @throws ValidationException
     */
    public function update(string $code, string $numeroChambre, string $typeChambreCode): Chambre
    {
        $chambre = $this->chambreRepository->findByCode($code);

        if (!$chambre) {
            throw new \Exception('Chambre introuvable.');
        }

        if ($this->chambreRepository->existsInNiveau(
            $chambre->code_batiment,
            $chambre->niveau_aile_code,
            $numeroChambre,
            $code
        )) {
            throw ValidationException::withMessages([
                'numero_chambre' => ['Ce numéro de chambre existe déjà pour ce niveau.']
            ]);
        }

        DB::beginTransaction();
        try {
            $result = $this->chambreRepository->update($code, [
                'numero_chambre'    => $numeroChambre,
                'type_chambre_id' => $typeChambreCode,
            ]);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Retourner les types d'occupation autorisés pour les chambres d'un bâtiment.
     * Utile pour alimenter le front-end (désactiver les options non autorisées).
     */
    public function getAllowedOccupationTypes(string $batimentCode): array
    {
        return $this->chambreRepository->getAllowedOccupationTypes($batimentCode);
    }

    public function getAll()
    {
        return  $this->chambreRepository->getAll();
    }
}