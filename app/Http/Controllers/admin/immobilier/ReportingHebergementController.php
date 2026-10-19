<?php

namespace App\Http\Controllers\admin\immobilier;

use App\Http\Controllers\Controller;
use App\Models\Cite;
use App\Services\HousingReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReportingHebergementController extends Controller
{
    public function index(Request $request, HousingReportingService $service, string $module = 'synthese')
    {
        abort_unless($module === 'synthese' || isset(HousingReportingService::MODULES[$module]), 404);
        $modules = $service->visibleModules($request->user('admin'));
        abort_unless($module === 'synthese' || isset($modules[$module]), 403);
        $filters = $request->validate([
            'periode' => ['nullable', Rule::in(['week', 'month', 'quarter', 'year', 'custom'])],
            'debut' => ['required_if:periode,custom', 'nullable', 'date_format:Y-m-d'],
            'fin' => ['required_if:periode,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:debut'],
            'cite' => ['nullable', 'string', Rule::exists((new Cite)->getTable(), 'id_cite')],
        ]);
        $periode = $filters['periode'] ?? 'month';
        $debut = match ($periode) {
            'week' => now()->startOfWeek(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            'custom' => Carbon::parse($filters['debut'])->startOfDay(),
            default => now()->startOfMonth(),
        };
        $fin = $periode === 'custom' ? Carbon::parse($filters['fin'])->endOfDay() : now()->endOfDay();
        $citeId = $filters['cite'] ?? null;
        $cites = Cite::query()->orderBy('name')->get(['id_cite', 'name']);
        // The entry page lets users choose a module without loading every report.
        $reports = $module === 'synthese' ? [] : $service->reports($module, $debut, $fin, $citeId, $request->user('admin'));
        $query = array_filter(['periode' => $filters['periode'] ?? null, 'debut' => $filters['debut'] ?? null, 'fin' => $filters['fin'] ?? null, 'cite' => $citeId]);
        $user = $request->user('admin');

        return Inertia::render('Admin/Hebergement/Reporting/Index', [
            'module' => $module,
            'modules' => collect($modules)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'href' => route('admin.hebergement.reporting.module', ['module' => $key] + $query),
            ])->values(),
            'reports' => array_values($reports),
            'filters' => ['periode' => $periode, 'debut' => $debut->toDateString(), 'fin' => $fin->toDateString(), 'cite' => $citeId],
            'cites' => $cites->map(fn ($cite) => ['id' => $cite->id_cite, 'name' => $cite->name])->values(),
            'synthese' => route('admin.hebergement.reporting.index', $query),
            'canExport' => (bool) $user?->hasPermission('hebergement.reporting.export'),
            'generatedAt' => now()->format('d/m/Y à H:i'),
        ])->toResponse($request)->header('Cache-Control', 'private, no-store');
    }
}
