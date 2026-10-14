        <div class="table-responsive">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>N° OT</th>
                    <th>Résidence</th>
                    <th>Chambre</th>
                    <th>Demandeur</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Priorité</th>
                    <th>Statut</th>
                    <th>Date création</th>@if($completed)<th>Date et heure de fin</th>@endif
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($ordres as $ordre)
                    @php
                        $rowClass = '';
                        if ($ordre->priorite === 'critique' && $ordre->statut !== 'termine') {
                            $rowClass = 'row-urgent';
                        } elseif ($ordre->statut === 'en_cours' || $ordre->statut === 'en_attente_pieces') {
                            $rowClass = 'row-in-progress';
                        } elseif ($ordre->statut === 'termine') {
                            $rowClass = 'row-resolved';
                        }
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td><strong>#{{ $ordre->numero_ot }}</strong></td>
                        <td>{{ $ordre->residence?->name ?? 'Toute la cite' }}</td>
                        <td>{{ $ordre->chambre?->numero_chambre ?? 'Toute la chambre' }}</td>
                        <td><strong> {{ $ordre->resident?->name ?? 'Tous les residents' }} </strong> <br>
                            {{ $ordre->resident?->telephone1 ?? '' }}</td>
                        <td><span class="tag {{ $ordre->categorie }}">{{ $ordre->categorie_label }}</span></td>
                        <td>{{ Str::limit($ordre->description, 30) }}</td>
                        <td>
                            <span class="priority-dot {{ $ordre->priorite }}"></span>
                            <span class="badge {{ $ordre->priorite === 'critique' ? 'danger' : ($ordre->priorite === 'elevee' ? 'warning' : 'info') }}">
                                {{ $ordre->priorite_label }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $ordre->statut_badge }}">
                                {{ $ordre->statut_label }}
                            </span>
                        </td>
                        <td>{{ $ordre->created_at ? $ordre->created_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                        @if($completed)
                            <td>{{ $ordre->date_resolution?->format('d/m/Y à H:i:s') ?? 'Non renseignée' }}</td>
                        @endif
                        <td>
                            @adminroute('admin.hebergement.maintenance.show')
<button class="btn-action primary" style="padding:2px 10px; font-size:var(--app-font-min, 15px);" onclick="window.location.href='{{ route('admin.hebergement.maintenance.show', $ordre->id_ordres_travail) }}'">📄</button>
@endadminroute

                            @if($ordre->statut !== 'termine' && $ordre->statut !== 'annule')
                                @adminroute('admin.hebergement.maintenance.edit')
<button class="btn-action warning" style="padding:2px 10px; font-size:var(--app-font-min, 15px);" onclick="window.location.href='{{ route('admin.hebergement.maintenance.edit', $ordre->id_ordres_travail) }}'">✏️</button>
@endadminroute

                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $completed ? 11 : 10 }}" style="text-align:center; padding:30px; color:var(--muted);">
                            <span style="font-size:40px; display:block; margin-bottom:10px;">🔧</span>
                            Aucun ordre de travail trouvé
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            <!-- Pagination -->
            @if($ordres instanceof \Illuminate\Pagination\LengthAwarePaginator && $ordres->hasPages())
                <div class="pagination-container" style="padding: 12px 16px; display: flex; justify-content: center; gap: 6px; flex-wrap: wrap;">
                    {{ $ordres->links() }}
                </div>
            @endif
        </div>

