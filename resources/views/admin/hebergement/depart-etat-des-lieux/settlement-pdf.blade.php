<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><title>Décompte de sortie</title>
<style>body{font-family:DejaVu Sans,sans-serif;color:#1f2937;font-size:12px}.header{border-bottom:3px solid #1f7c43;padding-bottom:14px;margin-bottom:22px}.logo{width:70px;height:70px;object-fit:contain;float:left;margin-right:18px}h1{color:#1f7c43;margin:8px 0 4px;font-size:22px}.clear{clear:both}.grid{width:100%;margin:15px 0;border-collapse:collapse}.grid td{width:50%;padding:8px;border:1px solid #d1d5db}.amounts{width:100%;border-collapse:collapse;margin-top:18px}.amounts th,.amounts td{padding:9px;border:1px solid #d1d5db;text-align:left}.amounts th{background:#eef7f1}.right{text-align:right!important}.total{font-weight:bold;background:#f8fafc}.footer{margin-top:35px;border-top:1px solid #d1d5db;padding-top:12px;color:#6b7280;font-size:10px}</style></head><body>
@php
    $logo = systemLogoDocument();
    $settlement = $departure->settlement;
@endphp
<div class="header"><img class="logo" src="{{ $logo }}"><h1>Décompte final de sortie</h1><div>{{ systemConfig('app_name', config('app.name')) }}</div><div>Référence : {{ $departure->id_demande_sortie }}</div><div class="clear"></div></div>
<table class="grid"><tr><td><strong>Étudiant :</strong> {{ $departure->etudiant->name ?? 'N/A' }}<br><strong>Matricule :</strong> {{ $departure->etudiant->matricule ?? 'N/A' }}</td><td><strong>Chambre :</strong> {{ $departure->chambre->numero_chambre ?? 'N/A' }}<br><strong>Cité :</strong> {{ $departure->chambre?->cite?->name ?? 'N/A' }}</td></tr><tr><td><strong>Demande :</strong> {{ $departure->date_demande?->format('d/m/Y') }}</td><td><strong>Sortie prévue :</strong> {{ $departure->date_sortie_prevue?->format('d/m/Y') }}</td></tr></table>
<table class="amounts"><thead><tr><th>Désignation</th><th class="right">Montant</th></tr></thead><tbody>
<tr><td>Pénalité de préavis ({{ $settlement->preavis_effectif_jours }}/{{ $settlement->preavis_requis_jours }} jours)</td><td class="right">{{ number_format((float)$settlement->penalite_preavis,0,',',' ') }} FCFA</td></tr>
<tr><td>Dégradations constatées</td><td class="right">{{ number_format((float)$settlement->total_degradations,0,',',' ') }} FCFA</td></tr>
<tr class="total"><td>Total des retenues</td><td class="right">{{ number_format((float)$settlement->total_retenues,0,',',' ') }} FCFA</td></tr>
<tr><td>Montant imputé sur la caution</td><td class="right">{{ number_format((float)$settlement->impute_caution,0,',',' ') }} FCFA</td></tr>
<tr><td>Solde débiteur de l'étudiant</td><td class="right">{{ number_format((float)$settlement->solde_debiteur,0,',',' ') }} FCFA</td></tr>
<tr class="total"><td>Caution à restituer</td><td class="right">{{ number_format((float)$settlement->caution_a_restituer,0,',',' ') }} FCFA</td></tr>
<tr><td>Caution effectivement restituée</td><td class="right">{{ number_format((float)$settlement->caution_restituee,0,',',' ') }} FCFA</td></tr>
</tbody></table>
<div class="footer">Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ systemConfig('app_name', config('app.name')) }}</div>
</body></html>
