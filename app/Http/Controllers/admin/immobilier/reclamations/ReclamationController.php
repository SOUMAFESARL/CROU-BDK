<?php

namespace App\Http\Controllers\admin\immobilier\reclamations;

use App\Http\Controllers\Controller;
use App\Http\Services\ReclamationService;
use App\Models\Campagne;
use App\Models\Reclamation;
use App\Models\ReclamationPeriode;
use App\Services\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Page « Réclamations » de la gestion hébergement : accueil, liste par statut, traitement et configuration des périodes. */
class ReclamationController extends Controller
{
    private const ROUTE = 'admin.hebergement.reclamations.';

    public function __construct(private ReclamationService $reclamations, private AdminAccess $access) {}

    /** Accueil : cartes de suivi et de paramétrage accessibles (mêmes cartes que l'accueil du paramétrage hébergement). */
    public function index(Request $request)
    {
        $admin = $request->user('admin');
        $modules = [
            ['label' => 'Liste des réclamations', 'icon' => 'liste', 'route' => self::ROUTE.'liste', 'description' => 'Suivi'],
            ['label' => 'Périodes de réclamation', 'icon' => 'calendrier', 'route' => self::ROUTE.'configuration', 'description' => 'Paramétrage'],
        ];

        return Inertia::render('Admin/Hebergement/Reclamations/Index', [
            'modules' => collect($modules)
                ->filter(fn (array $module) => $this->access->allows($admin, $module['route']))
                ->map(fn (array $module) => ['label' => $module['label'], 'icon' => $module['icon'], 'description' => $module['description'], 'href' => route($module['route'])])
                ->values()->all(),
        ]);
    }

    /** Liste des réclamations, un onglet par statut, avec le numéro et le type de la demande concernée. */
    public function liste(Request $request)
    {
        $admin = $request->user('admin');
        $statut = (string) $request->query('statut', '');
        $statut = array_key_exists($statut, Reclamation::STATUTS) ? $statut : '';
        $campagne = (string) $request->query('campagne', '');
        $type = (string) $request->query('type', '');
        $type = array_key_exists($type, Reclamation::TYPES) ? $type : '';
        $recherche = trim((string) $request->query('q', ''));

        $base = Reclamation::query()
            ->when($campagne !== '', fn ($query) => $query->where('campagne_id', $campagne))
            ->when($type !== '', fn ($query) => $query->where('type_demande', $type))
            ->when($recherche !== '', function ($query) use ($recherche) {
                $terme = '%'.addcslashes($recherche, '%_\\').'%';
                $query->where(fn ($q) => $q->where('numero', 'like', $terme)
                    ->orWhere('nom_prenoms', 'like', $terme)
                    ->orWhere('num_bac_matricule', 'like', $terme)
                    ->orWhereHas('demande', fn ($demande) => $demande->where('numero_demande', 'like', $terme)));
            });
        // Compteurs des onglets : mêmes filtres que la liste, hors statut.
        $compteurs = (clone $base)->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        $liste = $base->when($statut !== '', fn ($query) => $query->where('statut', $statut))
            ->avecResultat()->with(['campagne', 'demande', 'etudiant:id_etudiant,name,matricule,date_naissance'])
            ->orderByDesc('created_at')->orderBy('numero')
            ->paginate(10)->withQueryString();
        $voirDossier = $this->access->allows($admin, 'admin.hebergement.campagnes_admission.show');

        return Inertia::render('Admin/Hebergement/Reclamations/Liste', [
            'reclamations' => $liste->through(fn (Reclamation $reclamation) => $this->reclamations->presenter($reclamation, $admin) + [
                'etudiant' => $reclamation->etudiant?->name,
                'matricule' => $reclamation->etudiant?->matricule,
                'date_naissance' => $reclamation->etudiant?->date_naissance?->toDateString(),
                'demande' => $reclamation->demande ? [
                    'numero' => $reclamation->demande->numero_demande,
                ] : null,
                'url_dossier' => $voirDossier && $reclamation->etudiant
                    ? route('admin.hebergement.campagnes_admission.show', ['id' => $reclamation->etudiant_id, 'origine' => 'reclamations']) : null,
            ]),
            'compteurs' => ['' => (int) $compteurs->sum()]
                + collect(Reclamation::STATUTS)->map(fn ($libelle, $cle) => (int) ($compteurs[$cle] ?? 0))->all(),
            'filters' => ['statut' => $statut, 'campagne' => $campagne, 'type' => $type, 'q' => $recherche],
            'campagnes' => $this->campagnes(Reclamation::query()->distinct()->pluck('campagne_id')->all()),
            'urls' => [
                'index' => route(self::ROUTE.'liste'),
                'accueil' => route(self::ROUTE.'index'),
            ],
        ]);
    }

    /** Changement de statut d'une réclamation ; favorable, elle admet la demande à loger. */
    public function traiter(Request $request, Reclamation $reclamation): RedirectResponse
    {
        $donnees = $request->validate([
            'statut' => ['required', Rule::in(Reclamation::STATUTS_TRAITEMENT)],
        ], [
            'statut.required' => 'Choisissez la décision.',
            'statut.in' => 'La décision choisie n’est pas valide.',
        ]);
        $admise = $this->reclamations->traiter($reclamation, $donnees['statut'], $request->user('admin'));

        return back()->with('success', "Réclamation {$reclamation->numero} : ".mb_strtolower(Reclamation::STATUTS[$donnees['statut']]).'.'
            .($admise ? ' La demande passe dans « Admis à loger ».' : ''));
    }

    /** Sous-page de configuration : périodes de réclamation et campagne à laquelle chacune s'applique. */
    public function configuration()
    {
        $periodes = ReclamationPeriode::query()->with(['campagne' => fn ($query) => $query->withTrashed()])
            ->withCount('reclamations')->orderByDesc('date_debut')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Hebergement/Reclamations/Configuration', [
            'periodes' => $periodes->through(fn (ReclamationPeriode $periode) => [
                'id' => (string) $periode->getKey(),
                'campagne_id' => (string) $periode->campagne_id,
                'campagne' => $periode->campagne ? $this->campagne($periode->campagne) : null,
                'date_debut' => $periode->date_debut->toDateString(),
                'date_fin' => $periode->date_fin->toDateString(),
                'etat' => $periode->etat(),
                'reclamations' => (int) $periode->reclamations_count,
                'urls' => [
                    'update' => route(self::ROUTE.'periodes.update', ['periode' => $periode->getKey()]),
                    'destroy' => $periode->reclamations_count ? null : route(self::ROUTE.'periodes.destroy', ['periode' => $periode->getKey()]),
                ],
            ]),
            'campagnes' => $this->campagnes(),
            'urls' => [
                'index' => route(self::ROUTE.'index'),
                'store' => route(self::ROUTE.'periodes.store'),
            ],
        ]);
    }

    public function storePeriode(Request $request): RedirectResponse
    {
        $this->reclamations->enregistrerPeriode($this->validerPeriode($request), null, $request->user('admin'));

        return back()->with('success', 'Période de réclamation enregistrée.');
    }

    public function updatePeriode(Request $request, ReclamationPeriode $periode): RedirectResponse
    {
        $this->reclamations->enregistrerPeriode($this->validerPeriode($request, $periode), $periode, $request->user('admin'));

        return back()->with('success', 'Période de réclamation modifiée.');
    }

    public function destroyPeriode(ReclamationPeriode $periode): RedirectResponse
    {
        $this->reclamations->supprimerPeriode($periode);

        return back()->with('success', 'Période de réclamation supprimée.');
    }

    /** @return array{campagne_id: string, date_debut: string, date_fin: string} */
    private function validerPeriode(Request $request, ?ReclamationPeriode $periode = null): array
    {
        $campagne = Rule::exists('campagne', 'id_campagne');

        return $request->validate([
            // Une campagne supprimée ne peut pas être choisie, mais une période existante garde la sienne.
            'campagne_id' => ['required', 'string', $request->input('campagne_id') === $periode?->campagne_id ? $campagne : $campagne->whereNull('deleted_at')],
            'date_debut' => ['required', 'date_format:Y-m-d'],
            'date_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_debut'],
        ], [
            'campagne_id.required' => 'Choisissez la campagne à laquelle s’applique la réclamation.',
            'campagne_id.exists' => 'La campagne choisie n’existe plus.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date_format' => 'La date de début n’est pas valide.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.date_format' => 'La date de fin n’est pas valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);
    }

    /** Campagnes proposées, la plus récente d'abord (toutes, ou celles de la liste d'identifiants, supprimées comprises). */
    private function campagnes(?array $ids = null): array
    {
        return Campagne::query()
            ->when($ids !== null, fn ($query) => $query->withTrashed()->whereIn('id_campagne', $ids))
            ->orderByDesc('created_at')->get()
            ->map(fn (Campagne $campagne) => $this->campagne($campagne))->values()->all();
    }

    private function campagne(Campagne $campagne): array
    {
        return [
            'id' => (string) $campagne->getKey(),
            'libelle' => collect([$campagne->libelle ?: 'Campagne', $campagne->annee_campagne])->filter()->implode(' · '),
            'active' => $campagne->statut === 'active',
            // La réclamation commence après cette date.
            'fin' => $campagne->periode_fin?->toDateString(),
        ];
    }
}
