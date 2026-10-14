<?php

namespace App\Repositories;

use App\Models\Batiment;
use App\Models\Cite;
use App\Repositories\Interfaces\CiteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CiteRepository implements CiteRepositoryInterface
{
    protected      $model;
        protected  $batimentModel;
    public function __construct(
         Cite     $model,
         Batiment $batimentModel
    ) {
        $this->model = $model;
        $this->batimentModel = $batimentModel;
    }

    // -------------------------------------------------------------------------
    // Lecture
    // -------------------------------------------------------------------------

    public function getAll(): Collection
    {

        return $this->model->withDefaultRelations()->orderBy('name')->get();
    }

    public function findByCode(string $code): ?Cite
    {

        return $this->model->withDefaultRelations()->where('id_cite', $code)->first();
    }

    public function getByCampus(string $campusCode): Collection
    {
        return $this->model
            ->where('campus_id', $campusCode)->withDefaultRelations()
            ->orderBy('name')
            ->get();
    }

    public function getWithBatiments(string $code): ?Cite
    {
        return $this->model->withDefaultRelations()
            ->with(['batiments' => fn($q) => $q->orderBy('name')])
            ->where('code', $code)
            ->first();
    }

    // -------------------------------------------------------------------------
    // Type d'occupation
    // -------------------------------------------------------------------------

    /**
     * Retourne le type d'occupation stocké dans type_cite_code
     * ou dans un champ dédié. On normalise ici vers 'mixte' | 'hommes' | 'femmes'.
     */
    public function getTypeOccupation(string $code): string
    {
        $cite = $this->findByCode($code);

        if (!$cite) {
            return 'mixte';
        }

        // Si votre table cite dispose d'un champ type_occupation, utilisez-le
        // Sinon, on se base sur type_cite_code avec une correspondance
        return $this->resolveOccupationType($cite);
    }

    /**
     * Retourne les types d'occupation autorisés pour les bâtiments d'une cité.
     *  - Cité mixte  → tout est autorisé
     *  - Cité hommes → hommes uniquement
     *  - Cité femmes → femmes uniquement
     */
    public function getAllowedOccupationTypes(string $citeCode): array
    {
        $typeOccupation = $this->getTypeOccupation($citeCode);

        return match ($typeOccupation) {
            'hommes' => ['hommes'],
            'femmes' => ['femmes'],
            default  => ['mixte', 'hommes', 'femmes'],
        };
    }

    // -------------------------------------------------------------------------
    // Écriture
    // -------------------------------------------------------------------------

    public function create(array $data): Cite
    {

        try {
            $latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
            $longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;

            //  dd($data,$latitude,$longitude);

            return $this->model->create([

                'code_cite' => $data['code_cite'],
                'name' => $data['name'],
                'type_cite_id' => $data['type_cite'],
                'campus_id' => $data['campus_id'],
                'adresse' => $data['adresse'],
                'capacite_max'=> $data['capacite_max'],
                'nombre_batiments' => $data['nombre_batiments'],
                'superficie' => $data['superficie'],
                'annee_construction' => $data['annee_construction'],
                'responsable_id'   => $data['responsable_id'],
                'telephone1' => $data['telephone1'],
                'telephone2' => $data['telephone2'],
                'telephone3' => $data['telephone3'],
                'email1' => $data['email1'],
                'email2' => $data['email2'],
                'longitude'=> $longitude,
                'latitude' => $latitude,
                'reglement_interieur_id' => $data['reglement_id'],
                'statut' => $data['statut'] ?? 'ouvert',
                'wifi_gratuit'   => isset($data['wifi_gratuit']) ? 1 : 0,
                'salle_etude'    => isset($data['salle_etude'])  ? 1 : 0,
                'infirmerie'     => isset($data['infirmerie'])   ? 1 : 0,
                'restaurant'     => isset($data['restaurant'])    ? 1 : 0,
                // Plan facultatif : `upload()` lit $_FILES, il n'est appelé que si un fichier est envoyé.
                'plan' => ($data['plan'] ?? null) instanceof \Illuminate\Http\UploadedFile ? upload('cite', 'png', $data, 'plan') : null,
            ]);
        }
        catch (\Exception $e) {
            // Remonté au contrôleur, qui affiche un message simple (avant : redirection renvoyée à la place d'une Cite).
            throw $e;
        }

    }

    public function update(string $code, array $data): Cite
    {
        try {
            $cite = $this->findByCode($code);
            // Convertir les coordonnées en float
            $latitude = isset($data['latitude']) ? (float)$data['latitude'] : $cite->latitude;
            $longitude = isset($data['longitude']) ? (float)$data['longitude'] : $cite->longitude;


            $cite->update([
                'code_cite' => $data['code_cite'],
                'name' => $data['name'],
                'type_cite_id' => $data['type_cite'],
                'campus_id' => $data['campus_id'],
                'adresse' => $data['adresse'],
                'capacite_max'=> $data['capacite_max'],
                'nombre_batiments' => $data['nombre_batiments'],
                'superficie' => $data['superficie'],
                'annee_construction' => $data['annee_construction'],
                'responsable_id'   => $data['responsable_id'],
                'telephone1' => $data['telephone1'],
                'telephone2' => $data['telephone2'],
                'telephone3' => $data['telephone3'],
                'email1' => $data['email1'],
                'email2' => $data['email2'],
                'longitude'=> $longitude,
                'latitude' => $latitude,
                'reglement_interieur_id' => $data['reglement_id'],
                'statut' => $data['statut'] ?? 'ouvert',
                'wifi_gratuit'   => isset($data['wifi_gratuit']) ? 1 : 0,
                'salle_etude'    => isset($data['salle_etude'])  ? 1 : 0,
                'infirmerie'     => isset($data['infirmerie'])   ? 1 : 0,
                'restaurant'     => isset($data['restaurant'])    ? 1 : 0,
                'plan' => isset($data['plan']) && !empty($data['plan']) ?  update( 'cite', $cite->plan, 'png',$data, 'plan'): $cite->plan
            ]);
            // dd( $cite,  $data);
            return $cite->fresh();
        } catch(\Exception $e) {
            throw $e;
        }
    }

    public function delete(string $code): bool
    {
        $cite = $this->findByCodeOrFail($code);
        return $cite->delete();
    }

    // -------------------------------------------------------------------------
    // Vérifications
    // -------------------------------------------------------------------------

    public function hasBatiments(string $code): bool
    {
        return $this->batimentModel
            ->where('code_cite', $code)
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function findByCodeOrFail(string $code): Cite
    {
        $cite = $this->findByCode($code);
        if (!$cite) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Cité introuvable : {$code}");
        }
        return $cite;
    }

    /**
     * Normalise le type d'occupation depuis le modèle Cite.
     * Adaptez cette méthode si votre table a un champ dédié (ex: type_occupation).
     */
    private function resolveOccupationType(Cite $cite): string
    {
        // Priorité : champ type_occupation si existant
        if (isset($cite->type_cite_id) && in_array($cite->type_cite_id, ['mixte', 'hommes', 'femmes'])) {
            return $cite->type_cite_id;
        }

        // Sinon, exploiter type_cite_code ou name du TypeCite
        if ($cite->typeCite) {
            $nom = strtolower($cite->typeCite->name ?? '');

            if (str_contains($nom, 'Uniquement Homme') || str_contains($nom, 'masculin')) {
                return $cite->typeCite->id_type_cite;
            }
            if (str_contains($nom, 'Uniquement Femme') ||  str_contains($nom, 'feminin')) {
                return $cite->typeCite->id_type_cite;
            }
        }

        return $cite->typeCite->id_type_cite;
    }

    public function getCiteByStatut(string $statut): Collection
    {
        return $this->model->withDefaultRelations()->where('statut', $statut)->get();
    }
}