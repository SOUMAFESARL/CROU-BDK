@php
    // Valeurs par défaut
    $id = $id ?? 'simpletable-' . uniqid();
    $title = $title ?? 'Zero Configuration';
    $columns = $columns ?? [];
    $colWidth = $colWidth ?? '';
    $showSearch = $showSearch ?? true;
    $showActions = $showActions ?? true;
    $actions = $actions ?? ['view', 'edit', 'delete'];
    $showFooter = $showFooter ?? true;
    $responsive = $responsive ?? true;
    $striped = $striped ?? true;
    $exportable = $exportable ?? false;
    $ajaxUrl = $ajaxUrl ?? null;
@endphp

<div class="{{ $colWidth }}">
    <div class="card-classic">
        <div class="card-header-classic">
            <div class="px-4 py-4 row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">{{ $title }}</h5>
                </div>
                @if($showSearch || $exportable)
                <div class="col-md-6">
                    <div class="d-flex justify-content-end gap-2">
                        @if($showSearch)
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="search-{{ $id }}" 
                                   placeholder="Rechercher...">
                        </div>
                        @endif
                        
                        @if($exportable)
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                    data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportTable('{{ $id }}', 'excel')">
                                    <i class="fas fa-file-excel text-success me-2"></i>Excel
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportTable('{{ $id }}', 'pdf')">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>PDF
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportTable('{{ $id }}', 'print')">
                                    <i class="fas fa-print text-info me-2"></i>Imprimer
                                </a></li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <div class="card-block">
            <div class="{{ $responsive ? 'dt-responsive table-responsive' : 'table-responsive' }}">
                <table id="{{ $id }}"
                       class="table table-striped table-bordered {{ $responsive ? 'nowrap' : '' }}">
                    <thead>
                        <tr>
                            @foreach($columns as $column)
                                <th>{{ $column['label'] }}</th>
                            @endforeach
                            @if($showActions)
                                <th class="text-center" style="width: 150px;">Actions</th>
                            @endif
                        </tr>
                    </thead>
                   
                    <tbody>
                        @if(!empty($data))
                            @foreach($data as $item)
                                <tr>
                                    @foreach($columns as $column)
                                        <td>
                                            @if(isset($column['render']) && is_callable($column['render']))
                                                {!! $column['render']($item[$column['key']] ?? '', $item) !!}
                                            @else
                                                {{ $item[$column['key']] ?? '' }}
                                            @endif
                                        </td>
                                    @endforeach
                                    
                                    @if($showActions)
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                @if(in_array('view', $actions))
                                                    <button type="button" 
                                                            class="btn btn-outline-primary btn-sm"
                                                            onclick="viewItem('{{ $item['id'] ?? $loop->index }}')"
                                                            title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @endif
                                                
                                                @if(in_array('edit', $actions))
                                                    <button type="button" 
                                                            class="btn btn-outline-warning btn-sm"
                                                            onclick="editItem('{{ $item['id'] ?? $loop->index }}')"
                                                            title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endif
                                                
                                                @if(in_array('active', $actions))
                                                    <button type="button" 
                                                            class="btn btn-outline-success btn-sm"
                                                            onclick="activateItem('{{ $item['id'] ?? $loop->index }}')"
                                                            title="Activer">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @endif
                                                
                                                @if(in_array('reject', $actions))
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm"
                                                            onclick="rejectItem('{{ $item['id'] ?? $loop->index }}')"
                                                            title="Rejeter">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                                
                                                @if(in_array('delete', $actions))
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm"
                                                            onclick="deleteItem('{{ $item['id'] ?? $loop->index }}')"
                                                            title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ count($columns) + ($showActions ? 1 : 0) }}" 
                                    class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Aucune donnée disponible</p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    
{{--                    @if($showFooter)--}}
{{--                        <tfoot>--}}
{{--                            <tr>--}}
{{--                                @foreach($columns as $column)--}}
{{--                                    <th>{{ $column['label'] }}</th>--}}
{{--                                @endforeach--}}
{{--                                @if($showActions)--}}
{{--                                    <th class="text-center">Actions</th>--}}
{{--                                @endif--}}
{{--                            </tr>--}}
{{--                        </tfoot>--}}
{{--                    @endif--}}
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Fonctions d'action génériques
function viewItem(id) {
    console.log('Voir l\'élément:', id);
    // Remplacer par votre logique
    alert('Voir l\'élément ' + id);
}

function editItem(id) {
    console.log('Modifier l\'élément:', id);
    // Remplacer par votre logique
    alert('Modifier l\'élément ' + id);
}

function activateItem(id) {
    console.log('Activer l\'élément:', id);
    if (confirm('Voulez-vous activer cet élément ?')) {
        // Remplacer par votre logique AJAX
        alert('Élément ' + id + ' activé');
    }
}

function rejectItem(id) {
    console.log('Rejeter l\'élément:', id);
    if (confirm('Voulez-vous rejeter cet élément ?')) {
        // Remplacer par votre logique AJAX
        alert('Élément ' + id + ' rejeté');
    }
}

function deleteItem(id) {
    console.log('Supprimer l\'élément:', id);
    if (confirm('Voulez-vous vraiment supprimer cet élément ?')) {
        // Remplacer par votre logique AJAX
        alert('Élément ' + id + ' supprimé');
    }
}

// Fonction d'export
function exportTable(tableId, type) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    switch(type) {
        case 'excel':
            console.log('Export Excel de la table:', tableId);
            break;
        case 'pdf':
            console.log('Export PDF de la table:', tableId);
            break;
        case 'print':
            window.print();
            break;
    }
}

// Recherche simple
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-{{ $id }}');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.getElementById('{{ $id }}');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                let found = false;
                const cells = row.getElementsByTagName('td');
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
        });
    }
});
</script>
@endpush