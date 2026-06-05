<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'PresensiKu') - MSP</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#5B37E5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="MSP Mobile">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/img/pwa-192.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #5b37e5;
            --primary-2: #7b4dff;
            --primary-soft: #eee9ff;
            --ink: #101333;
            --muted: #737997;
            --surface: #f6f7ff;
            --card: #ffffff;
            --line: #e8eaf6;
            --success: #18b87a;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #2f80ed;
            --shadow: 0 18px 44px rgba(31, 35, 85, 0.08);
            --shadow-strong: 0 22px 54px rgba(91, 55, 229, 0.22);
            --gradient: linear-gradient(135deg, #7557f6 0%, #4c29d9 100%);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", sans-serif;
            background:
                radial-gradient(circle at 18% 5%, rgba(123, 77, 255, 0.12), transparent 32%),
                linear-gradient(180deg, #fbfbff 0%, #eef1fb 100%);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        .presence-phone {
            width: 100%;
            max-width: 430px;
            min-height: 100vh;
            margin: 0 auto;
            padding: 0 18px 112px;
            background: linear-gradient(180deg, #fbfbff 0%, var(--surface) 62%, #f3f5ff 100%);
            box-shadow: 0 0 0 1px rgba(31, 35, 85, 0.04), 0 30px 80px rgba(31, 35, 85, 0.12);
            overflow-x: hidden;
        }

        .app-content {
            padding-top: 20px;
        }

        .app-topbar {
            min-height: 46px;
            display: grid;
            grid-template-columns: 46px 1fr 46px;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .app-title {
            margin: 0;
            text-align: center;
            color: var(--ink);
            font-size: 0.9rem;
            font-weight: 900;
        }

        .icon-btn {
            width: 42px;
            height: 42px;
            border: 0;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            background: var(--primary-soft);
            text-decoration: none;
            position: relative;
        }

        .icon-btn.plain {
            color: var(--ink);
            background: transparent;
        }

        .icon-dot {
            position: absolute;
            top: 9px;
            right: 10px;
            width: 8px;
            height: 8px;
            border-radius: 99px;
            background: #ff3b5c;
            border: 2px solid #fff;
        }

        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon,
        .avatar-badge {
            width: 50px;
            height: 50px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: var(--gradient);
            box-shadow: var(--shadow-strong);
            flex: 0 0 auto;
        }

        .brand-icon {
            font-size: 1.35rem;
        }

        .avatar-badge {
            font-size: 1rem;
            font-weight: 900;
        }

        .eyebrow {
            color: var(--muted);
            font-size: 0.68rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .screen-heading {
            color: var(--ink);
            font-size: 1.35rem;
            font-weight: 900;
            line-height: 1.2;
            margin: 0;
        }

        .screen-subtitle {
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1.55;
            margin: 0;
        }

        .presence-card {
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(232,234,246,0.95);
            border-radius: 22px;
            box-shadow: var(--shadow);
        }

        .gradient-card {
            color: #fff;
            background:
                radial-gradient(circle at 86% 18%, rgba(255,255,255,0.22), transparent 26%),
                var(--gradient);
            border: 0;
            border-radius: 18px;
            box-shadow: var(--shadow-strong);
            overflow: hidden;
            position: relative;
        }

        .gradient-card > * {
            position: relative;
            z-index: 1;
        }

        .soft-panel {
            background: #f9faff;
            border: 1px solid var(--line);
            border-radius: 18px;
        }

        .section-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 0 2px 12px;
        }

        .section-row h2 {
            margin: 0;
            color: var(--ink);
            font-size: 0.9rem;
            font-weight: 900;
        }

        .section-row a,
        .section-row span {
            color: var(--primary);
            font-size: 0.68rem;
            font-weight: 900;
            text-decoration: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.6rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .status-badge.success {
            color: #047857;
            background: #dcfce7;
        }

        .status-badge.warning {
            color: #b45309;
            background: #fef3c7;
        }

        .status-badge.danger {
            color: #c62828;
            background: #fee2e2;
        }

        .status-badge.info {
            color: #235ecf;
            background: #e7efff;
        }

        .field-label {
            display: block;
            color: var(--ink);
            font-size: 0.68rem;
            font-weight: 900;
            margin: 0 0 8px 2px;
        }

        .presence-input {
            width: 100%;
            min-height: 52px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            color: var(--ink);
            padding: 0 14px;
            font-size: 0.82rem;
            font-weight: 800;
            outline: none;
            box-shadow: 0 8px 18px rgba(31, 35, 85, 0.03);
        }

        .presence-input:focus {
            border-color: rgba(91,55,229,0.5);
            box-shadow: 0 0 0 4px rgba(91,55,229,0.09);
        }

        input[type="date"].presence-input,
        input[type="time"].presence-input {
            display: block;
            min-height: 56px;
            line-height: 56px;
            padding: 0 14px;
            appearance: none;
            -webkit-appearance: none;
        }

        input[type="date"].presence-input::-webkit-calendar-picker-indicator,
        input[type="time"].presence-input::-webkit-calendar-picker-indicator {
            width: 20px;
            height: 20px;
            margin-left: 8px;
            opacity: 0.72;
        }

        .primary-btn,
        .outline-btn,
        .danger-btn {
            min-height: 54px;
            border-radius: 14px;
            font-size: 0.82rem;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .primary-btn {
            border: 0;
            color: #fff;
            background: var(--gradient);
            box-shadow: 0 16px 30px rgba(91,55,229,0.24);
        }

        .primary-btn:disabled {
            color: #8b91aa;
            background: #eceef7;
            box-shadow: none;
        }

        .outline-btn {
            border: 1px solid #d9dded;
            color: var(--primary);
            background: #fff;
        }

        .danger-btn {
            border: 1px solid #fecaca;
            color: #ef4444;
            background: #fff7f7;
        }

        .presence-nav {
            position: fixed;
            left: 50%;
            bottom: 12px;
            transform: translateX(-50%);
            width: calc(100% - 28px);
            max-width: 402px;
            height: calc(70px + env(safe-area-inset-bottom));
            display: grid;
            grid-template-columns: 1fr 1fr 72px 1fr 1fr;
            align-items: center;
            gap: 3px;
            padding: 8px 10px calc(8px + env(safe-area-inset-bottom));
            background: rgba(255,255,255,0.94);
            border: 1px solid rgba(232,234,246,0.9);
            border-radius: 26px;
            box-shadow: 0 18px 42px rgba(31,35,85,0.16);
            backdrop-filter: blur(22px);
            z-index: 1000;
        }

        .presence-nav a {
            min-width: 0;
            min-height: 52px;
            color: #151936;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border-radius: 18px;
            font-size: 0.54rem;
            font-weight: 900;
        }

        .presence-nav a.active {
            color: var(--primary);
        }

        .presence-nav i {
            width: 30px;
            height: 28px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: background-color 0.12s ease, color 0.12s ease;
        }

        .presence-nav a:not(.center).active i {
            color: var(--primary);
            background: var(--primary-soft);
        }

        .presence-nav .center {
            width: 58px;
            height: 58px;
            min-height: 58px;
            margin: -23px auto 0;
            border-radius: 22px;
            color: #fff;
            background: var(--gradient);
            box-shadow: var(--shadow-strong);
        }

        .presence-nav .center.active {
            color: #fff;
        }

        .presence-nav .center span {
            display: none;
        }

        .presence-nav .center i {
            width: 38px;
            height: 38px;
            border-radius: 16px;
            font-size: 1.25rem;
        }

        .fw-800 { font-weight: 800 !important; }
        .fw-900 { font-weight: 900 !important; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="presence-phone">
        @yield('content')

        @if(session()->has('attendance_employee_id') && !request()->routeIs('presensi.login'))
            <nav class="presence-nav">
                <a href="{{ route('presensi.index') }}" class="{{ request()->routeIs('presensi.index') ? 'active' : '' }}">
                    <i class="fas fa-house"></i>
                    <span>Beranda</span>
                </a>
                <a href="{{ route('presensi.history') }}" class="{{ request()->routeIs('presensi.history') ? 'active' : '' }}">
                    <i class="fas fa-clock-rotate-left"></i>
                    <span>Riwayat</span>
                </a>
                <a href="{{ route('presensi.index') }}#scan" class="center">
                    <i class="fas fa-qrcode"></i>
                    <span>Presensi</span>
                </a>
                <a href="{{ route('presensi.menu') }}" class="{{ request()->routeIs('presensi.menu') || request()->routeIs('presensi.request.*') || request()->routeIs('presensi.payslip') || request()->routeIs('presensi.inbox') ? 'active' : '' }}">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Pengajuan</span>
                </a>
                <a href="{{ route('presensi.account') }}" class="{{ request()->routeIs('presensi.account') ? 'active' : '' }}">
                    <i class="fas fa-user"></i>
                    <span>Akun</span>
                </a>
            </nav>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let deferredPwaPrompt = null;

        window.addEventListener('beforeinstallprompt', function(event) {
            event.preventDefault();
            deferredPwaPrompt = event;
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '{{ session('success') }}',
                timer: 2200,
                showConfirmButton: false,
                toast: true,
                position: 'top',
                confirmButtonColor: '#5b37e5'
            });
        @endif
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Oops',
                text: '{{ session('error') }}',
                toast: true,
                position: 'top',
                confirmButtonColor: '#5b37e5'
            });
        @endif

        @if(session()->pull('attendance_show_pwa_prompt', false))
            window.addEventListener('load', function() {
                const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
                if (isStandalone) return;

                setTimeout(function() {
                    Swal.fire({
                        icon: 'info',
                        title: 'Install PresensiKu',
                        text: 'Akses presensi lebih stabil lewat mode aplikasi di HP.',
                        confirmButtonText: 'Install / Tambah',
                        cancelButtonText: 'Nanti saja',
                        showCancelButton: true,
                        confirmButtonColor: '#5b37e5',
                    }).then(async function(result) {
                        if (!result.isConfirmed) return;

                        if (deferredPwaPrompt) {
                            deferredPwaPrompt.prompt();
                            await deferredPwaPrompt.userChoice.catch(function() {});
                            deferredPwaPrompt = null;
                            return;
                        }

                        Swal.fire({
                            icon: 'info',
                            title: 'Tambah ke layar utama',
                            text: 'Buka menu browser, lalu pilih Add to Home Screen atau Install App.',
                            confirmButtonColor: '#5b37e5',
                        });
                    });
                }, 900);
            });
        @endif
    </script>
    @stack('scripts')
</body>
</html>
