<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>État des lieux {{ $inventory->type_etat === 'entree' ? 'd’entrée' : 'de sortie' }}</title>
<style>
@page { margin: 38px 40px 58px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #243b39; line-height: 1.45; }
.header { border-bottom: 4px solid #146b55; padding-bottom: 18px; margin-bottom: 22px; }
.brand { font-size: 11pt; color: #146b55; font-weight: bold; text-transform: uppercase; }
h1 { font-size: 25pt; line-height: 1.2; margin: 12px 0; color: #164c40; }
h2 { font-size: 14pt; color: #146b55; margin: 24px 0 12px; page-break-after: avoid; }
h3 { font-size: 12pt; margin: 16px 0 8px; page-break-after: avoid; }
p { margin: 7px 0; }
.muted { color: #596d68; }
.reference { font-size: 11pt; word-wrap: break-word; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
td, th { padding: 10px; vertical-align: top; word-wrap: break-word; }
thead { display: table-header-group; }
.items th { background: #146b55; color: white; text-align: left; font-weight: bold; }
.items td { border-bottom: 1px solid #dce6e2; }
.items tr:nth-child(even) td { background: #f2f7f5; }
tr { page-break-inside: avoid; }
.details td { background: #f2f7f5; border: 4px solid white; }
.label { color: #596d68; display: block; margin-bottom: 4px; }
.notice { padding: 12px; border-left: 4px solid #bc8428; background: #fff8e9; }
.observation { padding: 14px; background: #f2f7f5; white-space: pre-line; word-wrap: break-word; }
.photos td { text-align: center; border: 4px solid white; background: #f2f7f5; }
.photos img { max-width: 215px; max-height: 165px; }
.signature-block { page-break-inside: avoid; }
.signatures td { border: 1px solid #dce6e2; width: 50%; }
.signature { height: 85px; padding-top: 12px; }
.signature img { max-height: 75px; max-width: 210px; }
.footer { position: fixed; bottom: -35px; left: 0; font-size: 11pt; color: #596d68; }
</style>
</head>
<body>
<div class="footer">État des lieux · {{ $inventory->date_etat?->format('d/m/Y') ?? 'Date non renseignée' }}</div>
<div class="header">
    <div class="brand">{{ systemConfig('app_name', config('app.name')) }} · Hébergement</div>
    <h1>État des lieux<br>{{ $inventory->type_etat === 'entree' ? 'd’entrée' : 'de sortie' }}</h1>
    <p class="reference">Référence : {{ $inventory->getKey() }}</p>
    <p class="muted">Constat du {{ $inventory->date_etat?->format('d/m/Y') ?? '—' }}</p>
</div>
@if(!$signatures['crou'] || !$signatures['etudiant'])
<p class="notice">Document en cours de validation : une ou plusieurs signatures sont absentes ou indisponibles.</p>
@endif
<h2>01 · Résident et logement</h2>
<table class="details">
<tr><td><span class="label">Résident</span><strong>{{ $inventory->etudiant?->name ?? 'Non renseigné' }}</strong></td>
<td><span class="label">Matricule</span>{{ $inventory->etudiant?->matricule ?? 'Non renseigné' }}</td></tr>
<tr><td><span class="label">Résidence</span>{{ $inventory->cite?->name ?? 'Non renseignée' }}</td>
<td><span class="label">Bâtiment / Chambre</span>{{ $inventory->batiment?->name ?? '—' }} / {{ $inventory->chambre?->numero_chambre ?? '—' }}</td></tr>
</table>
<h2>02 · État des pièces et équipements</h2>
@forelse($inventory->pieces as $piece)
<h3>{{ $loop->iteration }}. {{ $piece->name_piece }}</h3>
<table class="items">
<thead><tr><th style="width:30%">Élément</th><th style="width:20%">État constaté</th><th style="width:50%">Observations</th></tr></thead>
<tbody>
@forelse($piece->elements as $element)
<tr><td>{{ $element->name_element }}</td><td>{{ ['bon'=>'Bon','moyen'=>'Moyen','mauvais'=>'Mauvais'][$element->etat] ?? ($element->etat ?: 'Non renseigné') }}</td><td>{{ $element->observation ?: 'Aucune observation' }}</td></tr>
@empty<tr><td colspan="3">Aucun élément renseigné pour cette pièce.</td></tr>@endforelse
</tbody></table>
@empty<p class="muted">Aucune pièce renseignée.</p>@endforelse
<h2>03 · Observations générales et clés</h2>
<div class="observation">{{ $inventory->observation_generale ?: 'Aucune observation générale.' }}</div>
<p><strong>Nombre de clés consigné :</strong> {{ $inventory->nbre_cle_recus ?? 'Non renseigné' }}</p>
<h2>04 · Photographies du constat</h2>
@php($photoCount = $inventory->pieces->sum(fn ($piece) => $piece->images->count() + $piece->elements->filter(fn ($element) => filled($element->photo))->count()))
@if(!$photoCount)<p class="muted">Aucune photographie jointe à cet état des lieux.</p>@endif
@foreach($inventory->pieces as $piece)
@foreach($piece->elements->filter(fn ($element) => filled($element->photo)) as $element)
<h3>{{ $piece->name_piece }} — {{ $element->name_element }}</h3>
<table class="photos"><tr><td>
@if($elementPhotos[$element->getKey()] ?? null)<img src="{{ $elementPhotos[$element->getKey()] }}" alt="Photo de l’élément">
@else<p class="muted">Photographie indisponible</p>@endif
</td></tr></table>
@endforeach
@if($piece->images->isNotEmpty())
<h3>{{ $piece->name_piece }}</h3>
<table class="photos">
@foreach($piece->images->chunk(2) as $row)
<tr>@foreach($row as $image)<td>
@if($photos[$image->getKey()] ?? null)<img src="{{ $photos[$image->getKey()] }}" alt="Photographie du constat">
@else<p class="muted">Photographie indisponible</p>@endif
</td>@endforeach</tr>
@endforeach
</table>
@endif
@endforeach
<div class="signature-block">
<h2>05 · Signatures</h2>
<table class="signatures"><tr>
@foreach(['crou'=>'Représentant du CROU', 'etudiant'=>'Résident'] as $key=>$label)
<td><strong>{{ $label }}</strong><div class="signature">
@if($signatures[$key])<img src="{{ $signatures[$key] }}" alt="Signature {{ $label }}">
@else<span class="muted">Signature en attente ou indisponible</span>@endif
</div></td>
@endforeach
</tr></table>
<p class="muted">Copie des informations et signatures enregistrées pour cet état des lieux.</p>
</div>
</body></html>
