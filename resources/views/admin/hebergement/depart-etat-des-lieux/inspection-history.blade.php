<div class="table-container mb-3">
    <h5 class="mb-3"><i class="fas fa-clipboard-check me-2"></i>Historique des états des lieux réalisés</h5>
    <p class="text-muted">Tous les états des lieux d’entrée et de sortie enregistrés, du plus récent au plus ancien.</p>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Date</th><th>Étudiant</th><th>Cité / bâtiment</th><th>Chambre</th><th>Type</th><th>Observations</th></tr></thead>
        <tbody id="inspectionHistory">
        @foreach($inspectionHistory as $inspection)
            <tr data-date="{{ $inspection->date_etat?->format('Y-m-d') }}" data-cite="{{ $inspection->id_cite }}">
                <td>{{ $inspection->date_etat?->format('d/m/Y') ?? 'Non renseignée' }}</td>
                <td><strong>{{ $inspection->etudiant?->name ?? 'Étudiant indisponible' }}</strong><br><small>{{ $inspection->etudiant?->matricule }}</small></td>
                <td>{{ $inspection->cite?->name ?? '—' }} / {{ $inspection->batiment?->name ?? '—' }}</td>
                <td>{{ $inspection->chambre?->numero_chambre ?? '—' }}</td>
                <td><span class="badge {{ $inspection->type_etat === 'entree' ? 'bg-primary' : 'bg-success' }}">{{ $inspection->type_etat === 'entree' ? 'Entrée' : 'Sortie' }}</span></td>
                <td style="max-width:320px;white-space:pre-wrap">{{ $inspection->observation_generale ?: '—' }}</td>
            </tr>
        @endforeach
            <tr id="inspectionHistoryEmpty" @if($inspectionHistory->isNotEmpty()) hidden @endif><td colspan="6" class="text-center text-muted">Aucun état des lieux pour ces filtres.</td></tr>
        </tbody>
    </table></div>
</div>