<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login SF - Inventory MSP</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #ec2028;
            --primary-light: #ff5a62;
            --ink: #111827;
            --muted: #64748b;
            --line: #e2e8f0;
            --surface: #f3f7fb;
            --shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            overflow-x: hidden;
            background: #e9eff6;
        }

        body {
            min-height: 100vh;
            min-height: 100svh;
            margin: 0;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 18% 7%, rgba(236, 32, 40, 0.18), transparent 30%),
                radial-gradient(circle at 88% 22%, rgba(37, 99, 235, 0.12), transparent 28%),
                linear-gradient(180deg, #f8fafc 0%, #e9eff6 100%);
            display: flex;
            justify-content: center;
            align-items: stretch;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            overscroll-behavior-y: none;
        }

        .login-phone {
            width: 100%;
            max-width: 480px;
            min-height: 100vh;
            min-height: 100svh;
            background: var(--surface);
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior-y: contain;
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.05), var(--shadow);
        }

        .login-hero {
            min-height: clamp(330px, 45svh, 430px);
            padding: 26px 24px 92px;
            color: #fff;
            background: linear-gradient(145deg, #111827 0%, #293241 50%, #ec2028 100%);
            border-radius: 0 0 38px 38px;
            position: relative;
            overflow: hidden;
        }

        .login-hero::after {
            content: "";
            position: absolute;
            right: -34px;
            bottom: -44px;
            width: 166px;
            height: 166px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 42px;
            transform: rotate(18deg);
        }

        .brand-row,
        .hero-copy,
        .hero-strip {
            position: relative;
            z-index: 1;
        }

        .brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 34px;
        }

        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid rgba(255, 255, 255, 0.55);
            box-shadow: 0 14px 26px rgba(0, 0, 0, 0.18);
        }

        .brand-name {
            font-size: 1rem;
            font-weight: 900;
            line-height: 1;
        }

        .brand-sub {
            color: rgba(255, 255, 255, 0.62);
            font-size: 0.65rem;
            font-weight: 800;
            margin-top: 4px;
        }

        .secure-pill {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.11);
            color: rgba(255, 255, 255, 0.82);
            border-radius: 999px;
            padding: 8px 11px;
            font-size: 0.65rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .hero-copy h1 {
            font-size: 2.15rem;
            line-height: 1.02;
            font-weight: 900;
            margin: 0 0 10px;
            letter-spacing: 0;
        }

        .hero-copy p {
            max-width: 330px;
            margin: 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.86rem;
            line-height: 1.55;
            font-weight: 650;
        }

        .hero-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 9px;
            margin-top: 26px;
        }

        .strip-item {
            min-height: 78px;
            border-radius: 20px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.13);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .strip-item i {
            color: #fff;
            font-size: 1rem;
        }

        .strip-item span {
            color: rgba(255, 255, 255, 0.74);
            font-size: 0.61rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .login-card {
            position: relative;
            z-index: 2;
            margin: -70px 18px 24px;
            padding: 22px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 28px;
            box-shadow: 0 22px 55px rgba(15, 23, 42, 0.12);
            transform: translateZ(0);
        }

        @supports not (height: 100svh) {
            body,
            .login-phone {
                min-height: 100vh;
            }
        }

        .form-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .form-title h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 900;
        }

        .form-title p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 800;
        }

        .form-title i {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(236, 32, 40, 0.1);
            color: var(--primary);
        }

        .alert-premium {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 13px 15px;
            border-radius: 18px;
            font-size: 0.78rem;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .field-group {
            margin-bottom: 14px;
        }

        .field-label {
            display: block;
            color: #475569;
            font-size: 0.68rem;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0 0 8px 4px;
        }

        .field-wrap {
            position: relative;
        }

        .field-wrap > i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .login-input {
            width: 100%;
            min-height: 54px;
            border: 1px solid var(--line);
            border-radius: 19px;
            background: #f8fafc;
            padding: 0 48px;
            color: var(--ink);
            font-size: 0.95rem;
            font-weight: 800;
            outline: none;
            transition: all 0.2s ease;
        }

        .login-input:focus {
            background: #fff;
            border-color: rgba(236, 32, 40, 0.48);
            box-shadow: 0 0 0 4px rgba(236, 32, 40, 0.09);
        }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 8px;
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 14px;
            background: #eef2f7;
            color: #64748b;
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 4px 0 18px;
        }

        .form-check-label {
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 800;
        }

        .login-btn {
            width: 100%;
            min-height: 56px;
            border: 0;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: #fff;
            font-weight: 900;
            box-shadow: 0 14px 28px rgba(236, 32, 40, 0.26);
        }

        .login-btn:active {
            transform: scale(0.98);
        }

        .presence-link {
            min-height: 46px;
            margin-top: 14px;
            border-radius: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #111827;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 900;
        }

        .build-note {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 0.7rem;
            font-weight: 800;
            padding-bottom: 22px;
        }

        @media (max-height: 740px) {
            .login-hero {
                padding-bottom: 74px;
            }

            .hero-copy h1 {
                font-size: 1.8rem;
            }

            .hero-strip {
                display: none;
            }
        }
    </style>
</head>

<body>
    <main class="login-phone">
        <section class="login-hero">
            <div class="brand-row">
                <div class="brand-lockup">
                    <img src="/assets/img/MSP5.png" class="brand-logo" alt="MSP">
                    <div>
                        <div class="brand-name">MSP Connect</div>
                        <div class="brand-sub">Field Sales Console</div>
                    </div>
                </div>
                <div class="secure-pill">
                    <i class="fas fa-shield-halved me-1"></i> Secure
                </div>
            </div>

            <div class="hero-copy">
                <h1>Sales dan stok dalam satu layar.</h1>
                <p>Akses cepat untuk input kunjungan, pantau inventory, dan cek riwayat penjualan tim lapangan.</p>
            </div>

            <div class="hero-strip">
                <div class="strip-item">
                    <i class="fas fa-store"></i>
                    <span>Journey Plan</span>
                </div>
                <div class="strip-item">
                    <i class="fas fa-boxes-stacked"></i>
                    <span>Live Stock</span>
                </div>
                <div class="strip-item">
                    <i class="fas fa-clock-rotate-left"></i>
                    <span>Sales History</span>
                </div>
            </div>
        </section>

        <section class="login-card">
            <div class="form-title">
                <div>
                    <h2>Masuk SF</h2>
                    <p>Gunakan login code dari admin TAP.</p>
                </div>
                <i class="fas fa-fingerprint"></i>
            </div>

            @if(session('error'))
                <div class="alert-premium">
                    <i class="fas fa-circle-exclamation me-2"></i>{{ session('error') }}
                </div>
            @endif

            <form action="{{ route('mobile.login.post') }}" method="POST">
                @csrf
                <div class="field-group">
                    <label class="field-label" for="login_code">Sales Login Code</label>
                    <div class="field-wrap">
                        <i class="fas fa-id-badge"></i>
                        <input type="text" name="login_code" id="login_code" class="login-input" placeholder="06D5LA" value="{{ old('login_code') }}" required autocomplete="username">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label" for="password">Password</label>
                    <div class="field-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="login-input" placeholder="Password" required autocomplete="current-password">
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Tampilkan password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label ms-2" for="remember">Ingat perangkat ini</label>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    Masuk <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>
        </section>

        <div class="build-note">
            <i class="fas fa-sparkles"></i>
            <span>Inventory Systems 2026</span>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('passwordToggle')?.addEventListener('click', function() {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    </script>
</body>

</html>
