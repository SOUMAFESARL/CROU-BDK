<?php
// app/Repositories/PaiementRepository.php

namespace App\Repositories;

use App\Models\Paiement;
use App\Repositories\Interfaces\PaiementRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PaiementRepository implements PaiementRepositoryInterface
{
    protected $model;

    public function __construct(Paiement $model)
    {
        $this->model = $model;
    }

    public function getAllPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->with(['resident', 'chambre.cite', 'contrat']);
        
        if (!empty($filters['residence'])) {
            $query->where('cite_id', $filters['residence']);
        }
        
        if (!empty($filters['statut'])) {
            $query->where('statut_paiement', $filters['statut']);
        }
        
        if (!empty($filters['type'])) {
            $query->where('type_paiement', $filters['type']);
        }
        
        if (!empty($filters['mois'])) {
            $date = Carbon::createFromFormat('Y-m', $filters['mois']);
            $query->whereMonth('date_paiement', $date->month)
                  ->whereYear('date_paiement', $date->year);
        }
        
        if (!empty($filters['recherche'])) {
            $search = $filters['recherche'];
            $query->where(function ($q) use ($search) {
                $q->where('numero_paiement', 'LIKE', "%{$search}%")
                  ->orWhere('reference_transaction', 'LIKE', "%{$search}%")
                  ->orWhereHas('resident', function ($sq) use ($search) {
                      $sq->where('nom', 'LIKE', "%{$search}%")
                         ->orWhere('prenom', 'LIKE', "%{$search}%")
                         ->orWhere('matricule', 'LIKE', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_paiement', '>=', $filters['date_debut']);
        }
        
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_paiement', '<=', $filters['date_fin']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(string $id): ?Paiement
    {
        return $this->model->with(['resident', 'chambre.cite', 'contrat'])->find($id);
    }

    public function findByNumero(string $numero): ?Paiement
    {
        return $this->model->where('numero_paiement', $numero)->first();
    }



    public function getTotalEncaissementMois(): float
    {
        return (float) $this->model
            ->where('statut_paiement', 'valide')
            ->whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->sum('montant');
    }

    public function getTauxRecouvrement(): float
    {
        $totalDu = (float) $this->model
            ->whereMonth('created_at', now()->month)
            ->sum('montant_du');
            
        $totalPaye = $this->getTotalEncaissementMois();
        
        if ($totalDu > 0) {
            return round(($totalPaye / $totalDu) * 100, 1);
        }
        
        // Fallback: calculer sur les paiements valides vs total
        $totalPaiements = (float) $this->model
            ->whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->sum('montant');
            
        return $totalPaiements > 0 ? round(($totalPaye / $totalPaiements) * 100, 1) : 0;
    }

    public function getNombrePaiementsMois(): int
    {
        return $this->model
            ->whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->count();
    }

    public function getImpayes(): float
    {
        return (float) $this->model
            ->where('statut_paiement', '!=', 'valide')
            ->whereMonth('date_echeance', now()->month)
            ->whereYear('date_echeance', now()->year)
            ->sum('montant_du');
    }

    public function getTauxImpayes(): float
    {
        $totalPaiements = $this->model
            ->whereMonth('date_echeance', now()->month)
            ->count();
            
        $impayes = $this->model
            ->where('statut_paiement', '!=', 'valide')
            ->whereMonth('date_echeance', now()->month)
            ->count();
            
        return $totalPaiements > 0 ? round(($impayes / $totalPaiements) * 100, 1) : 0;
    }

    public function getLoyerMoyen(): float
    {
        return (float) $this->model
            ->where('statut_paiement', 'valide')
            ->whereMonth('date_paiement', now()->month)
            ->avg('montant') ?? 0;
    }
    public function getQuery()
    {
        return $this->model->newQuery();
    }

//  public function getStatistiquesParResidence(): Collection
// {
  
// }

    public function getPaiementsParMois(int $nbMois = 6): Collection
    {
        $resultats = collect();
        
        for ($i = $nbMois - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            
            $resultats->push([
                'mois' => $date->format('M'),
                'encaissements' => (float) $this->model
                    ->where('statut_paiement', 'valide')
                    ->whereMonth('date_paiement', $date->month)
                    ->whereYear('date_paiement', $date->year)
                    ->sum('montant'),
                'impayes' => (float) $this->model
                    ->where('statut_paiement', '!=', 'valide')
                    ->whereMonth('date_echeance', $date->month)
                    ->whereYear('date_echeance', $date->year)
                    ->sum('montant_du'),
            ]);
        }
        
        return $resultats;
    }

    public function getPaiementsParStatut(): Collection
    {
        return $this->model
            ->select('statut_paiement', DB::raw('COUNT(*) as total'))
            ->whereMonth('created_at', now()->month)
            ->groupBy('statut_paiement')
            ->get();
    }


        public function all(): Collection
    {
        return $this->model->with(['resident', 'chambre', 'cite'])->get();
    }

    public function find(string $id): ?object
    {
        return $this->model->with(['resident', 'chambre', 'cite', 'repartitions'])->find($id);
    }

    public function create(array $data): Paiement
    {
       
      
        return $this->model->create($data);
    }

    public function update(string $id, array $data): bool
    {
        $paiement = $this->find($id);
        if (!$paiement) {
            return false;
        }
        return $paiement->update($data);
    }

    public function delete(string $id): bool
    {
        $paiement = $this->find($id);
        if (!$paiement) {
            return false;
        }
        return $paiement->delete();
    }

    public function getByEtudiant(string $etudiantId): Collection
    {
        return $this->model->with(['chambre', 'cite'])
            ->where('resident_id', $etudiantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByEtudiantPaginated(string $etudiantId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->with(['chambre', 'cite'])
            ->where('resident_id', $etudiantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getByStatut(string $statut): Collection
    {
        return $this->model->with(['resident', 'chambre', 'cite'])
            ->where('statut_paiement', $statut)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByPeriode(string $mois, string $annee): Collection
    {
        return $this->model->with(['resident', 'chambre', 'cite'])
            ->where('mois', $mois)
            ->whereYear('created_at', $annee)
            ->get();
    }

    public function getLastPaiementByEtudiant(string $etudiantId): ?object
{
    return $this->model
        ->where('resident_id', $etudiantId)
        ->where('statut_paiement', 'valide')
        ->orderBy('created_at', 'desc')
        ->first();
}

/**
 * Récupère le total encaissé (tous les paiements validés)
 */
public function getTotalEncaissement(): float
{
    return $this->model
        ->where('statut_paiement', 'valide')
        ->sum('montant') ?? 0;
}

/**
 * Récupère le total dû (somme des montants_du de tous les paiements)
 */
public function getTotalDu(): float
{
    return $this->model
        ->where('statut_paiement', '!=', 'annule')
        ->sum('montant_du') ?? 0;
}

/**
 * Récupère le nombre de paiements en retard
 */
public function getNombrePaiementsEnRetard(): int
{
    return $this->model
        ->where('statut_paiement', '!=', 'valide')
        ->where('statut_paiement', '!=', 'annule')
        ->where('date_echeance', '<', now())
        ->count();
}

/**
 * Récupère le total des paiements en retard
 */
public function getTotalPaiementsEnRetard(): float
{
    return $this->model
        ->where('statut_paiement', '!=', 'valide')
        ->where('statut_paiement', '!=', 'annule')
        ->where('date_echeance', '<', now())
        ->sum('montant_du') ?? 0;
}
}