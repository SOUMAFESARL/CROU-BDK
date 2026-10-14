<?php

namespace App\Services;

use App\Models\{Equipement, EquipementAffectation, Cite, Batiment, Chambre};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EquipmentAllocationService
{
    public function allocate(array $data, ?string $actor): EquipementAffectation
    {
        return DB::transaction(function () use ($data, $actor) {
            $equipment = Equipement::whereKey($data['equipement_id'])->lockForUpdate()->firstOrFail();
            Cite::whereKey($data['cite_id'])->firstOrFail();
            $building = !empty($data['batiment_id']) ? Batiment::whereKey($data['batiment_id'])->where('cite_id', $data['cite_id'])->first() : null;
            if (!empty($data['batiment_id']) && !$building) throw ValidationException::withMessages(['batiment_id' => 'Ce bâtiment ne fait pas partie de la cité sélectionnée.']);
            if (!empty($data['chambre_id']) && (!$building || !Chambre::whereKey($data['chambre_id'])->where('batiment_id', $building->getKey())->where('cite_id', $data['cite_id'])->exists())) {
                throw ValidationException::withMessages(['chambre_id' => 'Choisissez une chambre du bâtiment sélectionné.']);
            }
            if ((int) $data['quantite'] < 1 || $equipment->stock_actuel < $data['quantite']) throw ValidationException::withMessages(['quantite' => 'Quantité invalide ou stock disponible insuffisant.']);
            $allocation = EquipementAffectation::create($data + ['created_by' => $actor]);
            $this->movement($equipment, 'exit', $allocation->quantite, 'Affectation au parc '.$allocation->id, $actor);
            return $allocation;
        });
    }

    public function returnToStock(EquipementAffectation $allocation, ?string $actor): void
    {
        DB::transaction(function () use ($allocation, $actor) {
            $equipment = Equipement::whereKey($allocation->equipement_id)->lockForUpdate()->firstOrFail();
            $allocation = EquipementAffectation::whereKey($allocation->id)->lockForUpdate()->firstOrFail();
            if ($allocation->returned_at) return;
            if ($allocation->etat !== 'bon') throw ValidationException::withMessages(['etat' => 'Remettez le matériel en bon état avant de le réintégrer au stock disponible.']);
            $this->movement($equipment, 'entry', $allocation->quantite, 'Retour du parc '.$allocation->id, $actor);
            $allocation->update(['returned_at' => now()]);
        });
    }

    public function movement(Equipement $equipment, string $type, float $quantity, ?string $reason, ?string $actor): void
    {
        if (!$equipment->mouvements()->exists() && (float) $equipment->stock_initial > 0) {
            $equipment->mouvements()->create(['type' => 'entry', 'quantity' => $equipment->stock_initial, 'reason' => 'Reprise du stock initial', 'created_by' => $actor]);
        }
        $equipment->mouvements()->create(['type' => $type, 'quantity' => $quantity, 'reason' => $reason, 'created_by' => $actor]);
        $equipment->updateStockActuel();
    }
}
