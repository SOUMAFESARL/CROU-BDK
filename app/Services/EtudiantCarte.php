<?php

namespace App\Services;

use App\Models\AssignationChambre;
use App\Models\Batiment;
use App\Models\Campus;
use App\Models\Cite;
use App\Models\Etudiant;

/**
 * Props de la page React « Carte interactive » de l'espace étudiant (Etudiant/Carte) :
 * campus, cités et bâtiments en service et géolocalisés, avec la résidence de l'étudiant
 * (assignation active) mise en évidence. Aucune donnée d'occupation n'est exposée.
 */
class EtudiantCarte
{
    public function props(Etudiant $etudiant): array
    {
        $assignation = AssignationChambre::actives()
            ->where('etudiant_id', (string) $etudiant->getKey())
            ->with('chambre')
            ->latest('date_assignation')
            ->first();

        $cites = Cite::query()->with('typeCite')->whereIn('statut', Cite::STATUTS_ACTIFS)->orderBy('name')->get()
            ->map(fn (Cite $cite) => [
                'id' => $cite->id_cite,
                'name' => $cite->name,
                'type' => $cite->typeCite?->name,
                'adresse' => $cite->adresse ?: null,
                'telephone' => $cite->telephone1 ?: null,
                'email' => $cite->email1 ?: null,
                'position' => $this->position($cite->latitude, $cite->longitude),
                'services' => array_keys(array_filter([
                    'Wi-Fi gratuit' => $cite->wifi_gratuit,
                    "Salle d'étude" => $cite->salle_etude,
                    'Infirmerie' => $cite->infirmerie,
                    'Restaurant' => $cite->restaurant,
                ])),
            ]);
        $idsCites = $cites->pluck('id')->all();

        $batiments = Batiment::query()->with('typeOccupation')->where('is_valid', 1)
            ->where(fn ($q) => $q->whereNull('statut')->orWhere('statut', '!=', 'desactive'))
            ->whereIn('cite_id', $idsCites)
            ->orderBy('name')->get()
            ->map(fn (Batiment $batiment) => [
                'id' => $batiment->id_batiment,
                'name' => $batiment->name,
                'cite_id' => $batiment->cite_id,
                'occupation' => $batiment->typeOccupation?->name,
                'etages' => (int) $batiment->total_etages ?: null,
                'position' => $this->position($batiment->latitude, $batiment->longitude),
            ]);

        // Seuls les éléments géolocalisés sont utiles sur la carte, sauf la résidence de l'étudiant (toujours listée).
        $citeEtudiant = $assignation?->chambre?->cite_id;
        $batimentEtudiant = $assignation?->chambre?->batiment_id;
        $batiments = $batiments->filter(fn ($b) => $b['position'] || $b['id'] === $batimentEtudiant)->values();
        $cites = $cites->filter(fn ($c) => $c['position'] || $c['id'] === $citeEtudiant
            || $batiments->contains('cite_id', $c['id']))->values();

        return [
            'cites' => $cites,
            'batiments' => $batiments,
            'campus' => Campus::query()->where('statut', '!=', 'ferme')->orderBy('name')->get()
                ->map(fn (Campus $campus) => [
                    'id' => $campus->id_campus,
                    'name' => $campus->name,
                    'adresse' => $campus->adresse ?: null,
                    'telephone' => $campus->telephone1 ?: null,
                    'position' => $this->position($campus->latitude, $campus->longitude),
                ])
                ->filter(fn ($campus) => $campus['position'] !== null)
                ->values(),
            'residence' => $assignation?->chambre ? [
                'cite_id' => $citeEtudiant,
                'batiment_id' => $batimentEtudiant,
                'chambre' => $assignation->chambre->numero_chambre ?: $assignation->chambre->code_chambre,
            ] : null,
        ];
    }

    /** Coordonnées exploitables ou null (non renseignées, ou laissées à 0 par l'ancien formulaire). */
    private function position($latitude, $longitude): ?array
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude) || ((float) $latitude === 0.0 && (float) $longitude === 0.0)) {
            return null;
        }

        return ['lat' => (float) $latitude, 'lng' => (float) $longitude];
    }
}
