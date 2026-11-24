{{--
    Halaman login Web Inventory. Submit memakai route login.post dari AuthController.
--}}<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=auto">
    <title>SURYA — Inventory Login</title>
    <link rel="manifest" href="/manifest-admin.json">
    <meta name="application-name" content="Inventory MSP">
    <meta name="theme-color" content="#bd001c">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="SURYA Inventory">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="msapplication-TileColor" content="#bd001c">
    <meta name="msapplication-TileImage" content="/assets/pwa/inventory/icon-144.png">
    <meta name="msapplication-config" content="/browserconfig.xml">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/pwa/inventory/icon-180.png">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#bd001c">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/pwa/surya/icon-192-round.png?v=round1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --red: #d10b20;
            --ink: #111827;
            --muted: #64748b;
            --line: #e5eaf1
        }

        * {
            box-sizing: border-box
        }

        /* Keep the iPad Home Screen status area outside the split login canvas. */
        html { background: #fff; color-scheme: light; }

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: var(--web-font);
            color: var(--ink)
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            background: #edf2f7
        }

        .auth-shell {
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(440px, .92fr);
            overflow: hidden;
            background: #fff
        }

        .login-status-surface { display: none; }

        /* Safari samples a fixed, opaque edge instead of the red/white split canvas.
           Reserve the same height in normal flow; never cover inputs or trap scrolling. */
        @supports (-webkit-touch-callout: none) {
            @media (pointer: coarse) {
                :root { --login-status-height: max(12px, env(safe-area-inset-top, 0px)); }
                html { background: #8e0925; }
                body { padding-top: var(--login-status-height); background: #fff; }
                .login-status-surface {
                    display: block; position: fixed; inset: 0 0 auto;
                    height: var(--login-status-height); z-index: 100;
                    background: #8e0925; pointer-events: none;
                }
                .auth-shell {
                    min-height: calc(100vh - var(--login-status-height));
                    min-height: calc(100dvh - var(--login-status-height));
                }
            }
        }

        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            color: #fff;
            overflow: hidden;
            background: linear-gradient(145deg, #300008 0%, #8d0014 48%, #ed1b2f 100%)
        }

        .brand-panel:before,
        .brand-panel:after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 44px;
            transform: rotate(18deg)
        }

        .brand-panel:before {
            width: 310px;
            height: 310px;
            right: -100px;
            top: 120px
        }

        .brand-panel:after {
            width: 210px;
            height: 210px;
            left: -100px;
            bottom: -90px
        }

        .brand-energy-lines {
            position: absolute;
            z-index: 0;
            overflow: visible;
            pointer-events: none
        }

        .brand-energy-lines rect {
            fill: none;
            stroke: rgba(255, 255, 255, .9);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-dasharray: 64 936;
            filter: drop-shadow(0 0 5px rgba(255, 255, 255, .95));
            animation: brand-energy-travel 8s linear infinite
        }

        .brand-energy-lines--primary {
            width: 310px;
            height: 310px;
            right: -100px;
            top: 120px;
            transform: rotate(18deg)
        }

        .brand-energy-lines--secondary {
            width: 210px;
            height: 210px;
            left: -100px;
            bottom: -90px;
            transform: rotate(18deg)
        }

        .brand-energy-lines--secondary rect {
            animation-duration: 6.5s;
            animation-delay: -3s;
            opacity: .8
        }

        @keyframes brand-energy-travel {
            to { stroke-dashoffset: -1000 }
        }

        @media (prefers-reduced-motion: reduce) {
            .brand-energy-lines rect {
                animation: none;
                opacity: 0
            }
        }

        .brand,
        .hero-copy,
        .operations-map {
            position: relative;
            z-index: 1
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 15px
        }

        .brand img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            border-radius: 15px
        }

        .brand strong {
            display: block;
            font-size: 23px;
            letter-spacing: .06em
        }

        .brand small {
            display: block;
            margin-top: 4px;
            opacity: .72;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .16em
        }

        .hero-copy {
            margin: 48px 0 40px
        }

        .hero-copy span {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .18em;
            opacity: .72
        }

        .hero-copy h1 {
            margin: 13px 0 16px;
            max-width: 470px;
            font-size: clamp(38px, 5vw, 62px);
            line-height: 1.02;
            letter-spacing: -.055em
        }

        .hero-copy p {
            max-width: 480px;
            margin: 0;
            color: rgba(255, 255, 255, .78);
            line-height: 1.7
        }

        .operations-map {
            width: 100%;
            max-width: 1000px;
            height: clamp(280px, 34vh, 450px);
            align-self: center;
            margin: 0 auto;
            overflow: visible
        }

        .operations-map svg {
            display: block;
            width: 100%;
            height: 100%;
            overflow: visible;
            background: radial-gradient(ellipse at center, rgba(20, 2, 7, .78) 0%, rgba(20, 2, 7, .45) 50%, transparent 76%)
        }

        .operations-map-mobile { display: none !important; }

        .operations-map .orbit {
            fill: none;
            stroke: rgba(255, 42, 65, .24);
            stroke-width: 1.5
        }

        .operations-map .orbit--dashed {
            stroke: rgba(255, 42, 65, .22);
            stroke-dasharray: 5 7
        }

        .operations-map .connector {
            fill: none;
            stroke: rgba(255, 43, 65, .7);
            stroke-width: 1.5
        }

        .operations-map .card {
            fill: rgba(19, 20, 26, .84);
            stroke: rgba(255, 255, 255, .2);
            stroke-width: 1.5
        }

        .operations-map .card-category {
            fill: rgba(255, 255, 255, .45);
            font: 700 11px var(--web-font);
            letter-spacing: 3px
        }

        .operations-map .card-title {
            fill: rgba(255, 255, 255, .92);
            font: 500 17px var(--web-font)
        }

        .operations-map .card-icon {
            fill: none;
            stroke: rgba(255, 255, 255, .65);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round
        }

        .operations-map .card-led,
        .operations-map .anchor-dot {
            fill: #ff2945;
            filter: drop-shadow(0 0 5px rgba(255, 41, 69, .9))
        }

        .operations-map .moving-dot {
            fill: #ff5366;
            filter: drop-shadow(0 0 7px rgba(255, 41, 69, 1))
        }

        .operations-map .center-logo-ring {
            fill: rgba(165, 8, 30, .3);
            stroke: rgba(255, 45, 68, .85);
            stroke-width: 1.5
        }

        .operations-map .center-logo {
            filter: drop-shadow(0 0 14px rgba(240, 20, 48, .45))
        }

        .operations-map .center-title {
            fill: rgba(255, 255, 255, .92);
            font: 800 16px var(--web-font);
            letter-spacing: 5px
        }

        .operations-map .center-caption {
            fill: rgba(255, 255, 255, .45);
            font: 600 7px var(--web-font);
            letter-spacing: 2px
        }

        @media (prefers-reduced-motion: reduce) {
            .operations-map .moving-dot { display: none }
        }

        @media (max-width: 560px) {
            .operations-map { height: 280px; }
            .operations-map-desktop { display: none !important; }
            .operations-map-mobile { display: block !important; }
            .operations-map-mobile .card { fill: rgba(19, 20, 26, .88); }
            .operations-map-mobile .card-category { font-size: 7px; letter-spacing: 1.6px; }
            .operations-map-mobile .card-title { font-size: 10px; }
            .operations-map-mobile .center-title { font-size: 9px; letter-spacing: 2px; }
            .operations-map-mobile .center-caption { font-size: 4px; letter-spacing: 1px; }
        }

        .login-panel {
            display: flex;
            align-items: center;
            padding: 56px;
            background: #fff
        }

        .login-box {
            width: 100%;
            max-width: 520px;
            margin: auto
        }

        .eyebrow {
            color: var(--red);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em
        }

        h2 {
            margin: 10px 0 8px;
            font-size: 34px;
            letter-spacing: -.04em
        }

        .subtitle {
            margin: 0 0 32px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55
        }

        .alert {
            margin-bottom: 18px;
            padding: 13px 14px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            background: #fff1f2;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 600
        }

        label {
            display: block;
            margin: 0 0 8px;
            color: #334155;
            font-size: 12px;
            font-weight: 700
        }

        .field {
            position: relative;
            margin-bottom: 18px
        }

        .field>i {
            position: absolute;
            left: 15px;
            bottom: 17px;
            color: #94a3b8
        }

        .field input {
            width: 100%;
            height: 52px;
            padding: 0 46px 0 43px;
            border: 1px solid var(--line);
            border-radius: 13px;
            background: #f8fafc;
            color: var(--ink);
            font-size: 14px;
            outline: 0;
            transition: .2s
        }

        .field input:focus {
            border-color: #ef6a78;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(209, 11, 32, .09)
        }

        .toggle-password {
            position: absolute;
            right: 8px;
            bottom: 7px;
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #64748b;
            cursor: pointer
        }

        .login-btn,
        .install-btn {
            width: 100%;
            min-height: 51px;
            border-radius: 13px;
            font: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer
        }

        .login-btn {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            border: 0;
            color: #fff;
            background: linear-gradient(110deg, #bd001c, #fb3344);
            box-shadow: 0 14px 28px rgba(209, 11, 32, .23)
        }

        .login-btn:disabled {
            opacity: .7;
            cursor: wait
        }

        .install-btn {
            margin-top: 12px;
            border: 1px solid #fecdd3;
            color: #b80d20;
            background: #fff1f2
        }

        .install-note {
            margin: 12px 8px 0;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
            text-align: center
        }

        .secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 25px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700
        }

        @media(max-width:820px) {
            body {
                padding: var(--login-status-height, 0px) 0 0;
                display: block;
                background: #fff
            }

            .auth-shell {
                min-height: calc(100dvh - var(--login-status-height, 0px));
                grid-template-columns: 1fr;
                border-radius: 0
            }

            .brand-panel {
                min-height: 330px;
                padding: 30px 26px
            }

            .hero-copy {
                margin: 38px 0 28px
            }

            .hero-copy h1 {
                font-size: 40px;
                max-width: 520px
            }

            .operations-map {
                height: 280px;
                max-width: 620px
            }

            .login-panel {
                align-items: flex-start;
                margin-top: -22px;
                padding: 38px 26px max(38px, env(safe-area-inset-bottom));
                border-radius: 26px 26px 0 0;
                position: relative;
                z-index: 2
            }
        }

        @media(max-width:430px) {
            .brand-panel {
                min-height: 300px
            }

            .hero-copy h1 {
                font-size: 35px
            }

            .login-panel {
                padding-left: 20px;
                padding-right: 20px
            }
        }
    </style>
    @include('layout.web-typography')
</head>

<body>
    <div class="login-status-surface" aria-hidden="true"></div>
    <main class="auth-shell">
        <section class="brand-panel">
            <svg class="brand-energy-lines brand-energy-lines--primary" viewBox="0 0 310 310" aria-hidden="true">
                <rect x="1" y="1" width="308" height="308" rx="44" pathLength="1000" />
            </svg>
            <svg class="brand-energy-lines brand-energy-lines--secondary" viewBox="0 0 210 210" aria-hidden="true">
                <rect x="1" y="1" width="208" height="208" rx="44" pathLength="1000" />
            </svg>
            <div class="brand"><img src="/assets/img/surya-logo-mobile.webp" alt="Logo SURYA" width="58"
                    height="58">
                <div><strong>SURYA</strong><small>INVENTORY & SALES CONTROL</small></div>
            </div>
            <div class="hero-copy"><span>UNIFIED OPERATIONS</span>
                <h1>Satu kendali untuk seluruh distribusi.</h1>
                <p>Pantau stok, aktivitas Sales Force, validasi transaksi, dan performa wilayah dalam workspace yang
                    terhubung.</p>
            </div>
            <div class="operations-map" aria-hidden="true">
                <svg class="operations-map-desktop" viewBox="0 0 1000 520" preserveAspectRatio="xMidYMid meet">
                    <circle class="orbit orbit--dashed" cx="500" cy="260" r="222" />
                    <circle class="orbit" cx="500" cy="260" r="166" />
                    <circle class="orbit" cx="500" cy="260" r="118" />

                    <path class="connector" d="M500 260 L280 140" />
                    <path class="connector" d="M500 260 L720 140" />
                    <path class="connector" d="M500 260 L280 380" />
                    <path class="connector" d="M500 260 L720 380" />
                    <circle class="anchor-dot" cx="280" cy="140" r="4" />
                    <circle class="anchor-dot" cx="720" cy="140" r="4" />
                    <circle class="anchor-dot" cx="280" cy="380" r="4" />
                    <circle class="anchor-dot" cx="720" cy="380" r="4" />

                    <circle class="moving-dot" r="4">
                        <animateMotion dur="3.8s" repeatCount="indefinite" path="M500 260 L280 140 L500 260" />
                    </circle>
                    <circle class="moving-dot" r="4">
                        <animateMotion dur="4.2s" begin="-1.1s" repeatCount="indefinite" path="M500 260 L720 140 L500 260" />
                    </circle>
                    <circle class="moving-dot" r="4">
                        <animateMotion dur="4.5s" begin="-2.2s" repeatCount="indefinite" path="M500 260 L280 380 L500 260" />
                    </circle>
                    <circle class="moving-dot" r="4">
                        <animateMotion dur="4s" begin="-3s" repeatCount="indefinite" path="M500 260 L720 380 L500 260" />
                    </circle>

                    <g>
                        <rect class="card" x="18" y="80" width="262" height="120" rx="18" />
                        <path class="card-icon" d="M46 122l16-9 16 9-16 9-16-9zm0 0v19l16 9 16-9v-19m-16 9v19" />
                        <text class="card-category" x="98" y="119">INVENTORY</text>
                        <text class="card-title" x="98" y="153">Stok gudang</text>
                        <circle class="card-led" cx="250" cy="140" r="3.5" />
                    </g>
                    <g>
                        <rect class="card" x="720" y="80" width="262" height="120" rx="18" />
                        <path class="card-icon" d="M748 151v-35m0 35h38m-31-10 10-10 9 5 13-16" />
                        <text class="card-category" x="800" y="119">SALES</text>
                        <text class="card-title" x="800" y="153">Penjualan</text>
                        <circle class="card-led" cx="952" cy="140" r="3.5" />
                    </g>
                    <g>
                        <rect class="card" x="18" y="320" width="262" height="120" rx="18" />
                        <circle class="card-icon" cx="54" cy="357" r="7" />
                        <circle class="card-icon" cx="75" cy="360" r="6" />
                        <path class="card-icon" d="M40 389c1-12 7-18 14-18s13 6 14 18m-1-15c10 0 16 5 17 15" />
                        <text class="card-category" x="98" y="359">PEOPLE</text>
                        <text class="card-title" x="98" y="393">Tim lapangan</text>
                        <circle class="card-led" cx="250" cy="380" r="3.5" />
                    </g>
                    <g>
                        <rect class="card" x="720" y="320" width="262" height="120" rx="18" />
                        <path class="card-icon" d="M765 346l17 6v13c0 12-7 20-17 25-10-5-17-13-17-25v-13l17-6zm-8 19 6 6 12-13" />
                        <text class="card-category" x="800" y="359">CONTROL</text>
                        <text class="card-title" x="800" y="393">Approval &amp; audit</text>
                        <circle class="card-led" cx="952" cy="380" r="3.5" />
                    </g>

                    <circle class="center-logo-ring" cx="500" cy="260" r="61" />
                    <image class="center-logo" href="/assets/img/surya-logo-mobile.webp" x="450" y="210" width="100" height="100" />
                    <text class="center-title" x="500" y="345" text-anchor="middle">SURYA</text>
                    <text class="center-caption" x="500" y="365" text-anchor="middle">CONNECTED WORKSPACE</text>
                </svg>
                <svg class="operations-map-mobile" viewBox="0 0 360 280" preserveAspectRatio="xMidYMid meet">
                    <circle class="orbit orbit--dashed" cx="180" cy="140" r="112" />
                    <circle class="orbit" cx="180" cy="140" r="73" />
                    <path class="connector" d="M180 140 L156 45 M180 140 L204 45 M180 140 L156 235 M180 140 L204 235" />
                    <circle class="anchor-dot" cx="156" cy="45" r="3" />
                    <circle class="anchor-dot" cx="204" cy="45" r="3" />
                    <circle class="anchor-dot" cx="156" cy="235" r="3" />
                    <circle class="anchor-dot" cx="204" cy="235" r="3" />
                    <circle class="moving-dot" r="3">
                        <animateMotion dur="3.2s" repeatCount="indefinite" path="M180 140 L156 45 L180 140" />
                    </circle>
                    <circle class="moving-dot" r="3">
                        <animateMotion dur="3.8s" begin="-1s" repeatCount="indefinite" path="M180 140 L204 45 L180 140" />
                    </circle>
                    <circle class="moving-dot" r="3">
                        <animateMotion dur="4s" begin="-2s" repeatCount="indefinite" path="M180 140 L156 235 L180 140" />
                    </circle>
                    <circle class="moving-dot" r="3">
                        <animateMotion dur="3.5s" begin="-2.7s" repeatCount="indefinite" path="M180 140 L204 235 L180 140" />
                    </circle>
                    <rect class="card" x="3" y="12" width="153" height="66" rx="12" />
                    <text class="card-category" x="15" y="37">INVENTORY</text>
                    <text class="card-title" x="15" y="57">Stok gudang</text>
                    <circle class="card-led" cx="142" cy="45" r="2.5" />
                    <rect class="card" x="204" y="12" width="153" height="66" rx="12" />
                    <text class="card-category" x="216" y="37">SALES</text>
                    <text class="card-title" x="216" y="57">Penjualan</text>
                    <circle class="card-led" cx="343" cy="45" r="2.5" />
                    <rect class="card" x="3" y="202" width="153" height="66" rx="12" />
                    <text class="card-category" x="15" y="227">PEOPLE</text>
                    <text class="card-title" x="15" y="247">Tim lapangan</text>
                    <circle class="card-led" cx="142" cy="235" r="2.5" />
                    <rect class="card" x="204" y="202" width="153" height="66" rx="12" />
                    <text class="card-category" x="216" y="227">CONTROL</text>
                    <text class="card-title" x="216" y="247">Approval &amp; audit</text>
                    <circle class="card-led" cx="343" cy="235" r="2.5" />
                    <circle class="center-logo-ring" cx="180" cy="140" r="34" />
                    <image class="center-logo" href="/assets/img/surya-logo-mobile.webp" x="151" y="111" width="58" height="58" />
                    <text class="center-title" x="180" y="190" text-anchor="middle">SURYA</text>
                    <text class="center-caption" x="180" y="198" text-anchor="middle">CONNECTED WORKSPACE</text>
                </svg>
            </div>
        </section>
        <section class="login-panel">
            <div class="login-box">
                <div class="eyebrow">SECURE ACCESS</div>
                <h2>Masuk Inventory</h2>
                <p class="subtitle">Login Menggunakan Akun Terdaftar</p>
                @if (session('error'))
                    <div class="alert"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
                @endif
                <form method="POST" action="{{ route('login.post') }}" onsubmit="return handleSubmit(this)">@csrf<div
                        class="field"><label for="username">Username</label><i class="fas fa-user"></i><input
                            id="username" type="text" name="username" value="{{ old('username') }}"
                            autocomplete="username" placeholder="Masukkan username" required autofocus></div>
                    <div class="field"><label for="password">Password</label><i class="fas fa-lock"></i><input
                            id="password" type="password" name="password" autocomplete="current-password"
                            placeholder="Masukkan password" required><button class="toggle-password" type="button"
                            aria-label="Tampilkan password" onclick="togglePassword(this)"><i
                                class="fas fa-eye"></i></button></div><button type="submit" class="login-btn"><span
                            class="btn-text">Masuk</span><i class="fas fa-arrow-right"></i></button><button
                        type="button" class="install-btn" data-pwa-install hidden><i class="fas fa-download"></i>
                        Install Aplikasi SURYA</button>
                    <p class="install-note">Chrome, Edge, dan Android dapat menampilkan prompt instalasi langsung.
                        Safari/iPhone menggunakan “Tambahkan ke Layar Utama”.</p>
                </form>
                <div class="secure-note"><i class="fas fa-shield-halved"></i> Encrypted session • Role-based access
                </div>
            </div>
        </section>
    </main>
    <script>
        function togglePassword(button) {
            const input = document.getElementById('password');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.querySelector('i').className = visible ? 'fas fa-eye' : 'fas fa-eye-slash'
        }

        function handleSubmit(form) {
            const button = form.querySelector('.login-btn');
            button.disabled = true;
            button.querySelector('.btn-text').textContent = 'Memuat…';
            requestAnimationFrame(() => form.submit());
            return false
        }
    </script>@include('partials.pwa-installer')
</body>

</html>
