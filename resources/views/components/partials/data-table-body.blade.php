@foreach($data as $item)
    <tr data-id="{{ $item['id'] ?? $loop->index }}">
        @if($showCheckboxes)
            <td class="text-center">
                <input type="checkbox" class="form-check-input row-checkbox" 
                       value="{{ $item['id'] ?? $loop->index }}">
            </td>
        @endif
        
        @foreach($visibleColumns as $column)
            @php
                $value = $item[$column['key']] ?? '';
                $cellContent = $value;
                
                // Rendu personnalisé
                if (isset($column['render']) && is_callable($column['render'])) {
                    $cellContent = $column['render']($value, $item);
                } else {
                    // Formatage par défaut
                    $cellContent = $value;
                }
            @endphp
            
            <td @if($column['align']) class="text-{{ $column['align'] }}" @endif>
                {!! $cellContent !!}
            </td>
        @endforeach
        
        @if($showActions && !empty($actions))
            <td class="text-center table-actions">
                <div class="btn-group btn-group-sm">
                    @if(in_array('view', $actions))
                        <button class="btn btn-outline-primary view-action" 
                                data-id="{{ $item['id'] ?? $loop->index }}" 
                                title="Voir">
                            <i class="fas fa-eye"></i>
                        </button>
                    @endif
                    
                    @if(in_array('edit', $actions))
                        <button class="btn btn-outline-warning edit-action" 
                                data-id="{{ $item['id'] ?? $loop->index }}" 
                                title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                    @endif
                    
                    @if(in_array('delete', $actions))
                        <button class="btn btn-outline-danger delete-action" 
                                data-id="{{ $item['id'] ?? $loop->index }}" 
                                title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    @endif
                    
                    <!-- Actions personnalisées -->
                    @foreach($actions as $action)
                        @if(is_array($action))
                            <button class="btn btn-outline-{{ $action['color'] ?? 'secondary' }} custom-action"
                                    data-id="{{ $item['id'] ?? $loop->index }}"
                                    data-action="{{ $action['name'] }}"
                                    title="{{ $action['title'] }}">
                                <i class="fas fa-{{ $action['icon'] }}"></i>
                            </button>
                        @endif
                    @endforeach
                </div>
            </td>
        @endif
    </tr>
@endforeach