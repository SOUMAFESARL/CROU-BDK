<?php
// app/Http/Services/PaiementService.php

namespace App\Http\Services;

use App\Repositories\Interfaces\PaiementRepositoryInterface;
use App\Repositories\Interfaces\LoyerRepositoryInterface;
use App\Models\Paiement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Services\LoyerService;
use App\Models\PaiementRepartition;
use App\Models\AssignationChambre;

class PaiementService
{
    protected $paiementRepository;
     protected  $loyerRepository;
     protected LoyerService $loyerService;

    public function __construct(PaiementRepositoryInterface $paiementRepository, LoyerRepositoryInterface $loyerRepository, LoyerService $loyerService)
    {
        $this->paiementRepository = $paiementRepository;
        $this->loyerRepository = $loyerRepository;
        $this->loyerService = $loyerService;
    }

    /**
     * Récupérer les paiements paginés avec filtres
     */
    public function getPaiementsPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paiementRepository->getAllPaginated($filters, $perPage);
    }

    /**
     * Récupérer les KPIs pour le tableau de bord
     */
    // public function getKPIs(): array
    // {
    //     $totalEncaissement = $this->paiementRepository->getTotalEncaissementMois();
    //     $tauxRecouvrement = $this->paiementRepository->getTauxRecouvrement();
    //     $impayes = $this->paiementRepository->getImpayes();
    //     $tauxImpayes = $this->paiementRepository->getTauxImpayes();
    //     $nombrePaiements = $this->paiementRepository->getNombrePaiementsMois();
    //     $loyerMoyen = $this->paiementRepository->getLoyerMoyen();

    //     return [
    //         [
    //             'label' => 'Total encaissé',
    //             'value' => number_format($totalEncaissement, 0, ',', ' ') . ' FCFA',
    //             'trend' => '▲ +8% vs mois dernier',
    //             'trend_class' => 'up',
    //             'type' => 'success',
    //         ],
    //         [
    //             'label' => 'Taux de recouvrement',
    //             'value' => $tauxRecouvrement . '%',
    //             'trend' => '● stable',
    //             'trend_class' => 'stable',
    //             'type' => 'info',
    //         ],
    //         [
    //             'label' => 'Impayés',
    //             'value' => number_format($impayes, 0, ',', ' ') . ' FCFA',
    //             'trend' => '▲ +1,2%',
    //             'trend_class' => 'danger',
    //             'type' => 'danger',
    //         ],
    //         [
    //             'label' => "Taux d'impayés",
    //             'value' => $tauxImpayes . '%',
    //             'trend' => 'Objectif <5%',
    //             'trend_class' => 'warning',
    //             'type' => 'warning',
    //         ],
    //         [
    //             'label' => 'Nombre de paiements',
    //             'value' => $nombrePaiements,
    //             'trend' => '▲ +12%',
    //             'trend_class' => 'up',
    //             'type' => 'purple',
    //         ],
    //         [
    //             'label' => 'Loyer moyen',
    //             'value' => number_format($loyerMoyen, 0, ',', ' ') . ' FCFA',
    //             'trend' => '● stable',
    //             'trend_class' => 'stable',
    //             'type' => '',
    //         ],
    //     ];
    // }

    /**
     * Récupérer les statistiques par résidence
     */
    public function getStatistiquesParResidence(): array
    {
        $stats = $this->paiementRepository->getStatistiquesParResidence();
        
        $result = [];
        foreach ($stats as $stat) {
            $result[] = [
                'residence' => $stat->cite->name ?? 'N/A',
                'total_attendu' => number_format($stat->total_attendu, 0, ',', ' ') . ' FCFA',
                'total_encaisse' => number_format($stat->total_encaisse, 0, ',', ' ') . ' FCFA',
                'taux_recouvrement' => $stat->taux_recouvrement,
                'impayes' => number_format($stat->impayes, 0, ',', ' ') . ' FCFA',
                'nb_residents' => $stat->nb_residents,
                'loyer_moyen' => number_format($stat->loyer_moyen, 0, ',', ' ') . ' FCFA',
                'alerte' => $stat->taux_recouvrement < 90,
            ];
        }
        
        return $result;
    }

    /**
     * Créer un nouveau paiement
     */
     /**
     * Crée un paiement avec répartition automatique entre arriérés et mois en cours
     */


/**
 * Récupère les statistiques des paiements
 */
public function getStatistiquesPaiements(?string $annee = null, ?string $mois = null, ?string $residence = null): array
{
    $query = $this->paiementRepository->getQuery()->where('type_paiement', 'loyer');
    
    if ($annee) {
        $query->whereYear('date_paiement', $annee);
    }
    if ($mois) {
        $query->whereMonth('date_paiement', $mois);
    }
    if ($residence) {
        $query->where('cite_id', $residence);
    }
    
    $totalEncaisse = $query->where('statut_paiement', 'valide')->sum('montant');
    $nombrePaiements = $query->where('statut_paiement', 'valide')->count();
    
    return [
        'total_encaisse' => $totalEncaisse,
        'nombre_paiements' => $nombrePaiements,
    ];
}

/**
 * Récupère l'évolution des paiements
 */
public function getEvolutionPaiements(?string $annee = null, ?string $mois = null, ?string $residence = null): array
{
    $annee = $annee ?? date('Y');
    $data = [];
    
    for ($i = 1; $i <= 12; $i++) {
        $query = $this->paiementRepository->getQuery();
        $query->where('statut_paiement', 'valide')
              ->whereYear('date_paiement', $annee)
              ->whereMonth('date_paiement', $i);
        
        if ($residence) {
            $query->where('cite_id', $residence);
        }
        
        $data[] = $query->sum('montant');
    }
    
    return $data;
}

/**
 * Récupère l'évolution des montants attendus
 */
public function getEvolutionAttendus(?string $annee = null, ?string $mois = null, ?string $residence = null): array
{
    $annee = $annee ?? date('Y');
    $data = [];
    
    for ($i = 1; $i <= 12; $i++) {
        $query = $this->loyerRepository->getQuery();
        $query->where('statut', '!=', 'annule')
              ->whereYear('created_at', $annee)
              ->whereMonth('created_at', $i);
        
        if ($residence) {
            $query->where('cite_id', $residence);
        }
        
        $data[] = $query->sum('montant_total');
    }
    
    return $data;
}

/**
 * Récupère les derniers paiements
 */
public function getDerniersPaiements(int $limit = 10): array
{
    $paiements = $this->paiementRepository->getQuery()
        ->with(['resident', 'cite'])
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();
    
    return $paiements->map(function ($paiement) {
        return [
            'id' => $paiement->id_paiement,
            'numero' => $paiement->numero_paiement,
            'resident' => $paiement->resident?->name ?? 'N/A',
            'montant' => $paiement->montant,
            'montant_formate' => number_format($paiement->montant, 0, ',', ' ') . ' FCFA',
            'methode' => $paiement->methode_label,
            'statut' => $paiement->statut_label,
            'statut_badge' => $paiement->statut_badge,
            'date' => $paiement->created_at?->format('d/m/Y H:i'),
            'residence' => $paiement->cite?->name ?? 'N/A',
        ];
    })->toArray();
}

public function getStatistiquesParModePaiement(?string $annee = null, ?string $mois = null, ?string $residence = null): array
{
    $query = $this->paiementRepository->getQuery()
        ->select(
            'methode_paiement',
            DB::raw('COUNT(*) as nombre'),
            DB::raw('SUM(montant) as total'),
            DB::raw('AVG(montant) as moyenne')
        )
        ->where('statut_paiement', 'valide')
        ->where('type_paiement', 'loyer');
    
    if ($annee) {
        $query->whereYear('date_paiement', $annee);
    }
    if ($mois) {
        $query->whereMonth('date_paiement', $mois);
    }
    if ($residence) {
        $query->where('cite_id', $residence);
    }
    
    $results = $query->groupBy('methode_paiement')->get();
    
    $labels = [
        'orange_money' => 'Orange Money',
        'mtn_money' => 'MTN Money',
        'moov_money' => 'Moov Money',
        'especes' => 'Espèces',
        'virement' => 'Virement bancaire',
        'carte_bancaire' => 'Carte bancaire',
        'autre' => 'Autre'
    ];
    
    $colors = [
        'orange_money' => '#f47b20',
        'mtn_money' => '#ffcb00',
        'moov_money' => '#00a651',
        'especes' => '#1e7aa8',
        'virement' => '#6a4d8a',
        'carte_bancaire' => '#b13e3e',
        'autre' => '#5b7b99'
    ];
    
    $result = [];
    foreach ($results as $item) {
        $result[] = [
            'methode' => $item->methode_paiement,
            'libelle' => $labels[$item->methode_paiement] ?? $item->methode_paiement,
            'couleur' => $colors[$item->methode_paiement] ?? '#5b7b99',
            'nombre' => (int) $item->nombre,
            'total' => (float) $item->total,
            'total_formate' => number_format($item->total, 0, ',', ' ') . ' FCFA',
            'moyenne' => (float) $item->moyenne,
            'moyenne_formate' => number_format($item->moyenne, 0, ',', ' ') . ' FCFA',
            'pourcentage' => 0 // Calculé après
        ];
    }
    
    // Calculer les pourcentages
    $totalGeneral = array_sum(array_column($result, 'total'));
    if ($totalGeneral > 0) {
        foreach ($result as &$item) {
            $item['pourcentage'] = round(($item['total'] / $totalGeneral) * 100, 1);
        }
    }
    
    return $result;
}

public function creerPaiement(array $data): Paiement
{
    try {
        DB::beginTransaction();
        
        // 1. Récupérer les arriérés de l'étudiant
        $arriereInfo = $this->loyerRepository->calculerArriere($data['resident_id']);
        $arriereTotal = $arriereInfo['solde_restant'];
        
        // 2. Récupérer le prochain mois à payer
        $prochainMois = $this->getProchainMoisAPayer($data['resident_id']);
        $moisCible = $prochainMois['mois'];
        $anneeCible = $prochainMois['annee'];
        
        // 3. Récupérer ou créer le loyer pour le prochain mois
        $loyerActuel = $this->getOrCreerLoyerParMois($data['resident_id'], $moisCible, $anneeCible);
        
        if (!$loyerActuel) {
            throw new \Exception("Impossible de créer le loyer pour le mois {$moisCible}-{$anneeCible}");
        }
        
        // 4. Traiter la répartition du paiement
        $montant = $data['montant'];
        $repartition = $this->repartirPaiement($montant, $arriereTotal, $loyerActuel, $data['resident_id']);
        
        // 5. Préparer les données du paiement
        $paiementData = [
            'resident_id' => $data['resident_id'],
            'chambre_id' => $data['chambre_id'] ?? null,
            'cite_id' => $data['cite_id'] ?? null,
            'contrat_id' => $data['contrat_id'] ?? null,
            'batiment_id' => $data['batiment_id'] ?? null,
            'loyer_id' => $loyerActuel->id_loyer ?? null,
            'type_paiement' => $data['type_paiement'] ?? 'loyer',
            'mois' => [],
            'montant' => $montant,
            'montant_du' => $data['montant_du'] ?? 0,
            'devise' => $data['devise'] ?? 'FCFA',
            'methode_paiement' => $data['methode_paiement'] ?? 'especes',
            'reference_transaction' => $data['reference_transaction'] ?? null,
            'statut_paiement' => 'valide',
            'date_paiement' => now(),
            'date_echeance' => $data['date_echeance'] ?? null,
            'notes' => $data['notes'] ?? null,
            'justificatif' => $data['justificatif'] ?? null,
            'valide_par' => $data['valide_par'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
          //  'created_by' => $data['created_by'] ?? (auth()->id() ?? 'system'),
        ];
        
        // 6. Enregistrer le paiement principal
        $paiement = $this->paiementRepository->create($paiementData);

        // if($paiement && empty($paiement->mois)){
        //     $this->paiementRepository->delete($paiement->id_paiement); 
        // }
        // Supprimer le paiement principal pour éviter les doublons
       
        // 7. Appliquer le paiement sur les arriérés
        $moisPayesArrieres = [];
        if ($repartition['arriere_paye'] > 0) {
            $moisPayesArrieres = $this->appliquerPaiementArrieres(
                $data['resident_id'],
                $repartition['arriere_paye'],
                $loyerActuel->id_loyer ?? null
            );
        }
        
        // 8. Appliquer le paiement sur le mois en cours (prochain mois à payer)
        if ($repartition['mois_paye'] > 0 && $loyerActuel) {
            $this->appliquerPaiementMoisActuel($loyerActuel->id_loyer, $repartition['mois_paye']);
        }
        
        // 9. Créer et solder les loyers des mois futurs
        if (!empty($repartition['mois_futurs']) && $loyerActuel) {
            foreach ($repartition['mois_futurs'] as $i => $moisFutur) {
                $nouveauLoyer = $this->creerLoyerMoisFutur(
                    $loyerActuel,
                    $moisFutur['mois'],
                    $moisFutur['annee'],
                    $moisFutur['montant_paye'],
                    $moisFutur['solde_restant']
                );
                $repartition['mois_futurs'][$i]['loyer_id'] = $nouveauLoyer->id_loyer;
            }
        }
        
        // 10. Construire la liste complète des mois couverts par ce paiement
        $moisComplet = $moisPayesArrieres;
        
        if ($repartition['mois_paye'] > 0 && $loyerActuel) {
            $moisComplet[] = $this->formatMoisAnnee($loyerActuel->mois_facturation, $loyerActuel->annee_facturation);
        }
        
        foreach ($repartition['mois_futurs'] as $moisFutur) {
            $moisComplet[] = $this->formatMoisAnnee($moisFutur['mois'], $moisFutur['annee']);
        }
        
        $paiement->mois = $moisComplet;
        $paiement->save();
        
        // 11. Créer les lignes de répartition
        $this->creerLignesRepartition($paiement, $repartition);
        
        DB::commit();
        
        Log::info('Paiement créé avec répartition', [
            'paiement_id' => $paiement->id_paiement,
            'numero' => $paiement->numero_paiement,
            'montant' => $montant,
            'arriere_paye' => $repartition['arriere_paye'],
            'mois_paye' => $repartition['mois_paye'],
            'mois_cible' => $moisCible . '-' . $anneeCible,
            'reste_a_payer' => $repartition['reste_a_payer']
        ]);
        

   $paiementsSansMois = $this->paiementRepository->getByEtudiant($data['resident_id'])
    ->whereNull('mois');

// Vérifier si des paiements sans mois existent
if ($paiementsSansMois->isNotEmpty()) {
    Log::warning('Suppression des paiements sans mois pour l\'étudiant', [
        'resident_id' => $data['resident_id'],
        'nombre' => $paiementsSansMois->count()
    ]);
    
    foreach ($paiementsSansMois as $paiement) {
        $this->paiementRepository->delete($paiement->id_paiement);
        Log::info('Paiement sans mois supprimé', [
            'paiement_id' => $paiement->id_paiement,
            'numero' => $paiement->numero_paiement
        ]);
    }
}
        return $paiement;
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur création paiement', [
            'error' => $e->getMessage(),
            'data' => $data
        ]);
        throw $e;
    }
}


    /**
     * Répartit le paiement entre arriérés et mois en cours
     */
/**
 * Répartit le paiement entre arriérés, mois en cours et mois futurs
 * En commençant par le prochain mois à payer
 */
protected function repartirPaiement(float $montant, float $arriereTotal, ?object $loyerActuel, string $etudiantId): array
{
    $repartition = [
        'montant_total'   => $montant,
        'arriere_paye'    => 0,
        'arriere_restant' => $arriereTotal,
        'mois_paye'       => 0,
        'mois_credit'     => null,
        'mois_restant'    => 0,
        'mois_futurs'     => [],
        'reste_a_payer'   => 0,
    ];

    $montantRestant = $montant;

    // 1. Arriérés (les plus anciens en premier)
    if ($arriereTotal > 0) {
        $repartition['arriere_paye']    = min($montantRestant, $arriereTotal);
        $repartition['arriere_restant'] = $arriereTotal - $repartition['arriere_paye'];
        $montantRestant -= $repartition['arriere_paye'];
    }

    // 2. Si tous les arriérés sont payés, passer aux mois à venir
    if ($montantRestant > 0 && $repartition['arriere_restant'] == 0) {
        // Récupérer le prochain mois à payer
        $prochainMois = $this->getProchainMoisAPayer($etudiantId);
        $moisActuel = (int) now()->format('m');
        $anneeActuelle = (int) now()->format('Y');
        
        // Vérifier si le prochain mois à payer est le mois en cours ou un mois futur
        $moisCible = $prochainMois['mois'];
        $anneeCible = $prochainMois['annee'];
        
        // Chercher ou créer le loyer pour ce mois
        $loyerCible = $this->getOrCreerLoyerParMois($etudiantId, $moisCible, $anneeCible);
        
        if ($loyerCible) {
            $montantMois = $loyerCible->solde_restant ?? $loyerCible->montant_total;
            $repartition['mois_paye'] = min($montantRestant, $montantMois);
            $repartition['mois_restant'] = $montantMois - $repartition['mois_paye'];
            $repartition['mois_credit'] = $this->formatMoisAnnee($moisCible, $anneeCible);
            $montantRestant -= $repartition['mois_paye'];
            
            // Si le mois en cours est totalement payé, passer aux mois futurs
            if ($montantRestant > 0 && $repartition['mois_restant'] == 0) {
                $moisRef = $moisCible;
                $anneeRef = $anneeCible;
                $montantRef = $loyerCible->montant_total;
                
                while ($montantRestant > 0) {
                    $moisRef++;
                    if ($moisRef > 12) {
                        $moisRef = 1;
                        $anneeRef++;
                    }
                    
                    $montantPourCeMois = min($montantRestant, $montantRef);
                    
                    $repartition['mois_futurs'][] = [
                        'mois'          => $moisRef,
                        'annee'         => $anneeRef,
                        'montant_du'    => $montantRef,
                        'montant_paye'  => $montantPourCeMois,
                        'solde_restant' => $montantRef - $montantPourCeMois,
                        'loyer_id'      => null, // Sera créé plus tard
                    ];
                    
                    $montantRestant -= $montantPourCeMois;
                }
            }
        }
    }

    $repartition['reste_a_payer'] = $montantRestant;

    return $repartition;
}

// app/Http/Services/PaiementService.php

/**
 * Récupère ou crée un loyer pour un mois et une année spécifiques
 */
protected function getOrCreerLoyerParMois(string $etudiantId, int $mois, int $annee): ?object
{
    // Chercher le loyer existant
    $loyer = $this->loyerRepository->getLoyerByMois($etudiantId, $mois, $annee);
    
    if ($loyer) {
        return $loyer;
    }
    
    // Créer le loyer s'il n'existe pas
    $assignation = AssignationChambre::where('etudiant_id', $etudiantId)
        ->where('statut', 'confirmee')
        ->latest('created_at')
        ->first();
    
    if (!$assignation) {
        Log::warning('Aucune assignation active trouvée', [
            'etudiant_id' => $etudiantId
        ]);
        return null;
    }
    
    // Récupérer les informations
    $chambre = $assignation->chambre;
    $cite = $chambre ? $chambre->cite : null;
    
    // Déterminer le montant du loyer
    $montantLoyer = 0;
    
    if ($cite && $cite->montant_loyer > 0) {
        $montantLoyer = (float) $cite->montant_loyer;
    } elseif ($chambre && $chambre->montant_loyer > 0) {
        $montantLoyer = (float) $chambre->montant_loyer;
    } else {
        // Valeur par défaut
        $montantLoyer = $this->getMontantLoyer($etudiantId);
    }
    
    // Vérifier que le montant est valide
    if ($montantLoyer <= 0) {
        Log::error('Montant de loyer invalide', [
            'etudiant_id' => $etudiantId,
            'montant' => $montantLoyer,
            'cite_id' => $cite ? $cite->id_cite : null,
            'chambre_id' => $chambre ? $chambre->id_chambre : null
        ]);
        $montantLoyer = 50000; // Valeur par défaut
    }
    
    // Créer le loyer
    $dateLimite = Carbon::create($annee, $mois, 10, 23, 59, 59);
    
    $data = [
        'etudiant_id' => $etudiantId,
        'chambre_id' => $chambre ? $chambre->id_chambre : null,
        'cite_id' => $cite ? $cite->id_cite : null,
        'campus_id' => $assignation->campus_id ?? null,
        'assignation_id' => $assignation->id_assignation,
        'montant_base' => $montantLoyer,
        'montant_total' => $montantLoyer,
        'caution' => 0,
        'frais_inscription' => 0,
        'annee_universitaire' => $this->getAnneeUniversitaire($annee),
        'periode' => 'mensuel',
        'nombre_echeances' => 1,
        'montant_echeance' => $montantLoyer,
        'statut' => 'actif',
        'date_limit_paiement' => $dateLimite,
        'mois_facturation' => $mois,
        'annee_facturation' => $annee,
        'boursier' => false,
        'reduction_bourse' => 0,
        'reduction_sociale' => 0,
        'supplement_prestation' => 0,
        'total_paye' => 0,
        'solde_restant' => $montantLoyer,
        'nombre_impayes' => 0,
        'total_penalites' => 0,
        'penalite_journaliere' => 0,
        'jours_retard_autorises' => 5,
        'renouvellement_auto' => true,
        'periode_rappel_jours' => 3,
        'created_by' => auth()->id() ?? 'system',
    ];
    
    return $this->loyerRepository->create($data);
}
protected function getMontantLoyer(string $etudiantId): float
{
    $assignation = AssignationChambre::where('etudiant_id', $etudiantId)
        ->where('statut', 'confirmee')
        ->latest('created_at')
        ->first();
    
    if (!$assignation) {
        Log::error('Aucune assignation trouvée pour l\'étudiant', [
            'etudiant_id' => $etudiantId
        ]);
        return 0;
    }
    
    $chambre = $assignation->chambre;
    $cite = $chambre ? $chambre->cite : null;
    
   
    
    if ($chambre && $chambre->prix > 0) {
        return (float) $chambre->prix;
    }
    
    // Valeur par défaut (à ajuster)
    Log::warning('Montant de loyer non trouvé, utilisation de la valeur par défaut', [
        'etudiant_id' => $etudiantId
    ]);
    
    return 50000; // Valeur par défaut
}
/**
 * Récupère l'année universitaire en fonction d'une année donnée
 */
protected function getAnneeUniversitaire(int $annee): string
{
    $moisActuel = (int) now()->format('m');
    $anneeDebut = ($moisActuel >= 9) ? $annee : $annee - 1;
    $anneeFin = $anneeDebut + 1;
    return $anneeDebut . '-' . $anneeFin;
}

/**
 * Crée un loyer pour un mois futur suite à un paiement en avance,
 * et le marque directement comme payé (totalement ou partiellement)
 */



/**
 * Crée un loyer pour un mois futur suite à un paiement en avance,
 * et le marque directement comme payé (totalement ou partiellement)
 */
protected function creerLoyerMoisFutur(object $loyerTemplate, int $mois, int $annee, float $montantPaye, float $soldeRestant): object
{
    $dateLimite = Carbon::create($annee, $mois, 10, 23, 59, 59);
    
    // S'assurer que le montant de base est défini
    $montantBase = $loyerTemplate->montant_base ?? $loyerTemplate->montant_total ?? 0;
    $montantTotal = $loyerTemplate->montant_total ?? $montantBase;
    
    // Si le montant est 0, essayer de récupérer depuis la chambre
    if ($montantBase <= 0 && $loyerTemplate->chambre_id) {
        $chambre = \App\Models\Chambre::find($loyerTemplate->chambre_id);
        if ($chambre && $chambre->cite) {
            $montantBase = (float) $chambre->cite->montant_loyer ?? 0;
            $montantTotal = $montantBase;
        }
    }
    
    // Valeur par défaut si toujours 0
    if ($montantBase <= 0) {
        Log::warning('Montant de base du loyer template invalide, utilisation de la valeur par défaut', [
            'loyer_template_id' => $loyerTemplate->id_loyer ?? null,
            'montant_base' => $loyerTemplate->montant_base ?? null,
            'montant_total' => $loyerTemplate->montant_total ?? null
        ]);
        $montantBase = 50000;
        $montantTotal = 50000;
    }
    
    $data = [
        'etudiant_id' => $loyerTemplate->etudiant_id,
        'chambre_id' => $loyerTemplate->chambre_id,
        'cite_id' => $loyerTemplate->cite_id,
        'campus_id' => $loyerTemplate->campus_id,
        'assignation_id' => $loyerTemplate->assignation_id,
        'montant_base' => $montantBase,
        'montant_total' => $montantTotal,
        'caution' => $loyerTemplate->caution ?? 0,
        'frais_inscription' => $loyerTemplate->frais_inscription ?? 0,
        'annee_universitaire' => $loyerTemplate->annee_universitaire ?? $this->getAnneeUniversitaire($annee),
        'periode' => $loyerTemplate->periode ?? 'mensuel',
        'nombre_echeances' => $loyerTemplate->nombre_echeances ?? 1,
        'montant_echeance' => $montantBase,
        'statut' => $soldeRestant > 0 ? 'actif' : 'termine',
        'date_limit_paiement' => $dateLimite,
        'mois_facturation' => $mois,
        'annee_facturation' => $annee,
        'boursier' => $loyerTemplate->boursier ?? false,
        'reduction_bourse' => $loyerTemplate->reduction_bourse ?? 0,
        'type_bourse' => $loyerTemplate->type_bourse ?? null,
        'reduction_sociale' => $loyerTemplate->reduction_sociale ?? 0,
        'justificatif_reduction' => $loyerTemplate->justificatif_reduction ?? null,
        'supplement_prestation' => $loyerTemplate->supplement_prestation ?? 0,
        'detail_supplements' => $loyerTemplate->detail_supplements ?? null,
        'total_paye' => $montantPaye,
        'solde_restant' => $soldeRestant,
        'nombre_impayes' => 0,
        'date_dernier_paiement' => now(),
        'total_penalites' => 0,
        'penalite_journaliere' => $loyerTemplate->penalite_journaliere ?? 0,
        'jours_retard_autorises' => $loyerTemplate->jours_retard_autorises ?? 5,
        'renouvellement_auto' => $loyerTemplate->renouvellement_auto ?? true,
        'periode_rappel_jours' => $loyerTemplate->periode_rappel_jours ?? 3,
        'notes' => $loyerTemplate->notes ?? null,
        'metadata' => $loyerTemplate->metadata ?? null,
        'created_by' => auth()->id() ?? 'system',
    ];
    
    return $this->loyerRepository->create($data);
}

    /**
     * Récupère le loyer du mois en cours pour un étudiant
     */
    public function getLoyerActuel(string $etudiantId): ?object
    {
        $mois = now()->format('m');
        $annee = now()->format('Y');
        
        $loyers = $this->loyerRepository->getActifByEtudiant($etudiantId);
        
        // Chercher le loyer du mois en cours
        foreach ($loyers as $loyer) {
            if ($loyer->mois_facturation == $mois && $loyer->annee_facturation == $annee) {
                return $loyer;
            }
        }
        
        // Si pas de loyer pour le mois en cours, chercher le dernier loyer actif
        return $loyers->first();
    }

    /**
     * Récupère le loyer du mois en cours pour un étudiant, en le générant
     * automatiquement s'il n'existe pas encore (ex: premier paiement du mois
     * avant que le job/cron de génération mensuelle des loyers n'ait tourné).
     *
     * @throws \Exception si aucune assignation de chambre active n'est trouvée
     *         pour cet étudiant, auquel cas il est impossible de déterminer
     *         le montant du loyer à générer.
     */
    protected function getOrCreerLoyerActuel(string $etudiantId): object
    {
        $loyerActuel = $this->getLoyerActuel($etudiantId);

        if ($loyerActuel) {
            return $loyerActuel;
        }

        Log::info('Aucun loyer trouvé pour le mois en cours, génération à la volée', [
            'etudiant_id' => $etudiantId,
            'mois' => now()->format('m'),
            'annee' => now()->format('Y'),
        ]);

        $assignation = AssignationChambre::where('etudiant_id', $etudiantId)
            ->where('statut', 'confirmee')
            ->latest('created_at')
            ->first();

        if (!$assignation) {
            throw new \Exception(
                "Impossible de générer le loyer du mois en cours : aucune assignation de chambre active trouvée pour l'étudiant {$etudiantId}."
            );
        }

        $loyerActuel = $this->loyerService->genererLoyerPourAssignation($assignation);

        Log::info('Loyer du mois en cours généré à la volée', [
            'etudiant_id' => $etudiantId,
            'loyer_id' => $loyerActuel->id_loyer ?? null,
        ]);

        return $loyerActuel;
    }

    /**
     * Applique le paiement sur les arriérés
     *
     * @param string $etudiantId
     * @param float  $montant
     * @param string|null $loyerActuelId identifiant du loyer du mois en cours,
     *        exclu volontairement de ce traitement pour éviter qu'il soit soldé
     *        deux fois (une fois ici en tant qu'"arriéré", une fois via
     *        appliquerPaiementMoisActuel()).
     */
    protected function appliquerPaiementArrieres(string $etudiantId, float $montant, ?string $loyerActuelId = null): array
    {
        
        $loyers = $this->loyerRepository->getActifByEtudiant($etudiantId)
            ->filter(function($loyer) use ($loyerActuelId) {
                return $loyer->solde_restant > 0
                    && (!$loyerActuelId || $loyer->id_loyer !== $loyerActuelId);
            })
            ->sortBy('date_limit_paiement'); // Payer d'abord les plus anciens

        $montantRestant = $montant;
        $moisPayes = [];

        foreach ($loyers as $loyer) {
            if ($montantRestant <= 0) break;
            
            $solde = $loyer->solde_restant;
           
            if ($montantRestant >= $solde) {
                // Payer entièrement ce loyer
                
                $this->loyerRepository->enregistrerPaiement(
                    $loyer->id_loyer,
                    $solde,
                    now()->format('Y-m-d H:i:s')
                );
               //  dd($loyer->id_loyer,$solde);
                $montantRestant -= $solde;
            } else {
                // Payer partiellement ce loyer
                $this->loyerRepository->enregistrerPaiement(
                    $loyer->id_loyer,
                    $montantRestant,
                    now()->format('Y-m-d H:i:s')
                );
                $montantRestant = 0;
            }

            $moisPayes[] = $this->formatMoisAnnee($loyer->mois_facturation, $loyer->annee_facturation);
        }

        return $moisPayes;
    }

    /**
     * Formate un couple (mois, année) en chaîne 'mm-yyyy' (mois sur 2 chiffres).
     */
    protected function formatMoisAnnee($mois, $annee): string
    {
        return sprintf('%02d-%d', (int) $mois, (int) $annee);
    }

    /**
     * Applique le paiement sur le mois en cours
     */
    protected function appliquerPaiementMoisActuel(string $loyerId, float $montant): void
    {
        $this->loyerRepository->enregistrerPaiement(
            $loyerId,
            $montant,
            now()->format('Y-m-d H:i:s')
        );
    }

    /**
     * Crée les lignes de répartition du paiement
     */
   protected function creerLignesRepartition(Paiement $paiement, array $repartition): void
{
    $lignes = [];

    if ($repartition['arriere_paye'] > 0) {
        $lignes[] = [
            'paiement_id' => $paiement->id_paiement,
            'type'        => PaiementRepartition::TYPE_ARRIERE,
            'montant'     => $repartition['arriere_paye'],
            'mois'        => null,
            'annee'       => null,
            'description' => 'Paiement des arriérés',
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

    if ($repartition['mois_paye'] > 0) {
        $moisCredit = explode('-', $repartition['mois_credit'] ?? '');
        $lignes[] = [
            'paiement_id' => $paiement->id_paiement,
            'type'        => PaiementRepartition::TYPE_MOIS_COURS,
            'montant'     => $repartition['mois_paye'],
            'mois'        => $moisCredit[0] ?? null,
            'annee'       => $moisCredit[1] ?? null,
            'description' => 'Paiement du mois en cours',
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

    // NEW : une ligne de répartition PAR mois futur payé, mais rattachée au même paiement
    // Ce sont des paiements anticipés => type 'avance' (seul type ENUM disponible pour ce cas)
    foreach ($repartition['mois_futurs'] as $moisFutur) {
        $lignes[] = [
            'paiement_id' => $paiement->id_paiement,
            'type'        => PaiementRepartition::TYPE_AVANCE,
            'montant'     => $moisFutur['montant_paye'],
            'mois'        => $moisFutur['mois'],
            'annee'       => $moisFutur['annee'],
            'description' => 'Paiement anticipé - loyer_id ' . ($moisFutur['loyer_id'] ?? 'N/A'),
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

    if (!empty($lignes)) {
        foreach ($lignes as $ligne) {
            PaiementRepartition::create($ligne);
        }
    }
}

    /**
     * Vérifie si un paiement est suffisant pour couvrir les arriérés
     */
    public function verifierPaiementSuffisant(string $etudiantId, float $montant): array
    {
        $arriereInfo = $this->loyerRepository->calculerArriere($etudiantId);
        $arriereTotal = $arriereInfo['solde_restant'];
        $loyerActuel = $this->getLoyerActuel($etudiantId);
        
        $montantMois = $loyerActuel ? ($loyerActuel->solde_restant ?? $loyerActuel->montant_total) : 0;
        $montantTotalDu = $arriereTotal + $montantMois;
        
        return [
            'arriere_total' => $arriereTotal,
            'montant_mois' => $montantMois,
            'montant_total_du' => $montantTotalDu,
            'montant_paye' => $montant,
            'suffisant' => $montant >= $arriereTotal,
            'peut_payer_mois' => $montant > $arriereTotal,
            'reste_apres_arriere' => max(0, $montant - $arriereTotal),
            'reste_apres_mois' => max(0, $montant - $montantTotalDu)
        ];
    }
   
    /**
     * Valider un paiement
     */
    public function validerPaiement(string $id): bool
    {
        try {
            DB::beginTransaction();
            
            $updated = $this->paiementRepository->update($id, [
                'statut_paiement' => 'valide',
                'valide_par' => auth()->id(),
                'date_paiement' => now(),
            ]);
            
            DB::commit();
            
            return $updated;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur validation paiement', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Annuler un paiement
     */
    public function annulerPaiement(string $id): bool
    {
        try {
            DB::beginTransaction();
            
            $updated = $this->paiementRepository->update($id, [
                'statut_paiement' => 'annule',
            ]);
            
            DB::commit();
            
            return $updated;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur annulation paiement', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Récupérer les données pour les graphiques
     */
    public function getDonneesGraphiques(): array
    {
        return [
            'evolution_paiements' => $this->paiementRepository->getPaiementsParMois(6),
            'statuts_paiements' => $this->paiementRepository->getPaiementsParStatut(),
        ];
    }

    /**
     * Exporter les paiements
     */
    public function exporterPaiements(array $filters = []): \Illuminate\Support\Collection
    {
        return $this->paiementRepository->getAllPaginated($filters, 1000);
    }



/**
 * Récupère le dernier mois payé pour un étudiant
 * Retourne le mois et l'année du dernier paiement effectué
 */
protected function getDernierMoisPaye(string $etudiantId): ?array
{
    // Récupérer le dernier paiement validé
    $dernierPaiement = $this->paiementRepository->getLastPaiementByEtudiant($etudiantId);
    
    if (!$dernierPaiement) {
        // Aucun paiement trouvé, on commence par le mois en cours
        return [
            'mois' => (int) now()->format('m'),
            'annee' => (int) now()->format('Y')
        ];
    }
    
    // Si le champ mois est un tableau (plusieurs mois payés)
    if (is_array($dernierPaiement->mois) || is_object($dernierPaiement->mois)) {
        $moisListe = (array) $dernierPaiement->mois;
        if (!empty($moisListe)) {
            // Prendre le dernier mois de la liste
            $dernier = end($moisListe);
            if (preg_match('/^(\d{2})-(\d{4})$/', $dernier, $matches)) {
                return [
                    'mois' => (int) $matches[1],
                    'annee' => (int) $matches[2]
                ];
            }
        }
    }
    
    // Si c'est une chaîne simple
    if (is_string($dernierPaiement->mois)) {
        if (preg_match('/^(\d{2})-(\d{4})$/', $dernierPaiement->mois, $matches)) {
            return [
                'mois' => (int) $matches[1],
                'annee' => (int) $matches[2]
            ];
        }
    }
    
    // Par défaut, retourner le mois en cours
    return [
        'mois' => (int) now()->format('m'),
        'annee' => (int) now()->format('Y')
    ];
}

/**
 * Récupère le prochain mois à payer
 * Retourne le mois et l'année du prochain mois à payer
 */
protected function getProchainMoisAPayer(string $etudiantId): array
{
    $dernier = $this->getDernierMoisPaye($etudiantId);
    
    $mois = $dernier['mois'];
    $annee = $dernier['annee'];
    
    // Passer au mois suivant
    $mois++;
    if ($mois > 12) {
        $mois = 1;
        $annee++;
    }
    
    return [
        'mois' => $mois,
        'annee' => $annee
    ];
}

/**
 * Récupère tous les mois à payer à partir d'une date donnée
 */
protected function getMoisAPayer(string $etudiantId, int $nombreMois = 12): array
{
    $prochain = $this->getProchainMoisAPayer($etudiantId);
    $moisListe = [];
    
    $mois = $prochain['mois'];
    $annee = $prochain['annee'];
    
    for ($i = 0; $i < $nombreMois; $i++) {
        $moisListe[] = [
            'mois' => $mois,
            'annee' => $annee,
            'libelle' => $this->formatMoisAnnee($mois, $annee)
        ];
        
        $mois++;
        if ($mois > 12) {
            $mois = 1;
            $annee++;
        }
    }
    
    return $moisListe;
}


/**
 * Récupère le total encaissé (tous les paiements validés)
 */
public function getTotalImpayes(): float
{
    // Récupérer la somme des soldes restants de tous les loyers actifs
    return $this->loyerRepository->getTotalSoldeRestant();
}

/**
 * Récupère le total encaissé (tous les paiements validés)
 */
public function getTotalEncaissement(): float
{
    return $this->paiementRepository->getTotalEncaissement();
}

/**
 * Récupère le total dû à partir des loyers
 */
public function getTotalDu(): float
{
    return $this->loyerRepository->getTotalMontantTotal();
}

/**
 * Récupère le nombre de loyers en retard
 */
public function getNombreLoyersEnRetard(): int
{
    return $this->loyerRepository->getNombreLoyersEnRetard();
}

/**
 * Récupère le total des loyers en retard
 */
public function getTotalLoyersEnRetard(): float
{
    return $this->loyerRepository->getTotalLoyersEnRetard();
}

/**
 * Récupère le taux de recouvrement
 */
public function getTauxRecouvrement(): float
{
    $totalDu = $this->getTotalDu();
    $totalEncaisse = $this->getTotalEncaissement();
    
    if ($totalDu <= 0) {
        return 0;
    }
    
    return round(($totalEncaisse / $totalDu) * 100, 2);
}

public function getKPIs(): array
{
    $statsLoyers = $this->loyerService->getStatistiquesLoyers();
    $nombrePaiements = $this->paiementRepository->getNombrePaiementsMois();
    
    return [
        [
            'label' => 'Total encaissé',
            'value' => number_format($statsLoyers['total_paye'], 0, ',', ' ') . ' FCFA',
            'trend' => '▲ +8% vs mois dernier',
            'trend_class' => 'up',
            'type' => 'success',
        ],
        [
            'label' => 'Taux de recouvrement',
            'value' => $statsLoyers['taux_recouvrement'] . '%',
            'trend' => '● stable',
            'trend_class' => 'stable',
            'type' => 'info',
        ],
        [
            'label' => 'Impayés',
            'value' => number_format($statsLoyers['total_solde_restant'], 0, ',', ' ') . ' FCFA',
            'trend' => '▲ +1,2%',
            'trend_class' => 'danger',
            'type' => 'danger',
        ],
        [
            'label' => 'Pénalités',
            'value' => number_format($statsLoyers['total_penalites'], 0, ',', ' ') . ' FCFA',
            'trend' => '⚠️ Retards accumulés',
            'trend_class' => 'warning',
            'type' => 'warning',
        ],
        [
            'label' => 'Nombre de paiements',
            'value' => $nombrePaiements,
            'trend' => '▲ +12%',
            'trend_class' => 'up',
            'type' => 'purple',
        ],
        [
            'label' => 'Loyers en retard',
            'value' => $statsLoyers['nombre_en_retard'],
            'trend' => '⚠️ À surveiller',
            'trend_class' => 'warning',
            'type' => 'warning',
        ],
    ];
    }

    /**
 * Récupère les paiements d'un étudiant
 */
public function getPaiementsByEtudiant(string $etudiantId, int $limit = 10)
{
    return $this->paiementRepository->getByEtudiant($etudiantId)
        ->take($limit);
}

/**
 * Récupère le montant total des impayés d'un étudiant
 */
public function getTotalImpayesByEtudiant(string $etudiantId): float
{
    $arriere = $this->loyerRepository->calculerArriere($etudiantId);
    return $arriere['solde_restant'] ?? 0;
}

}
