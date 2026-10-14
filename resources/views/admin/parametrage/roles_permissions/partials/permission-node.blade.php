@php
    $nodeLabels = ['dashboard' => 'Tableau de bord', 'utilisateurs' => 'Utilisateurs', 'hebergement' => 'Hébergement', 'parametrage' => 'Paramètres', 'activity' => 'Journal d’activité', 'parametrage.legal-documents' => 'Documents de la résidence', 'parametrage.payment-providers' => 'API de paiement'];
    $nodeName = $nodeLabels[$node->slug] ?? $node->name;
    $nodePermissions = $node->permissions->reject(fn ($permission) => $permission->slug === 'parametrage.legal-documents.signatures');
@endphp
<div class="permission-node" data-permission-node>
    @if($depth > 0)<details class="permission-submodule"><summary><span><i class="{{ $node->icon ?: 'fa-regular fa-folder' }}" aria-hidden="true"></i> {{ $nodeName }}</span><span class="node-selection-count"></span></summary><div class="permission-submodule-body">@endif
    @if($nodePermissions->isNotEmpty())
        @if($depth === 0)<h4 class="permission-root-title">Actions du module</h4>@endif
        <div class="permission-list">
            @foreach($nodePermissions as $permission)
                <label title="{{ $permission->description }}">
                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-action="{{ $permission->action }}" data-depth="{{ $depth }}" @checked(($isSuperAdmin ?? false) || ($selectedPermissions ?? collect())->contains($permission->id)) @disabled($isSuperAdmin ?? false)>
                    <span><strong>{{ $actionLabels[$permission->action] ?? $permission->name }}</strong></span>
                </label>
            @endforeach
        </div>
    @endif
    @foreach($depth === 0 ? $node->children : $node->activeDescendants as $child)
        @include('admin.parametrage.roles_permissions.partials.permission-node', ['node' => $child, 'depth' => $depth + 1])
    @endforeach
    @if($depth > 0)</div></details>@endif
</div>