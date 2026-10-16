<?php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

use App\Http\Controllers\Controller;
use App\Models\Cite;
use App\Models\Batiment;
use App\Models\Chambre;
use App\Models\AssignationChambre;
use App\Models\Etudiant;
use App\Models\Sanction;
use App\Models\SanctionHistory;
use App\Http\Services\DisciplinaryImpactService;
use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SanctionController extends Controller
{
    public function index(Request $request)
    {
        $filtres = [
            'type' => in_array($request->query('type'), array_keys(DisciplinaryImpactService::LABELS), true) ? $request->query('type') : '',
            'statut' => in_array($request->query('statut'), ['active', 'levee', 'annulee'], true) ? $request->query('statut') : '',
            'cite' => (string) $request->query('cite', ''),
        ];
        $sanctions = Sanction::query()->with(['etudiant:id_etudiant,name,matricule', 'cite:id_cite,name', 'batiment:id_batiment,name', 'chambre:id_chambre,numero_chambre', 'historique'])
            ->when($filtres['type'], fn ($query, $type) => $query->where('type', $type))
            ->when($filtres['statut'], fn ($query, $statut) => $query->where('statut', $statut))
            ->when($filtres['cite'], fn ($query, $cite) => $query->where('cite_id', $cite))
            ->latest('date_decision')->paginate(10)->withQueryString();

        $access = app(AdminAccess::class);
        $admin = $request->user('admin');
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $peutChanger = $access->allows($admin, 'admin.hebergement.vie_residentielle.sanctions.status');

        $sanctions->through(fn (Sanction $sanction) => [
            'id' => $sanction->id_sanction,
            'numero' => $sanction->numero_sanction,
            'date_decision' => $sanction->date_decision?->toDateString(),
            'portee' => $sanction->scope_type,
            'cible' => match ($sanction->scope_type) {
                'cite' => ['titre' => 'Toute la cité '.($sanction->cite?->name ?? ''), 'detail' => 'Tous les résidents de la cité'],
                'batiment' => ['titre' => 'Tout le bâtiment '.($sanction->batiment?->name ?? ''), 'detail' => $sanction->cite?->name],
                'chambre' => ['titre' => 'Toute la chambre '.($sanction->chambre?->numero_chambre ?? ''), 'detail' => trim(($sanction->cite?->name ?? '').' · '.($sanction->batiment?->name ?? ''), ' ·')],
                default => ['titre' => $sanction->etudiant?->name ?? 'Résident inconnu', 'detail' => trim(($sanction->etudiant?->matricule ?? '').' · Chambre '.($sanction->chambre?->numero_chambre ?? '—'), ' ·')],
            },
            'type' => $sanction->type,
            'type_label' => DisciplinaryImpactService::LABELS[$sanction->type] ?? ucfirst((string) $sanction->type),
            'motif' => $sanction->motif,
            'faits' => $sanction->faits_reproches,
            'date_debut' => $sanction->date_debut?->toDateString(),
            'date_fin' => $sanction->date_fin?->toDateString(),
            'statut' => $sanction->statut,
            'historique' => $sanction->historique->sortByDesc('created_at')->map(fn ($item) => [
                'id' => $item->getKey(),
                'action' => $item->action,
                'details' => $item->details,
                'ancien_statut' => $item->ancien_statut,
                'nouveau_statut' => $item->nouveau_statut,
                'date' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : null,
            ])->values(),
            'url_statut' => $peutChanger ? route('admin.hebergement.vie_residentielle.sanctions.status', $sanction) : null,
        ]);

        $cites = Cite::query()->orderBy('name')->get(['id_cite', 'name']);
        $buildings = Batiment::query()->orderBy('name')->get(['id_batiment', 'cite_id', 'name']);
        $assignments = AssignationChambre::actives()->with('etudiant:id_etudiant,name,matricule')
            ->get(['id_assignation', 'etudiant_id', 'chambre_id']);
        $rooms = Chambre::query()
            ->whereNotNull('numero_chambre')
            ->whereIn('id_chambre', $assignments->pluck('chambre_id')->filter()->unique())
            ->orderBy('numero_chambre')
            ->get(['id_chambre', 'batiment_id', 'cite_id', 'numero_chambre']);
        $residents = $assignments->filter(fn ($assignment) => $assignment->etudiant)->map(fn ($assignment) => [
            'id' => $assignment->etudiant->id_etudiant,
            'name' => $assignment->etudiant->name,
            'matricule' => $assignment->etudiant->matricule,
            'chambre_id' => $assignment->chambre_id,
        ])->values();

        return Inertia::render('Admin/Hebergement/Discipline/Sanctions', [
            'sanctions' => $sanctions,
            'stats' => [
                'total' => Sanction::count(),
                'active' => Sanction::where('statut', 'active')->count(),
                'avertissements' => Sanction::where('type', 'avertissement')->where('statut', 'active')->count(),
                'suspensions' => Sanction::where('type', 'suspension')->where('statut', 'active')->count(),
            ],
            'filters' => $filtres,
            'structure' => [
                'cites' => $cites->map(fn ($cite) => ['id' => $cite->id_cite, 'name' => $cite->name])->values(),
                'batiments' => $buildings->map(fn ($batiment) => ['id' => $batiment->id_batiment, 'cite_id' => $batiment->cite_id, 'name' => $batiment->name])->values(),
                'chambres' => $rooms->map(fn ($chambre) => ['id' => $chambre->id_chambre, 'batiment_id' => $chambre->batiment_id, 'numero' => $chambre->numero_chambre])->values(),
                'residents' => $residents,
            ],
            'urls' => [
                'index' => route('admin.hebergement.vie_residentielle.sanctions.index'),
                'store' => $url('admin.hebergement.vie_residentielle.sanctions.store'),
                'incidents' => $url('admin.hebergement.vie_residentielle.incidents.index'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user('admin')?->hasPermission('hebergement.discipline.create'), 403);
        $data = $request->validate([
            'scope_type' => ['required', 'in:cite,batiment,chambre,etudiant'],
            'cite_id' => ['required', 'exists:cite,id_cite'],
            'batiment_id' => ['nullable', 'required_unless:scope_type,cite', 'exists:batiment,id_batiment'],
            'chambre_id' => ['nullable', 'required_if:scope_type,chambre,etudiant', 'exists:chambre,id_chambre'],
            'etudiant_id' => ['nullable', 'required_if:scope_type,etudiant', 'exists:etudiants,id_etudiant'],
            'type' => ['required', 'in:avertissement,blame,suspension'],
            'motif' => ['required', 'string', 'max:2000'],
            'faits_reproches' => ['nullable', 'string', 'max:5000'],
            'date_decision' => ['required', 'date'],
            'date_debut' => ['nullable', 'required_if:type,suspension', 'date'],
            'date_fin' => ['nullable', 'required_if:type,suspension', 'date', 'after_or_equal:date_debut'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['scope_type'] !== 'cite' && !Batiment::where('id_batiment', $data['batiment_id'])->where('cite_id', $data['cite_id'])->exists()) {
            throw ValidationException::withMessages(['batiment_id' => 'Le bâtiment sélectionné n’appartient pas à cette cité.']);
        }
        if (in_array($data['scope_type'], ['chambre', 'etudiant'], true)
            && !Chambre::where('id_chambre', $data['chambre_id'])->where('batiment_id', $data['batiment_id'])->exists()) {
            throw ValidationException::withMessages(['chambre_id' => 'La chambre sélectionnée n’appartient pas à ce bâtiment.']);
        }
        if (in_array($data['scope_type'], ['chambre', 'etudiant'], true)
            && !AssignationChambre::actives()->where('chambre_id', $data['chambre_id'])->exists()) {
            throw ValidationException::withMessages(['chambre_id' => 'Cette chambre ne possède actuellement aucun résident affecté.']);
        }
        if ($data['scope_type'] === 'etudiant'
            && !AssignationChambre::actives()->where('chambre_id', $data['chambre_id'])->where('etudiant_id', $data['etudiant_id'])->exists()) {
            throw ValidationException::withMessages(['etudiant_id' => 'Ce résident n’est pas actuellement affecté à cette chambre.']);
        }
        if ($data['scope_type'] === 'cite') {
            $data['batiment_id'] = $data['chambre_id'] = $data['etudiant_id'] = null;
        } elseif ($data['scope_type'] === 'batiment') {
            $data['chambre_id'] = $data['etudiant_id'] = null;
        } elseif ($data['scope_type'] === 'chambre') {
            $data['etudiant_id'] = null;
        }
        $author = getInfoAdmin()->users->id_user ?? null;

        DB::transaction(function () use ($data, $author) {
            if ($data['etudiant_id'] ?? null) Etudiant::whereKey($data['etudiant_id'])->lockForUpdate()->firstOrFail();
            $sanction = Sanction::create($data + ['statut' => 'active', 'created_by' => $author]);
            app(\App\Http\Services\DisciplinaryImpactService::class)->synchroniser($sanction, $author);
            SanctionHistory::create([
                'sanction_id' => $sanction->id_sanction,
                'action' => 'creation',
                'nouveau_statut' => 'active',
                'details' => 'Sanction '.$sanction->type.' enregistrée.',
                'auteur_id' => $author,
            ]);
        });

        return back()->with('success', 'La sanction a été enregistrée et ajoutée à l’historique.');
    }

    public function changeStatus(Request $request, Sanction $sanction)
    {
        abort_unless($request->user('admin')?->hasPermission('hebergement.discipline.update'), 403);
        $data = $request->validate([
            'statut' => ['required', 'in:active,levee,annulee'],
            'details' => ['required', 'string', 'max:2000'],
        ]);
        $author = getInfoAdmin()->users->id_user ?? null;

        DB::transaction(function () use ($sanction, $data, $author) {
            if ($sanction->etudiant_id) Etudiant::whereKey($sanction->etudiant_id)->lockForUpdate()->firstOrFail();
            $sanction = Sanction::whereKey($sanction->getKey())->lockForUpdate()->firstOrFail();
            $oldStatus = $sanction->statut;
            $sanction->update(['statut' => $data['statut'], 'updated_by' => $author]);
            app(\App\Http\Services\DisciplinaryImpactService::class)->synchroniser($sanction, $author);
            SanctionHistory::create([
                'sanction_id' => $sanction->id_sanction,
                'action' => 'changement_statut',
                'ancien_statut' => $oldStatus,
                'nouveau_statut' => $data['statut'],
                'details' => $data['details'],
                'auteur_id' => $author,
            ]);
        });

        return back()->with('success', 'Le statut de la sanction a été mis à jour.');
    }
}
