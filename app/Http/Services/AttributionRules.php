<?php

namespace App\Http\Services;

use App\Models\Batiment;
use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\Cite;
use App\Models\Etudiant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttributionRules
{
    /** A appeler dans la transaction qui enregistre l'attribution. */
    public function verrouillerChambre(string $id): Chambre
    {
        $room = Chambre::findOrFail($id);
        // Meme ordre de verrouillage que la desactivation en cascade.
        $cite = Cite::whereKey($room->cite_id)->lockForUpdate()->firstOrFail();
        $batiment = Batiment::whereKey($room->batiment_id)->lockForUpdate()->firstOrFail();
        $room = Chambre::whereKey($id)->lockForUpdate()->firstOrFail();
        if ((string) $room->cite_id !== (string) $cite->getKey()
            || (string) $room->batiment_id !== (string) $batiment->getKey()) {
            throw ValidationException::withMessages(['chambre_id' => 'La chambre a changé. Veuillez réessayer.']);
        }
        return $room->setRelation('cite', $cite)->setRelation('batiment', $batiment);
    }

    public function erreurs(Etudiant $student, Chambre $room, ?Campagne $campaign = null, ?string $ignore = null): array
    {
        $errors = [];
        $discipline = app(DisciplinaryImpactService::class)->statut($student->getKey());
        if (in_array($discipline, ['expulsion', 'suspension'], true)) {
            $errors[] = 'Attribution bloquée : sanction disciplinaire active ('.$discipline.').';
        }
        if (!$room->estActivePourAttribution()) {
            $errors[] = 'La chambre, son bâtiment ou sa cité est désactivé, supprimé ou incohérent.';
        }
        $reservationExistante = $ignore && $room->assignations()->mobilisantPlace()
            ->whereKey($ignore)->where('etudiant_id', $student->getKey())->exists();
        $states = $reservationExistante ? ['disponible', 'reservee', 'occupee'] : ['disponible'];
        if (!in_array($room->etat, $states, true)) {
            $errors[] = 'Cette chambre est indisponible : '.$room->getLabelEtat().'.';
        }
        if ($room->pmr && !$student->pmr_confirme) {
            $errors[] = 'Cette chambre nécessite un statut PMR confirmé par un agent.';
        }
        $sex = $this->sexe($student->genre?->name);
        foreach ([$room->typeOccupation?->name, $room->batiment?->typeOccupation?->name, $room->cite?->typeCite?->name] as $type) {
            $normalized = Str::lower(Str::ascii(trim((string) $type)));
            if ($normalized === 'mixte') {
                continue;
            }
            $required = $this->sexe($type);
            if ($required === null) {
                $errors[] = "Le type d'occupation du parc doit être renseigné (hommes, femmes ou mixte).";
            } elseif ($sex !== $required) {
                $errors[] = "Le sexe de l'étudiant ne correspond pas aux règles de la chambre, du bâtiment ou de la cité.";
            }
        }
        if ($campaign && !$campaign->residencesCampagne()->where('cite_id', $room->cite_id)->exists()) {
            $errors[] = "Cette cité ne fait pas partie de la campagne d'attribution.";
        }
        $places = $room->assignations()->mobilisantPlace()
            ->when($ignore, fn ($q) => $q->where('id_assignation', '!=', $ignore));
        // Lecture courante sous MySQL REPEATABLE READ, apres attente du verrou de chambre.
        $used = $room->getConnection()->transactionLevel() > 0
            ? $places->lockForUpdate()->get()->count() : $places->count();
        if ($used >= (int) $room->capacite_max) {
            $errors[] = 'Toutes les places sont occupées ou réservées.';
        }
        return array_values(array_unique($errors));
    }

    public function verifier(Etudiant $student, Chambre $room, ?Campagne $campaign = null, ?string $ignore = null): void
    {
        if ($errors = $this->erreurs($student, $room, $campaign, $ignore)) {
            throw ValidationException::withMessages(['chambre_id' => $errors]);
        }
    }

    /**
     * Public accepté par la chambre (types d'occupation chambre, bâtiment et cité), pour le
     * pré-contrôle de l'écran d'attribution : 'mixte', 'm', 'f', 'incomplet' (type non renseigné)
     * ou 'conflit' (niveaux contradictoires). La règle qui fait foi reste erreurs().
     */
    public function sexeRequis(Chambre $room): string
    {
        $requis = [];
        foreach ([$room->typeOccupation?->name, $room->batiment?->typeOccupation?->name, $room->cite?->typeCite?->name] as $type) {
            if (Str::lower(Str::ascii(trim((string) $type))) === 'mixte') {
                continue;
            }
            $sexe = $this->sexe($type);
            if ($sexe === null) {
                return 'incomplet';
            }
            $requis[$sexe] = true;
        }

        return match (count($requis)) {
            0 => 'mixte',
            1 => array_key_first($requis),
            default => 'conflit',
        };
    }

    /** 'm', 'f' ou null si le genre de l'étudiant n'est pas reconnu. */
    public function sexeEtudiant(Etudiant $student): ?string
    {
        return $this->sexe($student->genre?->name);
    }

    private function sexe(?string $value): ?string
    {
        return match (Str::lower(Str::ascii(trim((string) $value)))) {
            'm', 'masculin', 'homme', 'hommes', 'garcon', 'garcons' => 'm',
            'f', 'feminin', 'femme', 'femmes', 'fille', 'filles' => 'f',
            default => null,
        };
    }
}
