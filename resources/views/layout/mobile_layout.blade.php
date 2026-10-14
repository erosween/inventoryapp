<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>SURYA - Sales Unified Reporting &amp; Yield Application</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- PWA Setup -->
    <link rel="manifest" href="/manifest.json">
    <meta name="application-name" content="SURYA">
    <meta name="theme-color" content="#b40012" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#250006" media="(prefers-color-scheme: dark)">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/pwa/surya/icon-180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/pwa/surya/icon-192.png">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#b40012">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="SURYA">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="msapplication-TileColor" content="#b40012">
    <meta name="msapplication-TileImage" content="/assets/pwa/surya/icon-144.png">
    <meta name="msapplication-config" content="none">
    <meta name="format-detection" content="telephone=no">
    
    <!-- Bootstrap 5 CSS (using CDN for modern look) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #c1121f;
            --primary-dark: #78000b;
            --primary-light: #ef233c;
            --accent: #ff6b35;
            --success: #10b981;
            --info: #2563eb;
            --ink: #111827;
            --ink-soft: #334155;
            --line: #e5e7eb;
            --bg-body: #eef3f8;
            --card-bg: #ffffff;
            --text-main: #111827;
            --text-muted: #64748b;
            --shadow-premium: 0 18px 45px rgba(15, 23, 42, 0.08);
            --shadow-soft: 0 10px 24px rgba(15, 23, 42, 0.06);
            --radius-xl: 24px;
            --hero-surface: linear-gradient(145deg, #210006 0%, #8f0010 48%, #e32636 100%);
            --mobile-safe-top: env(safe-area-inset-top, 0px);
            --mobile-safe-bottom: env(safe-area-inset-bottom, 0px);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #e8eef6;
            color: var(--text-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overscroll-behavior-y: contain;
        }

        .mobile-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            min-height: 100dvh;
            padding-top: var(--mobile-safe-top);
            padding-bottom: calc(88px + env(safe-area-inset-bottom, 0px));
            position: relative;
            background: var(--bg-body);
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.04), 0 30px 80px rgba(15, 23, 42, 0.12);
            overflow-x: hidden;
        }

        /* Keep iOS/PWA chrome fully opaque so scrolled content never bleeds
         * through the status bar as a white/blurred gradient. */
        .mobile-container::before {
            content: "";
            position: fixed;
            z-index: 1045;
            top: 0;
            left: 50%;
            width: min(100%, 480px);
            height: calc(var(--mobile-safe-top) + 2px);
            transform: translateX(-50%);
            pointer-events: none;
            background: #78000b;
            border: 0;
            box-shadow: none;
        }

        .main-content {
            will-change: opacity, transform;
            transition: opacity 0.14s ease, transform 0.14s ease;
        }

        .pull-refresh-indicator {
            position: fixed;
            z-index: 1065;
            top: calc(var(--mobile-safe-top) + 12px);
            left: 50%;
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: calc(100vw - 32px);
            padding: 9px 14px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 999px;
            background: #fff;
            color: var(--ink-soft);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
            font-size: 0.72rem;
            font-weight: 800;
            opacity: 0;
            pointer-events: none;
            transform: translate(-50%, -135%);
            transition: transform 0.16s ease, opacity 0.16s ease;
        }

        .pull-refresh-indicator.is-visible {
            opacity: 1;
            transform: translate(-50%, var(--pull-offset, 0px));
        }

        .pull-refresh-indicator.is-ready {
            color: var(--primary);
        }

        .pull-refresh-indicator.is-ready i {
            transform: rotate(180deg);
        }

        .pull-refresh-indicator.is-refreshing i {
            animation: pull-refresh-spin 0.75s linear infinite;
        }

        @keyframes pull-refresh-spin {
            to { transform: rotate(360deg); }
        }

        .glass-card {
            background: #fff !important;
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
            border-radius: var(--radius-xl) !important;
            margin-bottom: 16px;
            box-shadow: var(--shadow-soft) !important;
        }

        .main-content > .px-4:first-child,
        .sales-hero {
            background: var(--hero-surface) !important;
            border-radius: 0 0 34px 34px;
            box-shadow: 0 24px 58px rgba(17, 24, 39, 0.24);
            overflow: hidden;
        }

        .main-content > .px-4:first-child::after,
        .sales-hero::after {
            content: "";
            position: absolute;
            inset: auto 18px 16px auto;
            width: 112px;
            height: 112px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 32px;
            transform: rotate(18deg);
            pointer-events: none;
        }

        .premium-header {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0 0 18px;
            padding: 22px 20px 28px !important;
            background: var(--hero-surface);
            border-radius: 0 0 30px 30px;
            box-shadow: 0 20px 50px rgba(17, 24, 39, 0.18);
            overflow: hidden;
        }

        .premium-header::after {
            content: "";
            position: absolute;
            right: -28px;
            bottom: -42px;
            width: 128px;
            height: 128px;
            border-radius: 36px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            transform: rotate(18deg);
        }

        .premium-header > * {
            position: relative;
            z-index: 1;
        }

        .header-info h1 {
            font-size: 1.45rem;
            font-weight: 900;
            color: #fff !important;
            margin-bottom: 2px;
            letter-spacing: 0;
        }

        .header-info p {
            color: rgba(255, 255, 255, 0.66) !important;
            font-size: 0.72rem;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 16px !important;
            border: 1px solid rgba(255, 255, 255, 0.45) !important;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.18) !important;
            background: #fff;
        }

        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: white;
            border-radius: 20px;
            padding: 18px;
            text-align: center;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            color: white;
            font-size: 1.2rem;
        }

        .stat-box h2 {
            font-size: 1.6rem;
            font-weight: 900;
            margin-bottom: 2px;
            color: var(--text-main);
        }

        .stat-box span {
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .glass-input-group {
            margin-bottom: 20px;
        }

        .glass-label {
            color: var(--ink-soft);
            font-size: 0.72rem;
            font-weight: 900;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding-left: 4px;
        }

        .glass-input,
        .form-control,
        .form-select,
        textarea {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 18px !important;
            color: var(--text-main) !important;
            font-weight: 700 !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .glass-input {
            width: 100%;
            padding: 14px 18px;
            font-size: 0.95rem;
        }

        .glass-input:focus,
        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            outline: none;
            border-color: rgba(236, 32, 40, 0.45) !important;
            background: #fff !important;
            box-shadow: 0 0 0 4px rgba(236, 32, 40, 0.09) !important;
        }

        .btn-premium,
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 18px !important;
            font-weight: 900 !important;
            box-shadow: 0 14px 28px rgba(236, 32, 40, 0.24) !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-premium {
            width: 100%;
            padding: 17px;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-premium:active,
        .btn-primary:active,
        .btn:active {
            transform: scale(0.98);
        }

        .btn-outline-danger {
            border-color: rgba(236, 32, 40, 0.18) !important;
            color: var(--primary) !important;
            background: rgba(236, 32, 40, 0.08) !important;
        }

        .floating-nav {
            position: fixed;
            bottom: calc(7px + env(safe-area-inset-bottom, 0px));
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 28px);
            max-width: 444px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 22px;
            padding: 6px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.14);
        }

        /* Prevent Bootstrap modal from shifting the centered mobile layout */
        body.modal-open {
            padding-right: 0 !important;
            overflow-y: scroll !important;
        }

        .nav-item {
            text-decoration: none;
            color: var(--text-muted);
            text-align: center;
            padding: 7px 6px;
            border-radius: 16px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 54px;
            min-height: 48px;
            -webkit-tap-highlight-color: transparent;
        }

        .nav-item:active,
        .nav-item.is-loading {
            color: var(--primary);
            background: rgba(236, 32, 40, 0.08);
            transform: translateY(-2px) scale(0.98);
        }

        .nav-item i {
            font-size: 1rem;
            margin-bottom: 3px;
            transition: transform 0.2s;
        }

        .nav-item div {
            font-size: 0.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .nav-item.active {
            color: var(--primary);
            background: #fff1f2;
        }
        
        .nav-item.active i {
            transform: scale(1.06);
        }

        @keyframes reveal {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reveal {
            animation: reveal 0.24s ease-out forwards;
        }

        body.page-is-leaving .main-content {
            opacity: 0.65;
            transform: translateY(4px);
        }

        .activity-item,
        .history-row,
        .stock-item {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.85);
            border-radius: 20px;
            padding: 14px;
            box-shadow: var(--shadow-soft);
        }

        .activity-icon,
        .theme-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 15px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(236, 32, 40, 0.1) !important;
            color: var(--primary) !important;
        }

        .badge {
            font-weight: 900 !important;
            letter-spacing: 0;
        }

        .modal-content {
            border-radius: 28px 28px 0 0 !important;
            border: 1px solid rgba(226, 232, 240, 0.86) !important;
        }

        /* Every mobile dialog stays inside the visible app viewport. Inline
         * bottom-sheet positioning from older screens is intentionally
         * overridden so iOS status bars and PWA chrome never cover content. */
        .modal {
            padding: calc(var(--mobile-safe-top) + 12px) 12px calc(var(--mobile-safe-bottom) + 12px) !important;
            overflow: hidden !important;
        }

        .modal .modal-dialog {
            position: relative !important;
            inset: auto !important;
            width: 100% !important;
            max-height: 100% !important;
            margin: 0 auto !important;
        }

        .modal .modal-dialog:not(.modal-dialog-centered) {
            display: flex;
            height: 100%;
            align-items: flex-end;
        }

        .modal .modal-content {
            max-height: calc(100dvh - var(--mobile-safe-top) - var(--mobile-safe-bottom) - 24px) !important;
            overflow: hidden;
        }

        .modal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .swal2-container.swal2-top,
        .swal2-container.swal2-top-start,
        .swal2-container.swal2-top-end {
            padding-top: calc(var(--mobile-safe-top) + 54px) !important;
            padding-right: 12px !important;
            padding-left: 12px !important;
        }

        .swal2-popup.swal2-toast {
            max-width: min(430px, calc(100vw - 24px));
        }

        .list-group-item {
            border-radius: 18px !important;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .fw-800 {
            font-weight: 800 !important;
        }

        .fw-900 {
            font-weight: 900 !important;
        }

        .soon-feature {
            color: var(--text-main);
            cursor: default;
            opacity: 0.62;
        }

        .soon-feature .soon-badge {
            padding: 4px 9px;
            border-radius: 999px;
            background: #eef2f7;
            color: var(--text-muted);
            font-size: 0.58rem;
            font-weight: 800;
            letter-spacing: 0.05em;
        }

        @media (prefers-reduced-motion: reduce) {
            .reveal,
            .main-content,
            .nav-item,
            .nav-item i,
            .pull-refresh-indicator {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    <div class="mobile-container">
        <div id="mobile-pull-refresh" class="pull-refresh-indicator" aria-live="polite" aria-hidden="true">
            <i class="fas fa-arrow-down"></i>
            <span>Tarik untuk memperbarui</span>
        </div>

        @if(!request()->is('mobile') && !request()->is('mobile/history') && !request()->is('mobile/stock'))
            <!-- Premium Header Light for Non-Dashboard Pages -->
            <header class="premium-header reveal px-4 pt-4">
                <div class="header-info">
                    <p class="mb-0 text-muted small fw-bold">SURYA</p>
                    <h1 class="mb-0" style="color: var(--text-main);">@yield('title', 'Halaman')</h1>
                </div>
                <div class="avatar-wrapper">
                    <img src="/assets/img/surya-logo-mobile.webp" class="user-avatar" alt="SURYA" width="40" height="40" loading="eager" decoding="async" style="width: 40px; height: 40px; border-radius: 12px; border: 2px solid white; object-fit: cover;">
                </div>
            </header>
        @endif

        <!-- Main Content Area -->
        <main class="main-content reveal px-3 {{ request()->is('mobile') || request()->is('mobile/history') || request()->is('mobile/stock') ? 'pt-0' : 'pt-2' }}" style="animation-delay: 0.1s;">
            @yield('content')
        </main>

        <!-- Floating Premium Navigation -->
        <nav class="floating-nav fixed-bottom">
            <a href="{{ route('mobile.index') }}" class="nav-item {{ request()->is('mobile') ? 'active' : '' }}">
                <i class="fas fa-home"></i>
                <div>Home</div>
            </a>
            <a href="{{ route('mobile.stock') }}" class="nav-item {{ request()->is('mobile/stock') ? 'active' : '' }}">
                <i class="fas fa-boxes-stacked"></i>
                <div>Stok</div>
            </a>
            <a href="{{ route('mobile.history') }}" class="nav-item {{ request()->is('mobile/history') ? 'active' : '' }}">
                <i class="fas fa-history"></i>
                <div>History</div>
            </a>
            <a href="#" class="nav-item" id="nav-settings">
                <i class="fas fa-ellipsis"></i>
                <div>Lainnya</div>
            </a>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false,
                position: 'top',
                toast: true
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '{{ session('error') }}',
                position: 'top',
                toast: true
            });
        @endif
    </script>
    @stack('modals')

    <!-- Settings Bottom Sheet -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" style="margin: 0 auto; max-width: 480px; position: absolute; bottom: 0; left: 0; right: 0; width: 100%;">
            <div class="modal-content border-0" style="border-radius: 30px 30px 0 0; box-shadow: 0 -10px 50px rgba(0,0,0,0.15);">
                <div class="d-flex justify-content-center pt-3">
                    <div style="width: 45px; height: 6px; background: #e2e8f0; border-radius: 10px;"></div>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-800 mb-4" style="font-size: 1.1rem;">Pengaturan</h6>
                    <a href="{{ route('mobile.password') }}" class="d-flex align-items-center py-3 text-decoration-none border-bottom" style="color: var(--text-main);">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3" style="color: var(--primary);">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-800" style="font-size: 0.9rem;">Ganti Password</div>
                            <div class="small text-muted" style="font-size: 0.7rem;">Ubah password login Anda</div>
                        </div>
                        <i class="fas fa-chevron-right text-muted opacity-50" style="font-size: 0.7rem;"></i>
                    </a>
                    <button type="button" class="soon-feature d-flex align-items-center py-3 text-start border-0 border-bottom bg-transparent w-100" disabled aria-disabled="true"><div class="bg-danger bg-opacity-10 p-3 rounded-4 me-3" style="color: var(--primary);"><i class="fas fa-sim-card"></i></div><div class="flex-grow-1"><div class="fw-800" style="font-size: .9rem;">Konsinyasi SA</div><div class="small text-muted">Catat nomor perdana</div></div><span class="soon-badge">SOON</span></button>
                    <button type="button" class="soon-feature d-flex align-items-center py-3 text-start border-0 border-bottom bg-transparent w-100" disabled aria-disabled="true"><div class="bg-danger bg-opacity-10 p-3 rounded-4 me-3" style="color: var(--primary);"><i class="fas fa-chart-line"></i></div><div class="flex-grow-1"><div class="fw-800" style="font-size: .9rem;">Survey Kompetitor</div><div class="small text-muted">Pantau produk dan ketersediaan</div></div><span class="soon-badge">SOON</span></button>
                    <button type="button" class="soon-feature d-flex align-items-center py-3 text-start border-0 border-bottom bg-transparent w-100" disabled aria-disabled="true"><div class="bg-danger bg-opacity-10 p-3 rounded-4 me-3" style="color: var(--primary);"><i class="fas fa-signal"></i></div><div class="flex-grow-1"><div class="fw-800" style="font-size: .9rem;">Kondisi Network</div><div class="small text-muted">Laporkan kondisi sinyal</div></div><span class="soon-badge">SOON</span></button>
                    <button type="button" data-pwa-install hidden class="d-flex align-items-center py-3 text-start border-0 border-bottom bg-transparent w-100" style="color: var(--text-main);">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-4 me-3" style="color: var(--primary);"><i class="fas fa-download"></i></div>
                        <div class="flex-grow-1"><div class="fw-800" style="font-size: .9rem;">Install Aplikasi</div><div class="small text-muted">Pasang SURYA di perangkat ini</div></div>
                        <i class="fas fa-chevron-right text-muted opacity-50" style="font-size: .7rem;"></i>
                    </button>
                    <a href="{{ route('mobile.logout') }}" class="d-flex align-items-center py-3 text-decoration-none" style="color: #ef4444;">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-4 me-3">
                            <i class="fas fa-power-off"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-800" style="font-size: 0.9rem;">Keluar</div>
                            <div class="small text-muted" style="font-size: 0.7rem;">Logout dari akun ini</div>
                        </div>
                        <i class="fas fa-chevron-right text-muted opacity-50" style="font-size: 0.7rem;"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
    <script>
        (function() {
            const indicator = document.getElementById('mobile-pull-refresh');
            const indicatorIcon = indicator?.querySelector('i');
            const indicatorText = indicator?.querySelector('span');
            const snapshotKey = 'surya:pull-refresh:' + window.location.pathname + window.location.search;
            const threshold = 76;
            let startY = 0;
            let distance = 0;
            let tracking = false;
            let refreshing = false;
            let draftRestored = false;

            function fieldIdentifier(field, index) {
                return field.id ? 'id:' + field.id : (field.name ? 'name:' + field.name + ':index:' + index : 'index:' + index);
            }

            function preserveFormInputs() {
                const values = [];
                const fields = document.querySelectorAll('form input, form textarea, form select');

                fields.forEach(function(field, index) {
                    const type = String(field.type || '').toLowerCase();
                    if (['password', 'file', 'submit', 'button', 'reset', 'image'].includes(type) || field.name === '_token') {
                        return;
                    }

                    values.push({
                        key: fieldIdentifier(field, index),
                        type: type,
                        value: field.value,
                        checked: Boolean(field.checked)
                    });
                });

                sessionStorage.setItem(snapshotKey, JSON.stringify(values));
            }

            function restoreFormInputs() {
                if (draftRestored) return;
                const saved = sessionStorage.getItem(snapshotKey);
                if (!saved) return;

                try {
                    const values = JSON.parse(saved);
                    const fields = Array.from(document.querySelectorAll('form input, form textarea, form select'));

                    values.forEach(function(item) {
                        const field = fields.find(function(candidate, index) {
                            return fieldIdentifier(candidate, index) === item.key;
                        });
                        if (!field || field.disabled) return;

                        if (item.type === 'checkbox' || item.type === 'radio') {
                            field.checked = item.checked;
                        } else {
                            field.value = item.value;
                        }
                    });
                    draftRestored = true;
                    sessionStorage.removeItem(snapshotKey);
                    window.dispatchEvent(new CustomEvent('mobile:draft-restored'));
                } catch (error) {
                    sessionStorage.removeItem(snapshotKey);
                }
            }

            function hasUnsavedFiles() {
                return Array.from(document.querySelectorAll('input[type="file"]')).some(function(input) {
                    return input.files && input.files.length > 0;
                });
            }

            function resetIndicator() {
                if (!indicator || refreshing) return;
                indicator.classList.remove('is-visible', 'is-ready');
                indicator.style.removeProperty('--pull-offset');
                indicator.setAttribute('aria-hidden', 'true');
                if (indicatorIcon) indicatorIcon.className = 'fas fa-arrow-down';
                if (indicatorText) indicatorText.textContent = 'Tarik untuk memperbarui';
                distance = 0;
                tracking = false;
            }

            function beginRefresh() {
                if (!indicator || refreshing) return;

                if (hasUnsavedFiles()) {
                    resetIndicator();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Foto belum disimpan',
                        text: 'Selesaikan pengiriman foto sebelum memperbarui halaman supaya foto tidak hilang.',
                        confirmButtonColor: 'var(--primary)'
                    });
                    return;
                }

                refreshing = true;
                window.dispatchEvent(new CustomEvent('mobile:before-refresh'));
                preserveFormInputs();
                indicator.classList.add('is-visible', 'is-refreshing');
                indicator.classList.remove('is-ready');
                indicator.style.setProperty('--pull-offset', '8px');
                indicator.setAttribute('aria-hidden', 'false');
                if (indicatorIcon) indicatorIcon.className = 'fas fa-rotate';
                if (indicatorText) indicatorText.textContent = 'Memperbarui data...';

                window.setTimeout(function() {
                    window.location.reload();
                }, 180);
            }

            window.addEventListener('load', restoreFormInputs, { once: true });
            window.addEventListener('pageshow', function() {
                window.setTimeout(restoreFormInputs, 0);
            });

            document.addEventListener('touchstart', function(event) {
                if (
                    refreshing ||
                    event.touches.length !== 1 ||
                    window.scrollY > 0 ||
                    document.body.classList.contains('modal-open') ||
                    event.target.closest('input, textarea, select, [contenteditable="true"], .modal-body')
                ) {
                    return;
                }

                startY = event.touches[0].clientY;
                tracking = true;
            }, { passive: true });

            document.addEventListener('touchmove', function(event) {
                if (!tracking || refreshing || window.scrollY > 0) return;

                const delta = event.touches[0].clientY - startY;
                if (delta <= 0) {
                    resetIndicator();
                    return;
                }

                distance = Math.min(110, delta * 0.62);
                indicator.classList.add('is-visible');
                indicator.classList.toggle('is-ready', distance >= threshold);
                indicator.style.setProperty('--pull-offset', Math.min(42, distance * 0.38) + 'px');
                indicator.setAttribute('aria-hidden', 'false');

                if (indicatorText) {
                    indicatorText.textContent = distance >= threshold ? 'Lepas untuk memperbarui' : 'Tarik untuk memperbarui';
                }

                if (distance >= 8) event.preventDefault();
            }, { passive: false });

            document.addEventListener('touchend', function() {
                if (!tracking || refreshing) return;
                if (distance >= threshold) beginRefresh();
                else resetIndicator();
            }, { passive: true });

            document.addEventListener('touchcancel', resetIndicator, { passive: true });
        })();

        // Settings bottom sheet
        document.getElementById('nav-settings')?.addEventListener('click', function(e) {
            e.preventDefault();
            new bootstrap.Modal(document.getElementById('settingsModal')).show();
        });

        // Smooth mobile menu navigation without changing backend routing.
        const mobileNavLinks = document.querySelectorAll('.floating-nav a[href]:not([href="#"])');
        const currentUrl = new URL(window.location.href);

        // Ensure transition helper classes are always reset after navigation restore.
        const resetPageTransitionState = function() {
            document.body.classList.remove('page-is-leaving');
            mobileNavLinks.forEach(function(link) {
                link.classList.remove('is-loading');
            });
        };

        window.addEventListener('pageshow', resetPageTransitionState);
        window.addEventListener('load', resetPageTransitionState);

        mobileNavLinks.forEach(function(link) {
            const targetUrl = new URL(link.href, window.location.origin);

            if (targetUrl.origin === currentUrl.origin && targetUrl.href !== currentUrl.href) {
                const prefetch = document.createElement('link');
                prefetch.rel = 'prefetch';
                prefetch.href = targetUrl.href;
                document.head.appendChild(prefetch);
            }

            link.addEventListener('click', function(e) {
                if (
                    e.defaultPrevented ||
                    e.metaKey ||
                    e.ctrlKey ||
                    e.shiftKey ||
                    e.altKey ||
                    link.target ||
                    targetUrl.origin !== currentUrl.origin ||
                    targetUrl.href === currentUrl.href
                ) {
                    return;
                }

                e.preventDefault();
                link.classList.add('is-loading');
                document.body.classList.add('page-is-leaving');

                window.setTimeout(function() {
                    window.location.href = targetUrl.href;
                }, 90);
            });
        });
    </script>
    @include('partials.pwa-installer')
    @include('partials.idempotency')
</body>
</html>
