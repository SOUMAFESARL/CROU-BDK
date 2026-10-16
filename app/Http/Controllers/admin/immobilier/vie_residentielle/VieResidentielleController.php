<?php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

use App\Http\Controllers\Controller;
use App\Models\AssignationChambre;
use App\Models\Chambre;
use App\Models\Cite;
use App\Models\Paiement;
use App\Models\Caution;
use App\Models\Sanction;
use App\Models\DisciplinaryReport;
use App\Models\EtatDesLieux;
use App\Models\DepartureRequest;
use App\Models\DepartureSettlement;
use App\Models\Loyer;
use App\Http\Services\OrdreTravailService;
use Carbon\Carbon;
use Inertia\Inertia;

class VieResidentielleController extends Controller
{
    private $ordreTravailService;

    public function __construct(OrdreTravailService $ordreTravailService)
    {
        $this->ordreTravailService = $ordreTravailService;
    }

    private function normalizeEtat(string $etat): string
    {
        $etat = mb_strtolower(trim($etat));

        return match ($etat) {
            'disponible' => 'disponible',
            'en_maintenance', 'maintenance' => 'maintenance',
            'reservee', 'réservée', 'reservee' => 'reservee',
            'occupee', 'occupée', 'occuppee' => 'occupee',
            'hors_service', 'hors-service' => 'hors_service',
            default => 'inconnu',
        };
    }

    public function index()
    {
        $viewAccess = app(\App\Services\AdminViewAccess::class)->housing(auth('admin')->user());
        $cites = in_array(true, $viewAccess, true) ? Cite::query()->orderBy('name')->get() : collect();
        $residences = [];

        $globalCapacity = 0;
        $globalOccupied = 0;
        $globalMaintenance = 0;
        $globalHorsService = 0;
        $globalDue = 0.0;
        $globalPaid = 0.0;
        $globalUnpaid = 0.0;
        $globalReaffectations = 0;

        // Récupération des données de maintenance
        $ordresTravail = $viewAccess['maintenance'] ? $this->ordreTravailService->getAll() : collect();
        $paiementsGlobaux = $viewAccess['finance'] ? Paiement::all() : collect();
        $cautions = $viewAccess['finance'] ? Caution::all() : collect();
        $sanctions = $viewAccess['discipline'] ? Sanction::all() : collect();
        $rapportsDisciplinaires = $viewAccess['discipline'] ? DisciplinaryReport::all() : collect();
        $assignations = $viewAccess['occupation'] || $viewAccess['contracts'] || $viewAccess['departures']
            ? AssignationChambre::with('chambre:id_chambre,cite_id')->get() : collect();
        $assignationsActives = $viewAccess['occupation']
            ? AssignationChambre::actives()->with('chambre:id_chambre,cite_id')->get() : collect();
        $contractApprovals = $viewAccess['contracts'] ? \App\Models\StudentContractApproval::all() : collect();
        $loyers = $viewAccess['finance'] ? Loyer::where('statut', '!=', 'annule')->get() : collect();
        $academicStart = Carbon::parse(($viewAccess['occupation'] ? explode('-', getAnneeUniversitaire())[0] : now()->year).'-09-01')->startOfDay();
        $etatsDesLieux = $viewAccess['inspections'] ? EtatDesLieux::with(['pieces.elements', 'pieces.images'])->get() : collect();
        $demandesSortie = $viewAccess['departures'] ? DepartureRequest::all() : collect();
        $decomptesSortie = $viewAccess['departures'] ? DepartureSettlement::all() : collect();
        
        // Initialisation des compteurs globaux de maintenance
        $globalOTTotal = $ordresTravail->count();
        $globalOTResolus = $ordresTravail->where('statut', 'termine')->count();
        // Ordres ouverts : en attente, en cours ou en attente de pièces ; « urgent » = priorité critique (seule valeur de OrdreTravail::PRIORITES).
        $statutsOuverts = ['en_attente', 'en_cours', 'en_attente_pieces'];
        $globalOTUrgents = $ordresTravail->where('priorite', 'critique')
            ->whereIn('statut', $statutsOuverts)
            ->count();
        $globalOTEnCours = $ordresTravail->whereIn('statut', $statutsOuverts)->count();

        foreach ($cites as $cite) {
            $chambres = $viewAccess['occupation'] || $viewAccess['departures']
                ? Chambre::where('cite_id', $cite->id_cite)->get() : collect();
            $totalCapacity = (int) $chambres->sum('capacite_max');
            $activeAssignmentsResidence = $assignationsActives->filter(fn ($assignment) => $assignment->chambre?->cite_id === $cite->id_cite);
            $occupiedByRoom = $activeAssignmentsResidence->countBy('chambre_id');
            $occupiedPlaces = $activeAssignmentsResidence->count();

            $availableCount = $chambres->filter(fn ($chambre) =>
                $this->normalizeEtat($chambre->etat) === 'disponible'
                && ($occupiedByRoom[$chambre->id_chambre] ?? 0) < max(0, (int) $chambre->capacite_max)
            )->count();
            $maintenanceCount = $chambres->filter(fn ($chambre) => $this->normalizeEtat($chambre->etat) === 'maintenance')->count();
            $horsServiceCount = $chambres->filter(fn ($chambre) => $this->normalizeEtat($chambre->etat) === 'hors_service')->count();
            $reservedCount = $chambres->filter(fn ($chambre) => $this->normalizeEtat($chambre->etat) === 'reservee')->count();
            $occupiedStatusCount = $chambres->filter(fn ($chambre) => ($occupiedByRoom[$chambre->id_chambre] ?? 0) > 0)->count();

            $paiements = $paiementsGlobaux->where('cite_id', $cite->id_cite);
            $loyersResidence = $loyers->where('cite_id', $cite->id_cite);
            $totalDue = (float) $loyersResidence->sum(fn ($loyer) => max(0, (float) $loyer->montant_total));
            $unpaidAmount = (float) $loyersResidence->sum(fn ($loyer) => max(0, (float) $loyer->solde_restant));
            $paidAmount = max(0, $totalDue - $unpaidAmount);
            $recoveryRate = $totalDue > 0 ? round(min(100, ($paidAmount / $totalDue) * 100), 1) : 0;
            $unpaidRate = $totalDue > 0 ? round(min(100, ($unpaidAmount / $totalDue) * 100), 1) : 0;
            $occupationRate = $totalCapacity > 0 ? round(min(100, ($occupiedPlaces / $totalCapacity) * 100), 1) : 0;
            $reaffectations = $viewAccess['occupation'] ? AssignationChambre::where('statut', 'confirmee')
                ->where('type_assignation', 'reaffectation')
                ->where('date_assignation', '>=', $academicStart)
                ->whereHas('chambre', function ($query) use ($cite) {
                    $query->where('cite_id', $cite->id_cite);
                })
                ->count() : 0;

            // Statistiques maintenance par résidence
            $otResidence = $ordresTravail->where('cite_id', $cite->id_cite);
            $otResidenceResolus = $otResidence->where('statut', 'termine')->count();
            $otResidenceTotal = $otResidence->count();
            $otResidenceTauxResolution = $otResidenceTotal > 0 
                ? round(($otResidenceResolus / $otResidenceTotal) * 100, 1) 
                : 0;
            
            $otEnCours = $otResidence->whereIn('statut', $statutsOuverts)->count();
            // Une répétition concerne le même équipement, ou la même chambre et catégorie.
            $recurrenceCandidates = $otResidence->where('statut', '!=', 'annule');
            $recurrences = $recurrenceCandidates
                ->filter(fn ($ot) => filled($ot->equipement_id) || (filled($ot->chambre_id) && filled($ot->categorie)))
                ->groupBy(fn ($ot) => filled($ot->equipement_id)
                    ? 'equipement:'.$ot->equipement_id
                    : 'chambre:'.$ot->chambre_id.':'.$ot->categorie)
                ->sum(fn ($group) => max(0, $group->count() - 1));
            $recurrenceRate = $recurrenceCandidates->count()
                ? round($recurrences / $recurrenceCandidates->count() * 100, 1) : 0;
            // Calcul du temps moyen de résolution
            $tempsResolution = $otResidence
                ->where('statut', 'termine')
                ->filter(fn($ot) => $ot->date_debut_intervention && $ot->date_resolution)
                ->map(function($ot) {
                    return \Carbon\Carbon::parse($ot->date_debut_intervention)
                        ->diffInDays(\Carbon\Carbon::parse($ot->date_resolution));
                });
            
            $tempsResolutionMoyen = $tempsResolution->count() > 0 
                ? round($tempsResolution->avg(), 1) 
                : 0;

            // Incidents par catégorie
            $incidentsParCategorie = $otResidence->groupBy('categorie')
                ->map->count()
                ->toArray();
            
            $categoriePrincipale = !empty($incidentsParCategorie) 
                ? array_search(max($incidentsParCategorie), $incidentsParCategorie) 
                : '-';

            $roomIds = $chambres->pluck('id_chambre');
            $cautionsResidence = $cautions->where('cite_id', $cite->id_cite);
            $sanctionsResidence = $sanctions->where('cite_id', $cite->id_cite);
            $rapportsResidence = $rapportsDisciplinaires->where('cite_id', $cite->id_cite);
            $assignationsResidence = $assignations->filter(fn ($a) => $a->chambre?->cite_id === $cite->id_cite);
            $rotationRoomsCount = $assignationsResidence->filter(fn ($assignment) => in_array($assignment->statut, ['annulee', 'expulsion'], true) && $assignment->date_fin && Carbon::parse($assignment->date_fin)->gte($academicStart))
                ->pluck('chambre_id')->filter()->unique()->count();
            $rotationRate = $chambres->count() > 0 ? round(min(100, $rotationRoomsCount / $chambres->count() * 100), 1) : 0;
            $edlResidence = $etatsDesLieux->where('id_cite', $cite->id_cite);
            $departsResidence = $demandesSortie->whereIn('chambre_id', $roomIds);
            $decomptesResidence = $decomptesSortie->whereIn('chambre_id', $roomIds);
            $loyersValides = $paiements->where('type_paiement', 'loyer')->where('statut_paiement', 'valide');
            $coutReparations = $viewAccess['finance']
                ? (float) \App\Models\OrdreTravail::where('cite_id', $cite->id_cite)->sum('cout_reel') : 0;
            $cautionsRestituees = $cautionsResidence->where('statut', 'restitue')->count();
            $edlSignes = $edlResidence->filter(fn ($e) => filled($e->signature_crou) && filled($e->signature_etudiant))->count();
            $degradations = $edlResidence->flatMap->pieces->flatMap->elements->where('etat', 'mauvais')->count();
            $photos = $edlResidence->flatMap->pieces->flatMap->images->count()
                + $edlResidence->flatMap->pieces->flatMap->elements->filter(fn ($element) => filled($element->photo))->count();
            $contractsResidence = $contractApprovals->whereIn('assignment_id', $assignationsResidence->map(fn ($a) => $a->getKey())->all());
            $preavisNonRespectes = $decomptesResidence->filter(fn ($d) => $d->preavis_effectif_jours < $d->preavis_requis_jours)->count();

            $residences[] = [
                'id' => $cite->id_cite,
                'name' => $cite->name ?: 'Cité sans nom',
                'occupation_rate' => $occupationRate,
                'rotation_rate' => $rotationRate,
                'total_chambres' => $chambres->count(),
                'available' => $availableCount,
                'maintenance' => $maintenanceCount,
                'hors_service' => $horsServiceCount,
                'reserved' => $reservedCount,
                'occupied_status' => $occupiedStatusCount,
                'occupied_lits' => $occupiedPlaces,
                'recovery_rate' => $recoveryRate,
                'unpaid_rate' => $unpaidRate,
                'reaffectations' => $reaffectations,
                // Nouvelles données maintenance
                'ot_total' => $otResidenceTotal,
                'ot_en_cours' => $otEnCours,
                'ot_recurrence_rate' => $recurrenceRate,
                'ot_resolus' => $otResidenceResolus,
                'ot_taux_resolution' => $otResidenceTauxResolution,
                'ot_temps_resolution' => $tempsResolutionMoyen,
                'ot_categorie_principale' => $categoriePrincipale,
                'incidents_par_categorie' => $incidentsParCategorie,
                'finance' => [
                    'retenue_moyenne' => round((float) $cautionsResidence->avg('montant_retenu')),
                    'caution_restituee_rate' => $cautionsResidence->count() ? round($cautionsRestituees / $cautionsResidence->count() * 100, 1) : 0,
                    'cout_reparations' => $coutReparations,
                    'recettes_loyers' => (float) $loyersValides->sum('montant'),
                    'rentabilite' => (float) $loyersValides->sum('montant') - $coutReparations,
                    'loyer_moyen' => round((float) $loyersValides->avg('montant')),
                ],
                'discipline' => [
                    'incidents' => $rapportsResidence->count(),
                    'avertissements' => $sanctionsResidence->where('type', 'avertissement')->count(),
                    'blames' => $sanctionsResidence->where('type', 'blame')->count(),
                    'suspensions' => $sanctionsResidence->where('type', 'suspension')->count(),
                    'sanctions_actives' => $sanctionsResidence->where('statut', 'active')->count(),
                    'transmis_securite' => $rapportsResidence->whereIn('statut', ['transmis','en_cours','decision_prise','cloture'])->count(),
                    'decisions' => $rapportsResidence->whereNotNull('decision')->count(),
                ],
                'contrats' => [
                    'total' => $contractsResidence->count(),
                    'confirmes' => $contractsResidence->whereNotNull('approved_at')->count(),
                    'sejours_confirmes' => $assignationsResidence->where('statut', 'confirmee')->count(),
                    'edl_entree' => $edlResidence->where('type_etat', 'entree')->count(),
                    'edl_sortie' => $edlResidence->where('type_etat', 'sortie')->count(),
                    'degradations' => $degradations,
                    'photos' => $photos,
                    'conformite' => $edlResidence->count() ? round($edlSignes / $edlResidence->count() * 100, 1) : 0,
                ],
                'departs' => [
                    'demandes' => $departsResidence->count(),
                    'anticipes' => $departsResidence->whereIn('motif', ['demenagement','stage','raison_personnelle','autre'])->count(),
                    'disciplinaires' => $assignationsResidence->where('statut', 'expulsion')->count(),
                    'preavis_non_respecte' => $preavisNonRespectes,
                    'archives' => $decomptesResidence->count(),
                    'retenues' => (float) $decomptesResidence->sum('total_retenues'),
                    'cloture_rate' => $departsResidence->count() ? round($departsResidence->where('statut', 'cloturee')->count() / $departsResidence->count() * 100, 1) : 0,
                ],
            ];

            $globalCapacity += $totalCapacity;
            $globalOccupied += $occupiedPlaces;
            $globalMaintenance += $maintenanceCount;
            $globalHorsService += $horsServiceCount;
            $globalDue += $totalDue;
            $globalPaid += $paidAmount;
            $globalUnpaid += $unpaidAmount;
            $globalReaffectations += $reaffectations;
        }

        $globalOccupationRate = $globalCapacity > 0 ? round(($globalOccupied / $globalCapacity) * 100, 1) : 0;
        $globalRecoveryRate = $globalDue > 0 ? round(($globalPaid / $globalDue) * 100, 1) : 0;
        $globalImpayes = $globalDue > 0 ? round(min(100, $globalUnpaid / $globalDue * 100), 1) : 0;
        $globalReadmissionRate = $globalOccupied > 0 ? round(($globalReaffectations / max(1, $globalOccupied)) * 100, 1) : 0;
        
        // Taux global de résolution des OT
        $globalOTResolutionRate = $globalOTTotal > 0 
            ? round(($globalOTResolus / $globalOTTotal) * 100, 1) 
            : 0;

        // Indicateurs : un par bloc autorisé (les tendances codées en dur de l'ancien écran ne sont plus affichées).
        $kpis = array_values(array_filter([
            $viewAccess['occupation'] ? ['key' => 'occupation', 'label' => "Taux d'occupation", 'value' => $globalOccupationRate, 'unit' => '%', 'extra' => $globalOccupied.' lit(s) occupé(s)', 'tone' => 'green', 'highlight' => $globalOccupationRate < 90] : null,
            $viewAccess['finance'] ? ['key' => 'recouvrement', 'label' => 'Taux de recouvrement', 'value' => $globalRecoveryRate, 'unit' => '%', 'extra' => 'Objectif ≥ 90 %', 'tone' => 'blue', 'highlight' => $globalRecoveryRate < 90] : null,
            $viewAccess['finance'] ? ['key' => 'impayes', 'label' => "Taux d'impayés", 'value' => $globalImpayes, 'unit' => '%', 'extra' => $globalImpayes >= 10 ? 'À corriger' : 'Sous contrôle', 'tone' => 'amber', 'highlight' => $globalImpayes >= 10] : null,
            $viewAccess['occupation'] ? ['key' => 'readmission', 'label' => 'Taux de réadmission', 'value' => $globalReadmissionRate, 'unit' => '%', 'extra' => $globalReaffectations.' réaffectation(s)', 'tone' => 'plum', 'highlight' => false] : null,
            $viewAccess['maintenance'] ? ['key' => 'ot_en_cours', 'label' => 'OT en cours', 'value' => $globalOTEnCours, 'unit' => '', 'extra' => $globalOTUrgents.' urgent(s)', 'tone' => 'amber', 'highlight' => $globalOTUrgents > 0] : null,
            $viewAccess['maintenance'] ? ['key' => 'ot_resolution', 'label' => 'Résolution des OT', 'value' => $globalOTResolutionRate, 'unit' => '%', 'extra' => 'Objectif ≥ 85 %', 'tone' => 'green', 'highlight' => $globalOTResolutionRate < 85] : null,
        ]));

        $residences = array_map(function ($residence) use ($viewAccess) {
            $fields = [
                'occupation' => ['occupation_rate', 'rotation_rate', 'total_chambres', 'available', 'maintenance', 'hors_service', 'reserved', 'occupied_status', 'occupied_lits', 'reaffectations'],
                'finance' => ['finance', 'recovery_rate', 'unpaid_rate'],
                'maintenance' => ['ot_total', 'ot_en_cours', 'ot_recurrence_rate', 'ot_resolus', 'ot_taux_resolution', 'ot_temps_resolution', 'ot_categorie_principale', 'incidents_par_categorie'],
                'discipline' => ['discipline'],
                'departures' => ['departs'],
            ];
            foreach ($fields as $section => $keys) {
                if (!$viewAccess[$section]) {
                    $residence = array_diff_key($residence, array_flip($keys));
                }
            }
            if (!$viewAccess['contracts'] && !$viewAccess['inspections']) {
                unset($residence['contrats']);
            }
            return $residence;
        }, $residences);

        // Dernières demandes d'intervention (tableau explicite, jamais de modèle brut).
        $ordresRecents = $viewAccess['maintenance']
            ? $ordresTravail->sortByDesc('created_at')->take(10)->map(fn ($ot) => [
                'id' => $ot->id_ordres_travail,
                'numero' => $ot->numero_ot,
                'residence' => $ot->residence?->name,
                'categorie' => $ot->categorie_label,
                'priorite' => $ot->priorite,
                'priorite_label' => $ot->priorite_label,
                'statut' => $ot->statut,
                'statut_label' => $ot->statut_label,
                'assigne' => $ot->assigneA?->name,
                'date_creation' => $ot->created_at?->toDateString(),
                'date_resolution' => $ot->date_resolution?->toDateString(),
            ])->values()->all()
            : [];

        $access = app(\App\Services\AdminAccess::class);
        $admin = auth('admin')->user();
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $aTraiter = $rapportsDisciplinaires->whereIn('statut', ['transmis', 'en_cours'])->count();

        // Raccourcis : lien seulement si la route est autorisée, compteur seulement si le bloc de données l'est.
        $raccourcis = array_values(array_filter([
            ['key' => 'paiements', 'label' => 'Paiements des loyers', 'description' => 'Encaissements et suivi des impayés', 'icon' => 'wallet', 'href' => $url('admin.hebergement.paiements.index'),
                'badge' => $viewAccess['finance'] ? $paiementsGlobaux->where('created_at', '>=', now()->startOfMonth())->count().' paiement(s) ce mois' : null, 'alerte' => false],
            ['key' => 'maintenance', 'label' => 'Maintenance', 'description' => 'Ordres de travail et interventions', 'icon' => 'wrench', 'href' => $url('admin.hebergement.maintenance.index'),
                'badge' => $viewAccess['maintenance'] ? $globalOTUrgents.' OT urgent(s)' : null, 'alerte' => $viewAccess['maintenance'] && $globalOTUrgents > 0],
            ['key' => 'annonces', 'label' => 'Annonces', 'description' => 'Diffuser une information aux résidents', 'icon' => 'megaphone', 'href' => $url('admin.hebergement.annonce.index'), 'badge' => null, 'alerte' => false],
            ['key' => 'incidents', 'label' => 'Rapports disciplinaires', 'description' => 'Transmission et décision du Service Sécurité', 'icon' => 'shield-alert', 'href' => $url('admin.hebergement.vie_residentielle.incidents.index'),
                'badge' => $viewAccess['discipline'] ? $aTraiter.' à traiter' : null, 'alerte' => $viewAccess['discipline'] && $aTraiter > 0],
            ['key' => 'sanctions', 'label' => 'Sanctions disciplinaires', 'description' => 'Avertissements, blâmes, suspensions', 'icon' => 'gavel', 'href' => $url('admin.hebergement.vie_residentielle.sanctions.index'),
                'badge' => $viewAccess['discipline'] ? $sanctions->where('statut', 'active')->count().' active(s)' : null, 'alerte' => false],
            ['key' => 'departs', 'label' => 'Départs & états des lieux', 'description' => 'Sorties, contrats et états des lieux', 'icon' => 'door-open', 'href' => $url('admin.hebergement.depart_etat_des_lieux.depart'),
                'badge' => $viewAccess['contracts'] ? collect($residences)->sum('contrats.sejours_confirmes').' séjour(s) confirmé(s)' : null, 'alerte' => false],
        ], fn ($raccourci) => $raccourci['href'] !== null));

        return Inertia::render('Admin/Hebergement/VieResidentielle/Index', [
            'viewAccess' => $viewAccess,
            'kpis' => $kpis,
            'residences' => $residences,
            'raccourcis' => $raccourcis,
            'ordresRecents' => $ordresRecents,
            'synthese' => [
                'ot_urgents' => $viewAccess['maintenance'] ? $globalOTUrgents : null,
                'ot_en_cours' => $viewAccess['maintenance'] ? $globalOTEnCours : null,
                'ot_resolution' => $viewAccess['maintenance'] ? $globalOTResolutionRate : null,
                'readmission' => $viewAccess['occupation'] ? $globalReadmissionRate : null,
                'sejours_confirmes' => $viewAccess['contracts'] ? collect($residences)->sum('contrats.sejours_confirmes') : null,
                'departs_clotures' => $viewAccess['departures'] ? $demandesSortie->where('statut', 'cloturee')->count() : null,
                'departs_total' => $viewAccess['departures'] ? $demandesSortie->count() : null,
            ],
            'periode' => now()->year.'-'.(now()->year + 1),
            'miseAJour' => now()->format('d/m/Y H:i'),
        ]);
    }
}
