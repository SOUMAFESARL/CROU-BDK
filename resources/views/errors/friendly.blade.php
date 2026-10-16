<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status }} — Action interrompue</title>
    @php try { $favicon = systemLogo(); } catch (Throwable) { $favicon = asset('files/assets/logo/logo.jpg'); } @endphp
    <link rel="icon" href="{{ $favicon }}">
    <style>*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f3f6f4;color:#24362b;font-family:Arial,sans-serif;line-height:1.6}.card{width:100%;max-width:560px;background:white;border:1px solid #dce6df;border-radius:18px;padding:36px;box-shadow:0 8px 30px #2034280d}.code{color:#1f7c43;font-weight:bold}h1{font-size:26px;margin:12px 0}p{color:#506257}.actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:24px}a,button{font:inherit;border-radius:8px;padding:10px 18px;cursor:pointer;text-decoration:none}a{background:#1f7c43;color:white}button{border:1px solid #c8d8ce;background:white;color:#24362b}a:focus-visible,button:focus-visible{outline:3px solid #91c8a6;outline-offset:3px}</style>
</head>
<body>
<main class="card">
    <span class="code">{{ $status }}</span>
    <h1>{{ $status === 404 ? 'Page introuvable' : 'Action interrompue' }}</h1>
    <p role="alert">{{ $message }}</p>
    <div class="actions">
        <button type="button" onclick="history.back()">Retour</button>
        <a href="{{ request()->is('admin', 'admin/*') ? '/admin/dashboard' : (request()->is('etudiant', 'etudiant/*') ? '/etudiant/dashboard' : '/') }}">Retour à l’accueil</a>
    </div>
</main>
</body>
</html>
