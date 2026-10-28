@include('surya.partials.sidebar-boot')
<link rel="icon" href="{{ asset('assets/img/surya-logo-mobile.webp') }}">
@include('layout.web-typography')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/surya-sidebar.css') }}?v={{ filemtime(public_path('css/surya-sidebar.css')) }}">
<link rel="stylesheet" href="{{ asset('css/surya-shell.css') }}?v={{ filemtime(public_path('css/surya-shell.css')) }}">
<script src="{{ asset('js/surya-shell.js') }}?v={{ filemtime(public_path('js/surya-shell.js')) }}" defer></script>
