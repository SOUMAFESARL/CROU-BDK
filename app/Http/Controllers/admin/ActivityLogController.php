<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityLogController extends Controller
{
    private const ACTIONS = [
        'login' => 'Connexion',
        'logout' => 'Déconnexion',
        'create' => 'Création',
        'update' => 'Modification',
        'delete' => 'Suppression',
        'action' => 'Action',
    ];

    /** Nombre maximal de lignes exportées (Excel) ou imprimées, pour protéger le serveur. */
    private const EXPORT_LIMIT = 10000;

    private const PRINT_LIMIT = 2000;

    public function index(Request $request): Response
    {
        [$query, $filters, $canViewAll] = $this->filteredQuery($request);

        $actors = $canViewAll
            ? ActivityLog::query()->whereNotNull('actor_id')->select('actor_id', 'actor_name', 'actor_type')->distinct()->orderBy('actor_name')->get()
            : collect();

        $dateFormat = $this->dateFormat();

        $logs = $query->paginate(10)->withQueryString()->through(fn (ActivityLog $log) => [
            'id' => $log->id,
            'date' => $log->created_at?->format($dateFormat),
            'actor' => $log->actor_name ?: 'Anonyme',
            'actor_type' => $log->actor_type,
            'action' => $log->action,
            'action_label' => $this->actionLabel($log->action),
            'ip' => $log->ip_address,
            'status' => $log->status_code ? (int) $log->status_code : null,
        ]);

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'actor_id' => $canViewAll ? ($filters['actor_id'] ?? '') : '',
                'action' => $filters['action'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            // Un même acteur peut apparaître sous plusieurs noms : une seule entrée par identifiant.
            'actors' => $actors->unique('actor_id')->map(fn (ActivityLog $actor) => [
                'id' => (string) $actor->actor_id,
                'label' => ($actor->actor_name ?: $actor->actor_id).($actor->actor_type ? " ({$actor->actor_type})" : ''),
            ])->values(),
            'actions' => self::ACTIONS,
            'canViewAll' => $canViewAll,
        ]);
    }

    /** Export Excel (.xlsx) des activités correspondant aux filtres en cours. */
    public function export(Request $request): BinaryFileResponse
    {
        [$query, , $canViewAll] = $this->filteredQuery($request);
        $this->ensureExportable($query, self::EXPORT_LIMIT);

        $headers = array_merge(
            ['Date et heure'],
            $canViewAll ? ['Utilisateur', 'Type'] : [],
            ['Action', 'Méthode', 'URL', 'Adresse IP', 'Résultat'],
        );
        $dateFormat = $this->dateFormat();

        $rows = (function () use ($query, $canViewAll, $dateFormat) {
            foreach ($query->cursor() as $log) {
                yield array_merge(
                    [$log->created_at?->format($dateFormat) ?? ''],
                    $canViewAll ? [$log->actor_name ?: 'Anonyme', (string) $log->actor_type] : [],
                    [
                        $this->actionLabel($log->action),
                        (string) $log->method,
                        (string) $log->url,
                        (string) $log->ip_address,
                        $log->status_code ? (int) $log->status_code : '',
                    ],
                );
            }
        })();

        return SimpleXlsx::download(
            'journal-activite-'.now()->format('Y-m-d-His').'.xlsx',
            "Journal d'activité",
            $headers,
            $rows,
        );
    }

    /** Version imprimable des activités filtrées (ouverte dans un nouvel onglet, impression automatique). */
    public function print(Request $request): View
    {
        [$query, $filters, $canViewAll] = $this->filteredQuery($request);
        $this->ensureExportable($query, self::PRINT_LIMIT);

        $dateFormat = $this->dateFormat();
        $dayFormat = systemConfig('date_format', 'd/m/Y');

        $logs = $query->get()->map(fn (ActivityLog $log) => [
            'date' => $log->created_at?->format($dateFormat),
            'actor' => $log->actor_name ?: 'Anonyme',
            'actor_type' => $log->actor_type,
            'action' => $this->actionLabel($log->action),
            'ip' => $log->ip_address,
            'status' => $log->status_code,
        ]);

        $criteria = array_filter([
            'Utilisateur' => $canViewAll && !empty($filters['actor_id'])
                ? (ActivityLog::query()->where('actor_id', $filters['actor_id'])->value('actor_name') ?: $filters['actor_id'])
                : null,
            'Action' => !empty($filters['action']) ? self::ACTIONS[$filters['action']] : null,
            'Du' => !empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->format($dayFormat) : null,
            'Au' => !empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->format($dayFormat) : null,
        ]);

        return view('admin.activity-logs.print', [
            'logs' => $logs,
            'criteria' => $criteria,
            'canViewAll' => $canViewAll,
            'title' => $canViewAll ? 'Activités de tous les utilisateurs' : 'Mes activités',
            'printedAt' => now()->format($dateFormat),
        ]);
    }

    /**
     * Requête filtrée commune à la liste, à l'export et à l'impression.
     * Sans droit de consultation globale, seules les activités de l'utilisateur connecté sont visibles.
     *
     * @return array{0: Builder, 1: array<string, mixed>, 2: bool}
     */
    private function filteredQuery(Request $request): array
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 401);

        $roleName = mb_strtolower((string) $user->role?->name);
        $canViewAll = (int) $user->role_id === 1
            || in_array($roleName, ['super administrateur', 'super admin', 'administrateur'], true)
            || str_contains($roleName, 'chef')
            || $user->hasPermission('activity.view');

        $filters = $request->validate([
            'actor_id' => ['nullable', 'string', 'max:150'],
            'action' => ['nullable', 'in:login,logout,create,update,delete,action'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = ActivityLog::query()->latest();

        if (!$canViewAll) {
            $query->where('actor_type', 'admin')->where('actor_id', (string) $user->getAuthIdentifier());
        } elseif (!empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }

        $query
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));

        return [$query, $filters, $canViewAll];
    }

    private function ensureExportable(Builder $query, int $limit): void
    {
        $count = (clone $query)->count();

        if ($count === 0) {
            throw ValidationException::withMessages(['export' => 'Aucune activité à exporter pour ces critères.']);
        }

        if ($count > $limit) {
            throw ValidationException::withMessages([
                'export' => "Trop d'activités ({$count}) : affinez les filtres pour en exporter au plus {$limit}.",
            ]);
        }
    }

    private function actionLabel(?string $action): string
    {
        return self::ACTIONS[$action] ?? ucfirst((string) $action);
    }

    private function dateFormat(): string
    {
        return systemConfig('date_format', 'd/m/Y').' H:i:s';
    }
}
