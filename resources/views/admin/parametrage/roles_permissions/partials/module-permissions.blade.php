@php
    $editorId = 'permissions-'.\Illuminate\Support\Str::uuid();
    $moduleLabels = ['dashboard' => 'Tableau de bord', 'utilisateurs' => 'Utilisateurs', 'hebergement' => 'Hébergement', 'parametrage' => 'Paramètres', 'activity' => 'Journal d’activité'];
@endphp
<div class="permission-editor" id="{{ $editorId }}">
    <div class="permission-editor-toolbar">
        <label>Rechercher un sous-module ou une action<input type="search" class="permission-search" placeholder="Ex. Paiements, consulter…"></label>
        <span class="permission-total" aria-live="polite"></span>
    </div>
    <div class="permission-module-tabs" role="tablist" aria-label="Grands modules">
        @foreach($moduleTree as $root)
            <button type="button" role="tab" id="{{ $editorId }}-tab-{{ $root->id }}" aria-controls="{{ $editorId }}-module-{{ $root->id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-permission-module="{{ $root->id }}" class="{{ $loop->first ? 'active' : '' }}">
                <i class="{{ $root->icon ?: 'fa-solid fa-cube' }}" aria-hidden="true"></i>
                {{ $moduleLabels[$root->slug] ?? $root->name }} <span data-module-count></span>
            </button>
        @endforeach
    </div>
    @foreach($moduleTree as $root)
        <section class="permission-module-panel" role="tabpanel" id="{{ $editorId }}-module-{{ $root->id }}" aria-labelledby="{{ $editorId }}-tab-{{ $root->id }}" data-module-panel="{{ $root->id }}" @if(!$loop->first) hidden @endif>
            <div class="permission-module-heading">
                <label><input type="checkbox" class="module-enabled" @disabled($isSuperAdmin ?? false)> Autoriser {{ $moduleLabels[$root->slug] ?? $root->name }}</label>
                <button type="button" class="select-module-all" @disabled($isSuperAdmin ?? false)>Tout sélectionner</button>
            </div>
            <p class="permission-help">Décochez le module pour retirer tous ses droits. Cochez les actions nécessaires pour définir les accès.</p>
            @include('admin.parametrage.roles_permissions.partials.permission-node', ['node' => $root, 'depth' => 0])
        </section>
    @endforeach
</div>