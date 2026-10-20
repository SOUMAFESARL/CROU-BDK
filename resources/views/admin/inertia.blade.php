<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ systemLogo() }}">
    <title inertia>{{ systemAppName() }}</title>
    {{ systemBrandStyle() }}
    @viteReactRefresh
    @vite('resources/js/admin.tsx')
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
