@extends('admin.layouts.app', ['activeMenu'=>'parametres','activeSubMenu'=>'modules'])
@section('title','Gestion des modules')
@section('content')
<div class="page-body">
<x-breadcrumbs title="Gestion des modules" :items="['Gestion Paramétrage'=>route('admin.parametrage.index'),'Gestion des modules'=>null]" />
<div class="module-page">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="module-summary">
        <div><span class="eyebrow"><i class="fa-solid fa-sitemap"></i> Architecture fonctionnelle</span><h2>Modules de l’application</h2><p>Organisez l’arborescence, les routes et les actions disponibles pour construire les permissions.</p></div>
        <div class="summary-counts"><article><strong>{{ $allModules->count() }}</strong><small>Modules</small></article><article><strong>{{ $allModules->where('is_active',true)->count() }}</strong><small>Actifs</small></article><article><strong>{{ $allModules->sum(fn($m)=>count($m->actions ?? [])) }}</strong><small>Actions</small></article></div>
    </section>

    <section class="module-toolbar"><div class="module-search"><i class="fa-solid fa-magnifying-glass"></i><input id="moduleSearch" placeholder="Rechercher un module, une route ou un identifiant..."></div><div><button class="btn btn-outline-secondary" id="expandAll"><i class="fa-solid fa-angles-down"></i> Tout déplier</button>@adminroute('admin.parametrage.modules.store')
<button class="btn btn-primary" data-open-module="create"><i class="fa-solid fa-plus"></i> Ajouter un module</button>
@endadminroute
</div></section>

    <section class="module-tree">
        @forelse($modules as $root)
        <article class="module-root module-search-item collapsed" data-search="{{ Str::lower($root->name.' '.$root->slug.' '.$root->route_name) }}">
            <div class="module-root-head">
                <button class="tree-toggle" aria-label="Déplier"><i class="fa-solid fa-chevron-down"></i></button>
                <span class="module-icon"><i class="{{ $root->icon ?: 'fa-solid fa-cube' }}"></i></span>
                <div class="module-identity"><h3>{{ $root->name }} @if($root->is_system)<span>Système</span>@endif</h3><code>{{ $root->slug }}</code><small>{{ $root->route_name ?: 'Aucune route' }}</small></div>
                <div class="module-meta"><span class="status {{ $root->is_active?'active':'inactive' }}">{{ $root->is_active?'Actif':'Inactif' }}</span><span>{{ $root->children->count() }} sous-module(s)</span><span>Position {{ $root->position }}</span></div>
                <div class="module-actions">@adminroute('admin.parametrage.modules.store')
<button title="Ajouter un enfant" data-open-module="create" data-parent="{{ $root->id }}"><i class="fa-solid fa-plus"></i></button>
@endadminroute
@adminroute('admin.parametrage.modules.update')
<button title="Modifier" data-open-module="edit{{ $root->id }}"><i class="fa-solid fa-pen"></i></button>
@endadminroute
@adminroute('admin.parametrage.modules.toggle')
<form method="POST" action="{{ route('admin.parametrage.modules.toggle',$root) }}">@csrf @method('PATCH')<button title="{{ $root->is_active?'Désactiver':'Activer' }}"><i class="fa-solid fa-power-off"></i></button></form>
@endadminroute</div>
            </div>
            <div class="module-children">
                @forelse($root->children as $child)
                <div class="module-child module-search-item" data-search="{{ Str::lower($child->name.' '.$child->slug.' '.$child->route_name) }}">
                    <span class="tree-line"></span><span class="module-icon child"><i class="{{ $child->icon ?: 'fa-regular fa-folder' }}"></i></span>
                    <div class="module-identity"><h4>{{ $child->name }} @if($child->is_system)<span>Système</span>@endif</h4><code>{{ $child->slug }}</code><small>{{ $child->route_name ?: 'Aucune route' }}</small><div class="action-chips">@foreach($child->actions ?? [] as $action)<span>{{ $availableActions[$action] ?? $action }}</span>@endforeach</div></div>
                    <div class="module-meta"><span class="status {{ $child->is_active?'active':'inactive' }}">{{ $child->is_active?'Actif':'Inactif' }}</span>@if($child->show_in_menu)<span><i class="fa-solid fa-eye"></i> Menu</span>@endif<span>Position {{ $child->position }}</span></div>
                    <div class="module-actions">@adminroute('admin.parametrage.modules.store')
<button data-open-module="create" data-parent="{{ $child->id }}" title="Ajouter un enfant"><i class="fa-solid fa-plus"></i></button>
@endadminroute
@adminroute('admin.parametrage.modules.update')
<button data-open-module="edit{{ $child->id }}" title="Modifier"><i class="fa-solid fa-pen"></i></button>
@endadminroute
@adminroute('admin.parametrage.modules.toggle')
<form method="POST" action="{{ route('admin.parametrage.modules.toggle',$child) }}">@csrf @method('PATCH')<button title="Activer/Désactiver"><i class="fa-solid fa-power-off"></i></button></form>
@endadminroute
@unless($child->is_system)@adminroute('admin.parametrage.modules.destroy')
<form method="POST" action="{{ route('admin.parametrage.modules.destroy',$child) }}" onsubmit="return confirm('Supprimer ce module et ses permissions non utilisées ?')">@csrf @method('DELETE')<button class="delete" title="Supprimer"><i class="fa-solid fa-trash"></i></button></form>
@endadminroute
@endunless</div>
                </div>
                @empty<div class="module-empty">Aucun sous-module.</div>@endforelse
            </div>
        </article>
        @empty<div class="module-empty large">Aucun module configuré.</div>@endforelse
    </section>
</div>

<div id="moduleModalcreate" class="module-modal" hidden><div class="module-overlay" data-close-module></div><div class="module-modal-card"><header><div><span>Nouveau module</span><h3>Ajouter à l’arborescence</h3></div><button data-close-module>&times;</button></header>@adminroute('admin.parametrage.modules.store')
<form method="POST" action="{{ route('admin.parametrage.modules.store') }}">@csrf<div class="module-modal-body">@include('admin.parametrage.modules.partials.form-fields')</div><footer><button type="button" class="btn btn-secondary" data-close-module>Annuler</button><button class="btn btn-primary">Créer le module</button></footer></form>
@endadminroute</div></div>
@foreach($allModules as $module)
<div id="moduleModaledit{{ $module->id }}" class="module-modal" hidden><div class="module-overlay" data-close-module></div><div class="module-modal-card"><header><div><span>Modification</span><h3>{{ $module->name }}</h3></div><button data-close-module>&times;</button></header>@adminroute('admin.parametrage.modules.update')
<form method="POST" action="{{ route('admin.parametrage.modules.update',$module) }}">@csrf @method('PUT')<div class="module-modal-body">@include('admin.parametrage.modules.partials.form-fields',['module'=>$module])</div><footer><button type="button" class="btn btn-secondary" data-close-module>Annuler</button><button class="btn btn-primary">Enregistrer</button></footer></form>
@endadminroute</div></div>
@endforeach
</div>
@endsection

@push('styles')<style>
.module-page{padding:20px 0 40px;color:#374151}.module-summary,.module-toolbar,.module-root{background:#fff;border-radius:20px;box-shadow:0 8px 24px rgba(0,0,0,.08)}.module-summary{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;margin-bottom:14px}.module-summary .eyebrow{font-size:var(--app-font-min, 15px);text-transform:uppercase;color:#1F7C43;font-weight:600}.module-summary h2{font-size:18px;margin:5px 0;color:#1f2937}.module-summary p{font-size:var(--app-font-min, 15px);color:#6b7280;margin:0}.summary-counts{display:flex;gap:9px}.summary-counts article{min-width:85px;padding:9px 13px;text-align:center;background:#f5faf7;border:1px solid #dcece2;border-radius:14px}.summary-counts strong,.summary-counts small{display:block}.summary-counts strong{font-size:18px;color:#1F7C43}.summary-counts small{font-size:var(--app-font-min, 15px);text-transform:uppercase;color:#6b7280}.module-toolbar{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;margin-bottom:14px}.module-search{position:relative;width:min(460px,100%)}.module-search i{position:absolute;left:13px;top:13px;color:#9ca3af}.module-search input{width:100%;height:42px;border:1px solid #d1d5db;border-radius:20px;padding:0 15px 0 37px;font-size:var(--app-font-min, 15px)}.module-search input:focus{outline:0;border-color:#1F7C43;box-shadow:0 0 0 3px rgba(31,124,67,.12)}.module-toolbar>div:last-child{display:flex;gap:8px}.module-root{overflow:hidden;margin-bottom:13px}.module-root-head,.module-child{display:grid;grid-template-columns:30px 42px minmax(250px,1fr) auto auto;align-items:center;gap:10px;padding:15px 18px}.module-root-head{border-bottom:1px solid #edf0f3}.tree-toggle{border:0;background:#f3f4f6;color:#6b7280;width:28px;height:28px;border-radius:50%;transition:.2s}.module-root.collapsed .tree-toggle{transform:rotate(-90deg)}.module-root.collapsed .module-children{display:none}.module-icon{width:40px;height:40px;display:grid;place-items:center;border-radius:12px;background:#eaf5ee;color:#1F7C43;font-size:16px}.module-icon.child{width:35px;height:35px;background:#f3f4f6;color:#6b7280}.module-identity h3,.module-identity h4{font-size:16px;margin:0 0 3px;color:#1f2937}.module-identity h4{font-size:15px}.module-identity h3 span,.module-identity h4 span{font-size:var(--app-font-min, 15px);color:#1F7C43;background:#eaf5ee;border-radius:10px;padding:3px 6px;margin-left:5px}.module-identity code{font-size:var(--app-font-min, 15px);color:#1F7C43;background:none;padding:0}.module-identity small{display:block;color:#9ca3af;font-size:var(--app-font-min, 15px);margin-top:2px}.module-meta{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.module-meta>span{font-size:var(--app-font-min, 15px);padding:5px 7px;background:#f3f4f6;border-radius:10px;color:#6b7280}.module-meta .status.active{background:#eaf5ee;color:#1F7C43}.module-meta .status.inactive{background:#fef2f2;color:#dc2626}.module-actions{display:flex;gap:4px}.module-actions form{margin:0}.module-actions button{width:30px;height:30px;border:0;border-radius:7px;background:#f3f4f6;color:#4b5563}.module-actions button:hover{background:#eaf5ee;color:#1F7C43}.module-actions button.delete:hover{background:#fef2f2;color:#dc2626}.module-children{padding:5px 18px 12px 60px}.module-child{grid-template-columns:16px 37px minmax(230px,1fr) auto auto;padding:11px 5px;border-bottom:1px solid #f0f2f4}.module-child:last-child{border-bottom:0}.tree-line{width:13px;height:23px;border-left:1px solid #d1d5db;border-bottom:1px solid #d1d5db;margin-top:-20px}.action-chips{display:flex;gap:4px;flex-wrap:wrap;margin-top:6px}.action-chips span{font-size:var(--app-font-min, 15px);padding:3px 6px;border-radius:8px;background:#f5faf7;color:#1F7C43;border:1px solid #dcece2}.module-empty{text-align:center;padding:15px;color:#9ca3af;font-size:var(--app-font-min, 15px)}.module-empty.large{background:white;border-radius:20px;padding:40px}.module-modal[hidden]{display:none}.module-modal{position:fixed;inset:0;z-index:1400;display:grid;place-items:center;padding:20px}.module-overlay{position:absolute;inset:0;background:rgba(15,23,42,.55)}.module-modal-card{position:relative;width:min(800px,100%);max-height:92vh;overflow:auto;background:white;border-radius:14px;box-shadow:0 20px 55px rgba(0,0,0,.24)}.module-modal-card header{display:flex;justify-content:space-between;padding:17px 21px;border-bottom:1px solid #e5e7eb}.module-modal-card header span{font-size:var(--app-font-min, 15px);text-transform:uppercase;color:#1F7C43}.module-modal-card header h3{font-size:16px;margin:3px 0 0}.module-modal-card header button{border:0;background:none;font-size:23px;color:#6b7280}.module-modal-body{padding:18px 21px}.module-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:11px}.module-form-grid label>span{display:block;font-size:var(--app-font-min, 15px);font-weight:500;margin-bottom:5px}.module-form-grid input,.module-form-grid select,.module-form-grid textarea{width:100%;border:1px solid #d1d5db;border-radius:6px;padding:9px;font-size:var(--app-font-min, 15px)}.module-full{grid-column:1/-1}.action-fieldset{border:1px solid #e5e7eb;border-radius:9px;padding:10px;margin-top:13px}.action-fieldset legend{font-size:var(--app-font-min, 15px);font-weight:600;padding:0 6px}.action-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:6px}.action-grid label{display:flex;align-items:center;gap:5px;padding:7px;background:#f9fafb;border-radius:6px;font-size:var(--app-font-min, 15px)}.action-grid input{accent-color:#1F7C43}.action-grid small{color:#9ca3af}.switch-row{display:flex;gap:18px;margin-top:13px}.switch-row label{display:flex;gap:7px;font-size:var(--app-font-min, 15px)}.switch-row input[type=checkbox]{accent-color:#1F7C43}.module-modal-card footer{display:flex;justify-content:flex-end;gap:8px;padding:14px 21px;border-top:1px solid #e5e7eb}@media(max-width:900px){.module-root-head,.module-child{grid-template-columns:30px 40px 1fr}.module-meta,.module-actions{grid-column:3}.module-children{padding-left:30px}.module-summary{align-items:flex-start;flex-direction:column;gap:14px}}@media(max-width:600px){.module-toolbar{align-items:stretch;flex-direction:column;gap:9px}.module-toolbar>div:last-child{justify-content:flex-end}.module-form-grid{grid-template-columns:1fr}.module-full{grid-column:auto}.action-grid{grid-template-columns:1fr 1fr}.summary-counts{overflow:auto;width:100%}.module-children{padding-left:10px}.module-root-head,.module-child{padding:12px 8px}}
</style>@endpush
@push('scripts')<script>document.addEventListener('DOMContentLoaded',()=>{const close=()=>{document.querySelectorAll('.module-modal').forEach(m=>m.hidden=true);document.body.style.overflow=''};document.querySelectorAll('[data-open-module]').forEach(b=>b.onclick=()=>{const modal=document.getElementById('moduleModal'+b.dataset.openModule);if(!modal)return;if(b.dataset.parent){const select=modal.querySelector('[name=parent_id]');if(select)select.value=b.dataset.parent}modal.hidden=false;document.body.style.overflow='hidden'});document.querySelectorAll('[data-close-module]').forEach(b=>b.onclick=close);document.querySelectorAll('.tree-toggle').forEach(b=>b.onclick=()=>b.closest('.module-root').classList.toggle('collapsed'));let expanded=false;document.getElementById('expandAll').onclick=function(){expanded=!expanded;document.querySelectorAll('.module-root').forEach(r=>r.classList.toggle('collapsed',!expanded));this.innerHTML=expanded?'<i class="fa-solid fa-angles-up"></i> Tout replier':'<i class="fa-solid fa-angles-down"></i> Tout déplier'};document.getElementById('moduleSearch').oninput=function(){const q=this.value.toLowerCase();document.querySelectorAll('.module-search-item').forEach(el=>el.hidden=q&&!el.dataset.search.includes(q));if(q)document.querySelectorAll('.module-root').forEach(r=>{if(r.querySelector('.module-child:not([hidden])'))r.hidden=false;r.classList.remove('collapsed')})};document.addEventListener('keydown',e=>{if(e.key==='Escape')close()})});</script>@endpush
