<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisciplinaryReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_rapport,
            'numero' => $this->numero_rapport,
            'source' => $this->source,
            'confidentiel' => (bool) $this->confidentiel,
            'nature' => $this->nature,
            'description' => $this->description,
            'temoins' => $this->temoins,
            'lieu' => $this->lieu,
            'gravite' => $this->gravite,
            'statut' => $this->statut,
            'decision' => $this->decision,
            'sanction_decidee' => $this->sanction_decidee,
            'sanction' => $this->whenLoaded('sanction', fn () => $this->sanction ? [
                'id' => $this->sanction->getKey(), 'type' => $this->sanction->type,
                'statut' => $this->sanction->statut,
                'etudiant_id' => $this->sanction->etudiant_id,
                'date_fin' => $this->sanction->date_fin?->toDateString(),
                'statut_disciplinaire_etudiant' => app(\App\Http\Services\DisciplinaryImpactService::class)->statut($this->sanction->etudiant_id),
            ] : null),
            'date_incident' => $this->date_incident?->toIso8601String(),
            'transmis_at' => $this->transmis_at?->toIso8601String(),
            'pris_en_charge_at' => $this->pris_en_charge_at?->toIso8601String(),
            'decision_at' => $this->decision_at?->toIso8601String(),
            'residence' => $this->whenLoaded('cite', fn () => [
                'cite' => $this->cite ? ['id' => $this->cite->id_cite, 'nom' => $this->cite->name] : null,
                'batiment' => $this->batiment ? ['id' => $this->batiment->id_batiment, 'nom' => $this->batiment->name] : null,
                'chambre' => $this->chambre ? ['id' => $this->chambre->id_chambre, 'numero' => $this->chambre->numero_chambre] : null,
            ]),
            'etudiant' => $this->whenLoaded('etudiant', fn () => $this->etudiant ? [
                'id' => $this->etudiant->id_etudiant,
                'nom' => $this->etudiant->name,
                'matricule' => $this->etudiant->matricule,
            ] : null),
            'historique' => $this->whenLoaded('historique', fn () => $this->historique->map(fn ($event) => [
                'id' => $event->id_historique,
                'action' => $event->action,
                'ancien_statut' => $event->ancien_statut,
                'nouveau_statut' => $event->nouveau_statut,
                'commentaire' => $event->commentaire,
                'auteur_id' => $event->auteur_id,
                'date' => $event->created_at?->toIso8601String(),
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
