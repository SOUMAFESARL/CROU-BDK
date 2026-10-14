
@props(['title', 'icon' => 'bi-wrench', 'parameters'])

@if($parameters && count($parameters) > 0)
    <h6 class="section-title"><i class="bi {{ $icon }} me-1"></i> {{ $title }}</h6>
    <div class="table-responsive mb-4">
        <table class="table param-table">
            <thead class="table-light">
            <tr>
                <th>Paramètre</th>
                <th>Type</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>
            @foreach($parameters as $param)
                <tr>
                    <td>
                        <code>{{ $param['name'] ?? "" }}</code>

                        @if(isset($param['type']))
                            <span class="badge badge-type">{{ $param['type'] }}</span>
                        @endif

                        @if(isset($param['required']) && $param['required'])
                            <span class="badge badge-requis">requis</span>
                        @else
                            <span class="badge badge-optionnel">optionnel</span>
                        @endif

                        @if(isset($param['default']))
                            <small class="param-constraints">Défaut: {{ $param['default'] }}</small>
                        @endif
                    </td>
                    <td>
                        {{ $param['type'] ?? 'string' }}
                        @if(isset($param['format']))
                            <br><small class="text-muted">{{ $param['format'] }}</small>
                        @endif
                    </td>
                    <td>
                        {{ $param['description'] ?? '' }}

                        @if(isset($param['enum']))
                            <br>
                            <small class="param-constraints">
                                <i class="bi bi-list-ul me-1"></i>Valeurs possibles:
                                {{ implode(', ', $param['enum']) }}
                            </small>
                        @endif

                        @if(isset($param['minimum']) || isset($param['maximum']) || isset($param['min_length']) || isset($param['max_length']))
                            <br>
                            <small class="param-constraints">
                                <i class="bi bi-rulers me-1"></i>
                                @if(isset($param['minimum'])) min: {{ $param['minimum'] }} @endif
                                @if(isset($param['maximum'])) max: {{ $param['maximum'] }} @endif
                                @if(isset($param['min_length'])) min length: {{ $param['min_length'] }} @endif
                                @if(isset($param['max_length'])) max length: {{ $param['max_length'] }} @endif
                            </small>
                        @endif

                        @if(isset($param['example']))
                            <br>
                            <small class="param-constraints">
                                <i class="bi bi-lightbulb me-1"></i>
                                Exemple: <span class="param-example">{{ $param['example'] }}</span>
                            </small>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif