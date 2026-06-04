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
            --primary: #5b37e5;
            --primary-dark: #2f1aa8;
            --soft: #f5f3ff;
            --ink: #101333;
            --muted: #737997;
            --line: #e8eaf6;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: "Plus Jakarta Sans", sans-serif;
            background:
                radial-gradient(circle at 15% 8%, rgba(91,55,229,0.13), transparent 30%),
                linear-gradient(180deg, #fbfbff 0%, #eef1fb 100%);
            color: var(--ink);
        }

        .presence-admin-shell {
            max-width: 1240px;
            margin: 0 auto;
            padding: 28px 22px 44px;
        }

        .presence-admin-topbar {
            min-height: 66px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .brand-mark {
            width: 48px;
            height: 48px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(135deg, #7557f6, #4c29d9);
            box-shadow: 0 18px 36px rgba(91,55,229,0.24);
        }

        .brand-title {
            margin: 0;
            color: var(--ink);
            font-size: 1.05rem;
            font-weight: 900;
        }

        .brand-subtitle {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 800;
        }

        .admin-pill {
            border-radius: 999px;
            padding: 10px 14px;
            color: var(--primary);
            background: var(--soft);
            font-size: 0.74rem;
            font-weight: 900;
            text-decoration: none;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .page-title {
            margin: 0;
            color: var(--ink);
            font-size: 1.35rem;
            font-weight: 900;
        }

        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 0;
            margin: 0;
            list-style: none;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 800;
        }

        .breadcrumbs a {
            color: var(--primary);
            text-decoration: none;
        }

        .separator {
            color: #a0a6be;
            font-size: 0.62rem;
        }

        .card,
        .premium-card {
            border: 1px solid rgba(232,234,246,0.96) !important;
            border-radius: 22px !important;
            box-shadow: 0 18px 44px rgba(31,35,85,0.08) !important;
        }

        .table-indigo thead th {
            color: #fff !important;
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            font-size: 0.72rem;
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
            border-color: rgba(91,55,229,0.35) !important;
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
    </style>
    @stack('styles')
</head>
<body>
    <main class="presence-admin-shell">
        <header class="presence-admin-topbar">
            <div class="d-flex align-items-center">
                <div class="brand-mark mr-3"><i class="fas fa-check-double"></i></div>
                <div>
                    <h1 class="brand-title">Admin PresensiKu</h1>
                    <p class="brand-subtitle">Dashboard lokasi, jadwal, dan akses karyawan</p>
                </div>
            </div>
            @if(session('presence_admin_username'))
                <div class="d-flex align-items-center">
                    <span class="admin-pill mr-2"><i class="fas fa-user-shield mr-1"></i>{{ session('presence_admin_username') }}</span>
                    <a href="{{ route('admin-presensi.logout') }}" class="admin-pill text-danger">
                        <i class="fas fa-arrow-right-from-bracket mr-1"></i>Keluar
                    </a>
                </div>
            @endif
        </header>

        @yield('content')
    </main>

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
    </script>
    @stack('scripts')
</body>
</html>
