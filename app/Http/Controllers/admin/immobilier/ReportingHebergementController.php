<?php

namespace App\Http\Controllers\admin\immobilier;

use App\Http\Controllers\Controller;
use App\Models\Cite;
use App\Services\HousingReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        return response()->view('admin.hebergement.reporting.modules', compact('module', 'modules', 'reports', 'periode', 'debut', 'fin', 'citeId', 'cites'))
            ->header('Cache-Control', 'private, no-store');
    }
}
