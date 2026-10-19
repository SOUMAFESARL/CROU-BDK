<?php

namespace App\Services;

use App\Http\Services\DossierWorkflow;
use App\Models\AssignationChambre;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;
use App\Models\DepartureRequest;
use App\Models\Etudiant;
use Carbon\Carbon;

/**
 * Props de la page React « Mes demandes » de l'espace étudiant (Etudiant/MesDemandes) :
 * suivi du dossier d'hébergement en cours, demandes précédentes et demande de sortie de chambre.
 * Les actions restent celles des contrôleurs existants (DossierWorkflowController::student,
 * dashboardController::AcceptOrRefuseDemande et storeDepartureRequest), qui revérifient tout.
 */
class EtudiantDemandes
{
    /** Parcours nominal d'un dossier (DossierWorkflow::NEXT), affiché en frise. */
    private const PARCOURS = ['soumis', 'en_etude', 'eligible', 'confirme', 'attribue_definitif'];

    public const MOTIFS_SORTIE = [
        'fin_etudes' => 'Fin des études',
        'demenagement' => 'Déménagement',
        'stage' => 'Stage',
        'raison_personnelle' => 'Raison personnelle',
        'autre' => 'Autre motif',
    ];

    /** Délai minimal entre la demande de sortie et la date de sortie (dashboardController::storeDepartureRequest). */
    public const PREAVIS_SORTIE_JOURS = 15;

    public function props(Etudiant $etudiant, bool $focusSortie = false): array
    {
        $id = (string) $etudiant->getKey();
        $demandes = DemandeEtudiant::with(['campagne', 'transitions', 'proposition.chambre.batiment', 'proposition.chambre.cite'])
            ->where('etudiant_id', $id)
            ->latest('created_at')->orderByDesc('id_demande')
            ->get();
        $derniere = $demandes->first();

        return [
            'dossier' => $derniere ? $this->dossier($derniere) : null,
            'autres' => $demandes->skip(1)->map(fn ($demande) => [
                'id' => (string) $demande->getKey(),
                'annee' => $demande->campagne?->annee_campagne,
                'reference' => $demande->numero_demande ?: (string) $demande->getKey(),
                'soumiseLe' => $this->date($demande->date_demande ?? $demande->created_at),
                'etat' => $this->etat($demande->etat_dossier),
                'moyenne' => $demande->moyenne,
                'credits' => $demande->credit_valider,
            ])->values()->all(),
            'sortie' => $this->sortie($etudiant),
            'focusSortie' => $focusSortie,
            'liens' => [
                'readmission' => route('etudiants.readmission'),
                'entree' => route('etudiants.housing-entry'),
            ],
        ];
    }

    private function dossier(DemandeEtudiant $demande): array
    {
        $etat = (string) $demande->etat_dossier;
        $campagne = $demande->campagne;
        $documents = collect($demande->documents ?? []);
        $valides = $documents->where('statut', 'valide')->count();
        $proposition = $demande->proposition;
        $chambre = $proposition?->chambre;
        $echeance = $campagne?->date_echeance_demande;

        return [
            'id' => (string) $demande->getKey(),
            'reference' => $demande->numero_demande ?: (string) $demande->getKey(),
            'annee' => $campagne?->annee_campagne,
            'soumiseLe' => $this->date($demande->date_demande ?? $demande->created_at),
            'echeance' => $echeance ? $this->date($echeance) : null,
            'miseAJour' => $demande->updated_at?->locale('fr')->diffForHumans(),
            'moyenne' => $demande->moyenne,
            'credits' => $demande->credit_valider,
            'etat' => $this->etat($etat),
            'etapes' => $this->etapes($etat, $demande),
            'message' => $this->message($etat),
            'documents' => ['total' => $documents->count(), 'valides' => $valides],
            'proposition' => $chambre && in_array($etat, ['pre_attribue', 'accepte', 'attribue_definitif'], true) ? [
                'chambre' => collect(['Chambre '.$chambre->numero_chambre, $chambre->batiment?->name, $chambre->cite?->name])->filter()->implode(' · '),
                'limite' => $etat === 'pre_attribue' && $proposition->date_fin ? $proposition->date_fin->format('d/m/Y à H:i') : null,
            ] : null,
            // Confirmer son intérêt (éligible) ou répondre à une proposition : même chemin que le tableau de bord (ReponseProposition).
            'decision' => app(EtudiantDashboard::class)->decision($demande, $campagne instanceof Campagne ? $campagne : null),
            'actions' => $this->actions($etat),
            'transitionUrl' => route('etudiants.dossiers.transition', $demande->getKey()),
            'historique' => $demande->transitions->sortByDesc('id')->map(fn ($transition) => [
                'id' => $transition->getKey(),
                'date' => $transition->created_at?->format('d/m/Y à H:i'),
                'etat' => DossierWorkflow::LABELS[$transition->nouvel_etat] ?? $transition->nouvel_etat,
                'auteur' => $transition->details['auteur_nom'] ?? match ($transition->acteur_type) {
                    'etudiant' => 'Vous',
                    'admin' => 'Service Logement',
                    default => 'Traitement automatique',
                },
                'motif' => $transition->motif,
            ])->values()->all(),
        ];
    }

    /** Libellé et ton du badge d'un état de dossier. */
    private function etat(?string $etat): array
    {
        $etat = (string) $etat;

        return [
            'code' => $etat,
            'libelle' => DossierWorkflow::LABELS[$etat] ?? 'En attente',
            'ton' => match (true) {
                in_array($etat, ['confirme', 'accepte', 'attribue_definitif'], true) => 'success',
                in_array($etat, ['refuse', 'expire'], true) => 'danger',
                in_array($etat, ['incomplet', 'eligible', 'pre_attribue'], true) => 'warning',
                in_array($etat, ['annule', 'cloture', 'brouillon'], true) => 'neutral',
                default => 'info',
            },
        ];
    }

    /** Frise du parcours : étapes franchies, étape en cours, étapes à venir (avec la date de passage connue). */
    private function etapes(string $etat, DemandeEtudiant $demande): array
    {
        $position = array_search($etat, self::PARCOURS, true);
        if ($etat === 'incomplet') $position = 0;
        if ($etat === 'cloture') $position = count(self::PARCOURS);
        // Dossier arrêté (refus, annulation, expiration) : la frise s'arrête à la dernière étape atteinte.
        $arrete = in_array($etat, ['refuse', 'annule', 'expire'], true);
        if ($position === false) {
            $atteintes = $demande->transitions->pluck('nouvel_etat')->map(fn ($e) => array_search($e, self::PARCOURS, true))->filter(fn ($p) => $p !== false);
            $position = $atteintes->max() ?? -1;
        }
        $dates = $demande->transitions->groupBy('nouvel_etat')->map(fn ($groupe) => $groupe->last()->created_at?->format('d/m/Y'));

        return collect(self::PARCOURS)->map(fn ($etape, $index) => [
            'code' => $etape,
            'libelle' => DossierWorkflow::LABELS[$etape],
            'date' => $dates[$etape] ?? null,
            'statut' => $index < $position || ($index === $position && ($arrete || $etape === 'attribue_definitif'))
                ? 'fait'
                : ($index === $position ? 'en_cours' : 'a_venir'),
        ])->all();
    }

    private function message(string $etat): ?string
    {
        return match ($etat) {
            'brouillon' => 'Votre dossier n’est pas encore soumis. Soumettez-le pour qu’il soit étudié.',
            'soumis' => 'Votre dossier a été reçu. Il sera étudié par le service Logement.',
            'en_etude' => 'Votre dossier est en cours d’étude par le service Logement.',
            'incomplet' => 'Votre dossier est incomplet. Complétez les éléments signalés dans l’historique, puis soumettez-le à nouveau.',
            'eligible' => 'Votre dossier est éligible. Confirmez que vous souhaitez toujours être logé(e) cette année, ou refusez si vous n’êtes plus intéressé(e).',
            'confirme' => 'Vous avez confirmé votre demande. Une chambre vous sera attribuée par le service Logement selon les places disponibles.',
            'pre_attribue' => 'Une chambre vous est proposée. Acceptez ou refusez la proposition avant la date limite.',
            'accepte' => 'Vous avez accepté la proposition. La validation définitive reste à effectuer par le service Logement.',
            'attribue_definitif' => 'Votre attribution est définitive. Approuvez votre contrat pour obtenir votre rendez-vous d’entrée.',
            'refuse' => 'Votre dossier n’a pas été retenu. Le motif figure dans l’historique.',
            'annule' => 'Ce dossier a été annulé.',
            'expire' => 'Le délai de ce dossier est expiré.',
            'cloture' => 'Ce dossier est clôturé.',
            default => null,
        };
    }

    /** Transitions ouvertes à l'étudiant hors proposition (mêmes règles que DossierWorkflow pour l'acteur `etudiant`). */
    private function actions(string $etat): array
    {
        $possibles = match ($etat) {
            'brouillon', 'incomplet' => ['soumis', 'annule'],
            'soumis' => ['annule'],
            default => [],
        };

        return array_values(array_map(fn ($cible) => [
            'etat' => $cible,
            'libelle' => $cible === 'soumis' ? 'Soumettre mon dossier' : 'Annuler mon dossier',
            'motifObligatoire' => $cible === 'annule',
        ], array_intersect($possibles, DossierWorkflow::NEXT[$etat] ?? [])));
    }

    private function sortie(Etudiant $etudiant): array
    {
        $id = (string) $etudiant->getKey();
        $demandes = DepartureRequest::with(['chambre.cite', 'chambre.batiment'])
            ->where('etudiant_id', $id)->latest('date_demande')->latest('created_at')->get();
        $enCours = $demandes->firstWhere('statut', 'en_attente');
        $assignation = AssignationChambre::with(['chambre.cite', 'chambre.batiment'])->actives()
            ->where('etudiant_id', $id)->latest('date_assignation')->first();
        $chambre = $assignation?->chambre;
        $residence = fn ($chambre) => $chambre?->cite?->name ?? $chambre?->batiment?->name;

        return [
            'enCours' => $enCours ? [
                'id' => (string) $enCours->getKey(),
                'chambre' => $enCours->chambre?->numero_chambre,
                'residence' => $residence($enCours->chambre),
                'date' => $enCours->date_sortie_prevue?->format('d/m/Y'),
                'motif' => self::MOTIFS_SORTIE[$enCours->motif] ?? $enCours->motif,
                'deposeeLe' => $enCours->date_demande?->format('d/m/Y'),
                'approuvee' => $enCours->decision === 'approuvee',
                'decideeLe' => $enCours->date_decision?->format('d/m/Y à H:i'),
            ] : null,
            'historique' => $demandes->where('statut', '!=', 'en_attente')->map(fn ($demande) => [
                'id' => (string) $demande->getKey(),
                'chambre' => $demande->chambre?->numero_chambre,
                'date' => $demande->date_sortie_prevue?->format('d/m/Y'),
                'statut' => match (true) {
                    $demande->statut === 'cloturee' => ['libelle' => 'Clôturée', 'ton' => 'success'],
                    $demande->decision === 'refusee' => ['libelle' => 'Refusée', 'ton' => 'danger'],
                    default => ['libelle' => 'Annulée', 'ton' => 'neutral'],
                },
                'motifDecision' => $demande->motif_decision,
            ])->values()->all(),
            // Formulaire ouvert seulement avec une chambre active et sans demande en attente.
            'formulaire' => ! $enCours && $chambre ? [
                'url' => route('etudiants.departure-requests.store'),
                'nom' => $etudiant->name,
                'matricule' => $etudiant->matricule,
                'chambre' => $chambre->numero_chambre,
                'residence' => $residence($chambre),
                'dateMin' => today()->addDays(self::PREAVIS_SORTIE_JOURS)->toDateString(),
                'dateDefaut' => today()->addDays(30)->toDateString(),
                'motifs' => collect(self::MOTIFS_SORTIE)->map(fn ($libelle, $code) => ['code' => $code, 'libelle' => $libelle])->values()->all(),
            ] : null,
        ];
    }

    private function date($valeur): ?string
    {
        return $valeur ? Carbon::parse($valeur)->format('d/m/Y') : null;
    }
}
