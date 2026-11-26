<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Quittance de loyer – {{ $periode }}</title>
    {{-- Quittance d'un mois de loyer payé (espace étudiant › Mes paiements › Mes loyers) : une seule page A4. --}}
    <style>
        @page { margin: 14mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2328; }
        table { width: 100%; border-collapse: collapse; }
        .institution td { vertical-align: middle; padding: 0; }
        .institution td.logo { width: 70px; }
        .institution img { height: 58px; width: auto; }
        .institution .nom { font-size: 14px; color: #006400; }
        .institution .sous-titre { margin-top: 3px; font-size: 9px; color: #54595f; }
        .institution td.numero { text-align: right; width: 210px; }
        .numero .titre { font-size: 15px; color: #1f2328; }
        .numero .ref { margin-top: 3px; font-size: 10px; color: #006400; }
        .numero .date { margin-top: 2px; font-size: 9px; color: #54595f; }
        .filet { border-top: 2px solid #006400; margin: 10px 0 12px; }
        h2 { margin: 0 0 5px; font-size: 10px; font-weight: normal; color: #006400; text-transform: uppercase; letter-spacing: 0.5px; }
        .bloc { margin-bottom: 12px; }
        .grille td { padding: 4px 6px; border: 1px solid #e6e9e2; vertical-align: top; }
        .grille td.terme { width: 32%; background: #f8f8f8; color: #54595f; }
        .montant { margin-bottom: 12px; padding: 10px 12px; background: #ebf8de; border: 1px solid #86bb46; }
        .montant td { padding: 0; vertical-align: middle; }
        .montant .libelle { color: #54595f; font-size: 10px; }
        .montant .valeur { text-align: right; font-size: 17px; color: #006400; }
        .attestation { margin-bottom: 12px; line-height: 1.6; }
        .detail th, .detail td { padding: 3px 6px; border: 1px solid #e6e9e2; text-align: left; }
        .detail th { background: #ebf8de; color: #006400; font-weight: normal; }
        .detail td.somme, .detail th.somme { text-align: right; white-space: nowrap; }
        .pied { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #54595f; border-top: 1px solid #e6e9e2; padding-top: 4px; }
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
                <div class="nom">{{ systemAppName() }}</div>
                <div class="sous-titre">{{ systemFullName() }} · Service de l’hébergement</div>
            </td>
            <td class="numero">
                <div class="titre">Quittance de loyer</div>
                <div class="ref">N° {{ $numero }}</div>
                <div class="date">{{ $datePaiement ? 'Loyer payé le '.$datePaiement : 'Loyer soldé' }}</div>
            </td>
        </tr>
    </table>
    <div class="filet"></div>

    <div class="bloc">
        <h2>Locataire</h2>
        <table class="grille">
            <tr><td class="terme">Civilité</td><td>{{ $civilite ?? '—' }}</td></tr>
            <tr><td class="terme">Nom et prénoms</td><td>{{ $etudiant->name ?: '—' }}</td></tr>
            <tr><td class="terme">Matricule</td><td>{{ $etudiant->matricule ?: '—' }}</td></tr>
            <tr><td class="terme">Filière</td><td>{{ $parcours['filiere'] ?? '—' }}</td></tr>
            <tr><td class="terme">Niveau d’étude</td><td>{{ $parcours['niveau'] ?? '—' }}</td></tr>
            <tr><td class="terme">Chambre</td><td>{{ $chambre ?: '—' }}</td></tr>
            @if ($campagne)
                <tr><td class="terme">Campagne</td><td>{{ $campagne }}</td></tr>
            @endif
            <tr><td class="terme">Période</td><td>{{ $periode }}</td></tr>
        </table>
    </div>

    @if ($montant)
        <div class="montant">
            <table>
                <tr>
                    <td class="libelle">Loyer de {{ $periode }}</td>
                    <td class="valeur">{{ $montant }}</td>
                </tr>
            </table>
        </div>
    @endif

    <p class="attestation">
        Le service de l’hébergement atteste avoir reçu de {{ $etudiant->name ?: 'l’étudiant' }} le paiement intégral
        du loyer du mois de {{ $periode }}{{ $chambre ? ' pour la chambre '.$chambre : '' }}, et lui en donne quittance,
        sous réserve des sommes restant dues pour d’autres périodes.
    </p>

    @if (count($paiements) > 0)
        <div class="bloc">
            <h2>Paiements enregistrés</h2>
            <table class="detail">
                <thead><tr><th>N° de paiement</th><th>Date</th><th>Mode de règlement</th><th class="somme">Montant pour {{ $periode }}</th></tr></thead>
                <tbody>
                    @foreach ($paiements as $paiement)
                        <tr>
                            <td>{{ $paiement['numero'] }}</td>
                            <td>{{ $paiement['date'] ?? '—' }}</td>
                            <td>{{ $paiement['methode'] }}</td>
                            <td class="somme">{{ $paiement['montant'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="pied">
        {{ systemAppName() }} — Quittance n° {{ $numero }} éditée le {{ now()->format('d/m/Y à H:i') }}. Un paiement couvrant plusieurs mois est réparti à parts égales entre eux.
    </div>
</body>
</html>
