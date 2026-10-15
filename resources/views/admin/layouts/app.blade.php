<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@props([
    'activeMenu' => null,
    'activeSubMenu' => null,
])
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', systemConfig('app_name', config('app.name', 'CROU')))</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('admin/assets/images/logos/crou_bdk.jpeg') }}" type="image/jpeg">
    <style>:root { --app-brand-color: {{ systemConfig('brand_color', '#1F7C43') }}; }</style>

    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <!-- Switch component css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('files/bower_components/switchery/dist/switchery.min.css') }}">

    <!-- Date-time picker css -->
    <link rel="stylesheet" type="text/css"
          href="{{ asset('files/assets/pages/advance-elements/css/bootstrap-datetimepicker.css') }}">
    <!-- Date-range picker css  -->
    <link rel="stylesheet" type="text/css"
          href="{{ asset('files/bower_components/bootstrap-daterangepicker/daterangepicker.css') }}" />
    <!-- Date-Dropper css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('files/bower_components/datedropper/datedropper.min.css') }}" />
    <!-- Color Picker css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('files/bower_components/spectrum/spectrum.css') }}" />
    <!-- Mini-color css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('files/bower_components/jquery-minicolors/jquery.minicolors.css') }}" />
    <!-- Tags css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('files/bower_components/bootstrap-tagsinput/dist/bootstrap-tagsinput.css') }}" />

    <link rel="stylesheet" type="text/css" href="{{ asset('files/assets/css/jquery.mCustomScrollbar.css') }}">
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('files/bower_components/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/icon/feather/css/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css/jquery.mCustomScrollbar.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css/system-theme.css') }}">

    <!-- Select 2 css -->
    <link rel="stylesheet" href="{{ asset('files/bower_components/select2/dist/css/select2.min.css') }}" />

    <!-- Multi Select css -->
    <link rel="stylesheet" type="text/css"
          href="{{ asset('files/bower_components/bootstrap-multiselect/dist/css/bootstrap-multiselect.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('files/bower_components/multiselect/css/multi-select.css') }}" />
    <!-- Style.css -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@26.8.1/build/css/intlTelInput.css">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('files/assets/css/app-feedback.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-shell.css') }}?v={{ filemtime(public_path('css/admin-shell.css')) }}">
</head>

<body class="{{ systemConfig('force_dark_mode', false) ? 'app-dark-mode' : '' }}">
<!-- Pre-loader -->
<div class="theme-loader">
    <div class="ball-scale">
        <div class='contain'>
            @for($i = 1; $i <= 1; $i++)
                <div class="ring">
                    <div class="frame"></div>
                </div>
            @endfor
        </div>
    </div>
</div>

<x-admin-shell :title="html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES)" :breadcrumbs="$breadcrumbs ?? null">
    <div class="pcoded-inner-content">
        <div class="main-body">
            <div class="page-wrapper" id="main-content" tabindex="-1">
                <x-app-feedback />
                @yield('content')
            </div>
        </div>
    </div>

    <x-slot:footer>
        <x-app-footer
                :copyright-text="'© ' . date('Y') . ' ' . systemConfig('company_name', systemConfig('app_name', config('app.name'))) . '. ' . __('ui.all_rights')"
        />
    </x-slot:footer>
</x-admin-shell>

<!-- Scripts -->
<x-app-scripts
        :load-custom-dashboard="Route::currentRouteName() === 'dashboard'"
        :additional-scripts="[
            ['src' => asset('js/app.js'), 'defer' => true]
        ]"
/>

<!-- Page Scripts -->
<script src="{{ asset('js/app-feedback.js') }}" defer></script>
<script src="{{ asset('js/admin-shell.js') }}" defer></script>
</body>
</html>
