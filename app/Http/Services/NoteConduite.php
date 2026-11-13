<?php

namespace App\Http\Services;

use App\Models\Sanction;
use Illuminate\Support\Collection;

/**
 * Note de conduite d'un étudiant (docs/parametrage-sanctions.md) :
 * note initiale du paramétrage − points retirés par ses sanctions individuelles, sans descendre sous 0.
 *
 * - les points retirés sont ceux du barème au moment de la sanction (`sanctions.points_retires`), sinon ceux du type ;
 * - une sanction **annulée** restitue ses points ; une sanction **levée** a été appliquée : ses points restent retirés ;
 * - les sanctions collectives (chambre, bâtiment, cité) ne modifient pas la note individuelle.
 *
 * Sert au critère de rejet « Conduite en cité » de la réadmission (note minimale) et à l'espace étudiant.
 */
class NoteConduite
{
    public function __construct(private CatalogueSanctions $catalogue) {}

    public function note(string $etudiantId): float
    {
        return $this->bilan($etudiantId)['note'];
    }

    /**
     * @return array{initiale: float, retires: float, note: float, sanctions: Collection<int, array{sanction: Sanction, points: float, compte: bool}>}
     */
    public function bilan(string $etudiantId): array
    {
        $sanctions = Sanction::query()
            ->where('scope_type', 'etudiant')
            ->where('etudiant_id', $etudiantId)
            ->orderByDesc('date_decision')->orderByDesc('created_at')
            ->get()
            ->map(fn (Sanction $sanction) => [
                'sanction' => $sanction,
                'points' => $this->pointsRetires($sanction),
                'compte' => $sanction->statut !== 'annulee',
            ]);
        $initiale = $this->catalogue->noteInitiale();
        $retires = round((float) $sanctions->where('compte', true)->sum('points'), 2);

        return [
            'initiale' => $initiale,
            'retires' => $retires,
            'note' => max(0.0, round($initiale - $retires, 2)),
            'sanctions' => $sanctions,
        ];
    }

    /** Points retirés par une sanction : figés à sa création, sinon barème actuel de son type. */
    public function pointsRetires(Sanction $sanction): float
    {
        return $sanction->points_retires !== null
            ? (float) $sanction->points_retires
            : $this->catalogue->points($sanction->type);
    }

    /**
     * Critère de rejet « Conduite en cité » d'un jeu de critères de réadmission (`CriteresCampagne`) :
     * motif du rejet si le critère est actif et la note inférieure au minimum, sinon null.
     */
    public function motifRejet(array $reglesReadmission, string $etudiantId): ?string
    {
        $conduite = $reglesReadmission['readmission']['conduite'] ?? [];
        $minimum = $conduite['note_min'] ?? null;
        if (! ($conduite['actif'] ?? false) || $minimum === null) {
            return null;
        }
        $bilan = $this->bilan($etudiantId);
        if ($bilan['note'] >= (float) $minimum) {
            return null;
        }
        $format = fn (float $valeur) => CatalogueSanctions::formatPoints($valeur);

        return 'Note de conduite insuffisante : '.$format($bilan['note']).'/'.$format($bilan['initiale'])
            .' pour un minimum de '.$format((float) $minimum).'.';
    }
}
