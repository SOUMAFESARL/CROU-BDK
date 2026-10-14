{{-- resources/views/admin/hebergement/vie_residentielle/paiement/show.blade.php --}}
@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'finances'
])

@section('title', 'Détail du paiement')

@push('styles')
<style>
    .detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 4px;
    }

    .detail-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06);
        padding: 24px;
        margin-bottom: 24px;
    }

    .detail-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #eaeef4;
    }

    .detail-card .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #0b2b4a;
        margin: 0;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
    }

    .detail-item {
        padding: 12px 16px;
        background: #f8fafc;
        border-radius: 8px;
    }

    .detail-item .label {
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: #5b7b99 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
        margin-bottom: 4px;
    }

    .detail-item .value {
        font-size: 16px;
        font-weight: 600;
        color: #0a2b44;
    }

    .detail-item .value .badge {
        font-size: var(--app-font-min, 15px);
    }

    .repartition-table {
        width: 100%;
        border-collapse: collapse;
        font-size: var(--app-font-min, 15px);
    }

    .repartition-table thead th {
        text-align: left;
        padding: 10px 14px;
        background: #f2f6fb;
        color: #1f4462;
        font-weight: 600;
        font-size: var(--app-font-min, 15px);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 1px solid #dae2ec;
    }

    .repartition-table tbody td {
        padding: 10px 14px;
        border-bottom: 1px solid #eaeef4;
        color: #17324b;
    }

    .repartition-table tbody tr:last-child td {
        border-bottom: none;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        background: #f0f2f5;
        color: #1f4462;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 500;
        font-size: var(--app-font-min, 15px);
        transition: 0.2s;
    }

    .back-btn:hover {
        background: #e2e6ec;
        color: #0a2b44;
    }

    .print-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        background: #1e7aa8;
        color: #fff;
        border: none;
        border-radius: 30px;
        font-weight: 500;
        font-size: var(--app-font-min, 15px);
        cursor: pointer;
        transition: 0.2s;
    }

    .print-btn:hover {
        background: #166a94;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 30px;
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
    }

    .status-badge.success {
        background: #ddf0e8;
        color: #1a6e4f;
    }

    .status-badge.warning {
        background: #fcead8;
        color: #a8621a;
    }

    .status-badge.danger {
        background: #fce1e1;
        color: #b13e3e;
    }

    .status-badge.info {
        background: #dce8f5;
        color: #1e5f8a;
    }

    .amount-highlight {
        font-size: 24px;
        font-weight: 700;
        color: #1a6e4f;
    }

    .amount-highlight.danger {
        color: #b13e3e;
    }

    @media print {
        .no-print {
            display: none !important;
        }
        .detail-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>
@endpush

@section('content')

<x-breadcrumbs
    title="Détail du paiement"
    :items="[
        'Gestion Hébergement' => route('admin.hebergement.index'),
        'Vie résidentielle' => route('admin.hebergement.vie_residentielle.index'),
        'Paiements' => route('admin.hebergement.paiements.index'),
        'Détail' => null,
    ]"
/>

<div class="detail-container">
    <!-- Boutons d'action -->
    <div class="no-print" style="display:flex; gap:12px; margin-bottom:24px;">
        @adminroute('admin.hebergement.paiements.index')
        <a href="{{ route('admin.hebergement.paiements.index') }}" class="back-btn">
            ← Retour à la liste
        </a>
        @endadminroute
        <button onclick="window.print()" class="print-btn">
            🖨️ Imprimer
        </button>
        @if($paiement->statut_paiement === 'en_attente')
        @adminroute('admin.hebergement.paiements.valider')
        <form action="{{ route('admin.hebergement.paiements.valider', $paiement->id_paiement) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn-action success" style="padding:8px 20px; font-size:var(--app-font-min, 15px);">
                ✅ Valider le paiement
            </button>
        </form>
        @endadminroute
        @endif
        <!-- @if($paiement->statut_paiement !== 'annule')
        @adminroute('admin.hebergement.paiements.annuler')
        <form action="{{ route('admin.hebergement.paiements.annuler', $paiement->id_paiement) }}" method="POST" style="display:inline;" onsubmit="return confirm('Annuler ce paiement ?')">
            @csrf
            <button type="submit" class="btn-action danger" style="padding:8px 20px; font-size:var(--app-font-min, 15px);">
                ❌ Annuler le paiement
            </button>
        </form>
        @endadminroute
        @endif -->
    </div>

    <!-- Informations générales -->
    <div class="detail-card">
        <div class="card-header">
            <h3 class="card-title">📋 Informations générales</h3>
            <span class="status-badge {{ $paiement->statut_badge }}">
                <span class="status-dot {{ $paiement->statut_paiement === 'valide' ? 'paid' : ($paiement->statut_paiement === 'en_attente' ? 'pending' : 'overdue') }}"></span>
                {{ $paiement->statut_label }}
            </span>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <span class="label">📅 Numéro de paiement</span>
                <span class="value">{{ $paiement->numero_paiement }}</span>
            </div>
            <div class="detail-item">
                <span class="label">💰 Montant</span>
                <span class="value amount-highlight">{{ formatSystemAmount($paiement->montant) }}</span>
            </div>
            <div class="detail-item">
                <span class="label">🏦 Méthode de paiement</span>
                <span class="value">{{ $paiement->methode_label }}</span>
            </div>
            <div class="detail-item">
                <span class="label">📅 Date de paiement</span>
                <span class="value">{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y H:i') : '--' }}</span>
            </div>
            <div class="detail-item">
                <span class="label">📅 Date d'échéance</span>
                <span class="value">{{ $paiement->date_echeance ? $paiement->date_echeance->format('d/m/Y') : '--' }}</span>
            </div>
            <div class="detail-item">
                <span class="label">📋 Type de paiement</span>
                <span class="value">{{ ucfirst($paiement->type_paiement) }}</span>
            </div>
            <div class="detail-item">
                <span class="label">📱 Référence transaction</span>
                <span class="value">{{ $paiement->reference_transaction ?? '--' }}</span>
            </div>
            <div class="detail-item">
                <span class="label">👤 Créé par</span>
                <span class="value">{{ $paiement->createdBy?->name ?? 'Système' }}</span>
            </div>
        </div>

        @if($paiement->notes)
        <div style="margin-top:16px; padding:12px 16px; background:#f8fafc; border-radius:8px;">
            <span class="label" style="font-size:var(--app-font-min, 15px); font-weight:600; color:#5b7b99!important; text-transform:uppercase;">📝 Notes</span>
            <p style="margin:4px 0 0; color:#17324b;">{{ $paiement->notes }}</p>
        </div>
        @endif
    </div>

    <!-- Informations du résident -->
    <div class="detail-card">
        <div class="card-header">
            <h3 class="card-title">👤 Résident</h3>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <span class="label">Nom complet</span>
                <span class="value">{{ $paiement->resident?->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="label">📋 Matricule</span>
                <span class="value">{{ $paiement->resident?->matricule ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="label">📱 Téléphone</span>
                <span class="value">{{ $paiement->resident?->telephone1 ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="label">📧 Email</span>
                <span class="value">{{ $paiement->resident?->email1 ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="label">🏢 Résidence</span>
                <span class="value">{{ $paiement->cite?->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="label">🚪 Chambre</span>
                <span class="value">{{ $paiement->chambre?->numero_chambre ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Répartition du paiement -->
    <div class="detail-card">
        <div class="card-header">
            <h3 class="card-title">📊 Répartition du paiement</h3>
            <span class="section-badge">Total : {{ formatSystemAmount($paiement->montant) }}</span>
        </div>

        @if($paiement->repartitions->isNotEmpty())
        <table class="repartition-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Mois/Année</th>
                    <th>Montant</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalRepartitions = 0;
                @endphp
                @foreach($paiement->repartitions as $rep)
                    @php
                        $totalRepartitions += $rep->montant;
                        $typeColors = [
                            'arriere' => 'danger',
                            'mois_cours' => 'success',
                            'avance' => 'info'
                        ];
                        $typeLabels = [
                            'arriere' => 'Arriéré',
                            'mois_cours' => 'Mois en cours',
                            'avance' => 'Avance'
                        ];
                    @endphp
                    <tr>
                        <td>
                            <span class="badge {{ $typeColors[$rep->type] ?? 'secondary' }}">
                                {{ $typeLabels[$rep->type] ?? $rep->type }}
                            </span>
                        </td>
                        <td>
                            @if($rep->mois && $rep->annee)
                                {{ $rep->mois }}/{{ $rep->annee }}
                            @else
                                --
                            @endif
                        </td>
                        <td><strong>{{ formatSystemAmount($rep->montant) }}</strong></td>
                        <td>{{ $rep->description ?? '--' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight:600; background:#f2f6fb;">
                    <td colspan="2" style="text-align:right;">Total réparti :</td>
                    <td>{{ formatSystemAmount($totalRepartitions) }}</td>
                    <td></td>
                </tr>
                @if($totalRepartitions != $paiement->montant)
                <tr style="font-weight:600; background:#fcead8; color:#a8621a;">
                    <td colspan="2" style="text-align:right;">Différence :</td>
                    <td>{{ formatSystemAmount($paiement->montant - $totalRepartitions) }}</td>
                    <td>⚠️ Non réparti</td>
                </tr>
                @endif
            </tfoot>
        </table>
        @else
        <p style="text-align:center; color:#5b7b99; padding:20px;">Aucune répartition disponible pour ce paiement.</p>
        @endif
    </div>

    <!-- Mois couverts -->
    <div class="detail-card">
        <div class="card-header">
            <h3 class="card-title">📅 Mois couverts par ce paiement</h3>
        </div>

        @if($paiement->mois)
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                @php
                    $moisListe = $paiement->mois;
                    if (is_string($moisListe)) {
                        $moisListe = json_decode($moisListe, true);
                    }
                    if (!is_array($moisListe)) {
                        $moisListe = [$moisListe];
                    }
                @endphp
                @foreach($moisListe as $mois)
                    <span class="badge info" style="font-size:var(--app-font-min, 15px); padding:4px 14px;">
                        {{ $mois }}
                    </span>
                @endforeach
            </div>
        @else
            <p style="text-align:center; color:#5b7b99; padding:20px;">Aucun mois spécifié.</p>
        @endif
    </div>
</div>

@endsection
