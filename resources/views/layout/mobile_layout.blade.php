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
            --primary: #EC2028; /* Telkomsel Red */
            --primary-light: #FF4B53;
            --accent: #F5A623;
            --success: #10B981;
            --bg-body: #F4F7F9; /* Softer gray background */
            --card-bg: #FFFFFF;
            --text-main: #212121; /* Darker, crisper text */
            --text-muted: #757575;
            --shadow-premium: 0 4px 12px rgba(0, 0, 0, 0.05); /* Softer, tighter shadows */
            --radius-xl: 20px; /* Slightly less rounded than bento */
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        .mobile-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            padding-bottom: 120px;
            position: relative;
            background-color: var(--bg-body);
            box-shadow: 0 0 20px rgba(0,0,0,0.05); /* Show edges on desktop */
        }

        /* Solid White Card for Telkomsel style */
        .glass-card {
            background: var(--card-bg);
            border: none;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-premium);
        }

        /* Premium Header Light */
        .premium-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 10px 5px;
        }

        .header-info h1 {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -1px;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .header-info p {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            margin: 0;
        }

        .user-avatar {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            border: 3px solid white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* Stats Row Light */
        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: white;
            border-radius: 24px;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-premium);
            border: 1px solid rgba(255, 255, 255, 0.8);
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
            font-weight: 800;
            margin-bottom: 2px;
            color: var(--text-main);
        }

        .stat-box span {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Premium Form Elements */
        .glass-input-group {
            margin-bottom: 20px;
        }

        .glass-label {
            color: var(--text-main);
            font-size: 0.75rem;
            font-weight: 800;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-left: 4px;
        }

        .glass-input {
            width: 100%;
            background: #f1f5f9;
            border: 2px solid transparent;
            border-radius: 18px;
            padding: 14px 20px;
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .glass-input:focus {
            outline: none;
            border-color: var(--primary-light);
            background: white;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.1);
        }

        .btn-premium {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            width: 100%;
            padding: 18px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 1rem;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-premium:active {
            transform: scale(0.97);
        }

        .floating-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px; /* Match mobile container */
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 24px 24px 0 0;
            padding: 12px 16px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.06);
            padding-bottom: calc(12px + env(safe-area-inset-bottom)); /* Support iOS safe area */
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
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 75px;
        }

        .nav-item i {
            font-size: 1.25rem;
            margin-bottom: 4px;
            transition: transform 0.2s;
        }

        .nav-item div {
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-item.active {
            color: var(--primary);
            background: rgba(236, 32, 40, 0.08); /* Transparent Red */
        }
        
        .nav-item.active i {
            transform: scale(1.1);
        }

        @keyframes reveal {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reveal {
            animation: reveal 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
    </style>
    @stack('styles')
</head>
<body>

    <div class="mobile-container">
        @if(!request()->is('mobile') && !request()->is('mobile/history'))
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
    </script>
</body>
</html>
