<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login SF - Inventory MSP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #EC2028;
            /* Telkomsel Red */
            --primary-light: #FF4B53;
            --bg-dark: #f8fafc;
            --card-glass: #ffffff;
            /* Solid white card */
            --text-main: #212121;
            --text-muted: #757575;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            /* Telkomsel Red Gradient Background */
            background: linear-gradient(135deg, #EC2028 0%, #B00B11 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 24px;
            overflow: hidden;
            color: var(--text-main);
        }

        .glass-login-card {
            background: var(--card-glass);
            width: 100%;
            max-width: 400px;
            padding: 48px 36px;
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-section img {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            padding: 4px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .logo-section h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
            color: var(--text-main);
        }

        .logo-section p {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 6px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: block;
            padding-left: 4px;
        }

        .glass-input {
            width: 100%;
            background: #f1f5f9;
            border: 1px solid transparent;
            border-radius: 16px;
            padding: 16px 20px;
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .glass-input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }

        .glass-input:focus {
            outline: none;
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(236, 32, 40, 0.1);
        }

        .btn-premium {
            background: var(--primary);
            color: white;
            border: none;
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            font-weight: 800;
            font-size: 1rem;
            box-shadow: 0 8px 20px rgba(236, 32, 40, 0.3);
            margin-top: 10px;
            transition: all 0.2s;
        }

        .btn-premium:active {
            transform: scale(0.98);
        }

        .alert-premium {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #ef4444;
            padding: 14px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 24px;
        }
    </style>
</head>

<body>

    <div class="glass-login-card">
        <div class="logo-section">
            <img src="/assets/img/MSP5.png" alt="Logo">
            <h1>MSP Connect</h1>
            <p>Integrated Field Sales System</p>
        </div>

        @if(session('error'))
            <div class="alert-premium">
                <i class="fas fa-shield-exclamation me-2"></i> {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('mobile.login.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Sales Login Code</label>
                <input type="text" name="login_code" class="glass-input" placeholder="SF-XXXX" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="glass-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-premium">
                LOG IN <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="text-center mt-5">
            <p style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; margin: 0;">&copy; 2026 Inventory
                Systems</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>