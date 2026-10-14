<div class="catalog-node">
    <h4>{{ $node->name }}</h4>
    <div class="catalog-actions">
        @foreach($node->permissions->reject(fn ($permission) => $permission->slug === 'parametrage.legal-documents.signatures') as $permission)
            <span>{{ $actionLabels[$permission->action] ?? $permission->name }}</span>
        @endforeach
    </div>
    @foreach($depth === 0 ? $node->children : $node->activeDescendants as $child)
        <details class="permission-submodule"><summary>{{ $child->name }}</summary><div class="permission-submodule-body">
            @include('admin.parametrage.roles_permissions.partials.catalog-node', ['node' => $child, 'depth' => $depth + 1])
        </div></details>
    @endforeach
</div>