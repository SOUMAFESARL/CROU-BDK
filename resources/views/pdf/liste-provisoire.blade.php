<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $titre }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2328; }
        .institution { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .institution td { border: 0; padding: 0; vertical-align: middle; }
        .institution td.logo { width: 62px; }
        .institution img { height: 52px; width: auto; }
        .institution .nom { font-size: 13px; color: #006400; }
        .institution .sous-titre { margin-top: 2px; font-size: 9px; color: #54595f; }
        .entete { border-top: 2px solid #006400; padding-top: 8px; margin-bottom: 10px; }
        .entete h1 { margin: 0; font-size: 15px; color: #1f2328; font-weight: normal; }
        .entete p { margin: 3px 0 0; color: #54595f; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 5px; border: 1px solid #e6e9e2; text-align: left; vertical-align: top; }
        th { background: #ebf8de; color: #006400; font-weight: normal; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        td.rang { text-align: center; width: 28px; }
        .vide { padding: 16px; text-align: center; color: #54595f; }
        .pied { margin-top: 8px; color: #54595f; font-size: 8px; }
    </style>
</head>
<body>
    @php
        // Logo intégré au document (data URI) ; un logo introuvable sur ce serveur n'est pas affiché.
        $logo = systemLogoDocument();
    @endphp
    <table class="institution">
        <tr>
            @if (str_starts_with($logo, 'data:'))
                <td class="logo"><img src="{{ $logo }}" alt="Logo"></td>
            @endif
            <td>
                <div class="nom">{{ systemFullName() }}</div>
                <div class="sous-titre">{{ systemAppName() }} · Service de l’hébergement</div>
            </td>
        </tr>
    </table>

    <div class="entete">
        <h1>{{ $titre }}</h1>
        <p>
            Campagne {{ $campagne }}
            · {{ count($lignes) }} demande(s) recevable(s) · Édité le {{ now()->format('d/m/Y à H:i') }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($entetes as $entete)
                    <th>{{ $entete }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($lignes as $ligne)
                <tr>
                    @foreach ($ligne as $index => $valeur)
                        <td @if ($index === 0) class="rang" @endif>{{ $valeur === '' || $valeur === null ? '—' : $valeur }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="vide" colspan="{{ count($entetes) }}">Aucune demande recevable dans cette liste.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="pied">Liste provisoire indicative : le classement ne vaut pas attribution d’une chambre.</p>
</body>
</html>
