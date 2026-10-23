<?php

namespace App\Services;

use App\Http\Services\DisciplinaryImpactService;
use App\Models\AssignationChambre;
use App\Models\DisciplinaryReport;
use App\Models\Etudiant;

/**
 * Props de la page React « Signaler un incident » de l'espace étudiant (Etudiant/Incidents) :
 * statut disciplinaire de l'étudiant, résidence rattachée au signalement (assignation active,
 * comme DisciplinaryIncidentController::store) et suivi des signalements qu'il a déclarés.
 * Seul l'état du dossier est montré : ni la décision ni la sanction (qui peut viser un autre résident).
 */
class EtudiantIncidents
{
    /** Natures acceptées par DisciplinaryIncidentController::store (code => libellé enregistré). */
    public const NATURES = [
        'tapage' => 'Tapage / nuisance sonore',
        'violence' => 'Violence ou menace',
        'harcelement' => 'Harcèlement',
        'degradation' => 'Dégradation volontaire',
        'vol' => 'Vol ou tentative de vol',
        'non_respect_reglement' => 'Non-respect du règlement',
        'autre' => 'Autre incident',
    ];

    /** Statuts d'un rapport (DisciplinaryReportController) : libellé et ton du badge. */
    private const STATUTS = [
        'brouillon' => ['Brouillon', 'neutral'],
        'transmis' => ['Transmis', 'info'],
        'en_cours' => ['En cours d’examen', 'warning'],
        'retourne' => ['Retourné', 'danger'],
        'decision_prise' => ['Décision prise', 'success'],
        'cloture' => ['Clôturé', 'success'],
    ];

    private const GRAVITES = ['faible' => 'Faible', 'moyenne' => 'Moyenne', 'grave' => 'Grave', 'critique' => 'Critique'];

    public function props(Etudiant $etudiant): array
    {
        $id = (string) $etudiant->getKey();
        $assignation = AssignationChambre::actives()
            ->where('etudiant_id', $id)
            ->with(['chambre.cite', 'chambre.batiment'])
            ->latest('date_assignation')
            ->first();
        $chambre = $assignation?->chambre;

        $signalements = DisciplinaryReport::query()
            ->where('declarant_id', $id)
            ->latest('date_incident')
            ->paginate(10)
            ->withPath(route('etudiants.dashboard'))->appends(['section' => 'incidents']);

        $parStatut = DisciplinaryReport::query()->where('declarant_id', $id)->toBase()
            ->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        $statut = $etudiant->statut_disciplinaire;

        return [
            'statutDisciplinaire' => $statut && $statut !== 'aucune' ? [
                'cle' => $statut,
                'libelle' => DisciplinaryImpactService::LABELS[$statut] ?? ucfirst($statut),
            ] : null,
            'residence' => $chambre ? [
                'cite' => $chambre->cite?->name,
                'batiment' => $chambre->batiment?->name,
                'chambre' => $chambre->numero_chambre ?: $chambre->code_chambre,
            ] : null,
            'natures' => collect(self::NATURES)->map(fn (string $libelle, string $code) => ['code' => $code, 'libelle' => $libelle])->values()->all(),
            'compteurs' => [
                'total' => (int) $parStatut->sum(),
                'enCours' => (int) $parStatut->only(['transmis', 'en_cours'])->sum(),
                'traites' => (int) $parStatut->only(['decision_prise', 'cloture'])->sum(),
                'retournes' => (int) ($parStatut['retourne'] ?? 0),
            ],
            'signalements' => collect($signalements->items())->map(fn (DisciplinaryReport $rapport) => $this->signalement($rapport))->values()->all(),
            'pagination' => EtudiantMaintenance::pagination($signalements),
            'urls' => ['enregistrer' => route('etudiants.incidents.store')],
        ];
    }

    private function signalement(DisciplinaryReport $rapport): array
    {
        [$libelle, $ton] = self::STATUTS[$rapport->statut] ?? [ucfirst(str_replace('_', ' ', (string) $rapport->statut)), 'neutral'];

        return [
            'id' => (string) $rapport->getKey(),
            'numero' => (string) $rapport->numero_rapport,
            'nature' => (string) $rapport->nature,
            'lieu' => $rapport->lieu,
            'date' => $rapport->date_incident ? formatDateFr($rapport->date_incident->format('Y-m-d')).' à '.$rapport->date_incident->format('H:i') : null,
            'gravite' => ['cle' => $rapport->gravite, 'libelle' => self::GRAVITES[$rapport->gravite] ?? ucfirst((string) $rapport->gravite)],
            'statut' => ['cle' => $rapport->statut, 'libelle' => $libelle, 'ton' => $ton],
            'confidentiel' => (bool) $rapport->confidentiel,
            'transmisLe' => $rapport->transmis_at?->format('d/m/Y'),
        ];
    }
}
