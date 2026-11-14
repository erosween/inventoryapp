{{--
    Referensi aset bersama SURYA Web. Layout workspace memuat partial ini.
--}}<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#8e0925">
@include('surya.partials.sidebar-boot')
<link rel="icon" href="{{ asset('assets/img/surya-logo-mobile.webp') }}">
@include('layout.web-typography')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/surya-sidebar.css') }}?v={{ filemtime(public_path('css/surya-sidebar.css')) }}">
<link rel="stylesheet" href="{{ asset('css/surya-shell.css') }}?v={{ filemtime(public_path('css/surya-shell.css')) }}">
<script src="{{ asset('js/surya-shell.js') }}?v={{ filemtime(public_path('js/surya-shell.js')) }}" defer></script>

<link rel="stylesheet" href="{{ asset('css/surya-report-theme.css') }}?v={{ filemtime(public_path('css/surya-report-theme.css')) }}">
