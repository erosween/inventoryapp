{{--
    Referensi aset bersama SURYA Web. Layout workspace memuat partial ini.
--}}<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=auto">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="theme-color" content="#bd001c">
<meta name="mobile-web-app-capable" content="yes">
@include('surya.partials.sidebar-boot')
<link rel="manifest" href="{{ asset('manifest-surya-web.json') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/pwa/surya/icon-180.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/pwa/surya/icon-192.png') }}">
<meta name="apple-mobile-web-app-title" content="WEB SURYA">
@include('layout.web-typography')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/surya-sidebar.css') }}?v={{ filemtime(public_path('css/surya-sidebar.css')) }}">
<link rel="stylesheet" href="{{ asset('css/surya-shell.css') }}?v={{ filemtime(public_path('css/surya-shell.css')) }}">
<script src="{{ asset('js/surya-shell.js') }}?v={{ filemtime(public_path('js/surya-shell.js')) }}" defer></script>

<link rel="stylesheet" href="{{ asset('css/surya-report-theme.css') }}?v={{ filemtime(public_path('css/surya-report-theme.css')) }}">
