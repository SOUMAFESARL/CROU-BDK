<?php

namespace App\Http\Controllers\admin\immobilier\residents_contrat;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campagne;
use App\Models\Etudiant;
use App\Models\Chambre;
use App\Http\Services\AllocationChambreService;
use App\Repositories\Interfaces\ChambreRepositoryInterface;
use App\Repositories\Interfaces\AssignationChambreRepositoryInterface;
use App\Http\Services\AttributionRules;
use App\Http\Services\LitService;
use App\Services\AdminAccess;
use Inertia\Inertia;

use Exception;

/** Attribution d'une chambre à un étudiant admis à loger, depuis « Résidents & contrats ». */
class AttributionChambreController extends Controller
{
    protected AllocationChambreService $service;
    protected ChambreRepositoryInterface $chambreRepository;
    protected AssignationChambreRepositoryInterface $assignationRepository;

    public function __construct(
        AllocationChambreService $service,
        ChambreRepositoryInterface $chambreRepository,
        AssignationChambreRepositoryInterface $assignationRepository
    ) {
        $this->service = $service;
        $this->chambreRepository = $chambreRepository;
        $this->assignationRepository = $assignationRepository;
    }

    /**
     * Enregistrer la vérification du statut PMR d'un étudiant.
     */
    public function confirmerPmr(Request $request, $campagneId)
    {
        $data = $request->validate([
            'etudiant_id' => ['required', 'string'],
            'pmr_confirme' => ['required', 'boolean'],
        ]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $campagneId) {
            $etudiant = Etudiant::whereKey($data['etudiant_id'])
                ->whereHas('demandes', fn ($q) => $q->where('campagne_id', $campagneId))
                ->lockForUpdate()->firstOrFail();
            $etudiant->update([
                'pmr_confirme' => $data['pmr_confirme'],
                'pmr_confirme_by' => auth('admin')->id(),
                'pmr_confirme_at' => now(),
            ]);
        });
        return back()->with('success', 'Confirmation PMR enregistree.');
    }

    /**
     * Formulaire d'attribution d'une chambre (page React) avec le pré-contrôle PMR / sexe / places.
     */
    public function allocateForm(Request $request, AdminAccess $access, $campagneId)
    {
        $campagne = Campagne::findOrFail($campagneId);
        $etudiants = $this->assignationRepository->getStudentsSansChambres($campagneId);
        $chambres = $this->chambreRepository->getDisponibles($campagneId)
            ->load(['assignations' => fn ($q) => $q->mobilisantPlace()->with('etudiant.genre')]);
        // Filtrage des chambres (sexe, PMR) et pré-contrôle faits à l'écran ; AttributionRules::erreurs() refait la vérification complète.
        $regles = app(AttributionRules::class);
        $lits = app(LitService::class);
        // Types visés par l'attribution automatique (Etudiant::getPrioriteChambres()).
        $libellesTypes = ['individuelle' => 'individuelle', 'double' => 'double', 'triple' => 'triple ou plus'];
        $url = $this->urlAutorisee($request, $access);
        $parametres = ['campagneId' => $campagne->id_campagne];

        return Inertia::render('Admin/Hebergement/Residents/Attribution', [
            'campagne' => $this->campagneProps($campagne),
            'etudiants' => $etudiants->map(fn (Etudiant $etudiant) => [
                'id' => (string) $etudiant->id_etudiant,
                'name' => $etudiant->name,
                'matricule' => $etudiant->matricule,
                'email' => $etudiant->email1,
                'niveau' => $this->niveau($etudiant),
                'pmr' => (bool) $etudiant->pmr_confirme,
                'sexe' => $regles->sexeEtudiant($etudiant),
                'priorites' => collect($etudiant->getPrioriteChambres())->map(fn ($type) => $libellesTypes[$type] ?? $type)->implode(', '),
            ])->values(),
            'chambres' => $chambres->map(fn (Chambre $chambre) => [
                'id' => (string) $chambre->id_chambre,
                'numero' => $chambre->numero_chambre,
                'type' => ucfirst($chambre->typeChambre?->name ?? 'Non défini'),
                'batiment' => $chambre->batiment?->name,
                'lieu' => collect([$chambre->batiment?->name, $chambre->cite?->name])->filter()->implode(' · '),
                'capacite' => (int) $chambre->capacite_max,
                'occupes' => (int) $chambre->getNombrePlacesOccupees(),
                'libres' => (int) $chambre->getNombrePlacesDisponibles(),
                'prix' => (float) $chambre->prix,
                'pmr' => (bool) $chambre->pmr,
                'sexe' => $regles->sexeRequis($chambre),
                'sexeOccupants' => $regles->sexeOccupants($chambre->assignations),
                'lits' => array_values(array_map('intval', (array) $lits->disponibles($chambre))),
            ])->values(),
            'etudiantInitial' => (string) $request->query('etudiant_id', ''),
            'urls' => [
                'retour' => $url('admin.hebergement.residents_contrat.admission', ['etat' => 'admis_a_loger', 'campagne' => $campagne->id_campagne]),
                'allocate' => $url('admin.hebergement.residents_contrat.attribution.store', $parametres),
                'pmr' => $url('admin.hebergement.residents_contrat.attribution.pmr', $parametres),
            ],
        ]);
    }

    /**
     * Allouer une chambre à un étudiant
     */
    public function allocate(Request $request, $campagneId)
    {
        $validated = $request->validate([
            'etudiant_id' => 'required|string|exists:etudiants,id_etudiant',
            'chambre_id' => 'required_if:allocation_type,manuelle|nullable|string|exists:chambre,id_chambre',
            'allocation_type' => 'required|in:automatique,manuelle',
            'numero_lit' => 'nullable|integer|min:1',
        ]);

        try {
            $campagne = Campagne::findOrFail($campagneId);
            $etudiant = Etudiant::findOrFail($validated['etudiant_id']);

            $chambre = null;
            if ($validated['allocation_type'] === 'manuelle' && !empty($validated['chambre_id'])) {
                $chambre = Chambre::findOrFail($validated['chambre_id']);
            }

            $assignation = $this->service->allocateChambre($etudiant, $campagne, $chambre, $chambre ? ($validated['numero_lit'] ?? null) : null);

            if ($assignation) {
                return redirect()->route('admin.hebergement.residents_contrat.admission', ['etat' => 'residents'])->with('success',
                    "Chambre {$assignation->chambre->numero_chambre} assignée avec succès à {$etudiant->name}");
            }

            return back()->withInput()->with('error', 'Impossible d\'assigner une chambre. Vérifiez les critères.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /** URL d'une route si l'admin connecté y a accès (remplace `@adminroute`), sinon null. */
    private function urlAutorisee(Request $request, AdminAccess $access): \Closure
    {
        $admin = $request->user('admin');

        return fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
    }

    private function campagneProps(Campagne $campagne): array
    {
        return [
            'id' => (string) $campagne->id_campagne,
            'annee' => $campagne->annee_campagne,
        ];
    }

    /** Libellé du niveau : le code stocké sur la demande est souvent l'identifiant du niveau. */
    private function niveau(?Etudiant $etudiant): ?string
    {
        if (!$etudiant) {
            return null;
        }
        $demande = $etudiant->demandeActuelle();

        return $demande?->niveauEtudiantActuel?->name ?? $demande?->niveau_etude_actuel_code;
    }
}