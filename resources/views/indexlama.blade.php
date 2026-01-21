<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>INVENTORY MSP – Secure Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/img/MSP5.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Raleway', system-ui, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(900px at 15% 20%, rgba(255, 255, 255, .18), transparent 40%),
                radial-gradient(800px at 85% 80%, rgba(255, 255, 255, .15), transparent 40%),
                linear-gradient(135deg, #c7c5f4, #7a70d6);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* BLUR BACKDROP */
        .bg-blur {
            position: fixed;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(130px);
            z-index: 0;
        }

        .bg-1 {
            background: rgba(99, 102, 241, .35);
            top: -150px;
            left: -150px;
        }

        .bg-2 {
            background: rgba(168, 139, 250, .35);
            bottom: -150px;
            right: -150px;
        }

        /* LAYOUT */
        .auth-wrapper {
            width: 100%;
            max-width: 1200px;
            padding: 40px;
            display: flex;
            align-items: center;
            z-index: 1;
        }

        /* LEFT PITCH */
        .auth-left {
            flex: 1;
            color: #fff;
            padding-right: 80px;
        }

        .auth-left h1 {
            font-size: 52px;
            line-height: 1.1;
            margin-bottom: 20px;
            font-weight: 800;
        }

        .auth-left p {
            font-size: 16px;
            opacity: .9;
            max-width: 420px;
            line-height: 1.6;
        }

        .auth-left ul {
            margin-top: 30px;
            padding: 0;
            list-style: none;
        }

        .auth-left li {
            margin-bottom: 14px;
            font-size: 14px;
            opacity: .9;
        }

        .auth-left i {
            margin-right: 10px;
            color: #c7d2fe;
        }

        /* LOGIN CARD */
        .auth-right {
            width: 380px;
        }

        .login-card {
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(12px);
            border-radius: 22px;
            padding: 36px 32px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, .18);
        }

        .login-title {
            text-align: center;
            margin-bottom: 8px;
            font-size: 22px;
            font-weight: 800;
            color: #4f46e5;
        }

        .login-subtitle {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        /* FORM */
        .login-field {
            position: relative;
            margin-bottom: 24px;
        }

        .login-field i {
            position: absolute;
            top: 14px;
            left: 12px;
            color: #6366f1;
        }

        .login-input {
            width: 100%;
            padding: 14px 14px 14px 38px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            font-size: 14px;
            outline: none;
            transition: .2s;
        }

        .login-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .2);
        }

        .login-btn {
            width: 100%;
            border: none;
            background: linear-gradient(135deg, #6366f1, #7c3aed);
            color: #fff;
            padding: 14px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-btn i {
            margin-left: 10px;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, .35);
        }

        .alert {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .login-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .auth-wrapper {
                flex-direction: column;
                text-align: center;
            }

            .auth-left {
                padding: 0 0 40px;
            }

            .auth-left h1 {
                font-size: 38px;
            }

            .auth-right {
                width: 100%;
                max-width: 380px;
            }
        }

        /* ===============================
   NEXT LEVEL POLISH
================================ */

        /* Smooth global animation */
        * {
            transition: background .25s ease, box-shadow .25s ease, transform .2s ease;
        }

        /* Card entrance */
        .login-card {
            animation: floatIn .7s ease forwards;
        }

        @keyframes floatIn {
            from {
                opacity: 0;
                transform: translateY(25px) scale(.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Input premium focus */
        .login-input:focus {
            background: #ffffff;
        }

        /* Button micro interaction */
        .login-btn:active {
            transform: scale(.98);
        }

        /* Soft card hover */
        .login-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 40px 80px rgba(0, 0, 0, .25);
        }

        /* Background gradient drift */
        body {
            background-size: 200% 200%;
            animation: gradientMove 14s ease infinite;
        }

        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }
    </style>
</head>

<body>

    <div class="bg-blur bg-1"></div>
    <div class="bg-blur bg-2"></div>

    <div class="auth-wrapper">

        <!-- LEFT BRAND -->
        <div class="auth-left">
            <h1>Inventory<br>Management<br>Made Simple</h1>
            <p>
                Secure, scalable, and real-time inventory system
                designed for modern distribution teams.
            </p>

            <ul>
                <li><i class="fas fa-check-circle"></i> Real-time stock monitoring</li>
                <li><i class="fas fa-check-circle"></i> Multi-TAP & SF management</li>
                <li><i class="fas fa-check-circle"></i> Secure role-based access</li>
            </ul>
        </div>

        <!-- LOGIN -->
        <div class="auth-right">
            <div class="login-card">

                <div class="login-title">INVENTORY MSP</div>
                <div class="login-subtitle">Secure Inventory Management System</div>

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="login-field">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="login-input" placeholder="Username"
                            value="{{ old('username') }}" required>
                    </div>

                    <div class="login-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="login-input" placeholder="Password" required>
                    </div>

                    @if (session('error'))
                        <div class="alert">{{ session('error') }}</div>
                    @endif

                    <button class="login-btn">
                        LOG IN NOW <i class="fas fa-arrow-right"></i>
                    </button>
                    <div style="margin-top:14px; text-align:center; font-size:12px; color:#6b7280;">
                        <i class="fas fa-lock"></i> Secure Access • Encrypted Session
                    </div>

                </form>

                <div class="login-footer">
                    © {{ date('Y') }} INVENTORY MSP • Secure Access
                </div>

            </div>
        </div>

    </div>

</body>

</html>
