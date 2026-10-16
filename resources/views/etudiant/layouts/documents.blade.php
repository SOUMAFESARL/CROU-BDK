<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Espace étudiant</title>
    <link rel="icon" href="{{ asset('admin/assets/images/logos/crou_bdk.jpeg') }}" type="image/jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css/student-space.css') }}?v=20261016b">
    <link rel="stylesheet" href="{{ asset('files/assets/css/system-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css/student-documents.css') }}?v=20261014">
</head>
<body class="{{ systemConfig('force_dark_mode', false) ? 'app-dark-mode' : '' }}">
<x-etudiant-shell :student="auth('etudiant')->user()" :title="trim($__env->yieldContent('title'))">
    <div class="content active student-documents">
        @yield('content')
    </div>
</x-etudiant-shell>
<script src="{{ asset('js/admin-shell.js') }}" defer></script>
</body>
</html>
