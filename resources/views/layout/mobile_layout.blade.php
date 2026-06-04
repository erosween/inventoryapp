<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SF Mobile - Inventory MSP</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- PWA Setup -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#EC2028">
    <link rel="apple-touch-icon" href="/assets/img/MSP5.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Bootstrap 5 CSS (using CDN for modern look) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #ec2028;
            --primary-dark: #b70d18;
            --primary-light: #ff5a62;
            --accent: #f59e0b;
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
            --hero-surface: linear-gradient(145deg, #111827 0%, #293241 48%, #ec2028 100%);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background:
                linear-gradient(180deg, #f8fafc 0%, #e8eef6 100%);
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
            padding-bottom: 116px;
            position: relative;
            background:
                radial-gradient(circle at 20% 0%, rgba(236, 32, 40, 0.08), transparent 32%),
                linear-gradient(180deg, #f7fafc 0%, var(--bg-body) 100%);
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.04), 0 30px 80px rgba(15, 23, 42, 0.12);
            overflow-x: hidden;
        }

        .main-content {
            will-change: opacity, transform;
            transition: opacity 0.14s ease, transform 0.14s ease;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.96) !important;
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
            border-radius: var(--radius-xl) !important;
            margin-bottom: 16px;
            box-shadow: var(--shadow-soft) !important;
        }

        .main-content > .px-4:first-child,
        .attendance-hero {
            background: var(--hero-surface) !important;
            border-radius: 0 0 34px 34px;
            box-shadow: 0 24px 58px rgba(17, 24, 39, 0.24);
            overflow: hidden;
        }

        .main-content > .px-4:first-child::after,
        .attendance-hero::after {
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
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 24px);
            max-width: 456px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 26px;
            padding: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.16);
            padding-bottom: calc(8px + env(safe-area-inset-bottom));
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
            padding: 9px 8px;
            border-radius: 20px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 56px;
            -webkit-tap-highlight-color: transparent;
        }

        .nav-item:active,
        .nav-item.is-loading {
            color: var(--primary);
            background: rgba(236, 32, 40, 0.08);
            transform: translateY(-2px) scale(0.98);
        }

        .nav-item i {
            font-size: 1.12rem;
            margin-bottom: 4px;
            transition: transform 0.2s;
        }

        .nav-item div {
            font-size: 0.55rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .nav-item.active {
            color: var(--primary);
            background: linear-gradient(180deg, rgba(236, 32, 40, 0.12), rgba(236, 32, 40, 0.06));
        }
        
        .nav-item.active i {
            transform: scale(1.1);
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
            background: rgba(255, 255, 255, 0.96);
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

        @media (prefers-reduced-motion: reduce) {
            .reveal,
            .main-content,
            .nav-item,
            .nav-item i {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    <div class="mobile-container">
        @if(!request()->is('mobile') && !request()->is('mobile/history') && !request()->is('mobile/stock'))
            <!-- Premium Header Light for Non-Dashboard Pages -->
            <header class="premium-header reveal px-4 pt-4">
                <div class="header-info">
                    <p class="mb-0 text-muted small fw-bold">MSP Connect</p>
                    <h1 class="mb-0" style="color: var(--text-main);">@yield('title', 'Halaman')</h1>
                </div>
                <div class="avatar-wrapper">
                    <img src="/assets/img/MSP5.png" class="user-avatar" alt="Logo" style="width: 44px; height: 44px; border-radius: 12px; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                </div>
            </header>
        @endif

        <!-- Main Content Area -->
        <main class="main-content reveal px-3 {{ request()->is('mobile') ? 'pt-0' : 'pt-2' }}" style="animation-delay: 0.1s;">
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
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered', reg))
                    .catch(err => console.log('Service Worker registration failed', err));
            });
        }
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
</body>
</html>
