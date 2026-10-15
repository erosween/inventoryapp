<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>SURYA — Inventory Login</title>
    <link rel="manifest" href="/manifest-admin.json">
    <meta name="application-name" content="Inventory MSP">
    <meta name="theme-color" content="#bd001c">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="SURYA Inventory">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="msapplication-TileColor" content="#bd001c">
    <meta name="msapplication-TileImage" content="/assets/pwa/inventory/icon-144.png">
    <meta name="msapplication-config" content="/browserconfig.xml">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/pwa/inventory/icon-180.png">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#bd001c">
    <link rel="icon" href="/assets/img/MSP5.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, system-ui, sans-serif;
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

        .brand,
        .hero-copy,
        .feature-grid {
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

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px
        }

        .feature {
            padding: 16px 13px;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 15px;
            background: rgba(255, 255, 255, .08)
        }

        .feature i {
            margin-bottom: 13px;
            font-size: 18px
        }

        .feature strong {
            display: block;
            font-size: 12px
        }

        .feature small {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            opacity: .68
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
                padding: 0;
                display: block;
                background: #fff
            }

            .auth-shell {
                min-height: 100dvh;
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

            .feature-grid {
                display: none
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
</head>

<body>
    <main class="auth-shell">
        <section class="brand-panel">
            <div class="brand"><img src="/assets/img/surya-logo-mobile.webp" alt="Logo SURYA" width="58"
                    height="58">
                <div><strong>SURYA</strong><small>INVENTORY & SALES CONTROL</small></div>
            </div>
            <div class="hero-copy"><span>UNIFIED OPERATIONS</span>
                <h1>Satu kendali untuk seluruh distribusi.</h1>
                <p>Pantau stok, aktivitas Sales Force, validasi transaksi, dan performa wilayah dalam workspace yang
                    terhubung.</p>
            </div>
            <div class="feature-grid">
                <div class="feature"><i class="fas fa-warehouse"></i><strong>Stock Gudang</strong><small>Monitoring
                        real-time</small></div>
                <div class="feature"><i class="fas fa-people-group"></i><strong>Sales Force</strong><small>Aktivitas
                        lapangan</small></div>
                <div class="feature"><i class="fas fa-shield-halved"></i><strong>Approval & Audit</strong><small>Kontrol
                        terpusat</small></div>
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
