<?php

namespace App\Http\Controllers\admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AssignationChambre;
use App\Models\Chambre;
use App\Models\Cite;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Models\OrdreTravail;
use App\Models\Paiement;
use App\Services\AdminAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class dashboardController extends Controller
{
    public function index(Request $request, \App\Services\DashboardAccess $access, AdminAccess $adminAccess)
    {
        $user = \Illuminate\Support\Facades\Auth::guard('admin')->user();
        abort_unless($user, 401);
        $dashboardAccess = $access->forUser($user);
        [$period, $start, $end] = $this->resolvePeriod($request->string('periode')->toString());
        $safe = fn (callable $query, mixed $fallback = 0) => $this->safeQuery($query, $fallback);

        $students = $dashboardAccess['students'] ? $safe(fn () => Etudiant::count()) : 0;
        $rooms = $dashboardAccess['housing'] ? $safe(fn () => Chambre::query()->whereNotNull('numero_chambre')->get(['id_chambre', 'cite_id', 'capacite_max', 'etat']), collect()) : collect();
        $activeAssignments = $dashboardAccess['housing'] ? $safe(fn () => AssignationChambre::actives()->get(['id_assignation', 'chambre_id', 'date_assignation']), collect()) : collect();
        $occupiedBeds = $activeAssignments->count();
        $capacity = (int) $rooms->sum(fn ($room) => max(0, (int) $room->capacite_max));
        $availableBeds = max(0, $capacity - $occupiedBeds);
        $occupancyRate = $capacity > 0 ? round(min(100, ($occupiedBeds / $capacity) * 100), 1) : 0;

        $periodPayments = $dashboardAccess['finance'] ? $safe(fn () => Paiement::query()
            ->where('statut_paiement', 'valide')
            ->whereBetween('date_paiement', [$start, $end])
            ->get(['montant', 'date_paiement']), collect()) : collect();
        $revenue = (float) $periodPayments->sum('montant');

        $requests = $dashboardAccess['admissions'] ? $safe(fn () => DemandeEtudiant::query()->get(['statut_demande', 'created_at', 'date_demande']), collect()) : collect();
        $periodRequests = $requests->filter(function ($item) use ($start, $end) {
            $date = $item->date_demande ?? $item->created_at;
            return $date && Carbon::parse($date)->between($start, $end);
        });
        $pendingRequests = $requests->whereIn('statut_demande', ['en_attente', 'en_cours'])->count();

        $workOrders = $dashboardAccess['maintenance'] ? $safe(fn () => OrdreTravail::query()->get(['statut', 'priorite', 'titre', 'created_at', 'date_echeance']), collect()) : collect();
        $openWorkOrders = $workOrders->whereIn('statut', ['en_attente', 'en_cours', 'en_attente_pieces'])->count();
        $urgentWorkOrders = $workOrders->whereIn('statut', ['en_attente', 'en_cours', 'en_attente_pieces'])
            ->whereIn('priorite', ['urgente', 'urgent', 'critique'])->count();

        $requestStatuses = [
            'labels' => ['En attente', 'En cours', 'Validées', 'Rejetées'],
            'values' => [
                $requests->where('statut_demande', 'en_attente')->count(),
                $requests->where('statut_demande', 'en_cours')->count(),
                $requests->where('statut_demande', 'validee')->count(),
                $requests->where('statut_demande', 'rejetee')->count(),
            ],
        ];

        $trend = $this->buildTrend($periodRequests, $periodPayments, $start, $end, $period);
        $residences = $dashboardAccess['housing'] ? $this->buildResidences($safe(fn () => Cite::query()->get(['id_cite', 'name']), collect()), $rooms, $activeAssignments) : [];

        // Liens des raccourcis : uniquement ceux que l'utilisateur peut ouvrir.
        $link = fn (bool $visible, string $route) => $visible && $adminAccess->allows($user, $route) ? route($route) : null;

        return Inertia::render('Admin/Dashboard/Index', [
            'access' => $dashboardAccess,
            'period' => $period,
            'periodLabel' => $start->translatedFormat('d M Y').' — '.$end->translatedFormat('d M Y'),
            'stats' => [
                'students' => $students,
                'occupiedBeds' => $occupiedBeds,
                'capacity' => $capacity,
                'availableBeds' => $availableBeds,
                'occupancyRate' => $occupancyRate,
                'revenue' => $revenue,
                'revenueLabel' => $dashboardAccess['finance'] ? formatSystemAmount($revenue) : null,
                'pendingRequests' => $pendingRequests,
                'openWorkOrders' => $openWorkOrders,
                'urgentWorkOrders' => $urgentWorkOrders,
            ],
            'requestStatuses' => $requestStatuses,
            'trend' => $trend,
            'residences' => $residences,
            'links' => [
                'parc' => $link($dashboardAccess['parc'], 'admin.hebergement.parc-immobilier.index'),
                'admissions' => $link($dashboardAccess['admissions'], 'admin.hebergement.campagnes_admission.admission'),
                'paiements' => $link($dashboardAccess['finance'], 'admin.hebergement.paiements.index'),
                'maintenance' => $link($dashboardAccess['maintenance'], 'admin.hebergement.maintenance.index'),
            ],
        ]);
    }

    private function resolvePeriod(string $period): array
    {
        $period = in_array($period, ['week', 'month', 'quarter', 'year'], true) ? $period : 'month';
        $now = now();
        $start = match ($period) {
            'week' => $now->copy()->startOfWeek(),
            'quarter' => $now->copy()->startOfQuarter(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };

        return [$period, $start, $now->copy()->endOfDay()];
    }

    private function buildTrend(Collection $requests, Collection $payments, Carbon $start, Carbon $end, string $period): array
    {
        $daily = in_array($period, ['week', 'month'], true);
        $cursor = $start->copy();
        $labels = $requestValues = $paymentValues = [];

        while ($cursor->lte($end)) {
            $key = $daily ? $cursor->format('Y-m-d') : $cursor->format('Y-m');
            $labels[] = $daily ? $cursor->translatedFormat('d M') : $cursor->translatedFormat('M Y');
            $requestValues[] = $requests->filter(fn ($item) => optional($item->date_demande ?? $item->created_at)->format($daily ? 'Y-m-d' : 'Y-m') === $key)->count();
            $paymentValues[] = round((float) $payments->filter(fn ($item) => optional($item->date_paiement)->format($daily ? 'Y-m-d' : 'Y-m') === $key)->sum('montant'));
            $cursor = $daily ? $cursor->addDay() : $cursor->addMonth();
        }

        return ['labels' => $labels, 'requests' => $requestValues, 'payments' => $paymentValues];
    }

    private function buildResidences(Collection $cites, Collection $rooms, Collection $assignments): array
    {
        $occupiedByRoom = $assignments->countBy('chambre_id');

        return $cites->map(function ($cite) use ($rooms, $occupiedByRoom) {
            $citeRooms = $rooms->where('cite_id', $cite->id_cite);
            $capacity = (int) $citeRooms->sum('capacite_max');
            $occupied = (int) $citeRooms->sum(fn ($room) => $occupiedByRoom->get($room->id_chambre, 0));
            return [
                'name' => $cite->name ?: 'Cité sans nom',
                'capacity' => $capacity,
                'occupied' => $occupied,
                'rate' => $capacity ? min(100, round(($occupied / $capacity) * 100)) : 0,
            ];
        })->sortByDesc('rate')->take(6)->values()->all();
    }

    private function safeQuery(callable $query, mixed $fallback): mixed
    {
        try {
            return $query();
        } catch (\Throwable $exception) {
            Log::warning('Donnée indisponible sur le tableau de bord', ['message' => $exception->getMessage()]);
            return $fallback;
        }
    }

    public function restauration()
    {
        return view('admin.dashboard.restauration', ['moduleActif' => 'restauration']);
    }
}
