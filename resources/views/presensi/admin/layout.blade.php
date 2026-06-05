<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Presensi') - MSP</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #6c3ff2;
            --primary-dark: #3b1b9c;
            --primary-soft: #f0ebff;
            --ink: #111635;
            --muted: #78809c;
            --line: #e9ecf6;
            --surface: #f8f9ff;
            --card: #ffffff;
            --success: #35c889;
            --warning: #ffad27;
            --danger: #ff5b6b;
            --info: #4b7dff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: "Plus Jakarta Sans", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 16% 0%, rgba(108,63,242,0.12), transparent 24%),
                radial-gradient(circle at 94% 10%, rgba(79,70,229,0.08), transparent 24%),
                linear-gradient(180deg, #fbfcff 0%, #f3f5fb 100%);
            -webkit-font-smoothing: antialiased;
        }

        .admin-app-shell {
            width: min(100%, 1480px);
            min-height: calc(100vh - 28px);
            margin: 14px auto;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            border: 1px solid rgba(233,236,246,0.98);
            border-radius: 26px;
            overflow: hidden;
            background: rgba(255,255,255,0.92);
            box-shadow: 0 24px 70px rgba(31,35,85,0.12);
        }

        .admin-sidebar {
            min-height: calc(100vh - 28px);
            padding: 28px 20px;
            background: #fff;
            border-right: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(135deg, #8b62ff 0%, #5a35df 100%);
            box-shadow: 0 16px 34px rgba(108,63,242,0.26);
            flex: 0 0 auto;
        }

        .brand-title {
            margin: 0;
            color: var(--ink);
            font-size: 1.02rem;
            font-weight: 900;
        }

        .brand-subtitle {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 0.66rem;
            font-weight: 800;
        }

        .sidebar-label {
            margin: 8px 10px 8px;
            color: #9aa1bb;
            font-size: 0.61rem;
            font-weight: 900;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .sidebar-menu {
            display: grid;
            gap: 5px;
        }

        .sidebar-menu a {
            min-height: 45px;
            border-radius: 12px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            gap: 11px;
            color: #626b88;
            font-size: 0.76rem;
            font-weight: 900;
            text-decoration: none;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: var(--primary);
            background: var(--primary-soft);
            text-decoration: none;
        }

        .sidebar-menu i {
            width: 22px;
            text-align: center;
        }

        .sidebar-upgrade {
            margin-top: auto;
            border-radius: 18px;
            padding: 18px;
            color: var(--primary);
            background: linear-gradient(180deg, #faf8ff 0%, #f0ebff 100%);
            text-align: center;
        }

        .sidebar-upgrade i {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: var(--primary);
            margin-bottom: 10px;
        }

        .sidebar-upgrade strong,
        .sidebar-upgrade span {
            display: block;
        }

        .sidebar-upgrade strong {
            color: var(--ink);
            font-size: 0.78rem;
            font-weight: 900;
        }

        .sidebar-upgrade span {
            margin-top: 5px;
            color: var(--muted);
            font-size: 0.62rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .sidebar-user {
            min-height: 52px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--ink);
            text-decoration: none;
        }

        .sidebar-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(135deg, #111635, #6c3ff2);
            font-weight: 900;
        }

        .sidebar-user strong,
        .sidebar-user span {
            display: block;
        }

        .sidebar-user strong {
            font-size: 0.72rem;
            font-weight: 900;
        }

        .sidebar-user span {
            color: var(--muted);
            font-size: 0.62rem;
            font-weight: 800;
        }

        .admin-workspace {
            min-width: 0;
            padding: 42px 34px 48px;
            background: linear-gradient(180deg, #fbfcff 0%, #f7f8fe 100%);
        }

        .admin-main-topbar {
            min-height: 60px;
            display: grid;
            grid-template-columns: minmax(180px, 1fr) minmax(260px, 360px) auto auto auto;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }

        .admin-main-title h1 {
            margin: 0;
            color: var(--ink);
            font-size: 1.46rem;
            font-weight: 900;
        }

        .admin-main-title p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 800;
        }

        .admin-search {
            min-height: 46px;
            border: 1px solid var(--line);
            border-radius: 13px;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: #fff;
        }

        .admin-search input {
            width: 100%;
            border: 0;
            outline: none;
            padding: 0 14px;
            color: var(--ink);
            font-size: 0.74rem;
            font-weight: 800;
        }

        .admin-search button,
        .topbar-icon,
        .company-switch {
            min-height: 46px;
            border: 1px solid var(--line);
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #5f6684;
            font-weight: 900;
        }

        .admin-search button {
            width: 48px;
            border-width: 0 0 0 1px;
            border-radius: 0;
            color: var(--primary);
        }

        .topbar-icon {
            width: 46px;
            position: relative;
        }

        .topbar-icon em {
            position: absolute;
            top: 9px;
            right: 10px;
            width: 9px;
            height: 9px;
            border-radius: 99px;
            background: #ef4444;
            border: 2px solid #fff;
        }

        .company-switch {
            gap: 9px;
            padding: 0 14px;
            font-size: 0.72rem;
            white-space: nowrap;
        }

        .admin-dashboard-body .page-header,
        .admin-dashboard-body .admin-command-nav,
        .admin-dashboard-body .admin-ops-hero {
            display: none !important;
        }

        .admin-dashboard-body .main-panel,
        .admin-dashboard-body .content,
        .admin-dashboard-body .page-inner {
            min-width: 0;
        }

        .admin-dashboard-body .content,
        .admin-dashboard-body .page-inner {
            padding: 0 !important;
        }

        .card,
        .premium-card {
            border: 1px solid rgba(233,236,246,0.98) !important;
            border-radius: 18px !important;
            box-shadow: 0 18px 44px rgba(31,35,85,0.06) !important;
        }

        .table-indigo thead th {
            color: #66708f !important;
            background: #f7f8ff !important;
            border-color: var(--line) !important;
            font-size: 0.68rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .text-indigo { color: var(--primary) !important; }
        .btn-primary {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .btn-outline-primary {
            color: var(--primary) !important;
            border-color: rgba(108,63,242,0.35) !important;
        }
        .btn-round {
            border-radius: 999px !important;
            font-weight: 800;
        }
        .form-group-default {
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 9px 12px;
            background: #fff;
        }
        .form-group-default label {
            margin: 0 0 4px;
            color: var(--muted);
            font-size: 0.62rem;
            font-weight: 900;
            text-transform: uppercase;
        }
        .form-group-default .form-control {
            border: 0;
            padding: 0;
            height: 30px;
            box-shadow: none;
            font-weight: 800;
        }

        @media (max-width: 991.98px) {
            .admin-app-shell {
                display: block;
                margin: 0;
                border-radius: 0;
                border: 0;
            }

            .admin-sidebar {
                min-height: auto;
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .sidebar-menu {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-workspace {
                padding: 24px 16px 34px;
            }

            .admin-main-topbar {
                grid-template-columns: 1fr;
            }

            .admin-search,
            .company-switch {
                width: 100%;
            }
        }
    </style>
    @stack('styles')
</head>
@php
    $isAdminLogin = request()->routeIs('admin-presensi.login');
@endphp
<body class="{{ $isAdminLogin ? 'admin-login-body' : 'admin-dashboard-body' }}">
    @php
        $activeAdminSection = $activeAdminSection ?? request()->route('section') ?? 'dashboard';
        $adminPageMeta = $adminPageMeta ?? [
            'title' => 'Dashboard',
            'subtitle' => 'Ringkasan data presensi dan aktivitas karyawan',
        ];
        $adminSectionUrl = fn (string $section) => $section === 'dashboard'
            ? route('admin-presensi.index')
            : route('admin-presensi.section', $section);
        $adminSectionActive = fn (string $section) => $activeAdminSection === $section ? 'active' : '';
    @endphp
    @if($isAdminLogin)
        @yield('content')
    @else
        <div class="admin-app-shell">
            <aside class="admin-sidebar">
                <a href="{{ route('admin-presensi.index') }}" class="admin-brand">
                    <span class="brand-mark"><i class="fas fa-check-double"></i></span>
                    <span>
                        <strong class="brand-title">PresensiKu</strong>
                        <span class="brand-subtitle">Admin Karyawan</span>
                    </span>
                </a>

                <div>
                    <div class="sidebar-menu">
                        <a href="{{ $adminSectionUrl('dashboard') }}" class="{{ $adminSectionActive('dashboard') }}"><i class="fas fa-table-columns"></i>Dashboard</a>
                    </div>
                    <div class="sidebar-label">Menu Utama</div>
                    <nav class="sidebar-menu">
                        <a href="{{ $adminSectionUrl('karyawan') }}" class="{{ $adminSectionActive('karyawan') }}"><i class="fas fa-users"></i>Karyawan</a>
                        <a href="{{ $adminSectionUrl('presensi') }}" class="{{ $adminSectionActive('presensi') }}"><i class="fas fa-clock"></i>Presensi</a>
                        <a href="{{ $adminSectionUrl('rekap') }}" class="{{ $adminSectionActive('rekap') }}"><i class="fas fa-clipboard-list"></i>Rekap Presensi</a>
                        <a href="{{ $adminSectionUrl('izin-cuti') }}" class="{{ $adminSectionActive('izin-cuti') }}"><i class="fas fa-calendar-check"></i>Izin & Cuti</a>
                        <a href="{{ $adminSectionUrl('lembur') }}" class="{{ $adminSectionActive('lembur') }}"><i class="fas fa-business-time"></i>Lembur</a>
                        <a href="{{ $adminSectionUrl('lokasi') }}" class="{{ $adminSectionActive('lokasi') }}"><i class="fas fa-location-dot"></i>Lokasi</a>
                        <a href="{{ $adminSectionUrl('pengumuman') }}" class="{{ $adminSectionActive('pengumuman') }}"><i class="fas fa-bullhorn"></i>Pengumuman</a>
                    </nav>
                    <div class="sidebar-label">Pengaturan</div>
                    <nav class="sidebar-menu">
                        <a href="{{ $adminSectionUrl('pengaturan') }}" class="{{ $adminSectionActive('pengaturan') }}"><i class="fas fa-gear"></i>Pengaturan</a>
                        <a href="{{ $adminSectionUrl('role-akses') }}" class="{{ $adminSectionActive('role-akses') }}"><i class="fas fa-user-shield"></i>Role & Akses</a>
                        <a href="{{ $adminSectionUrl('aktivitas') }}" class="{{ $adminSectionActive('aktivitas') }}"><i class="fas fa-wave-square"></i>Aktivitas</a>
                    </nav>
                </div>

                <div class="sidebar-upgrade">
                    <i class="fas fa-crown"></i>
                    <strong>Presensi Premium</strong>
                    <span>Monitoring, approval, dan laporan dalam satu dashboard.</span>
                </div>

                <a href="{{ route('admin-presensi.logout') }}" class="sidebar-user">
                    <span class="sidebar-user-avatar">{{ strtoupper(substr(session('presence_admin_username', 'A'), 0, 1)) }}</span>
                    <span>
                        <strong>{{ session('presence_admin_username') }}</strong>
                        <span>Super Admin</span>
                    </span>
                    <i class="fas fa-chevron-down ml-auto"></i>
                </a>
            </aside>

            <main class="admin-workspace">
                <header class="admin-main-topbar">
                    <div class="admin-main-title">
                        <h1>{{ $adminPageMeta['title'] }}</h1>
                        <p>{{ $adminPageMeta['subtitle'] }}</p>
                    </div>
                    <label class="admin-search mb-0">
                        <input type="search" placeholder="Cari karyawan, presensi, divisi..." aria-label="Cari data presensi">
                        <button type="button" aria-label="Cari"><i class="fas fa-search"></i></button>
                    </label>
                    <span class="topbar-icon"><i class="fas fa-bell"></i><em></em></span>
                    <span class="topbar-icon"><i class="fas fa-map-location-dot"></i></span>
                    <span class="company-switch"><i class="fas fa-building"></i> PT. MSP <i class="fas fa-chevron-down"></i></span>
                </header>

                @yield('content')
            </main>
        </div>
    @endif

    @stack('modals')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
    <script>
        @if(session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), timer: 1800, showConfirmButton: false });
        @endif
        @if(session('error'))
            Swal.fire({ icon: 'error', title: 'Oops', text: @json(session('error')) });
        @endif
        @if($errors->any())
            Swal.fire({ icon: 'error', title: 'Peringatan', html: `{!! implode('<br>', $errors->all()) !!}` });
        @endif
        if (document.body.classList.contains('admin-dashboard-body') && window.location.hash) {
            history.replaceState(null, document.title, window.location.pathname + window.location.search);
            window.scrollTo(0, 0);
        }
    </script>
    @stack('scripts')
</body>
</html>
