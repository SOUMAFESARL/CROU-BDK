<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu de paiement {{ $paiement->numero_paiement }}</title>
    {{-- Reçu d'un paiement (admin › Paiements, « Imprimer le reçu ») : une seule page A4, un seul reçu pour les types enregistrés ensemble. --}}
    <style>
        @page { margin: 14mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2328; }
        table { width: 100%; border-collapse: collapse; }
        .institution td { vertical-align: middle; padding: 0; }
        .institution td.logo { width: 70px; }
        .institution img { height: 58px; width: auto; }
        .institution .nom { font-size: 14px; color: #006400; }
        .institution .sous-titre { margin-top: 3px; font-size: 9px; color: #54595f; }
        .institution td.numero { text-align: right; width: 190px; }
        .numero .titre { font-size: 15px; color: #1f2328; }
        .numero .ref { margin-top: 3px; font-size: 10px; color: #006400; }
        .numero .date { margin-top: 2px; font-size: 9px; color: #54595f; }
        .filet { border-top: 2px solid #006400; margin: 10px 0 12px; }
        .alerte { margin-bottom: 10px; padding: 6px 8px; border: 1px solid #faab04; background: #fff7e6; color: #7a5200; font-size: 9px; }
        h2 { margin: 0 0 5px; font-size: 10px; font-weight: normal; color: #006400; text-transform: uppercase; letter-spacing: 0.5px; }
        .bloc { margin-bottom: 12px; }
        .grille td { padding: 4px 6px; border: 1px solid #e6e9e2; vertical-align: top; }
        .grille td.terme { width: 32%; background: #f8f8f8; color: #54595f; }
        .montant { margin-bottom: 12px; padding: 10px 12px; background: #ebf8de; border: 1px solid #86bb46; }
        .montant td { padding: 0; vertical-align: middle; }
        .montant .libelle { color: #54595f; font-size: 10px; }
        .montant .valeur { text-align: right; font-size: 17px; color: #006400; }
        .repartition th, .repartition td { padding: 3px 6px; border: 1px solid #e6e9e2; text-align: left; }
        .repartition th { background: #ebf8de; color: #006400; font-weight: normal; }
        .repartition td.somme { text-align: right; white-space: nowrap; }
        .repartition tr.total td { background: #f8f8f8; color: #006400; }
        .avec-marge { margin-bottom: 6px; }
        .pied { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #54595f; border-top: 1px solid #e6e9e2; padding-top: 4px; }
    </style>
</head>
<body>
    @php
        // Logo intégré au document (data URI) ; un logo introuvable sur ce serveur n'est pas affiché.
        $logo = systemLogoDocument();
        $resident = $paiement->resident;
        // Numéro de la chambre, puis la cité : « 1 · Cité Zanzan ».
        $chambre = collect([$paiement->chambre?->numero_chambre, $paiement->cite?->name])->filter()->join(' · ');
        $valide = $paiement->statut_paiement === 'valide';
        $plusieurs = count($details) > 1;
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
                <div class="titre">Reçu de paiement</div>
                <div class="ref">N° {{ $paiement->numero_paiement ?: $paiement->id_paiement }}</div>
                <div class="date">{{ $paiement->date_paiement ? 'Payé le '.$paiement->date_paiement->format('d/m/Y à H:i') : 'Date de paiement non renseignée' }}</div>
            </td>
        </tr>
    </table>
    <div class="filet"></div>

    @unless ($valide)
        <div class="alerte">Paiement {{ mb_strtolower($paiement->statut_label) }} : ce document ne vaut pas quittance.</div>
    @endunless

    <div class="bloc">
        <h2>Bénéficiaire</h2>
        <table class="grille">
            <tr><td class="terme">Civilité</td><td>{{ $civilite ?? '—' }}</td></tr>
            <tr><td class="terme">Nom et prénoms</td><td>{{ $resident?->name ?: '—' }}</td></tr>
            <tr><td class="terme">Matricule / N° de table</td><td>{{ $matricule ?: '—' }}</td></tr>
            <tr><td class="terme">Filière</td><td>{{ $parcours['filiere'] ?? '—' }}</td></tr>
            <tr><td class="terme">Niveau d’étude</td><td>{{ $parcours['niveau'] ?? '—' }}</td></tr>
            <tr><td class="terme">Chambre</td><td>{{ $chambre ?: 'Non attribuée (paiement avant l’attribution)' }}</td></tr>
        </table>
    </div>

    <div class="montant">
        <table>
            <tr>
                <td class="libelle">Montant {{ $valide ? 'reçu' : 'du paiement' }}</td>
                <td class="valeur">{{ $montant }}</td>
            </tr>
        </table>
    </div>

    <div class="bloc">
        <h2>Détail du paiement</h2>
        @if ($plusieurs)
            <table class="repartition avec-marge">
                <thead><tr><th>Type de paiement</th><th>N° de paiement</th><th>Mois concernés</th><th style="text-align: right;">Montant</th></tr></thead>
                <tbody>
                    @foreach ($details as $detail)
                        <tr>
                            <td>{{ $detail['type'] }}</td>
                            <td>{{ $detail['numero'] ?: '—' }}</td>
                            <td style="text-transform: capitalize;">{{ $detail['mois'] ?? '—' }}</td>
                            <td class="somme">{{ $detail['montant'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="total"><td colspan="3">Total</td><td class="somme">{{ $montant }}</td></tr>
                </tbody>
            </table>
        @endif
        <table class="grille">
            @unless ($plusieurs)
                <tr><td class="terme">Type de paiement</td><td>{{ $details[0]['type'] }}</td></tr>
                @if ($details[0]['mois'])
                    <tr><td class="terme">Mois concernés</td><td style="text-transform: capitalize;">{{ $details[0]['mois'] }}</td></tr>
                @endif
            @endunless
            <tr><td class="terme">Mode de règlement</td><td>{{ $paiement->methode_label }}</td></tr>
            @if ($paiement->methode_paiement !== 'especes')
                <tr><td class="terme">Référence de transaction</td><td>{{ $references ?: '—' }}</td></tr>
            @endif
            @if ($notes)
                <tr><td class="terme">Observations</td><td>{{ \Illuminate\Support\Str::limit($notes, 300) }}</td></tr>
            @endif
        </table>
    </div>

    @if (count($repartitions) > 0)
        <div class="bloc">
            <h2>Répartition</h2>
            <table class="repartition">
                <thead><tr><th>Affectation</th><th>Période</th><th style="text-align: right;">Montant</th></tr></thead>
                <tbody>
                    @foreach ($repartitions as $ligne)
                        <tr><td>{{ $ligne['libelle'] }}</td><td>{{ $ligne['periode'] ?? '—' }}</td><td class="somme">{{ $ligne['montant'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="pied">
        {{ systemAppName() }} — Reçu n° {{ $paiement->numero_paiement ?: $paiement->id_paiement }} édité le {{ now()->format('d/m/Y à H:i') }}. Conservez ce reçu : il peut vous être demandé.
    </div>
</body>
</html>
