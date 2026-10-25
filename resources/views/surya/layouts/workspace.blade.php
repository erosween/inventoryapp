<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · SURYA</title>
    @stack('styles')
    @include('surya.partials.assets')
</head>
<body>
<div class="shell" data-surya-workspace>
    @include('surya.partials.sidebar', ['activePage' => trim($__env->yieldContent('active-page'))])
    <div class="surya-workspace-main">
        @include('surya.partials.header')
        <main class="surya-content @yield('content-class')">
            @include('surya.partials.page-heading', ['title' => trim($__env->yieldContent('title')), 'description' => trim($__env->yieldContent('description'))])
            @if(session('success'))<div class="surya-alert" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="surya-alert error" role="alert">{{ $errors->first() }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
