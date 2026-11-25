<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche de demande d’hébergement {{ $reference }}</title>
    {{-- Fiche remise à l'étudiant après l'envoi de sa demande (FicheDemandeService), sans l'état du dossier. --}}
    <style>
        @page { margin: 12mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2328; }
        table { width: 100%; border-collapse: collapse; }
        .institution td { vertical-align: middle; padding: 0; }
        .institution td.logo { width: 70px; }
        .institution img { height: 58px; width: auto; }
        .institution .nom { font-size: 14px; color: #006400; }
        .institution .sous-titre { margin-top: 3px; font-size: 9px; color: #54595f; }
        .institution td.numero { text-align: right; width: 210px; }
        .numero .titre { font-size: 14px; color: #1f2328; }
        .numero .ref { margin-top: 3px; font-size: 10px; color: #006400; }
        .numero .date { margin-top: 2px; font-size: 9px; color: #54595f; }
        .filet { border-top: 2px solid #006400; margin: 8px 0; }
        .campagne { margin-bottom: 8px; padding: 6px 10px; background: #ebf8de; border: 1px solid #86bb46; color: #006400; }
        h2 { margin: 0 0 3px; font-size: 9.5px; font-weight: normal; color: #006400; text-transform: uppercase; letter-spacing: 0.5px; }
        .bloc { margin-bottom: 8px; }
        .grille td { padding: 2.5px 6px; border: 1px solid #e6e9e2; vertical-align: top; }
        .grille td.terme { width: 32%; background: #f8f8f8; color: #54595f; }
        .mention { margin-top: 6px; font-size: 8.5px; color: #54595f; }
        .pied { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #54595f; border-top: 1px solid #e6e9e2; padding-top: 4px; }
    </style>
</head>
<body>
    @php
        // Logo intégré au document (data URI) ; un logo introuvable sur ce serveur n'est pas affiché.
        $logo = systemLogoDocument();
    @endphp

    <div class="pied">
        {{ systemAppName() }} — Fiche de demande n° {{ $reference }} éditée le {{ now()->format('d/m/Y à H:i') }}. Conservez cette fiche : elle peut vous être demandée.
    </div>

    <table class="institution">
        <tr>
            @if (str_starts_with($logo, 'data:'))
                <td class="logo"><img src="{{ $logo }}" alt="Logo"></td>
            @endif
            <td>
                <div class="nom">{{ systemAppName() }}</div>
                <div class="sous-titre">{{ systemFullName() }} · Service de l’hébergement</div>
            </td>
            <td class="numero">
                <div class="titre">Fiche de demande</div>
                <div class="ref">N° {{ $reference }}</div>
                @if ($deposeeLe)
                    <div class="date">Déposée le {{ $deposeeLe }}</div>
                @endif
            </td>
        </tr>
    </table>
    <div class="filet"></div>

    <div class="campagne">Demande d’hébergement en résidence universitaire — {{ $campagne }}</div>

    @foreach ($rubriques as $titre => $lignes)
        <div class="bloc">
            <h2>{{ $titre }}</h2>
            <table class="grille">
                @foreach ($lignes as $libelle => $valeur)
                    <tr><td class="terme">{{ $libelle }}</td><td>{{ \Illuminate\Support\Str::limit((string) $valeur, 400) }}</td></tr>
                @endforeach
            </table>
        </div>
    @endforeach

    <div class="bloc">
        <h2>Pièces jointes</h2>
        <table class="grille">
            @forelse ($pieces as $numero => $piece)
                <tr><td class="terme">Pièce {{ $numero + 1 }}</td><td>{{ $piece }}</td></tr>
            @empty
                <tr><td>Aucune pièce jointe.</td></tr>
            @endforelse
        </table>
    </div>

    @if ($restreinte)
        <div class="mention">Les coordonnées et la pièce d’identité déjà enregistrées ne figurent pas sur cette version : la fiche complète est disponible dans votre espace étudiant.</div>
    @endif
</body>
</html>
