<?php

namespace App\Http\Services;

use App\Models\Etudiant;
use App\Models\Chambre;
use App\Models\AssignationChambre;
use App\Models\Campagne;

use App\Repositories\Interfaces\ChambreRepositoryInterface;
use App\Repositories\Interfaces\AssignationChambreRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class AllocationChambreService
{
    protected  $chambreRepository;
    protected  $assignationRepository;

    public function __construct(
        ChambreRepositoryInterface $chambreRepository,
        AssignationChambreRepositoryInterface $assignationRepository

    ) {
        $this->chambreRepository = $chambreRepository;
        $this->assignationRepository = $assignationRepository;

    }

    /**
     * Allouer une chambre à un étudiant
     */
    public function allocateChambre(Etudiant $etudiant, Campagne $campagne, ?Chambre $chambreSpecifique = null, ?int $numeroLit = null): ?AssignationChambre
    {
        return DB::transaction(function () use ($etudiant, $campagne, $chambreSpecifique, $numeroLit) {
            $etudiant = Etudiant::whereKey($etudiant->getKey())->lockForUpdate()->firstOrFail();
            app(DisciplinaryImpactService::class)->verifier($etudiant);
            if (AssignationChambre::where('etudiant_id', $etudiant->getKey())->mobilisantPlace()->exists()) {
                throw new Exception("Cet etudiant a deja une chambre occupee ou reservee.");
            }
            $dossier = app(DossierWorkflow::class)->current($etudiant->getKey(), $campagne->getKey());
            if ($dossier->etat_dossier !== 'confirme') {
                throw \Illuminate\Validation\ValidationException::withMessages(['etat_dossier'=>'Seul un étudiant admis à loger (liste définitive publiée) peut recevoir une chambre.']);
            }
            app(DossierWorkflow::class)->eligible($dossier);
            return $chambreSpecifique
                ? $this->assignToSpecificChambre($etudiant, $chambreSpecifique, $campagne, $numeroLit)
                : $this->findAndAssignChambre($etudiant, $campagne);
        }, 3);
    }

    /**
     * Assigner à une chambre spécifique
     */
    protected function assignToSpecificChambre(Etudiant $etudiant, Chambre $chambre, Campagne $campagne, ?int $numeroLit = null): ?AssignationChambre
    {
        $rules = app(AttributionRules::class);
        $chambre = $rules->verrouillerChambre($chambre->getKey());
        $rules->verifier($etudiant, $chambre, $campagne);
        return $this->creerReservation($etudiant, $chambre, $campagne, 'manuelle', $numeroLit);
    }

    /** L'étudiant a déjà confirmé son intérêt : l'attribution est définitive, sans nouvelle acceptation. */
    private function creerReservation(Etudiant $etudiant, Chambre $chambre, Campagne $campagne, string $type, ?int $numeroLit = null): AssignationChambre
    {
        // Caution (nouvelles demandes) et avance payées avant l'attribution, selon les conditions de la campagne.
        app(PaiementsAvantAttribution::class)->verifier($etudiant, $chambre, $campagne);
        $assignation = $this->assignationRepository->create([
            'etudiant_id' => $etudiant->getKey(),
            'chambre_id' => $chambre->getKey(),
            'lit_id' => app(LitService::class)->choisir($chambre, $numeroLit)->getKey(),
            'campagne_id' => $campagne->getKey(),
            'date_assignation' => now(),
            'date_fin' => null,
            'statut' => 'confirmee',
            'type_assignation' => $type,
        ]);
        app(DossierWorkflow::class)->admin(
            app(DossierWorkflow::class)->current($etudiant->getKey(), $campagne->getKey())->getKey(),
            'attribue_definitif', 'Attribution de chambre.', ['assignation_id'=>$assignation->getKey()]);
        $chambre->actualiserOccupation();
        return $assignation;
    }

    /**
     * Chercher et assigner automatiquement une chambre
     */
    protected function findAndAssignChambre(Etudiant $etudiant, Campagne $campagne): ?AssignationChambre
    {
        $rules = app(AttributionRules::class);
        // Les types sont des UUID : ne pas les comparer aux libelles double/triple.
        $priorites = $etudiant->getPrioriteChambres();
        $candidates = Chambre::disponibles($campagne)->get()->filter(function ($room) use ($priorites, $etudiant) {
            $type = (int) $room->capacite_max === 1 ? 'individuelle' : ((int) $room->capacite_max === 2 ? 'double' : 'triple');
            return ($room->pmr && $etudiant->pmr_confirme) || in_array($type, $priorites, true);
        })->sortBy('capacite_max')->pluck('id_chambre');
        foreach ($candidates as $id) {
            $chambre = $rules->verrouillerChambre($id);
            if ($rules->erreurs($etudiant, $chambre, $campagne)) {
                continue;
            }
            return $this->creerReservation($etudiant, $chambre, $campagne, 'automatique');
        }
        return null;
    }

    /**
     * Confirmer une assignation (passer de en_attente à confirmée)
     */
    public function confirmerAssignation($assignationId): bool
    {
        return DB::transaction(function () use ($assignationId) {
            $initial = AssignationChambre::findOrFail($assignationId);
            $etudiant = Etudiant::whereKey($initial->etudiant_id)->lockForUpdate()->firstOrFail();
            app(DisciplinaryImpactService::class)->verifier($etudiant);
            $rules = app(AttributionRules::class);
            $chambre = $rules->verrouillerChambre($initial->chambre_id);
            $assignation = AssignationChambre::whereKey($assignationId)->lockForUpdate()->firstOrFail();
            if ($assignation->statut === 'confirmee') {
                return true;
            }
            if ($assignation->statut !== 'en_attente' || ($assignation->date_fin && $assignation->date_fin->lte(now()))) {
                throw new Exception('Cette reservation est annulee ou expiree.');
            }
            $rules->verifier($etudiant, $chambre, Campagne::findOrFail($assignation->campagne_id), $assignation->getKey());
            if (AssignationChambre::where('etudiant_id', $etudiant->getKey())->mobilisantPlace()
                ->where('id_assignation', '!=', $assignation->getKey())->exists()) {
                throw new Exception('Cet etudiant a deja une autre attribution active.');
            }
            app(LitService::class)->synchroniser($chambre);
            $assignation->refresh();
            $lit = $assignation->lit;
            if (!$lit || $lit->chambre_id !== $chambre->getKey()) {
                throw new Exception('Le lit attribue ne correspond pas a la chambre.');
            }
            app(LitService::class)->choisir($chambre, $lit->numero, $assignation->getKey());
            $dossier = \App\Models\DemandeEtudiant::where('assignation_id', $assignation->getKey())->lockForUpdate()->firstOrFail();
            if ($dossier->etat_dossier !== 'accepte') {
                throw \Illuminate\Validation\ValidationException::withMessages(['etat_dossier'=>'L’étudiant doit accepter cette proposition avant la validation définitive.']);
            }
            $assignation->confirmer();
            $etudiant->update(['statut_inscription' => 'residents']);
            app(DossierWorkflow::class)->admin($dossier->getKey(), 'attribue_definitif', 'Validation définitive par le service Logement.');
            $chambre->actualiserOccupation();
            return true;
        }, 3);
    }

    /**
     * Annuler une assignation
     */
    public function annulerAssignation($assignationId, $motif = null, bool $synchroniserDossier = true): bool
    {
        return DB::transaction(function () use ($assignationId, $motif, $synchroniserDossier) {
            $initial = AssignationChambre::findOrFail($assignationId);
            Etudiant::whereKey($initial->etudiant_id)->lockForUpdate()->firstOrFail();
            $chambre = app(AttributionRules::class)->verrouillerChambre($initial->chambre_id);
            $assignation = AssignationChambre::whereKey($assignationId)->lockForUpdate()->firstOrFail();
            if ($assignation->statut === 'confirmee') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'assignation_id'=>'Un séjour confirmé doit suivre la procédure de sortie.',
                ]);
            }
            $assignation->annuler($motif);
            if ($synchroniserDossier) {
                $dossier = \App\Models\DemandeEtudiant::where('assignation_id',$assignation->getKey())->first();
                if ($dossier && in_array($dossier->etat_dossier,['pre_attribue','accepte'],true)) {
                    app(DossierWorkflow::class)->transition($dossier->getKey(),'annule','systeme',auth('admin')->id(),$motif ?: 'Proposition annulée.');
                }
            }
            $chambre->actualiserOccupation();
            return true;
        }, 3);
    }
}
