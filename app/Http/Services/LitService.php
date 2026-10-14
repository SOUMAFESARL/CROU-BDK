<?php

namespace App\Http\Services;

use App\Models\Chambre;
use App\Models\Lit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LitService
{
    /** Provisionne les lits et rattache les réservations historiques sans déclencher de facturation. */
    public function synchroniser(Chambre $room): void
    {
        DB::transaction(function () use ($room) {
            $room = Chambre::whereKey($room->getKey())->lockForUpdate()->firstOrFail();
            $active = $room->assignations()->mobilisantPlace()->orderBy('date_assignation')
                ->orderBy('id_assignation')->lockForUpdate()->get();
            if ($active->count() > (int) $room->capacite_max) {
                throw ValidationException::withMessages(['numero_lit' => 'La chambre dépasse sa capacité : régularisez les attributions existantes.']);
            }
            $rows = [];
            for ($i = 1; $i <= (int) $room->capacite_max; $i++) {
                $rows[] = ['chambre_id' => $room->getKey(), 'numero' => $i, 'created_at' => now(), 'updated_at' => now()];
            }
            if ($rows) {
                Lit::insertOrIgnore($rows);
            }
            $lits = Lit::where('chambre_id', $room->getKey())->where('numero', '<=', $room->capacite_max)
                ->orderBy('numero')->lockForUpdate()->get();
            $used = $active->pluck('lit_id')->filter();
            if ($used->unique()->count() !== $used->count() || $used->diff($lits->modelKeys())->isNotEmpty()) {
                throw ValidationException::withMessages(['numero_lit' => 'Des attributions de lits sont incohérentes dans cette chambre.']);
            }
            $free = $lits->whereNotIn('id', $used)->values();
            foreach ($active->whereNull('lit_id') as $assignment) {
                $lit = $free->shift();
                DB::table('assignation_chambres')->where('id_assignation', $assignment->getKey())->update(['lit_id' => $lit->getKey()]);
            }
        });
    }

    /** L'appelant garde la transaction de chambre ouverte jusqu'à l'enregistrement de l'attribution. */
    public function choisir(Chambre $room, ?int $numero = null, ?string $ignore = null): Lit
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Le choix du lit nécessite une transaction.');
        }
        $this->synchroniser($room);
        $used = $room->assignations()->mobilisantPlace()
            ->when($ignore, fn ($q) => $q->where('id_assignation', '!=', $ignore))
            ->lockForUpdate()->get()->pluck('lit_id')->filter();
        $lit = Lit::where('chambre_id', $room->getKey())->where('numero', '<=', $room->capacite_max)
            ->when($numero !== null, fn ($q) => $q->where('numero', $numero))
            ->whereNotIn('id', $used)->orderBy('numero')->lockForUpdate()->first();
        if (!$lit) {
            throw ValidationException::withMessages(['numero_lit' => 'Ce lit est occupé, réservé ou inexistant dans cette chambre.']);
        }
        return $lit;
    }

    /** Lecture seule, également utilisable avant la première attribution d'une chambre. */
    public function disponibles(Chambre $room): array
    {
        if (!$room->estActivePourAttribution() || $room->etat !== 'disponible') {
            return [];
        }
        $active = $room->assignations()->mobilisantPlace()->with('lit')->get();
        $used = $active->pluck('lit.numero')->filter()->all();
        $numbers = (int) $room->capacite_max > 0 ? range(1, (int) $room->capacite_max) : [];
        $free = array_values(array_diff($numbers, $used));
        // Les anciennes attributions sans lit consomment déjà leurs places.
        return array_slice($free, $active->whereNull('lit_id')->count());
    }
}
