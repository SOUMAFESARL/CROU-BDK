<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{{ $snapshot['title'] }}</title>
<style>
@page{margin:45px 40px 55px}body{font-family:"DejaVu Sans",sans-serif;font-size:10px;color:#233344;line-height:1.55}h1{font-size:21px;color:#1f6543;margin:12px 0}h2{font-size:14px;margin-top:22px}.header{border-bottom:2px solid #1f6543;padding-bottom:12px}.meta{font-size:9px;color:#536775}.body{white-space:pre-wrap;word-wrap:break-word;margin:22px 0}.draft{background:#fff0c0;color:#755400;border:1px solid #d5b453;padding:10px;text-align:center;font-weight:bold}.schedule{border-collapse:collapse;width:100%;font-size:9px;table-layout:fixed}.schedule th,.schedule td{border:1px solid #bfcbd0;padding:7px;text-align:left;word-wrap:break-word}.schedule th{background:#eef4f0}.schedule tr{page-break-inside:avoid}.signature{display:inline-block;vertical-align:top;width:45%;padding:12px 2%;page-break-inside:avoid;margin-top:12px}.signature img{max-width:190px;max-height:85px}.footer{position:fixed;bottom:-34px;font-size:8px;color:#677682;left:0;right:0;border-top:1px solid #dbe1e5;padding-top:6px}.page:after{content:counter(page)}
.legal-rich{white-space:normal}.legal-rich table{width:100%;border-collapse:collapse;table-layout:fixed}.legal-rich th,.legal-rich td{border:1px solid #bcc8ce;padding:6px;word-wrap:break-word}.legal-rich p{margin:0 0 10px}.legal-rich ul,.legal-rich ol{padding-left:24px}.legal-rich h1,.legal-rich h2,.legal-rich h3{page-break-after:avoid}
</style>
</head>
<body>
<div class="footer">{{ $snapshot['institution'] }} · {{ $snapshot['reference'] }} · Version {{ $snapshot['version_label'] }} @if($snapshot['draft'])· BROUILLON @endif <span class="page"> · Page </span></div>
@if($snapshot['draft'])<div class="draft">BROUILLON — DOCUMENT NON VALIDÉ</div>@endif
<div class="header">
    <strong>{{ $snapshot['institution'] }}</strong>
    <h1>{{ $snapshot['title'] }}</h1>
    <div class="meta">Référence : {{ $snapshot['reference'] ?: 'À renseigner' }} · Version : {{ $snapshot['version_label'] }}<br>
        Date d’application : {{ $snapshot['effective_date'] ?: 'À renseigner' }}
        @if($snapshot['academic_year']) · Année universitaire : {{ $snapshot['academic_year'] }}@endif
    </div>
    @if($snapshot['scope_label'])<p>{{ $snapshot['scope_label'] }}</p>@endif
</div>
@if(($snapshot['body_format'] ?? 'text') === 'html')
<div class="body legal-rich">{!! \App\Http\Services\LegalDocumentHtml::clean($snapshot['body']) !!}</div>
@else
<div class="body">{{ $snapshot['body'] }}</div>
@endif
@if($snapshot['schedule'])
    <h2>Calendrier des visites ou interventions</h2>
    @include('pdf.legal-document-schedule')
@endif
<p>@if($snapshot['location'])Lieu : {{ $snapshot['location'] }}.@endif @if($snapshot['contact'])Contact : {{ $snapshot['contact'] }}.@endif</p>
@foreach($snapshot['signatures'] as $signature)
<div class="signature">
    <strong>{{ $signature['role'] }}</strong><br>
    {{ $signature['name'] ?: 'Nom à renseigner' }}<br>
    @if($signature['image'])<img src="{{ $signature['image'] }}" alt="Signature">
    @else<span class="meta">Signature non configurée ou désactivée</span>@endif
</div>
@endforeach
@if($snapshot['validated_at'])<p class="meta">Version validée le {{ $snapshot['validated_at'] }}.</p>@endif
</body>
</html>
