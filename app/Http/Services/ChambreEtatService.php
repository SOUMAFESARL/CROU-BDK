<?php

namespace App\Http\Services;

use App\Models\Chambre;
use App\Models\OrdreTravail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChambreEtatService
{
    public function modifier(string $id, array $data): Chambre
    {
        return DB::transaction(function () use ($id, $data) {
            $room = app(AttributionRules::class)->verrouillerChambre($id);
            if (isset($data['etat']) && $data['etat'] !== $room->etat) {
                $etat = $data['etat'];
                if (!array_key_exists($etat, Chambre::ETATS)) {
                    throw ValidationException::withMessages(['etat' => 'État de chambre invalide.']);
                }
                $places = $room->assignations()->mobilisantPlace()->lockForUpdate()->get();
                $occupants = $room->getNombrePlacesOccupees();
                if ($etat === 'occupee' && $occupants === 0) {
                    throw ValidationException::withMessages(['etat' => 'Une chambre sans occupant ne peut pas être marquée occupée.']);
                }
                if ($etat === 'disponible' && $places->count() >= (int) $room->capacite_max) {
                    throw ValidationException::withMessages(['etat' => 'Toutes les places sont occupées ou réservées.']);
                }
                if (in_array($etat, ['disponible', 'occupee'], true)
                    && OrdreTravail::where('chambre_id', $id)
                        ->whereNotIn('statut', ['termine', 'annule'])->exists()) {
                    throw ValidationException::withMessages(['etat' => 'Terminez ou annulez les maintenances ouvertes avant de remettre la chambre en service.']);
                }
                $data['occupants_actuels'] = $occupants;
            }
            $room->update($data);
            return $room;
        }, 3);
    }
}
