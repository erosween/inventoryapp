<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>Approval Visit — SURYA</title>
    <link rel="icon" href="/assets/img/surya-logo-mobile.webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --red: #d10b20;
            --deep: #3a000a;
            --ink: #111827;
            --muted: #64748b;
            --line: #e2e8f0;
            --soft: #edf2f7;
            --green: #14955f;
            --orange: #f59e0b;
            --sidebar: 236px
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, system-ui, sans-serif;
            color: var(--ink)
        }

        body {
            background: var(--soft)
        }

        button,
        input {
            font: inherit
        }

        .shell {
            min-height: 100vh
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar);
            padding: 18px 12px;
            display: flex;
            flex-direction: column;
            color: #fff;
            background: linear-gradient(180deg, var(--deep), #970017 52%, var(--red));
            z-index: 50
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 22px
        }

        .brand img {
            width: 36px;
            height: 36px;
            border-radius: 9px
        }

        .brand strong {
            display: block;
            font-size: 15px
        }

        .brand small {
            display: block;
            margin-top: 3px;
            color: rgba(255, 255, 255, .62);
            font-size: 10px;
            letter-spacing: 1.2px
        }

        .nav-label {
            padding: 0 12px 9px;
            color: rgba(255, 255, 255, .5);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.25px
        }

        .nav {
            display: grid;
            gap: 3px
        }

        .nav a {
            min-height: 40px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            gap: 11px;
            border-radius: 8px;
            color: rgba(255, 255, 255, .78);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none
        }

        .nav a:hover {
            color: #fff;
            background: rgba(255, 255, 255, .12)
        }

        .nav a.active {
            color: #b5001c;
            background: #fff;
            box-shadow: 0 8px 18px rgba(38, 0, 5, .18)
        }

        .nav i {
            width: 20px;
            text-align: center;
            font-size: 15px
        }

        .sidebar-foot {
            margin-top: auto;
            padding: 14px 8px 2px;
            border-top: 1px solid rgba(255, 255, 255, .18)
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px
        }

        .sidebar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .14)
        }

        .sidebar-user strong {
            display: block;
            font-size: 12px
        }

        .sidebar-user small {
            color: rgba(255, 255, 255, .62);
            font-size: 10px
        }

        .main {
            margin-left: var(--sidebar);
            min-width: 0
        }

        .topbar {
            min-height: 64px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255, 255, 255, .94);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 20;
            backdrop-filter: blur(12px)
        }

        .menu-button {
            display: none;
            width: 34px;
            height: 34px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff
        }

        .topbar h1 {
            margin: 0;
            font-size: 15px;
            font-weight: 650
        }

        .topbar p {
            margin: 3px 0 0;
            color: #a1a1aa;
            font-size: 10px
        }

        .top-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px
        }

        .connected,
        .logout {
            min-height: 34px;
            padding: 0 11px;
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            color: #52525b;
            background: #fff;
            font-size: 10px;
            font-weight: 700;
            text-decoration: none
        }

        .connected span {
            display: inline-block;
            width: 7px;
            height: 7px;
            margin-right: 6px;
            border-radius: 50%;
            background: #22c55e
        }

        .content {
            max-width: 1540px;
            margin: auto;
            padding: 24px 28px 80px
        }

        .hero {
            margin-bottom: 18px;
            padding: 24px 26px;
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 18px;
            color: #fff;
            border-radius: 18px;
            background: linear-gradient(120deg, #4a000b, #b5001b 62%, #ef2a3d);
            overflow: hidden;
            position: relative
        }

        .hero:after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: -60px;
            top: -100px;
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 50px;
            transform: rotate(25deg)
        }

        .hero-copy,
        .filters {
            position: relative;
            z-index: 1
        }

        .hero small {
            font-weight: 800;
            letter-spacing: .14em;
            opacity: .65
        }

        .hero h2 {
            margin: 8px 0 6px;
            font-size: 28px;
            letter-spacing: -.04em
        }

        .hero p {
            margin: 0;
            color: rgba(255, 255, 255, .72);
            font-size: 12px
        }

        .filters {
            display: flex;
            gap: 8px
        }

        .filters input,
        .filters button {
            height: 40px;
            border-radius: 9px;
            border: 1px solid rgba(255, 255, 255, .26)
        }

        .filters input {
            padding: 0 11px;
            color: #fff;
            background: rgba(255, 255, 255, .12);
            color-scheme: dark
        }

        .filters button {
            padding: 0 17px;
            border: 0;
            color: #a90019;
            background: #fff;
            font-weight: 800;
            cursor: pointer
        }

        .notice {
            margin-bottom: 16px;
            padding: 12px 15px;
            border: 1px solid #bbf7d0;
            border-radius: 11px;
            color: #166534;
            background: #f0fdf4;
            font-size: 12px;
            font-weight: 650
        }

        .card {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05)
        }

        .table-wrap {
            overflow: auto
        }

        .visit-table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse
        }

        .visit-table th {
            padding: 15px 14px;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid var(--line);
            font-size: 10px;
            letter-spacing: .05em;
            text-align: left;
            text-transform: uppercase
        }

        .visit-table td {
            padding: 15px 14px;
            border-bottom: 1px solid #edf1f5;
            color: #475569;
            font-size: 12px;
            vertical-align: middle
        }

        .visit-table tbody tr:hover {
            background: #fbfdff
        }

        .outlet strong {
            display: block;
            color: #172033;
            font-size: 12px
        }

        .outlet small {
            display: block;
            margin-top: 4px;
            color: #94a3b8
        }

        .badge {
            display: inline-flex;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800
        }

        .badge.ok {
            color: #08734a;
            background: #dcfce7
        }

        .badge.wait {
            color: #9a5b00;
            background: #fef3c7
        }

        .photo-open {
            padding: 0;
            border: 0;
            border-radius: 9px;
            background: transparent;
            cursor: zoom-in;
            box-shadow: 0 0 0 1px rgba(15, 23, 42, .09)
        }

        .photo-thumb {
            width: 72px;
            height: 54px;
            display: block;
            object-fit: cover;
            border-radius: 9px
        }

        .approve-btn {
            min-height: 36px;
            padding: 0 13px;
            border: 0;
            border-radius: 9px;
            color: #fff;
            background: var(--green);
            font-size: 10px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap
        }

        .empty {
            padding: 50px !important;
            text-align: center;
            color: #94a3b8 !important
        }

        .pagination {
            padding: 14px 18px
        }

        .viewer[hidden] {
            display: none
        }

        .viewer {
            position: fixed;
            inset: 0;
            z-index: 999;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr);
            background: rgba(8, 12, 20, .95)
        }

        .viewer-bar {
            min-height: 62px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            background: #111827
        }

        .viewer-title {
            margin-right: auto;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 700
        }

        .viewer button {
            height: 36px;
            padding: 0 12px;
            border: 0;
            border-radius: 8px;
            cursor: pointer
        }

        .viewer-stage {
            min-height: 0;
            padding: 20px;
            overflow: auto;
            display: flex;
            justify-content: center;
            align-items: flex-start
        }

        .viewer-image {
            max-width: 100%;
            max-height: calc(100vh - 102px);
            border-radius: 10px;
            background: #fff;
            transform-origin: top center;
            transition: transform .15s
        }

        .viewer-open {
            overflow: hidden !important
        }

        @media(max-width:900px) {
            .sidebar {
                transform: translateX(-100%);
                transition: .2s
            }

            .shell.menu-open .sidebar {
                transform: translateX(0)
            }

            .main {
                margin-left: 0
            }

            .menu-button {
                display: grid;
                place-items: center
            }

            .topbar {
                padding: 0 16px
            }

            .content {
                padding: 18px 14px 50px
            }

            .hero {
                align-items: flex-start;
                flex-direction: column
            }

            .filters {
                width: 100%;
                flex-wrap: wrap
            }

            .filters input {
                flex: 1;
                min-width: 130px
            }

            .filters button {
                flex: 0 0 auto
            }

            .connected {
                display: none
            }
        }
    </style>
</head>

<body>
    <div class="shell" id="surya-shell">
        <aside class="sidebar">
            <div class="brand"><img src="/assets/img/surya-logo-mobile.webp" alt="SURYA">
                <div><strong>SURYA</strong><small>PERFORMANCE</small></div>
            </div>
            <div class="nav-label">WORKSPACE</div>
            <nav class="nav">
                <a href="{{ route('surya.dashboard') }}#leader"><i class="fas fa-table-cells-large"></i>Dashboard</a>
                <a href="{{ route('surya.dashboard') }}#ngrs"><i class="fas fa-tower-broadcast"></i>NGRS</a>
                <a href="{{ route('surya.dashboard') }}#cvm"><i class="fas fa-chart-simple"></i>CVM</a>
                <a href="{{ route('surya.dashboard') }}#pv"><i class="fas fa-bolt"></i>PV</a>
                <a href="{{ route('surya.dashboard') }}#sa"><i class="fas fa-sim-card"></i>SA</a>
                <a href="{{ route('surya.dashboard') }}#digipos"><i class="fas fa-box-open"></i>DIGIPOS PACKAGE</a>
                <a class="active" href="{{ route('surya.visits.report') }}"><i class="fas fa-camera"></i>DETAIL
                    VISIT</a>
            </nav>
            <div class="sidebar-foot">
                <div class="sidebar-user">
                    <div class="sidebar-avatar"><i class="fas fa-user-shield"></i></div>
                    <div><strong>SURYA Leader</strong><small>Secure access</small></div>
                </div>
            </div>
        </aside>
        <div class="main">
            <header class="topbar"><button class="menu-button" type="button"
                    onclick="document.getElementById('surya-shell').classList.toggle('menu-open')"><i
                        class="fas fa-bars"></i></button>
                <div>
                    <h1>Monitoring & Performance</h1>
                    <p>Cluster Dumai, Bengkalis & Rokan Hilir</p>
                </div>
                <div class="top-actions">
                    <div class="connected"><span></span>Data terhubung</div><a class="logout"
                        href="{{ route('surya.leader.logout') }}" title="Logout"><i
                            class="fas fa-arrow-right-from-bracket"></i></a>
                </div>
            </header>
            <main class="content">
                <section class="hero">
                    <div class="hero-copy"><small>SURYA FIELD OPERATIONS</small>
                        <h2>Reporting & Approval Visit</h2>
                        <p>Periksa foto outlet dan setujui pembaruan koordinat dari satu layar.</p>
                    </div>
                    <form class="filters" method="GET"><input type="date" name="date_from"
                            value="{{ $dateFrom }}" aria-label="Tanggal awal"><input type="date" name="date_to"
                            value="{{ $dateTo }}" aria-label="Tanggal akhir"><button
                            type="submit">Filter</button></form>
                </section>
                @if (session('success'))
                    <div class="notice"><i class="fas fa-circle-check"></i>&nbsp; {{ session('success') }}</div>
                @endif
                <section class="card">
                    <div class="table-wrap">
                        <table class="visit-table">
                            <thead>
                                <tr>
                                    <th>Outlet</th>
                                    <th>TAP</th>
                                    <th>Sales Force</th>
                                    <th>Visit Terakhir</th>
                                    <th>Status GPS</th>
                                    <th>Jarak</th>
                                    <th>Dominasi Branding</th>
                                    <th>Kelayakan Branding</th>
                                    <th>Tampak Depan</th>
                                    <th>Tampak Dalam</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visits as $visit)
                                    <tr>
                                        <td class="outlet">
                                            <strong>{{ $visit->nama_outlet ?? $visit->id_outlet }}</strong><small>{{ $visit->id_outlet }}</small>
                                        </td>
                                        <td>{{ $visit->idtap }}</td>
                                        <td>{{ $visit->namasf ?? $visit->idsf }}</td>
                                        <td>{{ \Carbon\Carbon::parse($visit->visited_at)->format('d M Y H:i') }}</td>
                                        <td><span
                                                class="badge {{ in_array($visit->gps_status, ['valid', 'approved']) ? 'ok' : 'wait' }}">{{ strtoupper(str_replace('_', ' ', $visit->gps_status)) }}</span>
                                        </td>
                                        <td>{{ $visit->distance_meters !== null ? number_format($visit->distance_meters, 0) . ' m' : '—' }}
                                        </td>
                                        <td>{{ $visit->branding_dominance ?: '—' }}</td>
                                        <td>{{ $visit->branding_quality ?: '—' }}</td>
                                        <td>
                                            @if ($visit->front_photo_url)
                                                <button type="button" class="photo-open"
                                                    data-photo-src="{{ $visit->front_photo_url }}"
                                                    data-photo-title="{{ $visit->nama_outlet ?? $visit->id_outlet }} · Tampak Depan"><img
                                                        src="{{ $visit->front_thumbnail_url }}" loading="lazy"
                                                        decoding="async" alt="Tampak depan"
                                                        class="photo-thumb"></button>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($visit->inside_photo_url)
                                                <button type="button" class="photo-open"
                                                    data-photo-src="{{ $visit->inside_photo_url }}"
                                                    data-photo-title="{{ $visit->nama_outlet ?? $visit->id_outlet }} · Tampak Dalam"><img
                                                        src="{{ $visit->inside_thumbnail_url }}" loading="lazy"
                                                        decoding="async" alt="Tampak dalam"
                                                        class="photo-thumb"></button>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($visit->gps_status === 'pending_admin')
                                                <form method="POST"
                                                    action="{{ route('surya.visits.approve', $visit->id) }}">
                                                    @csrf<button class="approve-btn">Setujui & Perbarui
                                                        Koordinat</button></form>
                                                <form method="POST" action="{{ route('surya.visits.reject', $visit->id) }}"
                                                    onsubmit="return confirm('Tolak foto visit ini? Sales Force harus mengambil ulang foto depan dan dalam.')">
                                                    @csrf<button class="approve-btn" style="margin-top:6px;background:#b91c1c">Tolak Foto</button></form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty<tr>
                                        <td colspan="11" class="empty">Belum ada data visit.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination">{{ $visits->links() }}</div>
                </section>
            </main>
        </div>
    </div>
    <div id="visit-photo-viewer" class="viewer visit-photo-viewer" hidden aria-hidden="true" role="dialog"
        aria-modal="true">
        <div class="viewer-bar"><span id="viewer-title" class="viewer-title">Foto Visit</span><button type="button"
                data-photo-zoom-out><i class="fas fa-search-minus"></i></button><button type="button"
                data-reset>100%</button><button type="button" data-photo-zoom-in><i
                    class="fas fa-search-plus"></i></button><button type="button" data-photo-close><i
                    class="fas fa-times"></i></button></div>
        <div class="viewer-stage"><img id="viewer-image" class="viewer-image" alt="Foto visit"></div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const viewer = document.getElementById('visit-photo-viewer'),
                image = document.getElementById('viewer-image'),
                title = document.getElementById('viewer-title'),
                reset = viewer.querySelector('[data-reset]');
            let zoom = 1;
            const apply = () => {
                image.style.transform = `scale(${zoom})`;
                reset.textContent = `${Math.round(zoom*100)}%`
            };
            const close = () => {
                viewer.hidden = true;
                viewer.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('viewer-open');
                image.removeAttribute('src');
                zoom = 1;
                apply()
            };
            document.addEventListener('click', e => {
                const opener = e.target.closest('.photo-open');
                if (!opener) return;
                zoom = 1;
                image.src = opener.dataset.photoSrc;
                image.alt = title.textContent = opener.dataset.photoTitle || 'Foto Visit';
                viewer.hidden = false;
                viewer.setAttribute('aria-hidden', 'false');
                document.body.classList.add('viewer-open');
                apply()
            });
            viewer.querySelector('[data-photo-zoom-in]').onclick = () => {
                zoom = Math.min(3, zoom + .25);
                apply()
            };
            viewer.querySelector('[data-photo-zoom-out]').onclick = () => {
                zoom = Math.max(.5, zoom - .25);
                apply()
            };
            reset.onclick = () => {
                zoom = 1;
                apply()
            };
            viewer.querySelector('[data-photo-close]').onclick = close;
            viewer.onclick = e => {
                if (e.target === viewer) close()
            };
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && !viewer.hidden) close()
            })
        });
    </script>
</body>

</html>
