@extends('admin.layouts.app', ['activeMenu' => 'activity'])

@section('title', "Journal d'activité")

@section('content')
<x-breadcrumbs title="Journal d'activité" :items="['Tableau de bord' => route('admin.dashboard.index'), 'Journal d’activité' => null]" />

<div class="activity-shell">
    <header class="activity-header">
        <div>
            <h2>{{ $canViewAll ? 'Activités de tous les utilisateurs' : 'Mes activités' }}</h2>
            <p>{{ $canViewAll ? 'Suivi des opérations réalisées dans l’application.' : 'Historique des opérations réalisées avec votre compte.' }}</p>
        </div>
        <span class="scope-badge"><i class="fa-solid {{ $canViewAll ? 'fa-users' : 'fa-user' }}"></i> {{ $canViewAll ? 'Vue responsable' : 'Vue personnelle' }}</span>
    </header>

    <form method="GET" class="activity-filters">
        @if($canViewAll)
            <label>Utilisateur
                <select name="actor_id">
                    <option value="">Tous les utilisateurs</option>
                    @foreach($actors as $actor)
                        <option value="{{ $actor->actor_id }}" @selected(request('actor_id') === $actor->actor_id)>{{ $actor->actor_name ?: $actor->actor_id }} ({{ $actor->actor_type }})</option>
                    @endforeach
                </select>
            </label>
        @endif
        <label>Action
            <select name="action">
                <option value="">Toutes</option>
                @foreach(['login'=>'Connexion','logout'=>'Déconnexion','create'=>'Création','update'=>'Modification','delete'=>'Suppression','action'=>'Action'] as $value=>$label)
                    <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Du <input type="date" name="date_from" value="{{ request('date_from') }}"></label>
        <label>Au <input type="date" name="date_to" value="{{ request('date_to') }}"></label>
        <button><i class="fa-solid fa-filter"></i> Filtrer</button>
        @adminroute('admin.activity-logs.index')
        <a href="{{ route('admin.activity-logs.index') }}">Réinitialiser</a>
        @endadminroute

    </form>

    <div class="activity-table-wrap">
        <table>
            <thead><tr><th>Date et heure</th>@if($canViewAll)<th>Utilisateur</th>@endif<th>Action</th>
            <!-- <th>Page / route</th> -->
            <th>Adresse IP</th><th>Résultat</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td><strong>{{ $log->created_at?->format(systemConfig('date_format', 'd/m/Y').' H:i:s') }}</strong></td>
                    @if($canViewAll)<td><strong>{{ $log->actor_name ?: 'Anonyme' }}</strong><small>{{ $log->actor_type }} </small></td>@endif
                    <td><span class="action action-{{ $log->action }}">{{ ['login'=>'Connexion','logout'=>'Déconnexion','create'=>'Création','update'=>'Modification','delete'=>'Suppression','action'=>'Action'][$log->action] ?? ucfirst($log->action) }}</span></td>
                    <!-- <td><strong>{{ $log->route_name ?: 'Route non nommée' }}</strong><small>{{ $log->method }} · {{ parse_url($log->url, PHP_URL_PATH) }}</small></td> -->
                    <td>{{ $log->ip_address ?: '—' }}</td>
                    <td><span class="status">{{ $log->status_code }}</span></td>
                </tr>
            @empty
                <tr><td colspan="{{ $canViewAll ? 6 : 5 }}" class="empty"><i class="fa-solid fa-clock-rotate-left"></i><span>Aucune activité trouvée pour ces critères.</span></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="activity-pagination">{{ $logs->links() }}</div>
</div>
@endsection

@push('styles')
<style>
.activity-shell{overflow:hidden;border:1px solid #edf0ee;border-radius:20px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.08)}
.activity-header{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:24px;border-bottom:1px solid #edf0ee}.activity-header h2{margin:0;color:#1f7c43;font-size:20px;font-weight:600}.activity-header p{margin:5px 0 0;color:#6b7280;font-size:var(--app-font-min, 15px)}.scope-badge{display:inline-flex;align-items:center;gap:7px;padding:8px 12px;border-radius:20px;background:#eaf5ee;color:#1f7c43;font-size:var(--app-font-min, 15px);font-weight:600}
.activity-filters{display:flex;align-items:flex-end;flex-wrap:wrap;gap:12px;padding:18px 24px;background:#f8fbf9}.activity-filters label{display:flex;flex-direction:column;gap:6px;color:#4b5563;font-size:var(--app-font-min, 15px);font-weight:600}.activity-filters input,.activity-filters select{min-width:150px;height:40px;padding:0 11px;border:1px solid #d1d5db;border-radius:10px;background:#fff;font-size:var(--app-font-min, 15px)}.activity-filters button,.activity-filters>a{display:inline-flex;height:40px;align-items:center;gap:7px;padding:0 15px;border-radius:10px;font-size:var(--app-font-min, 15px);font-weight:600;text-decoration:none}.activity-filters button{border:0;background:#1f7c43;color:#fff}.activity-filters>a{border:1px solid #d1d5db;background:#fff;color:#4b5563}
.activity-table-wrap{overflow-x:auto;padding:0 24px}.activity-table-wrap table{width:100%;border-collapse:collapse}.activity-table-wrap th{padding:14px 10px;border-bottom:2px solid #e5e7eb;color:#4b5563;font-size:var(--app-font-min, 15px);text-align:left;white-space:nowrap}.activity-table-wrap td{padding:14px 10px;border-bottom:1px solid #eef0f1;color:#374151;font-size:var(--app-font-min, 15px);vertical-align:middle}.activity-table-wrap td small{display:block;margin-top:4px;color:#9ca3af;font-size:var(--app-font-min, 15px)}.action,.status{display:inline-flex;padding:5px 9px;border-radius:12px;background:#eef2f7;color:#475569;font-size:var(--app-font-min, 15px);font-weight:700}.action-create,.action-login{background:#eaf7ef;color:#1f7c43}.action-update{background:#e8f2ff;color:#2563eb}.action-delete,.action-logout{background:#fff0f0;color:#dc2626}.status{background:#eef8f1;color:#1f7c43}.empty{padding:45px!important;text-align:center}.empty i,.empty span{display:block;color:#9ca3af}.empty i{margin-bottom:8px;font-size:24px}.activity-pagination{padding:18px 24px}
@media(max-width:700px){.activity-header{align-items:flex-start;flex-direction:column}.activity-filters>*{width:100%}.activity-filters input,.activity-filters select{width:100%}}
</style>
@endpush
