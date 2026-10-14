<?php
// app/Http/Services/LoyerService.php

namespace App\Http\Services;

use App\Models\Loyer;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Models\AssignationChambre;
use App\Models\Cite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Repositories\Interfaces\LoyerRepositoryInterface;

class LoyerService
{
    protected LoyerRepositoryInterface $loyerRepository;

    public function __construct(LoyerRepositoryInterface $loyerRepository)
    {
        $this->loyerRepository = $loyerRepository;
      
    }
    /**
     * Génère automatiquement un loyer pour un étudiant assigné
     */
    public function genererLoyerPourAssignation(AssignationChambre $assignation)
    {
        try {
            DB::beginTransaction();

            $chambre = $assignation->chambre;
            $cite = $chambre->cite;
            $etudiant = $assignation->etudiant;

            // 1. Déterminer le montant de base selon la catégorie de chambre
            $montantBase = $this->calculerMontantBase($chambre);
            
            // 2. Vérifier les réductions (bourse, social)
            $reductions = $this->calculerReductions($etudiant);
            $moisFacturation = now()->month;
            $anneeFacturation = now()->year;
            $dateLimitPaiement = Carbon::create($anneeFacturation, $moisFacturation, 10, 23, 59, 59);
            
            // 3. Calculer les suppléments
            $supplements = $this->calculerSupplements($chambre);
            
            // 4. Calculer le montant total
            $montantTotal = $montantBase + $supplements - $reductions['total'];
            
            // 5. Déterminer la période et les échéances
            $periode = $this->determinerPeriode($assignation);
            $nombreEcheances = $this->calculerEcheances($periode);
            $montantEcheance = $nombreEcheances > 0 ? $montantTotal / $nombreEcheances : $montantTotal;
            
            // 6. Calculer la caution
            $caution = $this->calculerCaution($chambre, $montantTotal);
            
            // 7. Créer le loyer
            $loyer = Loyer::create([
                'etudiant_id' => $etudiant->id_etudiant,
                'chambre_id' => $chambre->id_chambre,
                'cite_id' => $cite->id_cite,
                'campus_id'=> $cite->campus_id,
                'assignation_id' => $assignation->id_assignation,
                'montant_base' => $montantBase,
                'montant_total' => $montantTotal + $caution,
                'caution' => $caution,
                'frais_inscription' => $this->getFraisInscription($chambre),
                'annee_universitaire' => $this->getAnneeUniversitaire(),
                'periode' => $periode,
                'nombre_echeances' => $nombreEcheances,
                'montant_echeance' => $montantEcheance,
                'statut' => 'actif',
                
                'date_limit_paiement' => $dateLimitPaiement,
                'mois_facturation' => $moisFacturation,
                'annee_facturation' => $anneeFacturation,
                'boursier' => $etudiant->boursier ?? false,
                'reduction_bourse' => $reductions['bourse'],
                'type_bourse' => $etudiant->type_bourse ?? null,
                'reduction_sociale' => $reductions['sociale'],
                'supplement_prestation' => $supplements,
                'solde_restant' => $montantTotal + $caution,
                'penalite_journaliere' => $this->getPenaliteJournaliere($montantEcheance),
                'jours_retard_autorises' => 5,
                'renouvellement_auto' => true,
                'created_by' => getInfoAdmin()->users->id_user ?? 'system',
            ]);

            DB::commit();
            
            Log::info("Loyer généré automatiquement", [
                'loyer_id' => $loyer->id_loyer,
                'etudiant' => $etudiant->id_etudiant,
                'montant' => $montantTotal
            ]);
            
            return $loyer;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur génération loyer", [
                'assignation' => $assignation->id_assignation,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    /**
 * Génère automatiquement un loyer pour un étudiant assigné
 */

/**
 * Calcule la date limite de paiement (10 du mois à 23:59:59)
 */
private function calculerDateLimitePaiement($mois, $annee)
{
    return Carbon::create($annee, $mois, 10, 23, 59, 59);
}

/**
 * Met à jour la date limite de paiement pour le mois suivant
 */
public function mettreAJourDateLimitePaiement(Loyer $loyer)
{
    $moisActuel = now()->month;
    $anneeActuelle = now()->year;
    
    // Si on a dépassé le 10 du mois, on passe au mois suivant
    if (now()->day > 10) {
        $dateLimite = Carbon::create($anneeActuelle, $moisActuel + 1, 10, 23, 59, 59);
    } else {
        $dateLimite = Carbon::create($anneeActuelle, $moisActuel, 10, 23, 59, 59);
    }
    
    $loyer->update([
        'date_limit_paiement' => $dateLimite,
        'mois_facturation' => $dateLimite->month,
        'annee_facturation' => $dateLimite->year,
    ]);
    
    return $loyer;
}

/**
 * Vérifie si un paiement est en retard
 */
public function estEnRetard(Loyer $loyer)
{
    $dateLimite = Carbon::parse($loyer->date_limit_paiement);
    
    // Si la date limite est dépassée ET qu'il reste un solde à payer
    if (now()->greaterThan($dateLimite) && $loyer->solde_restant > 0) {
        $joursRetard = now()->diffInDays($dateLimite);
        
        return [
            'en_retard' => true,
            'jours_retard' => $joursRetard,
            'date_limite' => $dateLimite->format('d/m/Y'),
            'message' => "Paiement en retard de {$joursRetard} jours (date limite: {$dateLimite->format('d/m/Y')})"
        ];
    }
    
    return [
        'en_retard' => false,
        'jours_retard' => 0,
        'date_limite' => $dateLimite->format('d/m/Y'),
        'message' => "Paiement à jour"
    ];
}

    /**
     * Génère les loyers en masse pour une promotion
     */
    public function genererLoyersMasse($citeId = null, $promotion = null)
    {
        $assignations = AssignationChambre::where('statut', 'confirmee')
            ->when($citeId, function ($query) use ($citeId) {
                return $query->whereHas('chambre', function ($q) use ($citeId) {
                    $q->where('cite_id', $citeId);
                });
            })
            ->whereDoesntHave('loyer', function ($q) {
                $q->where('annee_universitaire', $this->getAnneeUniversitaire());
            })
            ->get();

        $resultats = [
            'succes' => 0,
            'echecs' => 0,
            'details' => []
        ];

        foreach ($assignations as $assignation) {
            try {
                $this->genererLoyerPourAssignation($assignation);
                $resultats['succes']++;
            } catch (\Exception $e) {
                $resultats['echecs']++;
                $resultats['details'][] = [
                    'assignation' => $assignation->id_assignation,
                    'erreur' => \App\Support\UserFacingErrors::caught($e)
                ];
            }
        }

        return $resultats;
    }

    /**
     * Calcule le montant de base selon la catégorie de chambre
     */
 private function calculerMontantBase(Chambre $chambre)
{
    // Utiliser directement le montant du type de chambre s'il existe
    if ($chambre->typeChambre && $chambre->typeChambre->montant) {
        return $chambre->typeChambre->montant;
    }
    
    // Fallback : Grille tarifaire selon le type de chambre
    $grilleTarifaire = [
        'Individuelle' => 12000,
        'Double' => 6000,
        'Triple' => 4000,
    ];

    $typeChambre = $chambre->typeChambre?->name ?? 'Double';
    return $grilleTarifaire[$typeChambre] ?? 6000;
}

    /**
     * Calcule les réductions applicables
     */
    private function calculerReductions(Etudiant $etudiant)
    {
        $reductions = [
            'bourse' => 0,
            'sociale' => 0,
            'total' => 0
        ];

        // Réduction bourse
        if ($etudiant->boursier) {
            switch ($etudiant->type_bourse) {
                case 'entiere':
                    $reductions['bourse'] = 25000;
                    break;
                case 'demi':
                    $reductions['bourse'] = 12500;
                    break;
                case 'merite':
                    $reductions['bourse'] = 35000;
                    break;
            }
        }

        // Réduction sociale
        if ($etudiant->cas_social ?? false) {
            $reductions['sociale'] = 10000;
        }

        $reductions['total'] = $reductions['bourse'] + $reductions['sociale'];
        return $reductions;
    }

    /**
     * Calcule les suppléments pour services additionnels
     */
    private function calculerSupplements(Chambre $chambre)
    {
        $supplements = 0;

        // Supplément WiFi
        if ($chambre->options['wifi'] ?? false) {
            $supplements += 5000;
        }

        // Supplément climatisation
        if ($chambre->options['climatisation'] ?? false) {
            $supplements += 10000;
        }

        // Supplément repas
        if ($chambre->options['repas_inclus'] ?? false) {
            $supplements += 15000;
        }

        return $supplements;
    }

    private function calculerCaution(Chambre $chambre, $montantTotal)
    {
        // La caution est généralement égale à un mois de loyer
        return $chambre->caution_obligatoire ? $montantTotal / $this->calculerEcheances('annuel') : 0;
    }

    private function determinerPeriode($assignation)
    {
        $duree = $assignation->date_fin 
            ? now()->diffInMonths($assignation->date_fin) 
            : 12;

        return match(true) {
            $duree <= 3 => 'trimestriel',
            $duree <= 6 => 'semestriel',
            default => 'annuel'
        };
    }

    private function calculerEcheances($periode)
    {
        return match($periode) {
            'mensuel' => 12,
            'trimestriel' => 4,
            'semestriel' => 2,
            'annuel' => 1,
            default => 1
        };
    }
                    
    private function calculerProchaineEcheance($dateDebut, $periode)
    {
        $date = \Carbon\Carbon::parse($dateDebut);
        
        return match($periode) {
            'mensuel' => $date->addMonth()->format('Y-m-d'),
            'trimestriel' => $date->addMonths(3)->format('Y-m-d'),
            'semestriel' => $date->addMonths(6)->format('Y-m-d'),
            'annuel' => $date->format('Y-m-d'),
            default => $date->format('Y-m-d')
        };
    }

    private function getPenaliteJournaliere($montantEcheance)
    {
        // 2% du montant de l'échéance par jour de retard
        return round($montantEcheance * 0.02, 2);
    }

    private function getFraisInscription($chambre)
    {
       $montant = $this->calculerMontantBase($chambre);
        return  $chambre->typeChambre?->avance * $montant; // Frais d'inscription standard
    }

    private function getAnneeUniversitaire()
    {
        $now = now();
        return $now->month >= 9 
            ? $now->year . '-' . ($now->year + 1)
            : ($now->year - 1) . '-' . $now->year;
    }

    /**
     * Vérifie et met à jour les statuts des loyers
     */
    public function verifierEtMettreAJourStatuts()
    {
        $loyers = Loyer::where('statut', 'actif')
            ->where('date_prochain_paiement', '<', now())
            ->get();

        foreach ($loyers as $loyer) {
            $loyer->appliquerPenalites();
            
            if ($loyer->solde_restant > 0) {
                $joursRetard = now()->diffInDays($loyer->date_prochain_paiement);
                if ($joursRetard > 30) {
                    $loyer->statut = 'en_retard';
                    $loyer->nombre_impayes++;
                    $loyer->save();
                }
            }
        }
    }

    /**
 * Calcule la date limite de paiement
 * Format: 10 du mois de facturation à 23:59:59
 * Exemple: Mois 7, Année 2026 -> 2026-07-10 23:59:59
 */
// private function calculerDateLimitePaiement($moisFacturation, $anneeFacturation)
// {
//     return Carbon::create($anneeFacturation, $moisFacturation, 10, 23, 59, 59);
// }

/**
 * Calcule le mois et l'année de facturation
 */
private function calculerFacturation($dateDebut)
{
    $date = Carbon::parse($dateDebut);
    
    return [
        'mois' => $date->month,
        'annee' => $date->year,
    ];
}

 /**
     * Récupère tous les loyers
     */
    public function getAll(): Collection
    {
        return $this->loyerRepository->all();
    }

    /**
     * Récupère un loyer par son ID
     */
    public function getById(string $id): ?object
    {
        return $this->loyerRepository->find($id);
    }

    /**
     * Crée un nouveau loyer
     */
    public function create(array $data): object
    {
        try {
            DB::beginTransaction();
            
            $loyer = $this->loyerRepository->create($data);
            
            DB::commit();
            return $loyer;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du loyer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Met à jour un loyer
     */
    public function update(string $id, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            $result = $this->loyerRepository->update($id, $data);
            
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du loyer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprime un loyer
     */
    public function delete(string $id): bool
    {
        try {
            DB::beginTransaction();
            
            $result = $this->loyerRepository->delete($id);
            
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression du loyer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère les loyers d'un étudiant
     */
    public function getByEtudiant(string $etudiantId): Collection
    {
        return $this->loyerRepository->getByEtudiant($etudiantId);
    }

    /**
     * Récupère les loyers d'un étudiant avec pagination
     */
    public function getByEtudiantPaginated(string $etudiantId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->loyerRepository->getByEtudiantPaginated($etudiantId, $perPage);
    }

    /**
     * Récupère les loyers actifs d'un étudiant
     */
    public function getActifByEtudiant(string $etudiantId): Collection
    {
        return $this->loyerRepository->getActifByEtudiant($etudiantId);
    }

    /**
     * Calcule les arriérés d'un étudiant
     */
    public function calculerArriere(string $etudiantId): array
    {
        return $this->loyerRepository->calculerArriere($etudiantId);
    }

    /**
     * Calcule les arriérés pour plusieurs étudiants
     */
    public function calculerArrieresGroupes(array $etudiantIds): array
    {
        return $this->loyerRepository->calculerArrieresGroupes($etudiantIds);
    }

    /**
     * Récupère les loyers en retard
     */
    public function getEnRetard(): Collection
    {
        return $this->loyerRepository->getEnRetard();
    }

    /**
     * Récupère les loyers par résidence
     */
    public function getByCite(string $citeId): Collection
    {
        return $this->loyerRepository->getByCite($citeId);
    }

    /**
     * Récupère les loyers par année universitaire
     */
    public function getByAnneeUniversitaire(string $annee): Collection
    {
        return $this->loyerRepository->getByAnneeUniversitaire($annee);
    }

    /**
     * Met à jour le solde d'un loyer
     */
    public function updateSolde(string $loyerId): bool
    {
        return $this->loyerRepository->updateSolde($loyerId);
    }

    /**
     * Applique les pénalités pour tous les loyers en retard
     */
    public function appliquerPenalites(): int
    {
        try {
            return $this->loyerRepository->appliquerPenalites();
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'application des pénalités: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Enregistre un paiement pour un loyer
     */
    public function enregistrerPaiement(string $loyerId, float $montant, ?string $datePaiement = null): bool
    {
        try {
            DB::beginTransaction();
            
            $result = $this->loyerRepository->enregistrerPaiement($loyerId, $montant, $datePaiement);
            
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'enregistrement du paiement: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère les statistiques des loyers
     */
    public function getStatistiques(): array
    {
        return $this->loyerRepository->getStatistiques();
    }

    /**
     * Récupère les loyers avec filtres
     */
    public function getWithFilters(array $filters): Collection
    {
        return $this->loyerRepository->getWithFilters($filters);
    }

    /**
     * Récupère les loyers avec filtres paginés
     */
    public function getWithFiltersPaginated(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->loyerRepository->getWithFiltersPaginated($filters, $perPage);
    }

    /**
     * Crée plusieurs loyers en lot
     */
    public function createBatch(array $loyersData): array
    {
        try {
            DB::beginTransaction();
            
            $results = [];
            foreach ($loyersData as $data) {
                $results[] = $this->loyerRepository->create($data);
            }
            
            DB::commit();
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création en lot des loyers: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère le résumé des arriérés pour un étudiant (format simplifié)
     */
    public function getResumeArriere(string $etudiantId): array
    {
        $arriere = $this->calculerArriere($etudiantId);
        
        return [
            'etudiant_id' => $etudiantId,
            'total_du' => $arriere['total_du'],
            'total_paye' => $arriere['total_paye'],
            'solde_restant' => $arriere['solde_restant'],
            'nombre_impayes' => $arriere['nombre_impayes'],
            'statut' => $arriere['statut_global'],
            'prochain_paiement' => $arriere['prochain_paiement'],
            'montant_prochain' => $arriere['montant_prochain']
        ];
    }

    /**
     * Récupère tous les étudiants avec leurs arriérés
     */
    public function getAllEtudiantsAvecArrieres(array $etudiantIds): array
    {
        $arrieres = $this->calculerArrieresGroupes($etudiantIds);
        $resultats = [];

        foreach ($etudiantIds as $id) {
            $arriere = $arrieres[$id] ?? [
                'solde_restant' => 0,
                'statut_global' => 'Aucun loyer'
            ];
            
            $resultats[$id] = [
                'id' => $id,
                'solde_restant' => $arriere['solde_restant'],
                'statut' => $arriere['statut_global'],
                'nombre_impayes' => $arriere['nombre_impayes'] ?? 0
            ];
        }

        return $resultats;
    }

    public function getStatistiquesLoyers(?string $annee = null, ?string $mois = null, ?string $residence = null): array
{
    $stats = $this->loyerRepository->getStatistiquesLoyers($annee, $mois, $residence);
    
    // Calculer le taux de recouvrement
    if ($stats['total_montant'] > 0) {
        $stats['taux_recouvrement'] = round(
            min(100, max(0, $stats['total_paye']) / $stats['total_montant'] * 100),
            2
        );
    }
    
    return $stats;
}

/**
 * Récupère le total des impayés (solde restant)
 */
public function getTotalImpayes(): float
{
    return $this->loyerRepository->getTotalSoldeRestant();
}

/**
 * Récupère le total dû (montant total des loyers)
 */
public function getTotalDu(): float
{
    return $this->loyerRepository->getTotalMontantTotal();
}

/**
 * Récupère le total encaissé
 */
public function getTotalPaye(): float
{
    return $this->loyerRepository->getTotalPaye();
}

/**
 * Récupère le nombre de loyers en retard
 */
public function getNombreLoyersEnRetard(): int
{
    return $this->loyerRepository->getNombreLoyersEnRetard();
}
   public function getStatistiquesParResidence(): Collection
    {
        $stats = $this->loyerRepository->getStatistiquesParResidence();
        
        // Retourner une Collection, pas un array
        return $stats->map(function ($item) {
            return [
                'residence' => $item->residence,
                'total_attendu' => $item->total_attendu_formate,
                'total_encaisse' => $item->total_encaisse_formate,
                'taux_recouvrement' => $item->taux_recouvrement,
                'impayes' => $item->total_impayes_formate,
                'nb_residents' => $item->nb_residents,
                'loyer_moyen' => $item->loyer_moyen_formate,
                'nb_en_retard' => $item->nb_en_retard,
                'alerte' => $item->alerte,
            ];
        });
        // Supprimer ->toArray()
    }

public function getQuery()
{
    return $this->model->newQuery();
}

/**
 * Récupère les statistiques par résidence avec filtres
 */
public function getStatistiquesParResidenceFiltre(?string $annee = null, ?string $mois = null, ?string $residence = null): array
{
    $stats = $this->loyerRepository->getStatistiquesParResidenceFiltre($annee, $mois, $residence);
    
    if ($stats->isEmpty()) {
        return [];
    }
    
    $result = [];
    foreach ($stats as $item) {
        $result[] = [
            'residence' => $item->residence,
            // Valeurs NUMÉRIQUES pour les calculs
            'total_attendu' => (float) $item->total_attendu,
            'total_encaisse' => (float) $item->total_encaisse,
            'taux_recouvrement' => (float) $item->taux_recouvrement,
            'total_impayes' => (float) $item->total_impayes,
            'nb_residents' => (int) $item->nb_residents,
            'loyer_moyen' => (float) $item->loyer_moyen,
            'nb_en_retard' => (int) $item->nb_en_retard,
            'alerte' => (bool) $item->alerte,
            // Valeurs formatées pour l'affichage (optionnel)
            'total_attendu_formate' => number_format($item->total_attendu, 0, ',', ' ') . ' FCFA',
            'total_encaisse_formate' => number_format($item->total_encaisse, 0, ',', ' ') . ' FCFA',
            'total_impayes_formate' => number_format($item->total_impayes, 0, ',', ' ') . ' FCFA',
            'loyer_moyen_formate' => number_format($item->loyer_moyen, 0, ',', ' ') . ' FCFA',
        ];
    }
    
    return $result;
}

}
