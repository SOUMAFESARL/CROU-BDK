<?php

namespace App\Http\Services;

use App\Models\Filiere;
use App\Models\Region;
use App\Models\Ville;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Saisie libre de la filière, de la région et de la ville dans la demande d'hébergement
 * (`filiere_autre`, `region_autre`, `ville_autre`) : on rattache la valeur à l'entrée existante
 * de même nom (sans tenir compte de la casse ni des accents), sinon on crée l'entrée du référentiel.
 * Les identifiants obtenus remplacent ceux de la requête (`filiere`, `region`/`regions_id`, `ville`/`ville_id`).
 */
class ReferentielLibreService
{
    public function appliquer(Request $request): void
    {
        $filiere = $this->texte($request->input('filiere_autre'));
        if ($filiere !== null) {
            $request->merge(['filiere' => (string) $this->filiere($filiere)->getKey()]);
        }

        $region = $this->texte($request->input('region_autre'));
        if ($region !== null) {
            $id = (string) $this->region($region)->getKey();
            $request->merge(['region' => $id, 'regions_id' => $id]);
        }

        $ville = $this->texte($request->input('ville_autre'));
        if ($ville !== null && filled($request->input('region'))) {
            $id = (string) $this->ville($ville, (int) $request->input('region'))->getKey();
            $request->merge(['ville' => $id, 'ville_id' => $id]);
        }
    }

    private function filiere(string $nom): Model
    {
        return $this->trouver(Filiere::all(), $nom) ?? Filiere::create([
            'name' => $nom,
            'code_filiere' => generate('filiere', 'code_filiere', 50, 'a-zA-Z0-9'),
        ]);
    }

    private function region(string $nom): Model
    {
        return $this->trouver(Region::all(), $nom) ?? Region::create(['name' => $nom]);
    }

    private function ville(string $nom, int $regionId): Model
    {
        return $this->trouver(Ville::where('region_id', $regionId)->get(), $nom)
            ?? Ville::create(['name' => $nom, 'region_id' => $regionId]);
    }

    private function trouver(Collection $entrees, string $nom): ?Model
    {
        $cle = $this->cle($nom);

        return $entrees->first(fn (Model $entree) => $this->cle((string) $entree->name) === $cle);
    }

    private function cle(string $nom): string
    {
        return Str::of($nom)->ascii()->lower()->squish()->toString();
    }

    private function texte(mixed $valeur): ?string
    {
        $texte = Str::squish((string) $valeur);

        return $texte === '' ? null : $texte;
    }
}
