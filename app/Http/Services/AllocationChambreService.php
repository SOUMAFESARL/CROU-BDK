<?php

namespace App\Http\Services;

use App\Models\Etudiant;
use App\Models\Chambre;
use App\Models\AssignationChambre;
use App\Http\Services\DemandeService;
use App\Models\Campagne;
use App\Models\TypeChambre;

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
                throw \Illuminate\Validation\ValidationException::withMessages(['etat_dossier'=>'Seul un dossier confirmé par l’étudiant peut recevoir une chambre.']);
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
     * Mettre à jour le nombre d'occupants d'une chambre
     */


    protected function updateChambreOccupants(Chambre $chambre): void
    {
        $chambre->actualiserOccupation();
    }

    /**
     * Vérifier l'éligibilité d'un étudiant pour une chambre
     */
    public function verifierEligibilite(Etudiant $etudiant, Chambre $chambre): array
    {
        $errors = app(AttributionRules::class)->erreurs($etudiant, $chambre);
        return [
            'eligible' => !$errors,
            'niveau' => $etudiant->getAnneeEtude(),
            'niveau_numeric' => $etudiant->getNiveauNumeric(),
            'types_acceptes' => $etudiant->getTypesChambresEligibles(),
            'type_chambre' => $chambre->type_chambre_id,
            'message' => $errors ? implode(' ', $errors) : 'Eligible',
        ];
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

    /**
     * Réassigner un étudiant à une autre chambre
     */
    public function reaffecterChambre(Etudiant $etudiant, Chambre $nouvelleChambre, Campagne $campagne, $motif = "Reaffectation", ?int $numeroLit = null): ?AssignationChambre
    {
        return DB::transaction(function () use ($etudiant, $nouvelleChambre, $campagne, $motif, $numeroLit) {
            Etudiant::whereKey($etudiant->getKey())->lockForUpdate()->firstOrFail();
            $actuelle = AssignationChambre::where('etudiant_id', $etudiant->getKey())
                ->where('campagne_id', $campagne->getKey())->mobilisantPlace()->first();
            if ($actuelle) {
                $this->annulerAssignation($actuelle->getKey(), $motif, false);
                $dossier = app(DossierWorkflow::class)->current($etudiant->getKey(), $campagne->getKey());
                app(DossierWorkflow::class)->admin($dossier->getKey(), 'confirme', 'Nouvelle proposition nécessaire : '.$motif);
            }
            return $this->allocateChambre($etudiant, $campagne, $nouvelleChambre, $numeroLit);
        }, 3);
    }

    /**
     * Allouer automatiquement pour toute une campagne
     */
    public function allocateAllStudents($campagneId): array
    {
        // Sérialiser les lancements automatiques d'une même campagne.
        return DB::transaction(fn () => $this->allocateRankedStudents($campagneId), 3);
    }

    private function allocateRankedStudents($campagneId): array
    {
        $campagne = Campagne::whereKey($campagneId)->lockForUpdate()->first();

        if (!$campagne) {
            throw new Exception("Campagne introuvable");
        }

        // Classer les candidats avant de rechercher les chambres compatibles.
        $etudiants = app(CandidateRankingService::class)->rank(
            $this->assignationRepository->getStudentsSansChambres($campagneId), $campagne);

        $allocated = [];
        $notAllocated = [];

        foreach ($etudiants as $candidate) {
            $etudiant = $candidate['student'];
            $ranking = ['priority'=>$candidate['priority'], 'scoring_version'=>$candidate['scoring_version'], 'subscores'=>$candidate['subscores'], 'score'=>$candidate['score'], 'rang'=>$candidate['rang'], 'demande_id'=>$candidate['demande_id']];
            if ($candidate['score'] < $candidate['minimum_score']) {
                $notAllocated[] = $ranking + ['id_etudiant'=>$etudiant->getKey(), 'name'=>$etudiant->name,
                    'reason'=>'Score inférieur au seuil du barème ('.$candidate['minimum_score'].'/100).'];
                continue;
            }
            try {
                $assignation = $this->allocateChambre($etudiant, $campagne);

                if ($assignation) {
                    $allocated[] = $ranking + [
                        'id_etudiant' => $etudiant->id_etudiant,
                        'name' => $etudiant->name,
                        'niveau' => $etudiant->getAnneeEtude(),
                        'chambre' => $assignation->chambre->numero_chambre,
                        'type' => $assignation->chambre->type_chambre_code,
                        'status' => 'success'
                    ];
                } else {
                    $notAllocated[] = $ranking + [
                        'id_etudiant' => $etudiant->id_etudiant,
                        'name' => $etudiant->name,
                        'reason' => 'Aucune chambre disponible correspondant aux critères'
                    ];
                }
            } catch (Exception $e) {
                $notAllocated[] = $ranking + [
                    'id_etudiant' => $etudiant->id_etudiant,
                    'name' => $etudiant->name,
                    'reason' => \App\Support\UserFacingErrors::caught($e)
                ];
            }
        }

        return [
            'ranking_rule' => $etudiants->first()['scoring_version'] ?? null
                ? 'Barème de campagne, priorité, score décroissant, ancienneté du dossier.' : CandidateRankingService::RULE,
            'allocated' => $allocated,
            'not_allocated' => $notAllocated,
            'total' => count($etudiants),
            'success_count' => count($allocated),
            'failed_count' => count($notAllocated),
            'success_rate' => count($etudiants) > 0 ? round((count($allocated) / count($etudiants)) * 100, 2) : 0,
        ];
    }

    /**
     * Obtenir les statistiques d'allocation
     */
    public function getStatistiques($campagneId): array
    {

        $campagne = Campagne::find($campagneId);

        if (!$campagne) {
            throw new Exception("Campagne introuvable");
        }

        $assignations = $this->assignationRepository->getByCampagne($campagneId);
        $typeChambre = TypeChambre::all();
        //dd($assignations,$typeChambre);
        $demandesEnAtente = $campagne->demandes()->whereIn('statut_demande', ['En_attente'])->count();
        $demandesAcceptees = $campagne->demandes()->whereIn('statut_demande', ['Acceptée'])->count();

        $assignationsConfirmees = $assignations->where('statut', 'confirmee')->count();
        $assignationsEnAttente = $assignations->where('statut', 'en_attente')->count();
       // dd($campagneId);
        // Statistiques par type de chambre

        $parType = [];


        foreach ($typeChambre as $type) {
            $parType[$type->name] = [
                'nom'         => $type->name,
                'total'       => Chambre::parType($type->id_type_chambre)->count(),
                'occupees'    => $assignations->filter(function ($a) use ($type) {
                    return $a->chambre
                        && (string) $a->chambre->type_chambre_id === (string) $type->id_type_chambre;
                })->count(),
                'disponibles' => Chambre::parType($type->id_type_chambre)->disponibles($campagneId)->count(),
            ];
        }

     //   dd($campagne->demandes()->count()/ $demandesAcceptees);
//dd($parType);
        return [
            'campagne_id' => $campagne->id_campagne,
            'campagne_name' => $campagne->description ?? $campagne->annee_campagne,
            'demandes_acceptees' => $demandesAcceptees,
            'assignations_confirmees' => $assignationsConfirmees,
            'assignations_en_attente' => $assignationsEnAttente,
            'total_assignations' => $assignations->count(),
            'sans_chambre' => $demandesEnAtente,
            'taux_allocation' => $demandesAcceptees > 0 ?
                round(( 100* $demandesAcceptees) / $campagne->demandes()->count(), 2) : 0,
            'par_type' => $parType,
        ];
    }

    /**
     * Obtenir les chambres disponibles pour un étudiant
     */
    public function getChambresDisponibles(Etudiant $etudiant, Campagne $campagne): array
    {
        return Chambre::disponibles($campagne)->with(['typeChambre', 'batiment', 'cite'])->get()
            ->filter(fn ($room) => !app(AttributionRules::class)->erreurs($etudiant, $room, $campagne))
            ->groupBy('type_chambre_id')->map(fn ($rooms) => $rooms->map(fn ($room) => [
                'id' => $room->getKey(), 'numero' => $room->numero_chambre,
                'code' => $room->code_chambre, 'type' => $room->type_chambre_id,
                'capacite' => $room->capacite_max, 'occupes' => $room->getNombrePlacesOccupees(),
                'disponibles' => $room->getNombrePlacesDisponibles(), 'prix' => $room->prix,
                'batiment' => $room->batiment?->name,
            ])->values())->all();
    }

    /**
     * Valider une assignation avant confirmation
     */
    public function validerAssignation(AssignationChambre $assignation): array
    {
        $errors = [];
        if (!$assignation->etudiant || !$assignation->chambre) {
            $errors[] = "Etudiant ou chambre introuvable.";
        } else {
            $errors = app(AttributionRules::class)->erreurs($assignation->etudiant, $assignation->chambre,
                $assignation->campagne, $assignation->getKey());
        }
        if ($assignation->statut !== 'en_attente' || ($assignation->date_fin && $assignation->date_fin->lte(now()))) {
            $errors[] = 'Cette reservation ne peut pas etre confirmee.';
        }
        return ['valid' => !$errors, 'errors' => $errors, 'warnings' => []];
    }
}