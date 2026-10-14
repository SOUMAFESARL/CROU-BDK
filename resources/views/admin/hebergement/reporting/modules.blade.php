@extends('admin.layouts.app', ['activeMenu' => 'hebergement', 'activeSubMenu' => 'reporting'])

@section('title', 'Statistiques — '.($modules[$module] ?? 'Hébergement'))

@section('content')
<main class="housing-report">
    <x-breadcrumbs title="Statistiques de l’hébergement" :items="['Hébergement' => route('admin.hebergement.index')]" />
    <header class="report-heading">
        <div><span class="report-eyebrow">Reporting par module</span><h1>{{ $modules[$module] ?? 'Statistiques par module' }}</h1>
        <p>Du {{ $debut->format('d/m/Y') }} au {{ $fin->format('d/m/Y') }} · {{ $cites->firstWhere('id_cite', $citeId)?->name ?? 'Toutes les cités' }}</p></div>
        @if($module !== 'synthese' && auth('admin')->user()->hasPermission('hebergement.reporting.export'))
            <button type="button" class="report-button print-report" onclick="window.print()">Imprimer / PDF</button>
        @endif
    </header>
    @if($errors->any())
        <div role="alert" class="report-warning">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <form method="get" class="report-filters">
        <label>Période<select name="periode" id="report-period">
            @foreach(['week' => 'Cette semaine', 'month' => 'Ce mois', 'quarter' => 'Ce trimestre', 'year' => 'Cette année', 'custom' => 'Dates personnalisées'] as $value => $label)
                <option value="{{ $value }}" @selected($periode === $value)>{{ $label }}</option>
            @endforeach
        </select></label>
        <label>Début<input type="date" name="debut" value="{{ $debut->toDateString() }}"></label>
        <label>Fin<input type="date" name="fin" value="{{ $fin->toDateString() }}"></label>
        <label>Cité<select name="cite"><option value="">Toutes les cités</option>
            @foreach($cites as $cite)<option value="{{ $cite->id_cite }}" @selected($citeId === $cite->id_cite)>{{ $cite->name }}</option>@endforeach
        </select></label>
        <button class="report-button" type="submit">Appliquer</button>
    </form>
    @if($module === 'synthese')
        <section class="module-directory" aria-labelledby="choose-module">
            <h2 id="choose-module">Choisissez un module</h2>
            <p class="report-note">Consultez les indicateurs et les répartitions propres à chaque module. Les filtres sélectionnés seront conservés.</p>
            <div class="module-cards">
                @foreach($modules as $key => $label)
                    @adminroute('admin.hebergement.reporting.module')
                    <a class="module-card" href="{{ route('admin.hebergement.reporting.module', ['module' => $key] + request()->only('periode', 'debut', 'fin', 'cite')) }}">
                        <strong>{{ $label }}</strong><span>Consulter les statistiques &rarr;</span>
                    </a>
                    @endadminroute
                @endforeach
            </div>
        </section>
    @else
    <nav class="report-modules" aria-label="Statistiques par module">
        @adminroute('admin.hebergement.reporting.index')
        <a href="{{ route('admin.hebergement.reporting.index', request()->only('periode', 'debut', 'fin', 'cite')) }}">Tous les modules</a>
@endadminroute
        @foreach($modules as $key => $label)
            <a @class(['selected' => $module === $key]) @if($module === $key) aria-current="page" @endif href="{{ route('admin.hebergement.reporting.module', ['module' => $key] + request()->only('periode', 'debut', 'fin', 'cite')) }}">{{ $label }}</a>
        @endforeach
    </nav>
    @foreach($reports as $key => $report)
        <section class="module-report" aria-labelledby="module-{{ $key }}">
            <h2 id="module-{{ $key }}">{{ $report['title'] }}</h2>
            @foreach($report['sections'] as $section)
                <article class="report-panel">
                    <h3>{{ $section['title'] }}</h3><p class="report-note">{{ $section['note'] }}</p>
                    @if($section['unavailable'])
                        <p class="report-warning" role="status">Statistiques indisponibles : ce module n’est pas encore installé dans la base de données.</p>
                    @else
                        <div class="report-kpis">
                            @foreach($section['metrics'] as $label => $value)
                                <div><span>{{ $label }}</span><strong>{{ number_format($value, floor($value) == $value ? 0 : 1, ',', ' ') }}</strong></div>
                            @endforeach
                        </div>
                        <div class="report-tables">
                            @foreach($section['tables'] as $table)
                                <div class="report-table"><table><caption>{{ $table['title'] }}</caption>
                                    <thead><tr>@foreach($table['headers'] as $header)<th scope="col">{{ $header }}</th>@endforeach</tr></thead>
                                    <tbody>
                                    @forelse($table['rows'] as $row)
                                        <tr>@foreach($row as $cell)<td>{{ is_int($cell) || is_float($cell) ? number_format($cell, floor($cell) == $cell ? 0 : 1, ',', ' ') : $cell }}</td>@endforeach</tr>
                                    @empty
                                        <tr><td colspan="{{ count($table['headers']) }}">Aucune donnée pour cette sélection.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table></div>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </section>
    @endforeach
    <p class="report-note">Rapport généré le {{ now()->format('d/m/Y à H:i') }} ({{ config('app.timezone') }}). Les états et soldes correspondent aux données actuelles, même pour une période passée.</p>
    @endif
</main>
@endsection

@push('styles')
<style>
.module-directory{margin-top:28px}.module-directory h2{font-size:22px;font-weight:700}.module-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:16px}.module-card{display:flex;flex-direction:column;gap:18px;padding:24px;background:#fff;border:1px solid #e1e7ed;border-radius:12px;color:#203149;text-decoration:none}.module-card strong{font-size:17px}.module-card span{color:#16794a;font-size:13px}.module-card:hover,.module-card:focus-visible{border-color:#16794a;background:#f5f9f7}.module-card:focus-visible{outline:2px solid #16794a;outline-offset:3px}
.housing-report{max-width:1500px;margin:auto;padding:24px;color:#203149}.report-heading{display:flex;justify-content:space-between;align-items:center;gap:18px;margin:20px 0}.report-heading h1{font-size:27px;font-weight:700;margin:7px 0}.report-eyebrow{color:#16804b;font-size:12px;text-transform:uppercase;letter-spacing:1px}.report-filters{display:flex;flex-wrap:wrap;gap:14px;align-items:end;padding:18px;background:#fff;border:1px solid #e1e7ed;border-radius:12px}.report-filters label{display:flex;flex-direction:column;gap:5px;font-size:12px}.report-filters select,.report-filters input{border:1px solid #cbd5e1;border-radius:6px;padding:9px;background:white;color:#203149}.report-button{padding:10px 18px;border:0;border-radius:7px;background:#16794a;color:white;cursor:pointer}.report-modules{display:flex;flex-wrap:wrap;gap:8px;margin:22px 0}.report-modules a{padding:9px 12px;border-radius:7px;background:#edf2f7;color:#334155}.report-modules a.selected{background:#203149;color:#fff}.module-report h2{font-size:22px;margin:28px 0 12px;font-weight:700}.report-panel{background:white;border:1px solid #e1e7ed;border-radius:12px;padding:22px;margin:14px 0}.report-panel h3{font-size:17px;font-weight:600}.report-note{color:#64748b;font-size:12px;line-height:1.7;margin:8px 0 18px}.report-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}.report-kpis>div{padding:18px;border-left:3px solid #1f8a59;background:#f5f9f7;border-radius:6px}.report-kpis span{display:block;font-size:12px;min-height:32px}.report-kpis strong{display:block;font-size:25px;margin-top:8px}.report-tables{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:24px;margin-top:18px}.report-table{overflow:auto} .report-table table{width:100%;font-size:12px;border-collapse:collapse}.report-table caption{caption-side:top;font-weight:600;color:#203149;text-align:left;padding:10px 0}.report-table th,.report-table td{text-align:left;padding:10px;border-bottom:1px solid #eef2f6}.report-table th{background:#f6f8fb;font-weight:600}.report-table tbody tr:hover{background:#f8fafc}.report-warning{color:#8a5a00;background:#fff8e6;border:1px solid #f5d78e;border-radius:8px;padding:12px 14px;margin:10px 0}@media print{.report-filters,.report-modules,.report-button{display:none!important}.housing-report{padding:0}.report-panel{break-inside:avoid}}
</style>
@endpush
@push('scripts')
<script>
document.querySelectorAll('.report-filters input[type="date"]').forEach(input => {
    input.addEventListener('change', () => { document.getElementById('report-period').value = 'custom'; });
});
</script>
@endpush



