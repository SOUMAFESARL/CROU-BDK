<?php

namespace App\Services;

use App\Models\AssignationChambre;
use App\Models\Campagne;
use App\Models\Etudiant;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Props de la page React « Tableau de bord » de l'espace étudiant (Etudiant/TableauDeBord).
 * Deux vues : résident (chambre confirmée) ou suivi de la demande d'hébergement.
 * Seules les valeurs affichées sont envoyées au navigateur, déjà formatées.
 */
class EtudiantDashboard
{
    /** Statuts legacy de la demande (synchronisés par DossierWorkflow) : libellé et teinte du KPI. */
    private const STATUTS = [
        'Acceptée' => ['Acceptée', 'green'],
        'En_verification' => ['En vérification', 'blue'],
        'En_attente' => ['En attente', 'amber'],
        'Refusée' => ['Refusée', 'red'],
    ];

    /**
     * @param  array{stats?: array, paiements?: mixed, prochainPaiement?: ?array}  $paiementData
     */
    public function props(Etudiant $etudiant, ?Campagne $campagne, array $paiementData, iterable $ordres): array
    {
        $demande = $etudiant->derniereDemande;
        $statut = $demande?->statut_demande;
        $demandes = collect($etudiant->demandes ?? []);
        $assignations = collect($etudiant->assignationsChambre ?? []);
        $aAccepte = $statut === 'Acceptée' || $demandes->count() > 1;
        $assignation = $demande ? $assignations->firstWhere('campagne_id', $demande->campagne_id) : null;
        [$libelle, $ton] = self::STATUTS[$statut] ?? [$statut ? ucfirst(str_replace('_', ' ', $statut)) : 'Aucune demande', 'neutral'];

        return [
            'anneeAcademique' => $campagne?->annee_campagne ?? getAnneeUniversitaire(date('Y-m-d')),
            'aujourdHui' => formatDateFr(date('Y-m-d'), true),
            'statut' => ['code' => $statut, 'libelle' => $libelle, 'ton' => $ton],
            'resident' => $aAccepte && $assignation
                ? $this->resident($etudiant, $assignation, $paiementData, $ordres, $demandes, $assignations)
                : null,
            'demande' => $aAccepte && $assignation ? null : $this->demande($demande, $campagne, $statut),
        ];
    }

    private function resident(Etudiant $etudiant, AssignationChambre $assignation, array $paiementData, iterable $ordres, Collection $demandes, Collection $assignations): array
    {
        $chambre = $assignation->chambre;
        $stats = $paiementData['stats'] ?? [];
        $nbImpayes = (int) ($stats['nb_impayes'] ?? 0);

        $logement = $etudiant->assignationActive ?? $assignation;
        $piece = $logement?->chambre;
        $batiment = $piece?->batiment;
        $residence = $piece?->cite ?? $batiment?->cite;
        $responsable = $residence?->responsable ?? $batiment?->responsable;

        return [
            'chambre' => $chambre?->numero_chambre,
            'batiment' => $chambre?->batiment?->name,
            'loyer' => formatSystemAmount($chambre?->typeChambre?->montant ?? 0),
            'resteAPayer' => formatSystemAmount((float) ($stats['total_impayes'] ?? 0)),
            'nbImpayes' => $nbImpayes,
            'prochainPaiement' => $paiementData['prochainPaiement']['libelle'] ?? null,
            'activites' => $this->activites($paiementData['paiements'] ?? [], $ordres, $demandes, $assignations),
            'infos' => [
                'residence' => $residence?->name,
                'chambre' => $piece?->numero_chambre ? 'Chambre '.$piece->numero_chambre : null,
                'responsable' => $responsable?->name,
                'contact' => $residence?->telephone1 ?? $residence?->telephone2 ?? $responsable?->telephone1 ?? $responsable?->telephone ?? null,
                'wifi' => $residence ? ($residence->wifi_gratuit ? 'Disponible' : 'Non disponible') : null,
            ],
            'urls' => [
                'paiements' => route('etudiants.dashboard', ['section' => 'paiements']),
                'maintenance' => route('etudiants.dashboard', ['section' => 'maintenance']),
                'contrat' => route('etudiants.pdfContrat'),
                'profil' => route('etudiants.profile'),
            ],
        ];
    }

    /** Cinq derniers événements : paiements, maintenance, demandes et attributions. */
    private function activites(iterable $paiements, iterable $ordres, Collection $demandes, Collection $assignations): array
    {
        $elements = fn ($source) => collect(is_object($source) && method_exists($source, 'getCollection') ? $source->getCollection() : $source)
            ->filter(fn ($item) => is_object($item));
        $libelleStatut = fn (string $statut) => (string) str($statut)->replace('_', ' ')->ucfirst();

        $activites = collect()
            ->merge($elements($paiements)->map(fn ($paiement) => [
                'date' => $paiement->date_paiement ?? $paiement->created_at,
                'icone' => 'credit-card',
                'fait' => $paiement->statut_paiement === 'valide',
                'texte' => 'Paiement de '.number_format((float) ($paiement->montant ?? 0), 0, ',', ' ').' '.systemCurrencySymbol(),
                'statut' => $paiement->statut_paiement === 'valide' ? 'Confirmé' : 'En attente',
            ]))
            ->merge($elements($ordres)->map(function ($ordre) use ($libelleStatut) {
                $statut = strtolower((string) ($ordre->statut ?? ''));

                return [
                    'date' => $ordre->created_at,
                    'icone' => 'wrench',
                    'fait' => in_array($statut, ['termine', 'terminee', 'cloture', 'resolu'], true),
                    'texte' => 'Demande de maintenance'.($ordre->numero_ot ? ' '.$ordre->numero_ot : ''),
                    'statut' => $libelleStatut($statut ?: 'soumise'),
                ];
            }))
            ->merge($elements($demandes)->map(function ($demande) use ($libelleStatut) {
                $statut = (string) ($demande->statut_demande ?? 'en attente');

                return [
                    'date' => $demande->date_demande ?? $demande->created_at,
                    'icone' => 'file-pen-line',
                    'fait' => str($statut)->ascii()->lower()->contains('accept'),
                    'texte' => 'Demande d’hébergement',
                    'statut' => $libelleStatut($statut),
                ];
            }))
            ->merge($elements($assignations)->map(fn ($assignation) => [
                'date' => $assignation->date_assignation ?? $assignation->created_at,
                'icone' => 'bed-double',
                'fait' => true,
                'texte' => 'Attribution de la chambre '.($assignation->chambre?->numero_chambre ?? 'non renseignée'),
                'statut' => 'Effectuée',
            ]));

        return $activites
            ->filter(fn ($activite) => ! empty($activite['date']))
            ->map(fn ($activite) => [...$activite, 'date' => Carbon::parse($activite['date'])])
            ->sortByDesc(fn ($activite) => $activite['date']->timestamp)
            ->take(5)
            ->map(fn ($activite) => [...$activite, 'date' => formatDateFr($activite['date']->format('Y-m-d'), true)])
            ->values()
            ->all();
    }

    private function demande($demande, ?Campagne $campagne, ?string $statut): array
    {
        $documents = collect($demande?->documents ?? []);
        $total = $documents->count();
        $valides = $documents->where('statut', 'valide')->count();
        $echeance = $campagne?->date_echeance_demande ? Carbon::parse($campagne->date_echeance_demande) : null;

        return [
            'existe' => (bool) $demande,
            'numero' => $demande?->numero_demande,
            'soumiseLe' => $demande?->date_demande ? formatDateFr($demande->date_demande, true) : null,
            'echeance' => $echeance ? formatDateFr($echeance->format('Y-m-d'), true) : null,
            'echeanceCourte' => $echeance ? formatDateFr($echeance->format('Y-m-d')) : null,
            // Fin de journée de l'échéance, pour le compte à rebours affiché côté navigateur.
            'echeanceIso' => $echeance?->copy()->endOfDay()->toIso8601String(),
            'moyenne' => $demande?->moyenne,
            'residenceSouhaitee' => $demande?->preference_residence,
            'progression' => $total > 0 ? (int) round($valides / $total * 100) : 0,
            'documentsValides' => $valides,
            'documents' => $documents->map(fn ($document) => [
                'id' => (string) $document->getKey(),
                'nom' => $document->type_document ?: 'Document',
                'statut' => in_array($document->statut, ['valide', 'rejete'], true) ? $document->statut : 'en_attente',
            ])->values()->all(),
            'decision' => $this->decision($demande, $campagne),
            'acceptee' => $statut === 'Acceptée',
        ];
    }

    /**
     * Réponse à la proposition de chambre : seulement à l'état `pre_attribue`, avec la pré-attribution
     * exigée par DossierWorkflow (`assignation_id`), comme le vérifie AcceptOrRefuseDemande.
     */
    public function decision($demande, ?Campagne $campagne): ?array
    {
        if (! $demande || $demande->etat_dossier !== 'pre_attribue' || ! $demande->assignation_id) {
            return null;
        }
        $proposition = AssignationChambre::with(['chambre.batiment', 'chambre.cite'])->find($demande->assignation_id);
        $chambre = $proposition?->chambre;

        return [
            'url' => route('etudiants.validation-demande', ['idDemande' => $demande->getKey()]),
            'assignationId' => (string) $demande->assignation_id,
            'chambre' => $chambre
                ? collect(['Chambre '.$chambre->numero_chambre, $chambre->batiment?->name, $chambre->cite?->name])->filter()->implode(' · ')
                : null,
            'annee' => $campagne?->annee_campagne,
            'limite' => $proposition?->date_fin ? formatDateFr($proposition->date_fin->format('Y-m-d'), true) : null,
        ];
    }
}
