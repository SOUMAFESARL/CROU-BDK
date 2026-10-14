<?php
// app/Repositories/LoyerRepository.php

namespace App\Repositories;

use App\Models\Loyer;
use App\Models\Etudiant;
use App\Models\Paiement;
use App\Models\Cite;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Interfaces\LoyerRepositoryInterface;


class LoyerRepository implements LoyerRepositoryInterface
{
    protected Loyer $model;

    public function __construct(Loyer $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->with(['etudiant', 'chambre', 'cite'])->get();
    }

    public function find(string $id): ?object
    {
        return $this->model->with(['etudiant', 'chambre', 'cite', 'paiements'])
            ->find($id);
    }

    public function create(array $data): object
{
    // S'assurer que les champs requis ont des valeurs
    if (!isset($data['montant_base']) || $data['montant_base'] === null) {
        $data['montant_base'] = 0;
    }
    
    if (!isset($data['montant_echeance']) || $data['montant_echeance'] === null) {
        $data['montant_echeance'] = $data['montant_base'] ?? 0;
    }
    
    if (!isset($data['montant_total']) || $data['montant_total'] === null) {
        $data['montant_total'] = $data['montant_base'] ?? 0;
    }
    
    if (!isset($data['solde_restant']) || $data['solde_restant'] === null) {
        $data['solde_restant'] = $data['montant_total'] - ($data['total_paye'] ?? 0);
    }
    
    if (!isset($data['nombre_impayes'])) {
        $data['nombre_impayes'] = 0;
    }
    
    if (!isset($data['total_penalites'])) {
        $data['total_penalites'] = 0;
    }
    
    // Générer l'ID si non fourni
    if (empty($data['id_loyer'])) {
        $data['id_loyer'] = (string) Str::uuid();
    }
    
    return $this->model->create($data);
}

    public function update(string $id, array $data): bool
    {
        $loyer = $this->find($id);
        if (!$loyer) {
            return false;
        }

        // Recalculer le solde restant si nécessaire
        if (isset($data['montant_total']) || isset($data['total_paye']) || isset($data['total_penalites'])) {
            $montantTotal = $data['montant_total'] ?? $loyer->montant_total;
            $totalPaye = $data['total_paye'] ?? $loyer->total_paye;
            $totalPenalites = $data['total_penalites'] ?? $loyer->total_penalites;
            $data['solde_restant'] = $montantTotal + $totalPenalites - $totalPaye;
        }

        return $loyer->update($data);
    }

    public function delete(string $id): bool
    {
        $loyer = $this->find($id);
        if (!$loyer) {
            return false;
        }
        return $loyer->delete();
    }

    public function getByEtudiant(string $etudiantId): Collection
    {
        return $this->model->with(['chambre', 'cite'])
            ->where('etudiant_id', $etudiantId)
            ->orderBy('annee_facturation', 'desc')
            ->orderBy('mois_facturation', 'desc')
            ->get();
    }

    public function getByEtudiantPaginated(string $etudiantId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->with(['chambre', 'cite'])
            ->where('etudiant_id', $etudiantId)
            ->orderBy('annee_facturation', 'desc')
            ->orderBy('mois_facturation', 'desc')
            ->paginate($perPage);
    }

    public function getActifByEtudiant(string $etudiantId): Collection
    {
        return $this->model->with(['chambre', 'cite'])
            ->where('etudiant_id', $etudiantId)
            ->whereIn('statut', ['actif', 'en_retard', 'partiel'])
            ->orderBy('annee_facturation', 'desc')
            ->orderBy('mois_facturation', 'desc')
            ->get();
    }

    public function calculerArriere(string $etudiantId): array
    {
        // Récupérer les loyers actifs de l'étudiant
        $loyers = $this->getActifByEtudiant($etudiantId);

        if ($loyers->isEmpty()) {
            return [
                'total_du' => 0,
                'total_paye' => 0,
                'solde_restant' => 0,
                'nombre_impayes' => 0,
                'total_penalites' => 0,
                'details' => [],
                'statut_global' => 'Aucun loyer',
                'dernier_paiement' => null,
                'prochain_paiement' => null
            ];
        }

        $totalDu = 0;
        $totalPaye = 0;
        $totalSolde = 0;
        $totalPenalites = 0;
        $nombreImpayes = 0;
        $details = [];
        $dernierPaiement = null;

        foreach ($loyers as $loyer) {
            // Calculer le solde restant
            $solde = $loyer->montant_total + $loyer->total_penalites - $loyer->total_paye;
            
            // Compter comme impayé si solde > 0
            if ($solde > 0) {
                $nombreImpayes++;
            }

            // Ajouter aux totaux
            $totalDu += $loyer->montant_total;
            $totalPaye += $loyer->total_paye;
            $totalSolde += $solde;
            $totalPenalites += $loyer->total_penalites;

            // Vérifier si en retard
            $estEnRetard = false;
            $joursRetard = 0;
            if ($loyer->date_limit_paiement && $loyer->date_limit_paiement < now() && $solde > 0) {
                $estEnRetard = true;
                $joursRetard = now()->diffInDays($loyer->date_limit_paiement);
            }

            // Détails par loyer
            $details[] = [
                'id_loyer' => $loyer->id_loyer,
                'mois_facturation' => $loyer->mois_facturation,
                'annee_facturation' => $loyer->annee_facturation,
                'montant_base' => $loyer->montant_base,
                'montant_total' => $loyer->montant_total,
                'total_paye' => $loyer->total_paye,
                'solde_restant' => $solde,
                'penalites' => $loyer->total_penalites,
                'statut' => $loyer->statut,
                'date_limit_paiement' => $loyer->date_limit_paiement,
                'est_en_retard' => $estEnRetard,
                'jours_retard' => $joursRetard,
                'periode' => $loyer->periode,
                'chambre' => $loyer->chambre ? $loyer->chambre->numero : null,
                'cite' => $loyer->cite ? $loyer->cite->name : null
            ];
        }

        // Déterminer le statut global
        $statutGlobal = 'À jour';
        if ($totalSolde > 0) {
            $statutGlobal = $nombreImpayes > 0 ? 'En retard' : 'Partiellement payé';
        }

        // Dernier paiement
        $dernierPaiement = Loyer::where('etudiant_id', $etudiantId)
            ->whereNotNull('date_dernier_paiement')
            ->orderBy('date_dernier_paiement', 'desc')
            ->first();

        // Prochain paiement
        $prochainPaiement = $loyers->where('statut', 'actif')
            ->where('solde_restant', '>', 0)
            ->sortBy('date_limit_paiement')
            ->first();

        return [
            'total_du' => $totalDu,
            'total_paye' => $totalPaye,
            'solde_restant' => $totalSolde,
            'nombre_impayes' => $nombreImpayes,
            'total_penalites' => $totalPenalites,
            'statut_global' => $statutGlobal,
            'details' => $details,
            'dernier_paiement' => $dernierPaiement ? $dernierPaiement->date_dernier_paiement : null,
            'prochain_paiement' => $prochainPaiement ? $prochainPaiement->date_limit_paiement : null,
            'montant_prochain' => $prochainPaiement ? $prochainPaiement->solde_restant : 0
        ];
    }

    public function calculerArrieresGroupes(array $etudiantIds): array
    {
        $resultats = [];
        
        $loyers = $this->model->whereIn('etudiant_id', $etudiantIds)
            ->whereIn('statut', ['actif', 'en_retard', 'partiel'])
            ->with(['chambre', 'cite'])
            ->get()
            ->groupBy('etudiant_id');

        foreach ($etudiantIds as $id) {
            $loyersEtudiant = $loyers->get($id, collect());
            
            if ($loyersEtudiant->isEmpty()) {
                $resultats[$id] = [
                    'total_du' => 0,
                    'total_paye' => 0,
                    'solde_restant' => 0,
                    'nombre_impayes' => 0,
                    'total_penalites' => 0,
                    'statut_global' => 'Aucun loyer',
                    'details' => []
                ];
                continue;
            }

            $totalDu = 0;
            $totalPaye = 0;
            $totalSolde = 0;
            $totalPenalites = 0;
            $nombreImpayes = 0;
            $details = [];

            foreach ($loyersEtudiant as $loyer) {
                $solde = $loyer->montant_total + $loyer->total_penalites - $loyer->total_paye;
                
                if ($solde > 0) {
                    $nombreImpayes++;
                }

                $totalDu += $loyer->montant_total;
                $totalPaye += $loyer->total_paye;
                $totalSolde += $solde;
                $totalPenalites += $loyer->total_penalites;

                $details[] = [
                    'id_loyer' => $loyer->id_loyer,
                    'mois_facturation' => $loyer->mois_facturation,
                    'annee_facturation' => $loyer->annee_facturation,
                    'montant_total' => $loyer->montant_total,
                    'total_paye' => $loyer->total_paye,
                    'solde_restant' => $solde,
                    'penalites' => $loyer->total_penalites,
                    'statut' => $loyer->statut
                ];
            }

            $statutGlobal = 'À jour';
            if ($totalSolde > 0) {
                $statutGlobal = $nombreImpayes > 0 ? 'En retard' : 'Partiellement payé';
            }

            $resultats[$id] = [
                'total_du' => $totalDu,
                'total_paye' => $totalPaye,
                'solde_restant' => $totalSolde,
                'nombre_impayes' => $nombreImpayes,
                'total_penalites' => $totalPenalites,
                'statut_global' => $statutGlobal,
                'details' => $details
            ];
        }

        return $resultats;
    }

    public function getEnRetard(): Collection
    {
        return $this->model->with(['etudiant', 'chambre', 'cite'])
            ->whereIn('statut', ['actif', 'partiel'])
            ->where('solde_restant', '>', 0)
            ->where('date_limit_paiement', '<', now())
            ->orderBy('date_limit_paiement', 'asc')
            ->get();
    }

    public function getByCite(string $citeId): Collection
    {
        return $this->model->with(['etudiant', 'chambre'])
            ->where('cite_id', $citeId)
            ->orderBy('annee_facturation', 'desc')
            ->orderBy('mois_facturation', 'desc')
            ->get();
    }

    public function getByAnneeUniversitaire(string $annee): Collection
    {
        return $this->model->with(['etudiant', 'chambre', 'cite'])
            ->where('annee_universitaire', $annee)
            ->get();
    }

    public function updateSolde(string $loyerId): bool
    {
        $loyer = $this->find($loyerId);
        if (!$loyer) {
            return false;
        }

        $solde = $loyer->montant_total + $loyer->total_penalites - $loyer->total_paye;
        $loyer->solde_restant = $solde;

        // Mettre à jour le statut si nécessaire
        if ($solde <= 0) {
            $loyer->statut = 'termine';
        } elseif ($loyer->date_limit_paiement && $loyer->date_limit_paiement < now()) {
            $loyer->statut = 'en_retard';
        }

        return $loyer->save();
    }

    public function appliquerPenalites(): int
    {
        $loyersEnRetard = $this->model->where('solde_restant', '>', 0)
            ->where('date_limit_paiement', '<', now())
            ->where('penalite_journaliere', '>', 0)
            ->get();

        $compteur = 0;

        foreach ($loyersEnRetard as $loyer) {
            $joursRetard = now()->diffInDays($loyer->date_limit_paiement) - ($loyer->jours_retard_autorises ?? 0);
            
            if ($joursRetard > 0) {
                $penalite = $joursRetard * $loyer->penalite_journaliere;
                $loyer->total_penalites += $penalite;
                $loyer->solde_restant += $penalite;
                $loyer->statut = 'en_retard';
                $loyer->save();
                $compteur++;
            }
        }

        return $compteur;
    }

    public function enregistrerPaiement(string $loyerId, float $montant, ?string $datePaiement = null): bool
    {
        $loyer = $this->find($loyerId);
        if (!$loyer) {
            return false;
        }

        $datePaiement = $datePaiement ?? now()->format('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            // Mettre à jour le loyer
            $loyer->total_paye += $montant;
            $loyer->date_dernier_paiement = $datePaiement;
            $loyer->solde_restant = $loyer->montant_total + $loyer->total_penalites - $loyer->total_paye;

            // Mettre à jour le statut
            if ($loyer->solde_restant <= 0) {
                $loyer->statut = 'termine';
            }

            $loyer->save();

            // Créer l'enregistrement de paiement
            Paiement::create([
                'loyer_id' => $loyerId,
                'resident_id' => $loyer->etudiant_id,
                'contrat_id' => $loyer->assignation_id,
                'chambre_id' => $loyer->chambre_id,
                'batiment_id' => $loyer->chambre?->batiment_id ?? null,
                'cite_id' => $loyer->cite_id,
                'montant' => $montant,
                'date_paiement' => $datePaiement,
                'statut' => 'valide'
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getStatistiques(): array
    {
        $stats = [
            'total_loyers' => $this->model->count(),
            'total_montant' => $this->model->sum('montant_total'),
            'total_paye' => $this->model->sum('total_paye'),
            'total_restant' => $this->model->sum('solde_restant'),
            'total_penalites' => $this->model->sum('total_penalites'),
            'nombre_actifs' => $this->model->where('statut', 'actif')->count(),
            'nombre_en_retard' => $this->model->where('statut', 'en_retard')->count(),
            'nombre_termines' => $this->model->where('statut', 'termine')->count(),
            'taux_recouvrement' => 0
        ];

        if ($stats['total_montant'] > 0) {
            $stats['taux_recouvrement'] = round(($stats['total_paye'] / $stats['total_montant']) * 100, 2);
        }

        return $stats;
    }

    public function getWithFilters(array $filters): Collection
    {
        $query = $this->model->with(['etudiant', 'chambre', 'cite']);

        if (isset($filters['etudiant_id'])) {
            $query->where('etudiant_id', $filters['etudiant_id']);
        }

        if (isset($filters['cite_id'])) {
            $query->where('cite_id', $filters['cite_id']);
        }

        if (isset($filters['chambre_id'])) {
            $query->where('chambre_id', $filters['chambre_id']);
        }

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['annee_facturation'])) {
            $query->where('annee_facturation', $filters['annee_facturation']);
        }

        if (isset($filters['mois_facturation'])) {
            $query->where('mois_facturation', $filters['mois_facturation']);
        }

        if (isset($filters['boursier'])) {
            $query->where('boursier', $filters['boursier']);
        }

        if (isset($filters['date_debut']) && isset($filters['date_fin'])) {
            $query->whereBetween('created_at', [$filters['date_debut'], $filters['date_fin']]);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('etudiant', function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('prenom', 'LIKE', "%{$search}%")
                  ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function getWithFiltersPaginated(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['etudiant', 'chambre', 'cite']);

        if (isset($filters['etudiant_id'])) {
            $query->where('etudiant_id', $filters['etudiant_id']);
        }

        if (isset($filters['cite_id'])) {
            $query->where('cite_id', $filters['cite_id']);
        }

        if (isset($filters['chambre_id'])) {
            $query->where('chambre_id', $filters['chambre_id']);
        }

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['annee_facturation'])) {
            $query->where('annee_facturation', $filters['annee_facturation']);
        }

        if (isset($filters['mois_facturation'])) {
            $query->where('mois_facturation', $filters['mois_facturation']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('etudiant', function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('prenom', 'LIKE', "%{$search}%")
                  ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getLoyerByMois(string $etudiantId, string $mois, string $annee): ?object
{
    return $this->model
        ->where('etudiant_id', $etudiantId)
        ->where('mois_facturation', $mois)
        ->where('annee_facturation', $annee)
        ->first();
}

public function getTotalSoldeRestant(): float
{
    return $this->model
        ->where('statut', '!=', 'termine')
        ->where('statut', '!=', 'annule')
        ->sum('solde_restant') ?? 0;
}

/**
 * Récupère le total des montants totaux des loyers
 */
public function getTotalMontantTotal(): float
{
    return $this->model
        ->where('statut', '!=', 'annule')
        ->sum('montant_total') ?? 0;
}

/**
 * Récupère le total des montants payés
 */
public function getTotalPaye(): float
{
    return $this->model
        ->where('statut', '!=', 'annule')
        ->sum('total_paye') ?? 0;
}

/**
 * Récupère le nombre de loyers en retard
 */
public function getNombreLoyersEnRetard(): int
{
    return $this->model
        ->where('statut', 'en_retard')
        ->where('solde_restant', '>', 0)
        ->count();
}

/**
 * Récupère le total des loyers en retard
 */
public function getTotalLoyersEnRetard(): float
{
    return $this->model
        ->where('statut', 'en_retard')
        ->where('solde_restant', '>', 0)
        ->sum('solde_restant') ?? 0;
}

/**
 * Récupère les statistiques complètes des loyers
 */
public function getStatistiquesLoyers(?string $annee = null, ?string $mois = null, ?string $residence = null): array
{
    $query = $this->model->newQuery()->where('statut', '!=', 'annule');
    if ($annee) {
        $query->where(function ($dateQuery) use ($annee) {
            $dateQuery->whereYear('date_limit_paiement', $annee)
                ->orWhere(fn ($fallback) => $fallback->whereNull('date_limit_paiement')->whereYear('created_at', $annee));
        });
    }
    if ($mois) {
        $query->where(function ($dateQuery) use ($mois) {
            $dateQuery->whereMonth('date_limit_paiement', (int) $mois)
                ->orWhere(fn ($fallback) => $fallback->whereNull('date_limit_paiement')->whereMonth('created_at', (int) $mois));
        });
    }
    if ($residence) $query->where('cite_id', $residence);

    return [
        'total_montant' => (float) (clone $query)->sum('montant_total'),
        'total_paye' => (float) (clone $query)->sum('total_paye'),
        'total_solde_restant' => (float) (clone $query)->sum('solde_restant'),
        'total_penalites' => (float) (clone $query)->sum('total_penalites'),
        'nombre_total' => (clone $query)->count(),
        'nombre_actifs' => (clone $query)->where('statut', 'actif')->count(),
        'nombre_en_retard' => (clone $query)->where('statut', 'en_retard')->where('solde_restant', '>', 0)->count(),
        'nombre_termines' => (clone $query)->where('statut', 'termine')->count(),
        'taux_recouvrement' => 0,
    ];
}

     public function getStatistiquesParResidence(): Collection
    {
        // 1. Statistiques des loyers par cité
        $statsLoyers = $this->model
            ->select(
                'cite_id',
                DB::raw('SUM(montant_total) as total_attendu'),
                DB::raw('SUM(total_paye) as total_paye_loyers'),
                DB::raw('SUM(solde_restant) as total_impayes'),
                DB::raw('SUM(total_penalites) as total_penalites'),
                DB::raw('COUNT(DISTINCT etudiant_id) as nb_residents'),
                DB::raw('AVG(montant_total) as loyer_moyen'),
                DB::raw('COUNT(CASE WHEN statut = "en_retard" THEN 1 END) as nb_en_retard')
            )
          //  ->where('statut', '!=', 'annule')
          //  ->where('statut', '!=', 'termine')
            ->groupBy('cite_id')
            ->get();

        // 2. Récupérer les paiements validés par cité
        $statsPaiements = DB::table('paiements')
            ->select(
                'cite_id',
                DB::raw('SUM(montant) as total_encaisse_paiements'),
                DB::raw('COUNT(*) as nb_paiements')
            )
            ->where('statut_paiement', 'valide')
            ->where('type_paiement', 'loyer')
            ->groupBy('cite_id')
            ->get()
            ->keyBy('cite_id');

        // 3. Transformer les données et retourner une Collection
        $results = $statsLoyers->map(function ($item) use ($statsPaiements) {
            $cite = Cite::find($item->cite_id);
            $paiement = $statsPaiements->get($item->cite_id);
            
            $totalEncaisse = min((float) $item->total_attendu, max(0, (float) $item->total_paye_loyers));
            $nbPaiements = $paiement ? $paiement->nb_paiements : 0;
            
            $tauxRecouvrement = $item->total_attendu > 0 
                ? round(($totalEncaisse / $item->total_attendu) * 100, 1) 
                : 0;
            
            return (object) [
                'residence' => $cite ? $cite->name : 'N/A',
                'cite_id' => $item->cite_id,
                'total_attendu' => $item->total_attendu,
                'total_attendu_formate' => number_format($item->total_attendu, 0, ',', ' ') . ' FCFA',
                'total_encaisse' => $totalEncaisse,
                'total_encaisse_formate' => number_format($totalEncaisse, 0, ',', ' ') . ' FCFA',
                'total_impayes' => $item->total_impayes,
                'total_impayes_formate' => number_format($item->total_impayes, 0, ',', ' ') . ' FCFA',
                'total_penalites' => $item->total_penalites,
                'total_penalites_formate' => number_format($item->total_penalites, 0, ',', ' ') . ' FCFA',
                'nb_residents' => $item->nb_residents,
                'nb_en_retard' => $item->nb_en_retard,
                'nb_paiements' => $nbPaiements,
                'loyer_moyen' => $item->loyer_moyen,
                'loyer_moyen_formate' => number_format($item->loyer_moyen, 0, ',', ' ') . ' FCFA',
                'taux_recouvrement' => $tauxRecouvrement,
                'alerte' => $tauxRecouvrement < 80,
            ];
        });

        // Convertir en Eloquent Collection
        return new Collection($results->toArray());
    }

    /**
     * Récupère les statistiques par résidence avec filtrage par mois/année
     */
  public function getStatistiquesParResidenceFiltre(?string $annee = null, ?string $mois = null, ?string $residence = null): Collection
{
    // Nettoyer et valider les paramètres
    $mois = $mois ? str_pad((int) $mois, 2, '0', STR_PAD_LEFT) : null;
    $annee = $annee ? (string) (int) $annee : (string) now()->format('Y');
    
    // Construire la requête
    $query = $this->model
        ->select(
            'cite_id',
             
            DB::raw('SUM(montant_total) as total_attendu'),
            DB::raw('SUM(total_paye) as total_paye_loyers'),
            DB::raw('SUM(solde_restant) as total_impayes'),
            DB::raw('SUM(total_penalites) as total_penalites'),
            DB::raw('COUNT(DISTINCT etudiant_id) as nb_residents'),
            DB::raw('AVG(montant_total) as loyer_moyen'),
            DB::raw('COUNT(CASE WHEN statut = "en_retard" THEN 1 END) as nb_en_retard')
        );
      //  ->where('statut', '!=', 'annule')
       // ->where('statut', '!=', 'termine');
  
    // La période porte sur l'échéance du loyer, ou sa création si elle manque.
    if ($annee !== null) {
        $query->where(function ($dateQuery) use ($annee) {
            $dateQuery->whereYear('date_limit_paiement', (int) $annee)
                ->orWhere(fn ($fallback) => $fallback->whereNull('date_limit_paiement')->whereYear('created_at', (int) $annee));
        });
    }
    if ($mois !== null) {
        $query->where(function ($dateQuery) use ($mois) {
            $dateQuery->whereMonth('date_limit_paiement', (int) $mois)
                ->orWhere(fn ($fallback) => $fallback->whereNull('date_limit_paiement')->whereMonth('created_at', (int) $mois));
        });
    }
    
    if ($residence) {
        $query->where('cite_id', $residence);
    }
    
    $statsLoyers = $query->groupBy('cite_id')->get();
   // dd($statsLoyers);
    // Si aucun résultat, retourner une Eloquent Collection vide
    if ($statsLoyers->isEmpty()) {
        return new Collection(); // Utiliser new Collection() au lieu de collect()
    }

    // 2. Récupérer les paiements
    $paiementsQuery = DB::table('paiements')
        ->select(
            'cite_id',
            DB::raw('SUM(montant) as total_encaisse_paiements'),
            DB::raw('COUNT(*) as nb_paiements')
        )
        ->where('statut_paiement', 'valide');
    $paiementsQuery->where('type_paiement', 'loyer');
    
    if ($mois !== null) {
        $paiementsQuery->whereMonth('date_paiement', (int) $mois);
    }
    if ($annee !== null) {
        $paiementsQuery->whereYear('date_paiement', (int) $annee);
    }
    if ($residence) {
        $paiementsQuery->where('cite_id', $residence);
    }
    
    $statsPaiements = $paiementsQuery->groupBy('cite_id')->get()->keyBy('cite_id');

    // 3. Fusionner les données et retourner une Eloquent Collection
    $results = $statsLoyers->map(function ($item) use ($statsPaiements) {
        $cite = Cite::find($item->cite_id);
        $paiement = $statsPaiements->get($item->cite_id);
        
        $totalEncaisse = $paiement ? $paiement->total_encaisse_paiements : 0;
        $nbPaiements = $paiement ? $paiement->nb_paiements : 0;
        
        $tauxRecouvrement = $item->total_attendu > 0 
            ? round(min(100, max(0, $totalEncaisse) / $item->total_attendu * 100), 1)
            : 0;
        
        return (object) [
            'residence' => $cite ? $cite->name : 'N/A',
            'cite_id' => $item->cite_id,
            'total_attendu' => (float) $item->total_attendu,
            'total_encaisse' => (float) $totalEncaisse,
            'total_impayes' => (float) $item->total_impayes,
            'total_penalites' => (float) $item->total_penalites,
            'nb_residents' => (int) $item->nb_residents,
            'nb_en_retard' => (int) $item->nb_en_retard,
            'nb_paiements' => (int) $nbPaiements,
            'loyer_moyen' => (float) $item->loyer_moyen,
            'taux_recouvrement' => (float) $tauxRecouvrement,
            'alerte' => $tauxRecouvrement < 80,
        ];
    });
    
    // Convertir en Eloquent Collection
    return new Collection($results->toArray());
}
    public function getQuery()
    {
        return $this->model->newQuery();
    }
}
