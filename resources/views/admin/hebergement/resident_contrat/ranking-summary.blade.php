<div class="bg-blue-50 border border-blue-200 rounded p-4 mb-6">
    <strong>Ordre de l’attribution automatique</strong>
    <p>Le barème actif de la campagne est utilisé : priorité PMR si activée, score décroissant, puis ancienneté du dossier.</p>
    <p>Sans barème actif, le classement provisoire par score d’éligibilité et ancienneté reste appliqué. Configurez les règles dans Paramétrage hébergement → Scoring.</p>
</div>
@if(session('allocation_result'))
    @php($allocationReport = session('allocation_result'))
    <section class="bg-white rounded border p-4 mb-6">
        <h2 class="text-lg font-semibold mb-3">Classement utilisé lors de l’attribution</h2>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead><tr><th>Rang</th><th>Candidat</th><th>Score /100</th><th>Résultat</th></tr></thead>
                <tbody>
                @foreach(collect($allocationReport['allocated'] ?? [])->merge($allocationReport['not_allocated'] ?? [])->sortBy('rang') as $entry)
                    <tr><td>{{ $entry['rang'] ?? '—' }}</td><td>{{ $entry['name'] }}</td>
                    <td>{{ isset($entry['score']) ? number_format($entry['score'], 2, ',', ' ').' /100' : '—' }}</td>
                    <td>@if($entry['scoring_version'] ?? null)<small>Barème #{{ $entry['scoring_version'] }} · Priorité {{ $entry['priority'] }}</small><br>@else<small>Classement provisoire</small><br>@endif{{ isset($entry['chambre']) ? 'Chambre '.$entry['chambre'].' réservée' : ($entry['reason'] ?? 'Non attribué') }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
