<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Paiement de loyer')</title>
    <link rel="icon" href="{{ asset('admin/assets/images/logos/crou_bdk.jpeg') }}?v=20261015" type="image/jpeg">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f6fa;color:#18354b;font:16px/1.5 system-ui,sans-serif}
        main{max-width:1000px;margin:32px auto;padding:0 20px}a{color:#146a91}
        .card{background:white;border:1px solid #dbe5ee;border-radius:16px;padding:24px;margin:20px 0;box-shadow:0 8px 24px #18354b08}
        h1{font-size:28px;line-height:1.2}h2{font-size:20px}label{display:block;margin:16px 0 6px;font-weight:600}
        select,input{width:100%;padding:12px;border:1px solid #a9bacb;border-radius:8px;background:white;font:inherit}
        button,.button{display:inline-block;padding:12px 18px;border:0;border-radius:8px;background:#176d91;color:white;font:inherit;text-decoration:none;cursor:pointer}
        button:disabled{opacity:.5;cursor:wait}.muted{color:#577184}.notice{padding:14px;border-radius:8px;background:#eef6ff}
        .error{background:#fff0ef;color:#8b2929}.success{background:#e8f7ee;color:#18663b}.actions{display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:20px}
        .amount{font-size:36px;font-weight:700;margin:12px 0}table{width:100%;border-collapse:collapse}th,td{padding:12px 8px;text-align:left;border-bottom:1px solid #e3eaf0}th{font-size:var(--app-font-min, 15px);color:#577184}.table-scroll{overflow:auto}
        code{overflow-wrap:anywhere}dl{display:grid;grid-template-columns:auto 1fr;gap:8px 18px}dd{margin:0;overflow-wrap:anywhere}
        @media(max-width:600px){main{margin:20px auto;padding:0 12px}.card{padding:18px}dl{grid-template-columns:1fr;gap:3px}dd{margin-bottom:12px}}
    </style>
    <link rel="stylesheet" href="{{ asset('files/assets/css/typography.css') }}?v=20260923">
</head>
<body>
<main>
    <a href="{{ route('etudiants.dashboard') }}">← Mon espace étudiant</a>
    @yield('content')
</main>
@yield('scripts')
</body>
</html>
