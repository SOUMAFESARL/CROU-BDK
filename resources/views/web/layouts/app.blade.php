<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', systemConfig('app_name', config('app.name', 'CROU')))</title>

    <link rel="icon" href="{{ asset('admin/assets/images/logos/crou_bdk.jpeg') }}?v=20261015" type="image/jpeg">
    <style>:root { --app-brand-color: {{ systemConfig('brand_color', '#1F7C43') }}; }</style>

    <!-- Stylesheet -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@26.8.1/build/css/intlTelInput.css">

    <link rel="stylesheet" href="{{ asset('files/assets/css_web/vendor.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css_web/style.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css_web/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css/system-theme.css') }}">


    @stack('styles')
</head>
<body class="{{ systemConfig('force_dark_mode', false) ? 'app-dark-mode' : '' }}">
    <!-- preloader area start -->
{{--    <div class="preloader" id="preloader">--}}
{{--        <div class="preloader-inner">--}}
{{--            <div class="spinner">--}}
{{--                <div class="dot1"></div>--}}
{{--                <div class="dot2"></div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
    <!-- preloader area end -->

    <!-- search popup start-->
    <div class="td-search-popup" id="td-search-popup">
        <form action="{{ route('accueil') }}" class="search-form">
            <div class="form-group">
                <input type="text" class="form-control" placeholder="Search.....">
            </div>
            <button type="submit" class="submit-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>
    <!-- search popup end-->
    <div class="body-overlay" id="body-overlay"></div>

    <!-- sidebar menu start-->
    @include('web.partials.sidebar')
    <!-- sidebar menu end-->

    <!-- navbar start -->
    @include('web.partials.header')
    <!-- navbar end -->

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- footer area start -->
    @include('web.partials.footer')
    <!-- footer area end -->

    <!-- back to top area start -->
    <div class="back-to-top">
        <span class="back-top"><i class="fa-solid fa-angle-up"></i></span>
    </div>
    <!-- back to top area end -->

    <!-- Scripts -->
    @include('web.partials.scripts')
</body>
</html>
