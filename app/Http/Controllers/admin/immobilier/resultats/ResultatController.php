<?php

namespace App\Http\Controllers\admin\immobilier\resultats;

use App\Http\Controllers\Controller;
use App\Http\Services\DossierWorkflow;
use App\Http\Services\Eligibilite\ResultatsAdmission;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;
use App\Models\User;
use App\Services\AdminAccess;
use App\Services\AdmissionPermissions;
use App\Support\SimpleXlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Page « Résultats d'admission » de la gestion hébergement : résultats publiés, par statut, et visibilité de la page publique. */
class ResultatController extends Controller
{
    private const ROUTE = 'admin.hebergement.resultats.';

    /** Onglet de la page => résultat figé à la publication. */
    private const STATUTS = [
        'validees' => ResultatsAdmission::ADMIS,
        'attente' => ResultatsAdmission::RECEVABLE_NON_ADMIS,
        'non_recevables' => ResultatsAdmission::NON_RECEVABLE,
    ];

    /** Onglet => libellé (titre et feuille des exports). */
    private const LIBELLES = ['validees' => 'Demandes validées', 'attente' => 'File d’attente', 'non_recevables' => 'Non recevables ou rejetées'];

    public function __construct(private ResultatsAdmission $resultats, private AdminAccess $access) {}

    /** Demandes dont le résultat est publié, un onglet par statut, pour une campagne (par défaut la dernière publiée). */
    public function index(Request $request): Response
    {
        $admin = $request->user('admin');
        $statut = $request->query('statut');
        $statut = is_string($statut) && array_key_exists($statut, self::STATUTS) ? $statut : 'validees';
        $campagnes = $this->campagnes();
        [$campagne, $type, $recherche] = $this->filtres($request, $campagnes);

        [$lignes, $compteurs] = $this->resultats->disponible()
            ? $this->lignes($campagne, $statut, $type, $recherche, $admin)
            : [new LengthAwarePaginator([], 0, 10), collect()];

        return Inertia::render('Admin/Hebergement/Resultats/Index', [
            'resultats' => $lignes,
            'compteurs' => collect(self::STATUTS)->map(fn (string $resultat) => (int) ($compteurs[$resultat] ?? 0))->all(),
            'filters' => ['statut' => $statut, 'campagne' => $campagne, 'type' => $type, 'q' => $recherche],
            'campagnes' => $campagnes,
            'visibilite' => [
                'visible' => $this->resultats->campagnesConsultables()->isNotEmpty(),
                // Changement de visibilité ; null sans le droit (bouton absent).
                'url' => $this->access->allows($admin, self::ROUTE.'visibilite') ? route(self::ROUTE.'visibilite') : null,
                'page' => route('etudiant.resultats'),
            ],
            // Téléchargement de l'onglet affiché ; null sans le droit ou sans demande dans l'onglet.
            'exports' => ($compteurs[self::STATUTS[$statut]] ?? 0) > 0 && $this->access->allows($admin, self::ROUTE.'export')
                ? collect(['excel', 'pdf'])->mapWithKeys(fn (string $format) => [$format => route(self::ROUTE.'export',
                    array_filter(['statut' => $statut, 'format' => $format, 'campagne' => $campagne, 'type' => $type, 'q' => $recherche]))])->all()
                : null,
            'urls' => ['index' => route(self::ROUTE.'index')],
        ]);
    }

    /**
     * Téléchargement de toutes les demandes d'un onglet (filtres de l'écran compris : campagne, type, recherche), par ordre
     * alphabétique : classeur Excel, ou PDF à imprimer ouvert dans un nouvel onglet.
     */
    public function export(Request $request, string $statut, string $format)
    {
        abort_unless($this->access->allows($request->user('admin'), self::ROUTE.'index'), 403);
        $campagnes = $this->campagnes();
        [$campagne, $type, $recherche] = $this->filtres($request, $campagnes);
        $libelle = collect($campagnes)->firstWhere('id', $campagne)['libelle'] ?? null;
        if ($libelle === null) {
            return redirect()->route(self::ROUTE.'index')->with('error', 'Aucun résultat publié à exporter.');
        }
        $lignes = $this->onglet($this->requete($campagne, $type, $recherche), $statut)->get()->values()
            ->map(fn (object $ligne, int $index) => [
                $index + 1,
                (string) $ligne->name,
                (string) $ligne->matricule,
                (string) $ligne->num_table_bac,
                (string) $ligne->numero_demande,
                $ligne->volet === 'readmission' ? 'Réadmission' : 'Admission',
                DossierWorkflow::LABELS[$ligne->etat_dossier] ?? '',
            ])->all();
        $entetes = ['N°', 'Nom et prénoms', 'Matricule', 'N° de table bac', 'N° de demande', 'Type de demande', 'État du dossier'];
        $fichier = 'resultats-'.str_replace('_', '-', $statut).($type !== '' ? '-'.$type : '').'-'.Str::slug($libelle);
        if ($format === 'excel') {
            return SimpleXlsx::download($fichier.'.xlsx', self::LIBELLES[$statut], $entetes, $lignes);
        }

        return Pdf::loadView('pdf.liste-provisoire', [
            'titre' => 'Résultats d’admission — '.self::LIBELLES[$statut].match ($type) {
                'admission' => ' (admissions)',
                'readmission' => ' (réadmissions)',
                default => '',
            },
            'campagne' => $libelle,
            'entetes' => $entetes,
            'lignes' => $lignes,
            'resume' => 'demande(s)',
            'vide' => 'Aucune demande dans cette liste.',
            'pied' => 'Résultats figés à la publication des listes définitives.',
        ])->setPaper('a4', 'landscape')->stream($fichier.'.pdf');
    }

    /** Rend visibles les résultats des campagnes cochées (à la place des précédentes), ou masque la page publique. */
    public function visibilite(Request $request): RedirectResponse
    {
        abort_unless($this->access->allows($request->user('admin'), self::ROUTE.'index'), 403);
        $proposees = array_column(array_filter($this->campagnes(), fn (array $campagne) => ! $campagne['supprimee']), 'id');
        $donnees = $request->validate([
            'visible' => ['required', 'boolean'],
            'campagnes' => [Rule::excludeIf(! $request->boolean('visible')), 'required', 'array'],
            'campagnes.*' => [Rule::excludeIf(! $request->boolean('visible')), 'string', 'distinct', Rule::in($proposees)],
        ], [
            'visible.required' => 'Choisissez si la page des résultats est visible.',
            'visible.boolean' => 'Le choix de visibilité n’est pas valide.',
            'campagnes.required' => 'Cochez au moins une campagne.',
            'campagnes.array' => 'Le choix des campagnes n’est pas valide.',
            'campagnes.*' => 'Une campagne choisie n’a pas de résultat publié ou n’existe plus.',
        ]);
        $campagnes = array_values($donnees['campagnes'] ?? []);
        $this->resultats->definirCampagnesVisibles($campagnes, $request->user('admin')?->name);

        return back()->with('success', match (count($campagnes)) {
            0 => 'Page des résultats masquée : les étudiants ne peuvent plus consulter leur résultat.',
            1 => 'Les résultats de la campagne cochée sont visibles par les étudiants.',
            default => 'Les résultats des '.count($campagnes).' campagnes cochées sont visibles par les étudiants.',
        });
    }

    /** Fait passer une demande de la file d'attente dans « Admis à loger » (paiement, puis attribution d'une chambre). */
    public function admettre(Request $request, string $resultat): RedirectResponse
    {
        abort_unless($this->access->allows($request->user('admin'), self::ROUTE.'index'), 403);
        $etudiant = $this->resultats->admettre($resultat);

        return back()->with('success', trim((string) $etudiant->name).' passe dans « Admis à loger » : le paiement puis l’attribution d’une chambre sont possibles.');
    }

    /**
     * Filtres de l'écran et des exports ; campagne inconnue ou absente : la dernière publiée.
     *
     * @return array{0: string, 1: string, 2: string} campagne, type de demande, recherche
     */
    private function filtres(Request $request, array $campagnes): array
    {
        $filtre = fn (string $cle): string => is_string($valeur = $request->query($cle)) ? trim($valeur) : '';
        $ids = array_column($campagnes, 'id');

        return [
            in_array($filtre('campagne'), $ids, true) ? $filtre('campagne') : ($ids[0] ?? ''),
            in_array($filtre('type'), ['admission', 'readmission'], true) ? $filtre('type') : '',
            $filtre('q'),
        ];
    }

    /** Résultats publiés d'une campagne, filtrés, tous statuts confondus. */
    private function requete(string $campagne, string $type, string $recherche): Builder
    {
        return DB::table(ResultatsAdmission::TABLE.' as r')
            ->leftJoin('demande as d', 'd.id_demande', '=', 'r.demande_id')
            ->leftJoin('etudiants as e', 'e.id_etudiant', '=', 'r.etudiant_id')
            ->where('r.campagne_id', $campagne)
            ->when($type !== '', fn ($query) => $query->where('r.volet', $type))
            ->when($recherche !== '', function ($query) use ($recherche) {
                $terme = '%'.addcslashes($recherche, '%_\\').'%';
                $query->where(fn ($q) => $q->where('e.name', 'like', $terme)
                    ->orWhere('e.matricule', 'like', $terme)
                    ->orWhere('d.num_table_bac', 'like', $terme)
                    ->orWhere('d.numero_demande', 'like', $terme));
            });
    }

    /** Demandes d'un onglet, par ordre alphabétique. */
    private function onglet(Builder $requete, string $statut): Builder
    {
        return $requete->where('r.resultat', self::STATUTS[$statut])
            ->orderBy('e.name')->orderBy('r.id')
            ->select(['r.id', 'r.etudiant_id', 'r.volet', 'r.resultat', 'r.publie_at', 'd.id_demande', 'd.numero_demande', 'd.num_table_bac', 'd.etat_dossier', 'e.id_etudiant', 'e.name', 'e.matricule']);
    }

    /** @return array{0: LengthAwarePaginator, 1: Collection<string, int>} page de l'onglet et nombre de demandes par résultat */
    private function lignes(string $campagne, string $statut, string $type, string $recherche, ?User $admin): array
    {
        $base = $this->requete($campagne, $type, $recherche);
        // Compteurs des onglets : mêmes filtres que la liste, hors statut.
        $compteurs = (clone $base)->selectRaw('r.resultat, count(*) as total')->groupBy('r.resultat')->pluck('total', 'resultat');
        $voirDossier = $this->peutVoirDossier($admin);
        $page = $this->onglet($base, $statut)->paginate(10)->withQueryString();
        $admissibles = $statut === 'attente' && $this->access->allows($admin, self::ROUTE.'admettre')
            ? $this->resultats->admissibles(DemandeEtudiant::with(['campagne', 'etudiant.derniereDemande'])
                ->whereIn('id_demande', $page->pluck('id_demande')->filter()->all())->get())
            : [];

        $lignes = $page->through(fn (object $ligne) => [
                'id' => (string) $ligne->id,
                'etudiant' => $ligne->name,
                'matricule' => $ligne->matricule,
                'num_table_bac' => $ligne->num_table_bac,
                'numero_demande' => $ligne->numero_demande,
                'type' => $ligne->volet,
                'etat_dossier' => $ligne->etat_dossier,
                // Admission remise en cause depuis la publication : le résultat n'est plus affiché à l'étudiant.
                'remise_en_cause' => $ligne->id_demande !== null && $this->resultats->admissionRemiseEnCause($ligne->resultat, $ligne->etat_dossier),
                'publie_at' => $ligne->publie_at,
                'url_dossier' => $voirDossier && $ligne->id_etudiant
                    ? route(AdmissionPermissions::ROUTE.'show', ['id' => $ligne->etudiant_id, 'origine' => 'resultats']) : null,
                // File d'attente : passage dans « Admis à loger » ; null sans le droit ou si la demande ne peut pas être admise.
                'url_admettre' => in_array((string) $ligne->id_demande, $admissibles, true) ? route(self::ROUTE.'admettre', $ligne->id) : null,
            ]);

        return [$lignes, $compteurs];
    }

    /** Agent dont les droits ouvrent la fiche de toutes les demandes (le droit sur les résultats ne l'ouvre pas). */
    private function peutVoirDossier(?User $admin): bool
    {
        return $admin !== null && $this->access->allows($admin, AdmissionPermissions::ROUTE.'show')
            && (app(AdmissionPermissions::class)->allows($admin, 'toutes')
                || $admin->hasPermission('hebergement.residents.view') || $admin->hasPermission('hebergement.attributions.view'));
    }

    /**
     * Campagnes dont des résultats sont publiés (supprimées comprises), la dernière publiée d'abord.
     *
     * @return list<array{id: string, libelle: string, active: bool, supprimee: bool, visible: bool}>
     */
    private function campagnes(): array
    {
        if (! $this->resultats->disponible()) {
            return [];
        }
        $publications = DB::table(ResultatsAdmission::TABLE)->groupBy('campagne_id')
            ->selectRaw('campagne_id, max(publie_at) as publie_at')->orderByDesc('publie_at')->get();
        $campagnes = Campagne::withTrashed()->whereIn('id_campagne', $publications->pluck('campagne_id'))->get()
            ->keyBy(fn (Campagne $campagne) => (string) $campagne->getKey());
        $visibles = $this->resultats->campagnesVisibles();

        return $publications->filter(fn (object $publication) => isset($campagnes[(string) $publication->campagne_id]))
            ->map(function (object $publication) use ($campagnes, $visibles) {
                $campagne = $campagnes[(string) $publication->campagne_id];

                return [
                    'id' => (string) $campagne->getKey(),
                    'libelle' => collect([$campagne->libelle ?: 'Campagne', $campagne->annee_campagne])->filter()->implode(' · '),
                    'active' => $campagne->statut === 'active',
                    'supprimee' => $campagne->trashed(),
                    'visible' => ! $campagne->trashed() && in_array((string) $campagne->getKey(), $visibles, true),
                ];
            })->values()->all();
    }
}
