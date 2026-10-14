<table class="legal-table schedule">
    <thead><tr><th>Date / heure</th><th>Visite ou intervention</th><th>Lieu</th><th>Responsable</th></tr></thead>
    <tbody>@foreach($snapshot['schedule'] as $event)
        <tr><td>{{ \Carbon\Carbon::parse($event['date'])->format('d/m/Y') }}<br>{{ $event['time'] ?? '' }}</td>
            <td>{{ $event['title'] }}</td><td>{{ $event['location'] ?? '' }}</td><td>{{ $event['responsible'] ?? '' }}</td></tr>
    @endforeach</tbody>
</table>
