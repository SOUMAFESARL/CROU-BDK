@extends('admin.layouts.app', ['activeMenu'=>'parametres','activeSubMenu'=>null])
@section('title','Rôles et permissions')
@section('content')
<div class="page-body">
<x-breadcrumbs title="Rôles et permissions" :items="['Gestion Paramétrage'=>route('admin.parametrage.index'),'Rôles et permissions'=>null]" />
<div class="rbac-page">
    @if(session('success'))<div class="rbac-alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rbac-alert error">{{ $errors->first() }}</div>@endif

    <header class="rbac-header"><div><span><i class="fa-solid fa-shield-halved"></i> Contrôle des accès</span><h1>Gestion des rôles et permissions</h1><p>Choisissez un rôle, puis définissez ses accès par module et sous-module.</p></div><div class="rbac-stats"><div><b>{{ $roles->count() }}</b><small>Rôles</small></div><div><b>{{ $permissions->count() }}</b><small>Permissions</small></div><div><b>{{ $users->count() }}</b><small>Utilisateurs</small></div></div></header>

    <nav class="rbac-tabs"><button class="active" data-target="rolesPanel">Rôles</button><button data-target="permissionsPanel">Modules et actions</button>@adminblock('roles.assignments')
<button data-target="usersPanel">Affectations utilisateurs</button>
@endadminblock
</nav>

    <section id="rolesPanel" class="rbac-panel active">
        <div class="rbac-toolbar"><div><h2>Matrice des rôles</h2><p>Modifiez un rôle à la fois. Les modules non autorisés ne sont pas accessibles.</p></div>@adminroute('admin.parametrage.roles-permissions.roles.store')
<button type="button" class="rbac-primary" data-open="newRoleModal"><i class="fa-solid fa-plus"></i> Nouveau rôle</button>
@endadminroute
</div>
        <div class="role-selector-bar"><label for="roleSelector">Rôle à configurer</label><select id="roleSelector">@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }} — {{ $role->users_count }} utilisateur(s)</option>@endforeach</select></div>
        <div class="role-grid">
            @forelse($roles as $role)
            <article class="role-card" data-role-id="{{ $role->id }}" @if(!$loop->first) hidden @endif>
                <form method="POST" action="{{ route('admin.parametrage.roles-permissions.roles.update',$role) }}">
<fieldset style="display: contents" @disabled(!auth('admin')->user()->hasPermission('parametrage.roles.update'))>@csrf @method('PUT')
                    <div class="role-card-head"><label>Nom du rôle<input name="name" value="{{ $role->name }}" required></label><span>{{ $role->users_count }} utilisateur(s)</span></div>
                    @if((int)$role->id===1)<div class="super-admin-notice"><i class="fa-solid fa-crown"></i><span><strong>Super-administrateur</strong> Ce rôle possède automatiquement tous les droits actuels et futurs.</span></div>@endif
                    @include('admin.parametrage.roles_permissions.partials.module-permissions',['selectedPermissions'=>$role->permissions->pluck('id'),'isSuperAdmin'=>(int)$role->id===1])
                    <div class="role-actions"><button class="rbac-primary">Enregistrer</button></div>
                </fieldset></form>
                @adminroute('admin.parametrage.roles-permissions.roles.destroy')
                <form method="POST" action="{{ route('admin.parametrage.roles-permissions.roles.destroy',$role) }}" onsubmit="return confirm('Supprimer définitivement ce rôle ?')">@csrf @method('DELETE')<button class="rbac-danger" @disabled((int)$role->id===1 || $role->users_count > 0) title="{{ (int)$role->id===1?'Le rôle super-administrateur est protégé':'Retirez d’abord ce rôle des utilisateurs' }}">Supprimer</button></form>
@endadminroute
            </article>
            @empty<div class="rbac-empty">Aucun rôle. Créez le premier rôle pour commencer.</div>@endforelse
        </div>
    </section>

    <section id="permissionsPanel" class="rbac-panel">
        <div class="rbac-toolbar"><div><h2>Référentiel dynamique</h2><p>Ce référentiel est en lecture seule. Modifiez les actions depuis la Gestion des modules.</p></div>@adminroute('admin.parametrage.modules.index')
<a class="rbac-primary" href="{{ route('admin.parametrage.modules.index') }}"><i class="fa-solid fa-sitemap"></i> Gérer les modules</a>
@endadminroute</div>
        <div class="dynamic-catalog">
            @php($catalogLabels = ['dashboard' => 'Tableau de bord', 'utilisateurs' => 'Utilisateurs', 'hebergement' => 'Hébergement', 'parametrage' => 'Paramètres', 'activity' => 'Journal d’activité'])
            @foreach($moduleTree as $root)
                <details class="catalog-root">
                    <summary style="padding:18px;cursor:pointer;font-weight:600"><i class="{{ $root->icon ?: 'fa-solid fa-cube' }}" aria-hidden="true"></i> {{ $catalogLabels[$root->slug] ?? $root->name }}</summary>
                    <div class="catalog-children">@include('admin.parametrage.roles_permissions.partials.catalog-node', ['node' => $root, 'depth' => 0])</div>
                </details>
            @endforeach
        </div>
    </section>

@adminblock('roles.assignments')
    <section id="usersPanel" class="rbac-panel">
        <div class="rbac-toolbar"><div><h2>Affectations utilisateurs</h2><p>Un utilisateur hérite de toutes les permissions de son rôle.</p></div><input id="userSearch" class="rbac-search" placeholder="Rechercher un utilisateur…"></div>
        <div class="user-table-wrap"><table class="user-table"><thead><tr><th>Utilisateur</th><th>Contact</th><th>Campus</th><th>Rôle actuel</th><th>Affectation</th></tr></thead><tbody>@forelse($users as $user)<tr data-user="{{ Str::lower($user->name.' '.$user->email.' '.$user->matricule) }}"><td><strong>{{ $user->name }}</strong><small>{{ $user->matricule ?: 'Sans matricule' }}</small></td><td>{{ $user->email }}</td><td>{{ $user->campus?->name ?? '—' }}</td><td><span class="role-badge">{{ $user->role?->name ?? 'Non défini' }}</span></td><td>@adminroute('admin.parametrage.roles-permissions.users.assign-role')
<form method="POST" action="{{ route('admin.parametrage.roles-permissions.users.assign-role',$user) }}">@csrf @method('PUT')<select name="role_id" required><option value="">Sélectionner</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($user->role_id==$role->id)>{{ $role->name }}</option>@endforeach</select><button class="rbac-primary">Affecter</button></form>
@endadminroute</td></tr>@empty<tr><td colspan="5" class="rbac-empty">Aucun utilisateur.</td></tr>@endforelse</tbody></table></div>
    </section>
@endadminblock

</div>

<div class="rbac-modal" id="newRoleModal" hidden><div class="rbac-backdrop" data-close></div><div class="rbac-modal-card"><button class="rbac-close" data-close>&times;</button><h2>Nouveau rôle</h2>@adminroute('admin.parametrage.roles-permissions.roles.store')
<form method="POST" action="{{ route('admin.parametrage.roles-permissions.roles.store') }}">@csrf<label>Nom du rôle<input name="name" required placeholder="Ex. Chef service logement"></label>@include('admin.parametrage.roles_permissions.partials.module-permissions',['selectedPermissions'=>collect()])<div class="modal-actions"><button type="button" data-close>Annuler</button><button class="rbac-primary">Créer le rôle</button></div></form>
@endadminroute</div></div>
</div>
@endsection

@push('styles')<style>
.rbac-page{max-width:1500px;margin:auto;padding:22px 28px 40px;color:#17233b;font-family:Montserrat,sans-serif}.rbac-alert{padding:11px 14px;border-radius:9px;margin-bottom:12px;font-size:var(--app-font-min, 15px)}.rbac-alert.success{background:#e9f8f1;color:#147a54}.rbac-alert.error{background:#fff0ef;color:#bd413d}.rbac-header{display:flex;justify-content:space-between;align-items:end;background:linear-gradient(125deg,#172b4d,#254f83);color:white;border-radius:16px;padding:24px;margin-bottom:14px}.rbac-header>div>span{font-size:var(--app-font-min, 15px);text-transform:uppercase;letter-spacing:1.3px;color:#9ec4ff}.rbac-header h1{font-size:24px;margin:5px 0}.rbac-header p{font-size:var(--app-font-min, 15px);margin:0;color:#d3def0}.rbac-stats{display:grid;grid-template-columns:repeat(3,auto auto);gap:5px 9px;align-items:baseline}.rbac-stats b{font-size:22px}.rbac-stats small{font-size:var(--app-font-min, 15px);color:#b8c8df}.rbac-tabs{display:flex;gap:5px;padding:5px;background:#e9edf4;border-radius:11px;margin-bottom:14px}.rbac-tabs button{border:0;background:transparent;padding:10px 14px;border-radius:8px;font-size:var(--app-font-min, 15px);font-weight:700;color:#68758a}.rbac-tabs button.active{background:white;color:#2458c4;box-shadow:0 2px 7px #1b29421a}.rbac-panel{display:none}.rbac-panel.active{display:block}.rbac-toolbar{display:flex;justify-content:space-between;align-items:center;background:#fff;border:1px solid #e4e9f1;border-radius:13px;padding:15px 17px;margin-bottom:12px}.rbac-toolbar h2{font-size:var(--app-font-min, 15px);margin:0 0 3px}.rbac-toolbar p{font-size:var(--app-font-min, 15px);color:#7a8598;margin:0}.rbac-primary{border:0;background:#315efb;color:white;border-radius:8px;padding:9px 12px;font-size:var(--app-font-min, 15px);font-weight:700}.role-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.role-card{background:white;border:1px solid #e4e9f1;border-radius:13px;padding:16px;position:relative}.role-card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}.role-card-head label{font-size:var(--app-font-min, 15px);color:#7b8799}.role-card-head input{display:block;border:0;border-bottom:1px solid #dfe5ee;font-size:15px;font-weight:700;padding:5px 0;width:250px}.role-card-head>span,.permission-module h3 span{background:#edf2ff;color:#315efb;padding:5px 8px;border-radius:12px;font-size:var(--app-font-min, 15px)}.role-card fieldset,.rbac-modal fieldset{border:1px solid #edf0f5;border-radius:9px;margin:9px 0;padding:9px}.role-card legend,.rbac-modal legend{font-size:var(--app-font-min, 15px);font-weight:750;padding:0 6px}.role-card legend{display:flex;width:100%;justify-content:space-between}.toggle-group{border:0;background:none;color:#315efb;font-size:var(--app-font-min, 15px)}.permission-list{display:grid;grid-template-columns:1fr 1fr;gap:6px}.permission-list label{display:flex;gap:7px;align-items:flex-start;background:#f8f9fc;border-radius:7px;padding:7px;cursor:pointer}.permission-list input{margin-top:2px;accent-color:#315efb}.permission-list strong,.permission-list small{display:block;font-size:var(--app-font-min, 15px)}.permission-list small{font-size:var(--app-font-min, 15px);color:#8993a4;margin-top:2px}.role-actions{display:flex;justify-content:flex-end;margin-top:10px}.rbac-danger{position:absolute;left:16px;bottom:16px;border:0;background:#fff0ef;color:#c44842;padding:8px 10px;border-radius:7px;font-size:var(--app-font-min, 15px)}.rbac-danger:disabled{opacity:.4}.permission-module{background:#fff;border:1px solid #e4e9f1;border-radius:12px;padding:14px;margin-bottom:10px}.permission-module h3{font-size:var(--app-font-min, 15px);display:flex;justify-content:space-between}.permission-table{display:grid;grid-template-columns:1fr auto;gap:6px}.permission-table form:first-of-type{}.permission-table form{display:contents}.permission-table input{border:1px solid #e3e8ef;border-radius:7px;padding:8px;font-size:var(--app-font-min, 15px);min-width:0}.icon-save,.icon-delete{border:0;border-radius:7px;color:#315efb;background:#edf2ff}.icon-delete{color:#c44842;background:#fff0ef}.rbac-search{border:1px solid #dfe5ed;border-radius:8px;padding:9px;width:260px;font-size:var(--app-font-min, 15px)}.user-table-wrap{overflow:auto;background:white;border:1px solid #e4e9f1;border-radius:12px;padding:12px}.user-table{width:100%;border-collapse:collapse}.user-table th{text-align:left;background:#f6f8fb;padding:10px;font-size:var(--app-font-min, 15px);text-transform:uppercase;color:#7d8798}.user-table td{padding:10px;border-bottom:1px solid #edf0f4;font-size:var(--app-font-min, 15px)}.user-table td strong,.user-table td small{display:block}.user-table td small{color:#8993a4;margin-top:3px}.user-table form{display:flex;gap:6px}.user-table select{border:1px solid #dfe5ed;border-radius:7px;padding:7px;font-size:var(--app-font-min, 15px)}.role-badge{background:#edf2ff;color:#315efb;padding:5px 7px;border-radius:7px}.rbac-empty{text-align:center;padding:28px;color:#8993a4}.rbac-modal[hidden]{display:none}.rbac-modal{position:fixed;inset:0;z-index:1300;display:grid;place-items:center;padding:20px}.rbac-backdrop{position:absolute;inset:0;background:#101a2bbf}.rbac-modal-card{position:relative;background:white;border-radius:15px;padding:22px;width:min(760px,100%);max-height:90vh;overflow:auto}.rbac-modal-card.small{width:min(480px,100%)}.rbac-modal-card h2{font-size:17px}.rbac-modal-card>form>label{display:block;font-size:var(--app-font-min, 15px);margin:10px 0}.rbac-modal-card input,.rbac-modal-card textarea{width:100%;border:1px solid #dfe5ed;border-radius:8px;padding:9px;font-size:var(--app-font-min, 15px);margin-top:5px}.rbac-close{position:absolute;right:14px;top:10px;border:0;background:none;font-size:22px}.modal-actions{display:flex;justify-content:flex-end;gap:7px;margin-top:13px}.modal-actions>button:not(.rbac-primary){border:1px solid #dfe5ed;background:white;border-radius:8px;padding:9px;font-size:var(--app-font-min, 15px)}@media(max-width:900px){.role-grid{grid-template-columns:1fr}.rbac-header{align-items:flex-start;flex-direction:column;gap:15px}}@media(max-width:600px){.rbac-page{padding:14px 10px}.permission-list{grid-template-columns:1fr}.rbac-tabs{overflow:auto}.permission-table{display:block}.permission-table form{display:grid;gap:5px;margin-bottom:8px}.rbac-toolbar{align-items:flex-start;flex-direction:column;gap:10px}}
/* Alignement avec le design commun des pages de paramétrage */
.rbac-page{max-width:none;margin:0;padding:20px 0 40px;font-family:inherit;color:#374151}
.rbac-header{align-items:center;background:#fff;color:#374151;border:0;border-bottom:1px solid #e5e7eb;border-radius:20px 20px 0 0;padding:20px 24px;margin-bottom:0;box-shadow:0 8px 24px rgba(0,0,0,.08)}
.rbac-header>div>span{display:block;color:#1F7C43;font-size:var(--app-font-min, 15px);font-weight:600;letter-spacing:.5px;text-transform:uppercase;margin-bottom:5px}
.rbac-header h1{font-size:18px;font-weight:600;color:#1f2937;margin:0 0 5px}
.rbac-header p{font-size:var(--app-font-min, 15px);color:#6b7280}
.rbac-stats{display:flex;gap:10px}
.rbac-stats>div{display:flex;min-width:90px;flex-direction:column;align-items:center;padding:9px 14px;background:#f5faf7;border:1px solid #dcece2;border-radius:14px}
.rbac-stats b{font-size:18px;line-height:1.1;color:#1F7C43}.rbac-stats small{font-size:var(--app-font-min, 15px);color:#6b7280;margin-top:3px;text-transform:uppercase}
.rbac-tabs{justify-content:flex-start;gap:0;padding:0 20px;background:#fff;border-radius:0 0 20px 20px;border-top:0;margin-bottom:20px;box-shadow:0 8px 24px rgba(0,0,0,.08)}
.rbac-tabs button{padding:14px 17px;border-radius:0;color:#6b7280;font-size:var(--app-font-min, 15px);border-bottom:3px solid transparent}
.rbac-tabs button:hover{color:#1F7C43}.rbac-tabs button.active{color:#1F7C43;background:#fff;border-bottom-color:#1F7C43;box-shadow:none}
.rbac-toolbar,.role-card,.permission-module,.user-table-wrap{border:0;border-radius:20px;box-shadow:0 8px 24px rgba(0,0,0,.08)}
.rbac-toolbar{padding:18px 20px}.rbac-toolbar h2{font-size:16px;color:#1f2937}.rbac-toolbar p{font-size:var(--app-font-min, 15px)}
.rbac-primary{background:#1F7C43;border:1px solid #1F7C43;border-radius:6px;padding:8px 14px;font-size:var(--app-font-min, 15px);font-weight:500;transition:.2s}
.rbac-primary:hover{background:#176536;border-color:#176536}.role-grid{gap:16px}.role-card{padding:20px}
.role-card-head input{color:#1f2937;font-size:15px}.role-card-head>span,.permission-module h3 span,.role-badge{background:#eaf5ee;color:#1F7C43}
.role-card fieldset,.rbac-modal fieldset{border-color:#e5e7eb;border-radius:10px;background:#fff}.role-card legend,.rbac-modal legend{color:#374151}
.toggle-group{color:#1F7C43}.permission-list label{background:#f9fafb;border:1px solid #f0f1f3}.permission-list label:hover{border-color:#b9d8c4;background:#f5faf7}.permission-list input{accent-color:#1F7C43}
.permission-list strong{color:#374151}.permission-list small{color:#9ca3af}.rbac-danger{border:1px solid #fee2e2;background:#fff;color:#dc2626}.rbac-danger:hover:not(:disabled){background:#fef2f2}
.permission-module h3{color:#1f2937}.permission-table input,.rbac-search,.user-table select{border-color:#d1d5db;background:#fff}.permission-table input:focus,.rbac-search:focus,.user-table select:focus,.rbac-modal-card input:focus,.rbac-modal-card textarea:focus{outline:0;border-color:#1F7C43;box-shadow:0 0 0 3px rgba(31,124,67,.12)}
.icon-save{color:#1F7C43;background:#eaf5ee}.user-table th{background:#f9fafb;color:#6b7280;font-size:var(--app-font-min, 15px);padding:13px}.user-table td{font-size:var(--app-font-min, 15px);padding:13px}.user-table tbody tr:hover{background:#f9fafb}
.rbac-modal{font-family:inherit}.rbac-backdrop{background:rgba(15,23,42,.55)}.rbac-modal-card{border-radius:14px;padding:0;width:min(760px,calc(100% - 30px));box-shadow:0 20px 50px rgba(0,0,0,.2)}
.rbac-modal-card h2{font-size:16px;font-weight:600;color:#1f2937;margin:0;padding:18px 22px;border-bottom:1px solid #e5e7eb}.rbac-modal-card>form{padding:18px 22px}.rbac-modal-card>form>label{font-size:var(--app-font-min, 15px);color:#374151;font-weight:500}.rbac-modal-card input,.rbac-modal-card textarea{border-color:#d1d5db;border-radius:6px;font-size:var(--app-font-min, 15px)}.rbac-close{right:16px;top:13px;color:#6b7280}.modal-actions{border-top:1px solid #e5e7eb;padding-top:15px}.rbac-alert{font-size:var(--app-font-min, 15px);border-radius:8px}
@media(max-width:700px){.rbac-header{padding:18px}.rbac-stats{width:100%;overflow:auto}.rbac-stats>div{min-width:85px}.rbac-tabs{padding:0;overflow:auto}.rbac-tabs button{white-space:nowrap}.role-card{padding:15px}}
.rbac-primary{text-decoration:none;display:inline-flex;align-items:center;gap:6px}.rbac-primary:hover{color:#fff;text-decoration:none}.super-admin-notice{display:flex;align-items:center;gap:9px;padding:10px 12px;margin:10px 0;border-radius:9px;background:#fff8e6;border:1px solid #f1d98b;color:#8a6500;font-size:var(--app-font-min, 15px)}.super-admin-notice i{font-size:15px}.super-admin-notice strong{display:block;font-size:var(--app-font-min, 15px)}.permission-module-group{padding:12px!important;margin:12px 0!important;background:#fcfdfc!important}.permission-module-group>legend span{display:flex;align-items:center;gap:6px;font-size:var(--app-font-min, 15px);color:#1F7C43}.permission-subgroup{border-top:1px solid #eef1f0;padding-top:9px;margin-top:8px;margin-left:calc(var(--permission-depth) * 14px)}.permission-subgroup:first-of-type{border-top:0}.permission-subgroup h4{display:flex;align-items:center;gap:6px;font-size:var(--app-font-min, 15px);margin:0 0 7px;color:#4b5563}.permission-subgroup h4 small{margin-left:auto;color:#9ca3af;font-size:var(--app-font-min, 15px)}.permission-empty{font-size:var(--app-font-min, 15px);color:#9ca3af;padding:6px}.dynamic-catalog{display:grid;grid-template-columns:1fr 1fr;gap:14px}.catalog-root{background:#fff;border-radius:20px;box-shadow:0 8px 24px rgba(0,0,0,.08);overflow:hidden}.catalog-root>header{display:flex;align-items:center;gap:10px;padding:15px 17px;border-bottom:1px solid #edf0f2}.catalog-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;background:#eaf5ee;color:#1F7C43}.catalog-root h3{font-size:var(--app-font-min, 15px);margin:0}.catalog-root code,.catalog-root small{display:block;font-size:var(--app-font-min, 15px);color:#9ca3af;background:none;padding:0}.catalog-root>header>strong{margin-left:auto;font-size:var(--app-font-min, 15px);padding:5px 8px;border-radius:10px;background:#eaf5ee;color:#1F7C43}.catalog-children{padding:7px 15px 14px}.catalog-children section{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 2px;border-bottom:1px solid #f0f2f3}.catalog-children section:last-child{border:0}.catalog-children section>div:first-child{display:flex;align-items:center;gap:7px;font-size:var(--app-font-min, 15px)}.catalog-children section b{display:block}.catalog-actions{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:4px}.catalog-actions span{font-size:var(--app-font-min, 15px);padding:4px 6px;border-radius:8px;background:#f5faf7;border:1px solid #dcece2;color:#1F7C43}@media(max-width:900px){.dynamic-catalog{grid-template-columns:1fr}}@media(max-width:600px){.catalog-children section{align-items:flex-start;flex-direction:column}.catalog-actions{justify-content:flex-start}}
</style>@endpush
@push('scripts')<script>document.addEventListener('DOMContentLoaded',()=>{const tabs=document.querySelectorAll('.rbac-tabs button'),panels=document.querySelectorAll('.rbac-panel');tabs.forEach(t=>t.onclick=()=>{tabs.forEach(x=>x.classList.remove('active'));panels.forEach(x=>x.classList.remove('active'));t.classList.add('active');document.getElementById(t.dataset.target)?.classList.add('active')});document.querySelectorAll('[data-open]').forEach(b=>b.onclick=()=>document.getElementById(b.dataset.open).hidden=false);document.querySelectorAll('.rbac-page [data-close],.rbac-modal [data-close]').forEach(b=>b.onclick=()=>b.closest('.rbac-modal').hidden=true);document.querySelectorAll('.toggle-group').forEach(b=>b.onclick=()=>{const boxes=b.closest('fieldset').querySelectorAll('input[type=checkbox]'),all=[...boxes].every(x=>x.checked);boxes.forEach(x=>x.checked=!all)});const search=document.getElementById('userSearch');if(search)search.oninput=()=>document.querySelectorAll('[data-user]').forEach(r=>r.hidden=!r.dataset.user.includes(search.value.toLowerCase()))});</script>@endpush

@push('styles')
<style>
.role-grid { grid-template-columns: minmax(0, 1fr); }
.role-card[hidden], .permission-module-panel[hidden], .permission-node[hidden] { display: none !important; }
.role-selector-bar { display: flex; align-items: center; gap: 14px; margin: 16px 0; }
.role-selector-bar label { font-weight: 600; }
.role-selector-bar select { max-width: 480px; width: 100%; border: 1px solid #cbd5e1; border-radius: 9px; padding: 10px 14px; background: #fff; }
.permission-editor-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 16px; margin: 18px 0 12px; }
.permission-editor-toolbar label { color: #64748b; flex: 1; font-size: var(--app-font-min, 15px); }
.permission-search { display: block; width: 100%; max-width: 480px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 6px; }
.permission-total { color: #1f7c43; white-space: nowrap; font-weight: 600; }
.permission-module-tabs { display: flex; gap: 8px; flex-wrap: wrap; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
.permission-module-tabs button { display: flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 12px; border-radius: 9px; color: #475569; }
.permission-module-tabs button.active { color: #1f7c43; background: #eaf5ee; border-color: #93bfaa; font-weight: 600; }
.permission-module-tabs button span { font-size: var(--app-font-min, 15px); }
.permission-module-panel { margin-top: 16px; padding: 4px 8px 12px 0; max-height: 52vh; overflow: auto; scrollbar-gutter: stable; }
.permission-module-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.permission-module-heading label { display: flex; align-items: center; gap: 8px; font-weight: 600; }
.permission-module-heading input { accent-color: #1f7c43; width: 18px; height: 18px; }
.select-module-all { color: #1f7c43; border: 1px solid #bdd9c7; border-radius: 8px; padding: 7px 10px; background: #fff; }
.permission-help { color: #64748b; font-size: var(--app-font-min, 15px); margin: 12px 0; }
.permission-root-title { font-size: var(--app-font-min, 15px); color: #475569; margin: 12px 0; }
.permission-submodule { border: 1px solid #e2e8f0; border-radius: 9px; margin-top: 10px; background: #fff; }
.permission-submodule summary { display: flex; align-items: center; justify-content: space-between; gap: 12px; cursor: pointer; padding: 13px; font-weight: 600; color: #334155; }
.permission-submodule summary::before { content: '+'; color: #1f7c43; }
.permission-submodule[open] > summary::before { content: '−'; }
.permission-submodule summary > span:first-of-type { flex: 1; }
.node-selection-count { font-weight: 400; color: #64748b; font-size: var(--app-font-min, 15px); }
.permission-submodule-body { padding: 0 13px 13px; }
.permission-editor .permission-list { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
.role-actions { position: sticky; bottom: 0; background: #fff; padding: 14px 0; border-top: 1px solid #e2e8f0; z-index: 1; }
@media(max-width:700px) { .role-selector-bar, .permission-editor-toolbar { align-items: stretch; flex-direction: column; } .permission-module-tabs button { flex: 1; } .permission-module-panel { max-height: 55vh; } }
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selector = document.getElementById('roleSelector');
    selector?.addEventListener('change', () => {
        document.querySelectorAll('[data-role-id]').forEach(card => card.hidden = card.dataset.roleId !== selector.value);
    });
    document.querySelectorAll('.permission-editor').forEach(editor => {
        const panels = [...editor.querySelectorAll('[data-module-panel]')];
        const tabs = [...editor.querySelectorAll('[data-permission-module]')];
        const refresh = () => {
            const boxes = [...editor.querySelectorAll('input[name="permissions[]"]')];
            editor.querySelector('.permission-total').textContent = boxes.filter(box => box.checked).length + ' droit(s) sélectionné(s)';
            panels.forEach(panel => {
                const permissions = [...panel.querySelectorAll('input[name="permissions[]"]')];
                const count = permissions.filter(box => box.checked).length;
                panel.querySelector('.module-enabled').checked = count > 0;
                const tab = tabs.find(tab => tab.dataset.permissionModule === panel.dataset.modulePanel);
                tab.querySelector('[data-module-count]').textContent = count + '/' + permissions.length;
                panel.querySelectorAll('.permission-submodule').forEach(details => {
                    const childBoxes = [...details.querySelectorAll('input[name="permissions[]"]')];
                    details.querySelector('.node-selection-count').textContent = childBoxes.filter(box => box.checked).length + '/' + childBoxes.length;
                });
            });
        };
        const activate = tab => {
            tabs.forEach(button => { const active = button === tab; button.classList.toggle('active', active); button.setAttribute('aria-selected', active); button.tabIndex = active ? 0 : -1; });
            panels.forEach(panel => panel.hidden = panel.dataset.modulePanel !== tab.dataset.permissionModule);
        };
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activate(tab));
            tab.addEventListener('keydown', event => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
                activate(tabs[next]); tabs[next].focus();
            });
        });
        panels.forEach(panel => {
            panel.querySelector('.module-enabled').addEventListener('change', event => {
                const boxes = [...panel.querySelectorAll('input[name="permissions[]"]')].filter(box => !box.disabled);
                if (!event.target.checked) boxes.forEach(box => box.checked = false);
                else {
                    const entry = boxes.find(box => box.dataset.depth === '0' && box.dataset.action === 'view') || boxes.find(box => ['view', 'manage', 'configure'].includes(box.dataset.action));
                    if (entry) entry.checked = true;
                }
                refresh();
            });
            panel.querySelector('.select-module-all').addEventListener('click', () => {
                panel.querySelectorAll('input[name="permissions[]"]:not(:disabled)').forEach(box => box.checked = true); refresh();
            });
        });
        editor.addEventListener('change', refresh);
        editor.querySelector('.permission-search').addEventListener('input', event => {
            const term = event.target.value.trim().toLocaleLowerCase('fr');
            editor.querySelectorAll('[data-permission-node]').forEach(node => {
                const matches = node.textContent.toLocaleLowerCase('fr').includes(term);
                node.hidden = Boolean(term) && !matches;
                const details = node.querySelector(':scope > details');
                if (details) details.open = Boolean(term) && matches;
            });
        });
        refresh();
    });
});
</script>
@endpush