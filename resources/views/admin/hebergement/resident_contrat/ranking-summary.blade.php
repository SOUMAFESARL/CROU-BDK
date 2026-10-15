{{-- Après « Attribution automatique » : classement utilisé. --}}
@if(session('allocation_result'))
    @php
        $allocationReport = session('allocation_result');
        $classement = collect($allocationReport['allocated'] ?? [])->merge($allocationReport['not_allocated'] ?? [])->sortBy('rang');
    @endphp
    <div class="ca-card">
        <div class="ca-card-header">
            <h2 class="ca-card-title">
                <span class="ca-kpi-icon tone-amber"><x-admin-icon name="zap" /></span>
                Classement utilisé lors de l’attribution
            </h2>
            <span class="ca-card-meta">{{ count($allocationReport['allocated'] ?? []) }} réservation(s) sur {{ $classement->count() }} candidat(s)</span>
        </div>
        <div class="ca-table-wrap">
            <table class="ca-table">
                <thead>
                <tr><th class="is-num">Rang</th><th>Candidat</th><th>Score</th><th>Résultat</th></tr>
                </thead>
                <tbody>
                @foreach($classement as $entry)
                    <tr>
                        <td class="is-num ca-mono">{{ $entry['rang'] ?? '—' }}</td>
                        <td class="ca-strong">{{ $entry['name'] }}</td>
                        <td class="ca-mono ca-nowrap">{{ isset($entry['score']) ? number_format($entry['score'], 2, ',', ' ').' / 100' : '—' }}</td>
                        <td>
                            @if(isset($entry['chambre']))
                                <span class="ca-badge tone-green"><x-admin-icon name="check" size="14" /> Chambre {{ $entry['chambre'] }} réservée</span>
                            @else
                                <span class="ca-badge tone-red"><x-admin-icon name="x" size="14" /> {{ $entry['reason'] ?? 'Non attribué' }}</span>
                            @endif
                            <span class="ca-muted ca-small" style="display:block;margin-top:4px">
                                @if($entry['scoring_version'] ?? null)
                                    Barème #{{ $entry['scoring_version'] }} · Priorité {{ $entry['priority'] }}
                                @else
                                    Classement provisoire
                                @endif
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
