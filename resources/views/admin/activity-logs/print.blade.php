<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Journal d'activité — {{ systemConfig('app_name', config('app.name')) }}</title>
    <link rel="icon" href="{{ systemLogo('files/assets/logo/logo.jpg') }}" type="image/x-icon">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: 'DM Sans', 'Segoe UI', system-ui, sans-serif; font-size: 12px; color: #1f2328; background: #fff; }
        header { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-bottom: 12px; border-bottom: 2px solid #006400; margin-bottom: 16px; }
        header img { height: 48px; width: auto; }
        h1 { margin: 0; font-size: 18px; color: #006400; }
        .meta { color: #54595f; font-size: 11px; text-align: right; }
        .criteria { display: flex; flex-wrap: wrap; gap: 6px 16px; margin-bottom: 12px; color: #54595f; }
        .criteria strong { color: #1f2328; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border: 1px solid #e6e9e2; text-align: left; vertical-align: top; }
        th { background: #ebf8de; color: #006400; font-weight: 600; }
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .actions { margin-bottom: 16px; display: flex; gap: 8px; }
        .actions button { cursor: pointer; border: 0; border-radius: 8px; padding: 8px 16px; font: inherit; font-weight: 600; background: #006400; color: #fff; }
        .actions button.secondary { background: #fff; color: #54595f; border: 1px solid #d1d5db; }
        footer { margin-top: 12px; color: #54595f; font-size: 11px; }
        @media print {
            body { padding: 0; }
            .actions { display: none; }
            @page { size: A4 landscape; margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Imprimer</button>
        <button type="button" class="secondary" onclick="window.close()">Fermer</button>
    </div>

    <header>
        <div style="display:flex;align-items:center;gap:12px">
            <img src="{{ systemLogo('files/assets/logo/logo.jpg') }}" alt="">
            <div>
                <h1>Journal d'activité</h1>
                <div>{{ $title }}</div>
            </div>
        </div>
        <div class="meta">
            {{ systemConfig('app_name', config('app.name')) }}<br>
            Imprimé le {{ $printedAt }}
        </div>
    </header>

    @if ($criteria)
        <div class="criteria">
            @foreach ($criteria as $label => $value)
                <span>{{ $label }} : <strong>{{ $value }}</strong></span>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Date et heure</th>
                @if ($canViewAll)
                    <th>Utilisateur</th>
                @endif
                <th>Action</th>
                <th>Adresse IP</th>
                <th>Résultat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td style="white-space:nowrap">{{ $log['date'] ?? '—' }}</td>
                    @if ($canViewAll)
                        <td>{{ $log['actor'] }}@if ($log['actor_type']) <span style="color:#54595f">({{ $log['actor_type'] }})</span>@endif</td>
                    @endif
                    <td>{{ $log['action'] }}</td>
                    <td>{{ $log['ip'] ?? '—' }}</td>
                    <td>{{ $log['status'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer>{{ $logs->count() }} activité(s)</footer>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
