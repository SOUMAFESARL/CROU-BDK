@extends('admin.layouts.app', ['activeMenu' => 'dashboard', 'activeSubMenu' => null])

@section('title', 'Tableau de bord')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <div class="eyebrow">{{ $dashboardAccess['roleName'] }}</div>
            <h1>Mon tableau de bord</h1>
            <p>Les indicateurs et les accès correspondant aux permissions de votre rôle.</p>
        </div>
        @adminroute('admin.dashboard.index')
        <form method="GET" action="{{ route('admin.dashboard.index') }}" class="period-form">
            <i class="feather icon-calendar"></i>
            <select name="periode" onchange="this.form.submit()" aria-label="Choisir une période">
                <option value="week" @selected($period === 'week')>Cette semaine</option>
                <option value="month" @selected($period === 'month')>Ce mois</option>
                <option value="quarter" @selected($period === 'quarter')>Ce trimestre</option>
                <option value="year" @selected($period === 'year')>Cette année</option>
            </select>
        </form>
        @endadminroute
    </div>

    <div class="date-context">
        <span class="live-dot"></span>Données actualisées
        <span class="date-divider"></span>
        {{ $start->translatedFormat('d M Y') }} — {{ $end->translatedFormat('d M Y') }}
    </div>

    <div class="metric-grid">
        @if($dashboardAccess['students'])
        <article class="metric-card metric-primary">
            <div class="metric-top"><span class="metric-icon"><i class="fa-solid fa-user-graduate"></i></span><span class="metric-tag">Étudiants</span></div>
            <strong>{{ number_format($students, 0, ',', ' ') }}</strong>
            <p>Étudiants enregistrés</p>
        </article>
        @endif
        @if($dashboardAccess['housing'])
        <article class="metric-card metric-green">
            <div class="metric-top"><span class="metric-icon"><i class="fa-solid fa-bed"></i></span><span class="metric-tag">Hébergement</span></div>
            <strong>{{ number_format($occupiedBeds, 0, ',', ' ') }}</strong>
            <p>Résidents actifs <span>{{ $occupancyRate }}% d’occupation</span></p>
        </article>
        @endif
        @if($dashboardAccess['admissions'])
        <article class="metric-card metric-amber">
            <div class="metric-top"><span class="metric-icon"><i class="fa-solid fa-file-circle-check"></i></span><span class="metric-tag">Admissions</span></div>
            <strong>{{ number_format($pendingRequests, 0, ',', ' ') }}</strong>
            <p>Demandes à traiter</p>
        </article>
        @endif
        @if($dashboardAccess['finance'])
        <article class="metric-card metric-purple">
            <div class="metric-top"><span class="metric-icon"><i class="fa-solid fa-wallet"></i></span><span class="metric-tag">Finances</span></div>
            <strong>{{ formatSystemAmount($revenue) }}</strong>
            <p>Encaissements sur la période</p>
        </article>
        @endif
        @if($dashboardAccess['maintenance'])
        <article class="metric-card metric-amber">
            <div class="metric-top"><span class="metric-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span><span class="metric-tag">Maintenance</span></div>
            <strong>{{ $openWorkOrders }}</strong><p>Interventions ouvertes · {{ $urgentWorkOrders }} urgentes</p>
        </article>
        @endif
    </div>

    @unless($dashboardAccess['housing'] || $dashboardAccess['admissions'] || $dashboardAccess['finance'] || $dashboardAccess['maintenance'])
        <section class="panel"><h2>Aucun indicateur disponible pour votre rôle</h2><p>Vos accès sont définis dans les rôles et permissions par l’administrateur.</p></section>
    @endunless
    <div class="dashboard-grid dashboard-grid-main">
        @if($dashboardAccess['admissions'] || $dashboardAccess['finance'])
        <section class="panel trend-panel">
            <div class="panel-heading">
                <div><span class="panel-kicker">Activité</span><h2>{{ $dashboardAccess['admissions'] && $dashboardAccess['finance'] ? 'Évolution des demandes et encaissements' : ($dashboardAccess['finance'] ? 'Évolution des encaissements' : 'Évolution des demandes') }}</h2></div>
                <span class="legend-note"><i></i> Données de la période</span>
            </div>
            <div id="activityChart" class="chart-area"></div>
        </section>

        @endif
        @if($dashboardAccess['housing'])
        <section class="panel occupancy-panel">
            <div class="panel-heading"><div><span class="panel-kicker">Capacité</span><h2>Occupation globale</h2></div></div>
            <div id="occupancyChart" class="donut-chart"></div>
            <div class="occupancy-summary">
                <div><span class="summary-dot occupied"></span><strong>{{ $occupiedBeds }}</strong><small>Occupés</small></div>
                <div><span class="summary-dot free"></span><strong>{{ $availableBeds }}</strong><small>Disponibles</small></div>
                <div><span class="summary-dot total"></span><strong>{{ $capacity }}</strong><small>Capacité</small></div>
            </div>
        </section>
        @endif
    </div>

    <div class="module-strip">
        @if($dashboardAccess['parc'])@adminroute('admin.hebergement.parc-immobilier.index')
<a href="{{ route('admin.hebergement.parc-immobilier.index') }}" class="module-card"><span class="module-icon blue"><i class="fa-solid fa-building"></i></span><div><small>Parc immobilier</small><strong>{{ $capacity }} lits</strong></div><i class="feather icon-arrow-up-right"></i></a>
@endadminroute
@endif
        @if($dashboardAccess['admissions'])@adminroute('admin.hebergement.campagnes_admission.admission')
<a href="{{ route('admin.hebergement.campagnes_admission.admission') }}" class="module-card"><span class="module-icon amber"><i class="fa-solid fa-file-signature"></i></span><div><small>Admissions</small><strong>{{ $pendingRequests }} en attente</strong></div><i class="feather icon-arrow-up-right"></i></a>
@endadminroute
@endif
        @if($dashboardAccess['finance'])@adminroute('admin.hebergement.paiements.index')
<a href="{{ route('admin.hebergement.paiements.index') }}" class="module-card"><span class="module-icon green"><i class="fa-solid fa-money-check-dollar"></i></span><div><small>Paiements</small><strong>{{ formatSystemAmount($revenue) }}</strong></div><i class="feather icon-arrow-up-right"></i></a>
@endadminroute
@endif
        @if($dashboardAccess['maintenance'])@adminroute('admin.hebergement.maintenance.index')
<a href="{{ route('admin.hebergement.maintenance.index') }}" class="module-card"><span class="module-icon red"><i class="fa-solid fa-screwdriver-wrench"></i></span><div><small>Maintenance</small><strong>{{ $openWorkOrders }} intervention{{ $openWorkOrders > 1 ? 's' : '' }}</strong></div><i class="feather icon-arrow-up-right"></i></a>
@endadminroute
@endif
    </div>

    <div class="dashboard-grid dashboard-grid-bottom">
        @if($dashboardAccess['housing'])<section class="panel residence-panel">
            <div class="panel-heading">
                <div><span class="panel-kicker">Hébergement</span><h2>Occupation par cité</h2></div>
                @if($dashboardAccess['parc'])@adminroute('admin.hebergement.parc-immobilier.index')
<a href="{{ route('admin.hebergement.parc-immobilier.index') }}">Voir le parc <i class="feather icon-arrow-right"></i></a>
@endadminroute
@endif
            </div>
            <div class="residence-list">
                @forelse($residences as $residence)
                    <div class="residence-row">
                        <div class="residence-name"><span>{{ mb_substr($residence['name'], 0, 2) }}</span><div><strong>{{ $residence['name'] }}</strong><small>{{ $residence['occupied'] }} / {{ $residence['capacity'] }} lits</small></div></div>
                        <div class="progress-wrap"><div class="progress-track"><span style="width: {{ $residence['rate'] }}%"></span></div><strong>{{ $residence['rate'] }}%</strong></div>
                    </div>
                @empty
                    <div class="empty-state"><i class="fa-regular fa-building"></i><p>Aucune cité configurée pour le moment.</p></div>
                @endforelse
            </div>
        </section>

        @endif @if($dashboardAccess['admissions'])<section class="panel request-panel">
            <div class="panel-heading"><div><span class="panel-kicker">Admissions</span><h2>Répartition des demandes</h2></div></div>
            <div id="requestsChart" class="request-chart"></div>
            <div class="request-total"><strong>{{ array_sum($requestStatuses['values']) }}</strong><span>demandes au total</span></div>
        </section>

        @endif @if($dashboardAccess['parc'] || $dashboardAccess['admissions'] || $dashboardAccess['maintenance'])<section class="panel alert-panel">
            <div class="panel-heading"><div><span class="panel-kicker">À surveiller</span><h2>Points d’attention</h2></div></div>
            <div class="alert-list">
                @if($dashboardAccess['maintenance'])@adminroute('admin.hebergement.maintenance.index')
<a href="{{ route('admin.hebergement.maintenance.index') }}" class="alert-row {{ $urgentWorkOrders ? 'is-urgent' : '' }}">
                    <span><i class="fa-solid fa-triangle-exclamation"></i></span><div><strong>{{ $urgentWorkOrders }} intervention{{ $urgentWorkOrders > 1 ? 's' : '' }} urgente{{ $urgentWorkOrders > 1 ? 's' : '' }}</strong><small>Maintenance technique</small></div><i class="feather icon-chevron-right"></i>
                </a>
                @endadminroute
@endif
                @if($dashboardAccess['admissions'])@adminroute('admin.hebergement.campagnes_admission.admission')
<a href="{{ route('admin.hebergement.campagnes_admission.admission') }}" class="alert-row">
                    <span><i class="fa-regular fa-clock"></i></span><div><strong>{{ $pendingRequests }} dossier{{ $pendingRequests > 1 ? 's' : '' }} à traiter</strong><small>Campagnes et admissions</small></div><i class="feather icon-chevron-right"></i>
                </a>
                @endadminroute
@endif
                @if($dashboardAccess['parc'])@adminroute('admin.hebergement.parc-immobilier.index')
<a href="{{ route('admin.hebergement.parc-immobilier.index') }}" class="alert-row">
                    <span><i class="fa-solid fa-bed"></i></span><div><strong>{{ $availableBeds }} place{{ $availableBeds > 1 ? 's' : '' }} disponible{{ $availableBeds > 1 ? 's' : '' }}</strong><small>Capacité d’accueil actuelle</small></div><i class="feather icon-chevron-right"></i>
                </a>
                @endadminroute
@endif
            </div>
        </section>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .dashboard-shell .metric-grid,.dashboard-shell .module-strip,.dashboard-shell .dashboard-grid{grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr))}
    :root{--dash-ink:#18233b;--dash-muted:#778198;--dash-line:#e9edf4;--dash-blue:#315efb;--dash-bg:#f5f7fb}
    .page-wrapper{background:var(--dash-bg);min-height:calc(100vh - 70px);padding:0!important}.dashboard-shell{padding:30px;max-width:1600px;margin:auto;font-family:Montserrat,sans-serif;color:var(--dash-ink)}
    .dashboard-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:24px}.eyebrow,.panel-kicker{color:var(--dash-blue);font-weight:700;font-size:var(--app-font-min, 15px);text-transform:uppercase;letter-spacing:1.2px}.dashboard-heading h1{font-size:28px;font-weight:750;margin:5px 0 4px;color:var(--dash-ink)}.dashboard-heading p{color:var(--dash-muted);margin:0;font-size:var(--app-font-min, 15px)}.period-form{height:42px;display:flex;align-items:center;gap:9px;padding:0 13px;border:1px solid var(--dash-line);border-radius:11px;background:#fff;box-shadow:0 5px 16px rgba(31,45,75,.04)}.period-form select{border:0;background:transparent;outline:0;color:var(--dash-ink);font-size:var(--app-font-min, 15px);font-weight:600}.date-context{display:flex;align-items:center;gap:8px;color:#8b94a8;font-size:var(--app-font-min, 15px);margin-top:13px}.live-dot{width:7px;height:7px;border-radius:50%;background:#21b573;box-shadow:0 0 0 4px rgba(33,181,115,.12)}.date-divider{height:12px;width:1px;background:#d9deea;margin:0 3px}
    .metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:24px 0 16px}.metric-card,.panel,.module-card{background:#fff;border:1px solid var(--dash-line);border-radius:15px;box-shadow:0 7px 24px rgba(30,45,75,.045)}.metric-card{padding:18px 19px;position:relative;overflow:hidden}.metric-card:after{content:"";position:absolute;inset:auto -25px -38px auto;width:90px;height:90px;border-radius:50%;background:currentColor;opacity:.035}.metric-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:17px}.metric-icon{display:grid;place-items:center;width:38px;height:38px;border-radius:10px;font-size:15px}.metric-tag{text-transform:uppercase;letter-spacing:.7px;font-size:var(--app-font-min, 15px);font-weight:700;color:#9aa3b5}.metric-card strong{display:block;font-size:24px;line-height:1.1;color:var(--dash-ink);font-weight:750}.metric-card strong small{font-size:var(--app-font-min, 15px);font-weight:650}.metric-card p{font-size:var(--app-font-min, 15px);color:var(--dash-muted);margin:7px 0 0}.metric-card p span{color:#21a66f;font-weight:650;margin-left:5px}.metric-primary{border-top:3px solid #315efb}.metric-primary .metric-icon{color:#315efb;background:#edf1ff}.metric-green{border-top:3px solid #20b477}.metric-green .metric-icon{color:#20a970;background:#eaf9f3}.metric-amber{border-top:3px solid #eea526}.metric-amber .metric-icon{color:#d99012;background:#fff6e5}.metric-purple{border-top:3px solid #7758d8}.metric-purple .metric-icon{color:#6d4fc8;background:#f1edff}
    .dashboard-grid{display:grid;gap:16px}.dashboard-grid-main{grid-template-columns:minmax(0,1.8fr) minmax(310px,.8fr)}.panel{padding:20px;min-width:0}.trend-panel{overflow:hidden}.panel-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:15px}.panel-heading h2{font-size:var(--app-font-min, 15px);font-weight:700;margin:4px 0 0;color:var(--dash-ink)}.panel-heading a{font-size:var(--app-font-min, 15px);color:var(--dash-blue);text-decoration:none;font-weight:650}.legend-note{font-size:var(--app-font-min, 15px);color:var(--dash-muted)}.legend-note i{display:inline-block;width:7px;height:7px;border-radius:2px;background:var(--dash-blue)}.chart-area{height:320px;width:100%;min-width:0;margin-top:6px;padding-bottom:8px}.chart-area .apexcharts-canvas,.chart-area svg{max-width:100%!important}.chart-area .apexcharts-xaxis,.chart-area .apexcharts-xaxis-texts-g{visibility:visible!important;opacity:1!important}.donut-chart{height:245px}.occupancy-summary{border-top:1px solid var(--dash-line);display:grid;grid-template-columns:repeat(3,1fr);padding-top:14px}.occupancy-summary div{position:relative;padding-left:12px}.occupancy-summary strong,.occupancy-summary small{display:block}.occupancy-summary strong{font-size:var(--app-font-min, 15px)}.occupancy-summary small{font-size:var(--app-font-min, 15px);color:var(--dash-muted)}.summary-dot{position:absolute;width:5px;height:18px;border-radius:4px;left:0;top:1px}.summary-dot.occupied{background:#315efb}.summary-dot.free{background:#dfe5f1}.summary-dot.total{background:#20b477}
    .module-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:16px 0}.module-card{padding:13px 14px;display:flex;align-items:center;gap:11px;text-decoration:none;color:var(--dash-ink);transition:.2s}.module-card:hover{color:var(--dash-ink);transform:translateY(-2px);box-shadow:0 10px 25px rgba(30,45,75,.09)}.module-card>i{margin-left:auto;color:#a5adbc}.module-icon{width:35px;height:35px;border-radius:9px;display:grid;place-items:center}.module-icon.blue{background:#edf1ff;color:#315efb}.module-icon.amber{background:#fff6e5;color:#dc9318}.module-icon.green{background:#eaf9f3;color:#1ca16b}.module-icon.red{background:#fff0ef;color:#e45851}.module-card small,.module-card strong{display:block}.module-card small{font-size:var(--app-font-min, 15px);color:var(--dash-muted);margin-bottom:2px}.module-card strong{font-size:var(--app-font-min, 15px)}
    .dashboard-grid-bottom{grid-template-columns:1.35fr .75fr .9fr}.residence-list{margin-top:18px}.residence-row{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:10px 0;border-bottom:1px solid #f0f2f6}.residence-row:last-child{border:0}.residence-name{display:flex;align-items:center;gap:10px;min-width:160px}.residence-name>span{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;text-transform:uppercase;background:#eef2ff;color:#315efb;font-size:var(--app-font-min, 15px);font-weight:750}.residence-name strong,.residence-name small{display:block}.residence-name strong{font-size:var(--app-font-min, 15px)}.residence-name small{font-size:var(--app-font-min, 15px);color:var(--dash-muted);margin-top:2px}.progress-wrap{display:flex;align-items:center;gap:9px;flex:1}.progress-track{height:6px;background:#edf0f5;border-radius:10px;overflow:hidden;flex:1}.progress-track span{display:block;height:100%;background:linear-gradient(90deg,#315efb,#6f8cff);border-radius:10px}.progress-wrap>strong{width:29px;font-size:var(--app-font-min, 15px);text-align:right}.request-panel{position:relative}.request-chart{height:245px}.request-total{position:absolute;left:50%;top:56%;transform:translate(-50%,-50%);text-align:center;pointer-events:none}.request-total strong,.request-total span{display:block}.request-total strong{font-size:23px}.request-total span{font-size:var(--app-font-min, 15px);color:var(--dash-muted)}.alert-list{margin-top:14px}.alert-row{display:flex;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid #f0f2f6;text-decoration:none;color:var(--dash-ink)}.alert-row:last-child{border:0}.alert-row>span{width:31px;height:31px;border-radius:9px;background:#f1f4f9;color:#6c7890;display:grid;place-items:center;font-size:var(--app-font-min, 15px)}.alert-row.is-urgent>span{background:#fff0ef;color:#e45851}.alert-row>div{flex:1}.alert-row strong,.alert-row small{display:block}.alert-row strong{font-size:var(--app-font-min, 15px)}.alert-row small{font-size:var(--app-font-min, 15px);color:var(--dash-muted);margin-top:3px}.alert-row>i{font-size:var(--app-font-min, 15px);color:#aab2c1}.empty-state{text-align:center;padding:36px 15px;color:#a0a8b8}.empty-state i{font-size:24px}.empty-state p{font-size:var(--app-font-min, 15px);margin:8px 0}
    @media(max-width:1200px){.metric-grid,.module-strip{grid-template-columns:repeat(2,1fr)}.dashboard-grid-bottom{grid-template-columns:1fr 1fr}.alert-panel{grid-column:1/-1}}
    @media(max-width:800px){.dashboard-shell{padding:20px 14px}.dashboard-heading{align-items:flex-start;flex-direction:column}.dashboard-grid-main,.dashboard-grid-bottom{grid-template-columns:1fr}.alert-panel{grid-column:auto}.metric-grid{grid-template-columns:1fr 1fr}.module-strip{grid-template-columns:1fr 1fr}}
    @media(max-width:520px){.metric-grid,.module-strip{grid-template-columns:1fr}.dashboard-heading h1{font-size:23px}.period-form{width:100%}.period-form select{flex:1}.chart-area{height:300px}.trend-panel{padding-left:12px;padding-right:12px}}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Le layout peut réévaluer les scripts de page lors d'une navigation interne.
    // Ce verrou empêche la création d'une deuxième instance dans les mêmes conteneurs.
    const dashboardRoot = document.querySelector('.dashboard-shell');
    if (!dashboardRoot || dashboardRoot.dataset.chartsInitialized === 'true') return;
    dashboardRoot.dataset.chartsInitialized = 'true';

    const ink = '#778198', grid = '#eef1f6';
    const money = value => new Intl.NumberFormat('fr-FR', {notation:'compact', maximumFractionDigits:1}).format(value) + ' F';
    const requestSeries = @json($trend['requests']);
    const paymentSeries = @json($trend['payments']);
    const requestMax = Math.max(...requestSeries.map(Number), 0);
    const paymentMax = Math.max(...paymentSeries.map(Number), 0);
    const requestCeiling = Math.max(5, Math.ceil(requestMax * 1.2));
    const paymentStep = Math.max(1000, Math.pow(10, Math.max(3, String(Math.ceil(paymentMax || 1)).length - 1)));
    const paymentCeiling = Math.max(paymentStep, Math.ceil((paymentMax * 1.15) / paymentStep) * paymentStep);
    @if($dashboardAccess['admissions'] || $dashboardAccess['finance'])
    const activityContainer = document.querySelector('#activityChart');
    const activityChart = new ApexCharts(activityContainer, {
        series: [
            @if($dashboardAccess['admissions']) {name:'Demandes', type:'area', data:requestSeries}, @endif
            @if($dashboardAccess['finance']) {name:'Encaissements', type:'column', data:paymentSeries} @endif
        ],
        chart:{height:'100%',width:'100%',type:'line',toolbar:{show:false},fontFamily:'Montserrat',redrawOnParentResize:true,redrawOnWindowResize:true,parentHeightOffset:0},
        colors:['#315efb','#20b477'],stroke:{width:[3,0],curve:'smooth'},fill:{opacity:[.13,.9]},
        plotOptions:{bar:{columnWidth:'38%',borderRadius:4}},dataLabels:{enabled:false},
        xaxis:{categories:@json($trend['labels']),tickPlacement:'on',labels:{show:true,rotate:-30,rotateAlways:false,hideOverlappingLabels:true,trim:false,minHeight:34,maxHeight:48,offsetY:1,style:{colors:ink,fontSize:'9px'}},axisBorder:{show:true,color:'#dfe4ed',height:1},axisTicks:{show:true,color:'#dfe4ed',height:3}},
        yaxis:[
            @if($dashboardAccess['admissions'])
            {min:0,max:requestCeiling,forceNiceScale:false,tickAmount:4,decimalsInFloat:0,labels:{minWidth:20,maxWidth:34,style:{colors:ink,fontSize:'9px'},formatter:v=>Math.round(v)}},
            @endif
            @if($dashboardAccess['finance'])
            {min:0,max:paymentCeiling,forceNiceScale:false,tickAmount:4,opposite:@json($dashboardAccess['admissions']),labels:{minWidth:34,maxWidth:55,style:{colors:ink,fontSize:'9px'},formatter:money}}
            @endif
        ],
        grid:{borderColor:grid,strokeDashArray:4,padding:{left:4,right:8,top:0,bottom:14}},legend:{position:'top',horizontalAlign:'left',fontSize:'10px',markers:{size:5}},tooltip:{shared:true,intersect:false},
        responsive:[{breakpoint:600,options:{legend:{fontSize:'9px'},plotOptions:{bar:{columnWidth:'52%'}}}}]
    });
    activityChart.render().then(() => {
        // La largeur disponible change après l'ouverture de la barre latérale.
        requestAnimationFrame(() => activityChart.updateOptions({
            chart: { width: Math.floor(activityContainer.getBoundingClientRect().width) }
        }, false, false));
    });

    const activityResizeObserver = new ResizeObserver(entries => {
        const width = Math.floor(entries[0].contentRect.width);
        if (width > 0) activityChart.updateOptions({ chart: { width } }, false, false);
    });
    activityResizeObserver.observe(activityContainer);

    @endif
    @if($dashboardAccess['housing'])
    const occupancyContainer = document.querySelector('#occupancyChart');
    occupancyContainer.replaceChildren();
    new ApexCharts(occupancyContainer, {
        series:[{{ $occupiedBeds }},{{ $availableBeds }}],labels:['Occupés','Disponibles'],chart:{type:'donut',height:245,fontFamily:'Montserrat'},
        colors:['#315efb','#e5e9f1'],stroke:{width:0},dataLabels:{enabled:false},legend:{show:false},
        plotOptions:{pie:{donut:{size:'75%',labels:{show:true,name:{show:false},value:{show:false},total:{show:true,label:'TAUX',fontSize:'9px',color:ink,formatter:()=> '{{ $occupancyRate }}%'}}}}}
    }).render();

    @endif
    @if($dashboardAccess['admissions'])
    new ApexCharts(document.querySelector('#requestsChart'), {
        series:@json($requestStatuses['values']),labels:@json($requestStatuses['labels']),chart:{type:'donut',height:245,fontFamily:'Montserrat'},
        colors:['#f0a529','#315efb','#20b477','#e45851'],stroke:{width:3,colors:['#fff']},dataLabels:{enabled:false},
        plotOptions:{pie:{donut:{size:'72%'}}},legend:{position:'bottom',fontSize:'9px',markers:{size:5},itemMargin:{horizontal:5}}
    }).render();

    @endif
});
</script>
@endpush
