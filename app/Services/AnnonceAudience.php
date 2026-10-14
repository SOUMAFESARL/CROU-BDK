<?php
namespace App\Services;

use App\Models\Annonce;
use App\Models\Etudiant;
use Illuminate\Database\Eloquent\Builder;

class AnnonceAudience
{
    public static function currentAssignment(Builder $query): void
    {
        $query->where('statut', 'confirmee')
            ->where('date_assignation', '<=', now())
            ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>', now()));
    }

    public function recipients(Annonce $annonce): Builder
    {
        $query = Etudiant::query();
        $target = $annonce->audience_cible ?? [];
        if ($annonce->audience === 'all') {
            return $query;
        }
        if ($annonce->audience === 'specific') {
            return $query->whereIn('id_etudiant', $target['etudiants'] ?? []);
        }
        $fields = ['cite' => ['cites', 'cite_id'], 'batiment' => ['batiments', 'batiment_id'], 'chambre' => ['chambres', 'id_chambre']];
        if (!isset($fields[$annonce->audience])) {
            return $query->whereRaw('1 = 0');
        }
        [$key, $column] = $fields[$annonce->audience];
        return $query->whereHas('assignationsChambre', function ($q) use ($target, $key, $column) {
            self::currentAssignment($q);
            $q->whereHas('chambre', fn ($room) => $room->whereIn($column, $target[$key] ?? []));
        });
    }

    public function visible(Builder $query, Etudiant $student): Builder
    {
        $rooms = $student->assignationsChambre()->where(function ($q) {
            self::currentAssignment($q);
        })->with('chambre')->get()->pluck('chambre')->filter();

        return $query->actif()->parCanal('flux')->where(function ($q) use ($student, $rooms) {
            $q->where('audience', 'all')->orWhere(fn ($specific) => $specific
                ->where('audience', 'specific')->whereJsonContains('audience_cible->etudiants', (string) $student->id_etudiant));
            foreach (['cite' => ['cites', 'cite_id'], 'batiment' => ['batiments', 'batiment_id'], 'chambre' => ['chambres', 'id_chambre']] as $audience => [$key, $column]) {
                foreach ($rooms->pluck($column)->filter()->unique() as $id) {
                    $q->orWhere(fn ($target) => $target->where('audience', $audience)
                        ->whereJsonContains('audience_cible->'.$key, (string) $id));
                }
            }
        });
    }
}
