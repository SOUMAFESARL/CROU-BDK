<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\sanctions;

use App\Http\Controllers\Controller;
use App\Http\Services\CatalogueSanctions;
use App\Models\Sanction;
use App\Models\TypeSanction;
use App\Services\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paramétrage des sanctions (paramétrage hébergement, docs/parametrage-sanctions.md) : note de conduite initiale
 * et types de sanction (points retirés, effet) utilisés par les décisions disciplinaires de tout le système.
 * Consultation : `hebergement.discipline.view` ; modifications : `hebergement.discipline.configure`.
 */
class ParametrageSanctionsController extends Controller
{
    private const ROUTE = 'admin.hebergement.parametrage-herb.sanctions.';

    public function __construct(private CatalogueSanctions $catalogue, private AdminAccess $access) {}

    public function index(Request $request): Response
    {
        $admin = $request->user('admin');
        $url = fn (string $action, array $parametres = []) => $this->access->allows($admin, self::ROUTE.$action) ? route(self::ROUTE.$action, $parametres) : null;
        $utilisations = Sanction::query()->toBase()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
        $types = $this->catalogue->tous();

        return Inertia::render('Admin/Hebergement/Parametrage/Sanctions', [
            'noteInitiale' => $this->catalogue->noteInitiale(),
            'types' => $types->map(fn (array $type) => $type + [
                'effet_label' => CatalogueSanctions::EFFETS[$type['effet']],
                'utilisations' => (int) ($utilisations[$type['code']] ?? 0),
                'urls' => [
                    'update' => $type['id'] ? $url('update', ['type' => $type['id']]) : null,
                    'destroy' => $type['id'] ? $url('destroy', ['type' => $type['id']]) : null,
                ],
            ])->values(),
            'effets' => collect(CatalogueSanctions::EFFETS)->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])->values(),
            'stats' => [
                'total' => $types->count(),
                'actifs' => $types->where('actif', true)->count(),
                'bloquants' => $types->where('actif', true)->whereIn('effet', CatalogueSanctions::EFFETS_BLOQUANTS)->count(),
            ],
            'max' => CatalogueSanctions::NOTE_MAX,
            'urls' => [
                'parametrage' => route('admin.hebergement.parametrage-herb.index'),
                'note' => $url('note'),
                'store' => $url('store'),
            ],
        ]);
    }

    public function noteConduite(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['note_initiale' => ['required', 'numeric', 'min:1', 'max:'.CatalogueSanctions::NOTE_MAX]],
            [],
            ['note_initiale' => 'note de conduite initiale'],
        );
        $this->catalogue->definirNoteInitiale((float) $data['note_initiale'], $this->auteur($request));

        return back()->with('success', 'Note de conduite initiale enregistrée : '.CatalogueSanctions::formatPoints((float) $data['note_initiale']).' points.');
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $this->catalogue->creer($this->valider($request, creation: true), $this->auteur($request));

        return back()->with('success', 'Type de sanction « '.$type->libelle.' » ajouté.');
    }

    public function update(Request $request, TypeSanction $type): RedirectResponse
    {
        $this->catalogue->modifier($type, $this->valider($request), $this->auteur($request));

        return back()->with('success', 'Type de sanction « '.$type->libelle.' » modifié. Les nouveaux points s’appliquent aux prochaines sanctions.');
    }

    public function destroy(TypeSanction $type): RedirectResponse
    {
        $this->catalogue->supprimer($type);

        return back()->with('success', 'Type de sanction « '.$type->libelle.' » supprimé.');
    }

    private function valider(Request $request, bool $creation = false): array
    {
        return $request->validate([
            'libelle' => ['required', 'string', 'max:100'],
            'points_retires' => ['required', 'numeric', 'min:0', 'max:'.CatalogueSanctions::NOTE_MAX, 'decimal:0,2'],
            'effet' => ['required', Rule::in(array_keys(CatalogueSanctions::EFFETS))],
            'description' => ['nullable', 'string', 'max:1000'],
            'actif' => [$creation ? 'sometimes' : 'required', 'boolean'],
        ], [], [
            'libelle' => 'libellé',
            'points_retires' => 'nombre de points retirés',
            'effet' => 'effet de la sanction',
        ]);
    }

    private function auteur(Request $request): ?string
    {
        $id = $request->user('admin')?->getKey();

        return $id === null ? null : (string) $id;
    }
}
