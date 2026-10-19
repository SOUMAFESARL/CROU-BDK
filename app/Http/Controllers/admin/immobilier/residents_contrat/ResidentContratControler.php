<?php

namespace App\Http\Controllers\admin\immobilier\residents_contrat;

use App\Http\Controllers\Controller;
use App\Http\Services\CampagneValidationService;
use App\Http\Services\EtudiantService;
use App\Models\AssignationChambre;
use App\Models\Etudiant;
use App\Models\Genre;
use App\Repositories\EtudiantRepository;
use App\Services\AdminAccess;
use App\Services\AdminViewAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ResidentContratControler extends Controller
{
    /** Filtres de la page : dossiers en vérification et étudiants déjà logés. */
    private const FILTRES = ['en_verification', 'residents'];

    public function __construct(protected EtudiantService $etudiantService) {}

    public function liste(Request $request, ?string $etat = null)
    {
        try {
            if (! $request->wantsJson()) {
                return $this->pageResidents($request, $etat ?? $request->get('etat'));
            }

            $result = $this->etudiantService->getListeEtudiantsWithStats($etat ?? $request->get('etat'));

            return response()->json(app(\App\Services\AdminViewData::class)->filter($result, $request->user('admin')));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la récupération de la liste',
                    'error' => \App\Support\UserFacingErrors::caught($e),
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la récupération de la liste');
        }
    }

    /**
     * Résidents et contrats (page React `Admin/Hebergement/Residents/Index`).
     * Colonnes personnelles et historique envoyés seulement avec les droits correspondants ;
     * chaque URL d'action n'est envoyée que si `AdminAccess` l'autorise.
     */
    private function pageResidents(Request $request, ?string $filtre)
    {
        $admin = $request->user('admin');
        $access = app(AdminAccess::class);
        $vues = app(AdminViewAccess::class);
        $personnel = $vues->allows($admin, 'admissions.personal');
        $historique = $vues->allows($admin, 'admissions.history');
        $etat = in_array($filtre, self::FILTRES, true) ? $filtre : 'residents';
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $repository = app(EtudiantRepository::class);
        $campagne = app(CampagneValidationService::class)->getCampagneActive();

        $recherche = trim((string) $request->query('q', ''));
        $page = $repository->getAdmissionsPage($etat, 10, $recherche);
        $page->getCollection()->load(['assignationsChambre' => fn ($q) => $q->where('statut', 'confirmee')
            ->with(['chambre.batiment', 'chambre.cite'])]);

        $modifiable = $access->allows($admin, 'admin.hebergement.residents_contrat.update_etudiant');

        $stats = $repository->getStats();
        $stats['sans_chambre'] = Etudiant::whereRaw('LOWER(statut_inscription) = ?', ['residents'])
            ->whereDoesntHave('assignationsChambre', fn ($q) => $q->where('statut', 'confirmee'))
            ->count();

        return Inertia::render('Admin/Hebergement/Residents/Index', [
            'residents' => $page->through(function (Etudiant $etudiant) use ($personnel, $historique, $url, $etat, $modifiable) {
                /** @var AssignationChambre|null $assignation */
                $assignation = $etudiant->assignationsChambre->sortByDesc('created_at')->first();
                $chambre = $assignation?->chambre;

                return [
                    'id' => (string) $etudiant->id_etudiant,
                    'name' => $etudiant->name,
                    'numero_demande' => $etudiant->derniereDemande?->numero_demande,
                    'date' => $etudiant->created_at?->format('Y-m-d H:i'),
                    'personnel' => $personnel ? [
                        'telephone' => $etudiant->telephone1,
                        'adresse' => $etudiant->adresse,
                        'region' => $etudiant->region?->name,
                        'ville' => $etudiant->ville?->name,
                        'date_naissance' => $etudiant->date_naissance?->toDateString(),
                    ] : null,
                    'demandes' => $historique ? (int) $etudiant->demandes_count : null,
                    'chambre' => $assignation ? [
                        'numero' => $chambre?->numero_chambre,
                        'lieu' => collect([$chambre?->batiment?->name, $chambre?->cite?->name])->filter()->implode(' · ') ?: null,
                    ] : null,
                    'urls' => [
                        'dossier' => $url('admin.hebergement.campagnes_admission.show', ['id' => $etudiant->id_etudiant]),
                        'traiter' => $etat === 'en_verification'
                            ? $url('admin.hebergement.campagnes_admission.show', ['id' => $etudiant->id_etudiant])
                            : null,
                        // Dossier confirmé par l'étudiant : attribution de la chambre (formulaire présélectionné sur l'étudiant).
                        'attribuer' => $etat === 'en_verification' && $etudiant->derniereDemande?->etat_dossier === 'confirme'
                            ? $url('admin.allocation.allocate-form', [
                                'campagneId' => $etudiant->derniereDemande->campagne_id,
                                'etudiant_id' => $etudiant->id_etudiant,
                            ])
                            : null,
                        'contrat' => $url('admin.etudiant.contrat', ['etudiant' => $etudiant->id_etudiant]),
                        'modifier' => $modifiable
                            ? route('admin.hebergement.residents_contrat.update_etudiant', ['etudiant' => $etudiant->id_etudiant])
                            : null,
                    ],
                    // Valeurs du formulaire de modification : envoyées seulement avec le droit de modifier.
                    'edition' => $modifiable ? $this->valeursEdition($etudiant) : null,
                ];
            }),
            'stats' => [
                'residents' => $stats['residents'],
                'en_verification' => $stats['en_verification'],
                'sans_chambre' => $stats['sans_chambre'],
            ],
            'etat' => $etat,
            'filters' => ['q' => $recherche],
            'colonnes' => ['personnel' => $personnel, 'historique' => $historique],
            'campagneActive' => $campagne ? ['annee' => $campagne->annee_campagne] : null,
            'genres' => $modifiable
                ? Genre::query()->orderBy('name')->get()->map(fn ($genre) => ['id' => (string) $genre->id, 'name' => $genre->name])->values()
                : [],
            'urls' => [
                'admissionDirecte' => $url('admin.etudiant.admission_directe'),
                'attributions' => $campagne ? $url('admin.allocation.show', ['campagneId' => $campagne->id_campagne]) : null,
                'filtres' => $access->allows($admin, 'admin.hebergement.residents_contrat.admission')
                    ? collect(self::FILTRES)->mapWithKeys(fn ($f) => [$f => route('admin.hebergement.residents_contrat.admission', ['etat' => $f])])
                    : null,
            ],
        ]);
    }

    /** Champs modifiables de la fiche étudiant (identité, coordonnées, tuteur). */
    private const CHAMPS_EDITION = ['name', 'genre_id', 'matricule', 'numero_etudiant', 'date_naissance', 'lieu_naissance', 'nationalite',
        'telephone1', 'telephone2', 'email1', 'email2', 'adresse', 'name_tuteur', 'telephone_tuteur', 'adresse_tuteur'];

    private function valeursEdition(Etudiant $etudiant): array
    {
        return collect(self::CHAMPS_EDITION)->mapWithKeys(fn ($champ) => [$champ => match ($champ) {
            'date_naissance' => $etudiant->date_naissance?->toDateString() ?? '',
            default => (string) ($etudiant->{$champ} ?? ''),
        }])->all();
    }

    /**
     * Modification des informations personnelles d'un étudiant depuis la liste des résidents.
     * Le statut, le dossier et l'affectation ne sont pas modifiables ici (cycle géré par `DossierWorkflow`).
     */
    public function updateEtudiant(Request $request, string $etudiant): RedirectResponse
    {
        $etudiant = Etudiant::findOrFail($etudiant);
        $unique = fn (string $colonne) => Rule::unique('etudiants', $colonne)->ignore($etudiant->getKey(), 'id_etudiant');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'genre_id' => ['required', 'integer', Rule::exists('genre', 'id')],
            'matricule' => ['required', 'string', 'max:100', $unique('matricule')],
            'numero_etudiant' => ['nullable', 'string', 'max:100', $unique('numero_etudiant')],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'nationalite' => ['nullable', 'string', 'max:100'],
            'telephone1' => ['required', 'string', 'max:30', $unique('telephone1')],
            'telephone2' => ['nullable', 'string', 'max:30'],
            'email1' => ['nullable', 'email', 'max:255', $unique('email1')],
            'email2' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'name_tuteur' => ['nullable', 'string', 'max:255'],
            'telephone_tuteur' => ['nullable', 'string', 'max:30'],
            'adresse_tuteur' => ['nullable', 'string', 'max:255'],
        ], [
            'matricule.unique' => 'Un autre étudiant possède déjà ce matricule.',
            'numero_etudiant.unique' => 'Un autre étudiant possède déjà ce numéro étudiant.',
            'telephone1.unique' => 'Un autre étudiant possède déjà ce numéro de téléphone.',
            'email1.unique' => 'Un autre étudiant possède déjà cette adresse e-mail.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd’hui.',
        ]);

        $etudiant->update($data);

        return back()->with('success', 'Informations de l’étudiant mises à jour.');
    }
}
