<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monita Leader Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Inter", Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background:
                linear-gradient(135deg, rgba(16, 24, 40, 0.95), rgba(130, 24, 31, 0.92)),
                url("https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1800&q=80");
            background-size: cover;
            background-position: center;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
        }

        .login-shell {
            width: min(1040px, 100%);
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 28px;
            align-items: stretch;
        }

        .hero-panel,
        .login-panel {
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(18px);
        }

        .hero-panel {
            border-radius: 30px;
            padding: 34px;
            min-height: 540px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.06) 1px, transparent 1px);
            background-size: 38px 38px;
            pointer-events: none;
        }

        .hero-panel > * {
            position: relative;
            z-index: 1;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #fff;
            color: #d10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-copy h1 {
            max-width: 620px;
            font-size: 48px;
            line-height: 1.04;
            margin-bottom: 18px;
        }

        .hero-copy p {
            max-width: 560px;
            color: rgba(255, 255, 255, 0.76);
            line-height: 1.7;
            font-size: 15px;
        }

        .metric-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .metric {
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.12);
            padding: 16px;
        }

        .metric small {
            display: block;
            color: rgba(255, 255, 255, 0.62);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .metric strong {
            font-size: 22px;
        }

        .login-panel {
            border-radius: 28px;
            padding: 28px;
            background: rgba(255, 255, 255, 0.94);
            color: #20293a;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-panel h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .login-panel p {
            color: #667085;
            line-height: 1.6;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .alert {
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .alert.error {
            background: #fff1f1;
            color: #c1121f;
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 900;
            color: #475467;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #98a2b3;
        }

        input {
            width: 100%;
            height: 54px;
            border: 1px solid #d0d5dd;
            border-radius: 16px;
            padding: 0 16px 0 46px;
            font-size: 16px;
            font-weight: 800;
            color: #20293a;
            outline: none;
        }

        input:focus {
            border-color: #d10000;
            box-shadow: 0 0 0 4px rgba(209, 0, 0, 0.1);
        }

        .submit-btn,
        .back-link {
            width: 100%;
            min-height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 900;
            text-decoration: none;
        }

        .submit-btn {
            border: 0;
            background: #d10000;
            color: #fff;
            cursor: pointer;
            font-size: 15px;
            box-shadow: 0 14px 30px rgba(209, 0, 0, 0.22);
        }

        .back-link {
            margin-top: 12px;
            color: #475467;
            background: #f2f4f7;
            font-size: 14px;
        }

        @media (max-width: 860px) {
            body {
                align-items: flex-start;
                padding: 18px;
                overflow-y: auto;
            }

            .login-shell {
                grid-template-columns: 1fr;
            }

            .hero-panel {
                min-height: 360px;
                padding: 24px;
            }

            .hero-copy h1 {
                font-size: 34px;
            }

            .metric-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="hero-panel">
            <div class="brand">
                <div class="brand-icon"><i class="fas fa-chart-line"></i></div>
                <span>MONITA LEADER</span>
            </div>

            <div class="hero-copy">
                <h1>Command center outlet Dumai, Rohil, Bengkalis.</h1>
                <p>Dashboard internal untuk membaca performa wilayah, menemukan outlet prioritas, dan menyusun call to action leader tanpa bercampur dengan sistem inventory.</p>
            </div>

            <div class="metric-row">
                <div class="metric">
                    <small>Coverage</small>
                    <strong>3 Area</strong>
                </div>
                <div class="metric">
                    <small>Mode</small>
                    <strong>Leader</strong>
                </div>
                <div class="metric">
                    <small>Search</small>
                    <strong>Public</strong>
                </div>
            </div>
        </section>

        <section class="login-panel">
            <h2>Login Monita</h2>
            <p>Masukkan kode akses khusus leader untuk membuka analisa wilayah. Pencarian outlet tetap bisa dipakai tanpa login.</p>

            @if(session('error'))
                <div class="alert error">{{ session('error') }}</div>
            @endif

            <form action="{{ route('monita.leader.login.post') }}" method="POST">
                @csrf
                <div class="field">
                    <label for="access_code">Kode akses</label>
                    <div class="input-wrap">
                        <i class="fas fa-key"></i>
                        <input id="access_code" name="access_code" type="password" value="{{ old('access_code') }}" autocomplete="current-password" autofocus>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-right-to-bracket"></i>
                    Buka Dashboard
                </button>
                <a href="{{ url('/monitadumai') }}" class="back-link">
                    <i class="fas fa-magnifying-glass"></i>
                    Kembali ke Search Outlet
                </a>
            </form>
        </section>
    </main>
</body>
</html>
