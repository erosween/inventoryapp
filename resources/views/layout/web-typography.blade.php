@once
<link rel="preload" href="{{ asset('assets/fonts/geist/geist-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('css/web-typography.css') }}?v={{ filemtime(public_path('css/web-typography.css')) }}">
<script src="{{ asset('js/web-typography.js') }}?v={{ filemtime(public_path('js/web-typography.js')) }}" defer></script>
@endonce
