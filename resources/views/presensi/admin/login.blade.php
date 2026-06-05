@extends('presensi.admin.layout')

@section('title', 'Login Admin Presensi')

@section('content')
<main class="admin-login-screen">
    <section class="admin-login-visual">
        <div class="login-brand-lockup">
            <div class="login-logo"><i class="fas fa-check-double"></i></div>
            <div>
                <h1>PresensiKu</h1>
                <p>Aplikasi Presensi Karyawan</p>
            </div>
        </div>

        <div class="login-copy">
            <h2>Presensi Mudah,<br>Kerja Makin <span>Produktif</span></h2>
            <p>Kelola kehadiran karyawan secara real-time, akurat, aman, dan efisien dalam satu sistem.</p>
        </div>

        <div class="login-feature-list">
            <div>
                <i class="fas fa-users-viewfinder"></i>
                <span><strong>Monitoring Kehadiran Real-time</strong><em>Pantau kehadiran karyawan secara langsung</em></span>
            </div>
            <div>
                <i class="fas fa-chart-simple"></i>
                <span><strong>Laporan Lengkap & Akurat</strong><em>Data presensi lengkap dalam berbagai format</em></span>
            </div>
            <div>
                <i class="fas fa-shield-halved"></i>
                <span><strong>Aman & Terpercaya</strong><em>Sistem terenkripsi dan perlindungan data</em></span>
            </div>
            <div>
                <i class="fas fa-mobile-screen-button"></i>
                <span><strong>Akses di Mana Saja</strong><em>Akses mudah melalui web dan mobile</em></span>
            </div>
        </div>

        <div class="login-device-preview">
            <img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=900&q=80" alt="Dashboard presensi di laptop">
        </div>

        <div class="login-copyright">© 2026 PresensiKu. MSP Attendance System.</div>
    </section>

    <section class="admin-login-panel">
        <div class="language-pill"><i class="fas fa-globe"></i> Bahasa Indonesia <i class="fas fa-chevron-down"></i></div>

        <div class="admin-login-card">
            <div class="login-card-logo"><i class="fas fa-check-double"></i></div>
            <h2>Selamat Datang Kembali!</h2>
            <p>Masuk ke akun admin untuk melanjutkan</p>

            <form method="POST" action="{{ route('admin-presensi.login.post') }}">
                @csrf
                <label class="login-field-label">Email atau Username</label>
                <div class="login-field">
                    <i class="fas fa-envelope"></i>
                    <input type="text" name="username" value="{{ old('username') }}" placeholder="Masukkan email atau username" required autofocus>
                </div>

                <label class="login-field-label">Password</label>
                <div class="login-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="adminPassword" placeholder="Masukkan password" required>
                    <button type="button" class="login-eye" id="adminPasswordToggle" aria-label="Tampilkan password"><i class="fas fa-eye"></i></button>
                </div>

                <div class="login-options-row">
                    <label><input type="checkbox" checked> <span>Ingat saya</span></label>
                    <span>Lupa password?</span>
                </div>

                <button type="submit" class="login-submit-btn">Masuk</button>
            </form>

            <div class="admin-contact">Belum punya akun? <strong>Hubungi Admin HR</strong></div>
        </div>
    </section>
</main>
@endsection

@push('styles')
<style>
    .admin-login-body {
        min-height: 100vh;
        overflow-x: hidden;
        background: #fbfaff;
    }

    .admin-login-screen {
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(420px, 47vw) minmax(0, 1fr);
        background:
            radial-gradient(circle at 91% 26%, rgba(108,63,242,0.12), transparent 20%),
            linear-gradient(90deg, #2d177d 0%, #fbfaff 48%, #ffffff 100%);
    }

    .admin-login-visual {
        min-height: 100vh;
        padding: 56px 70px 34px;
        color: #fff;
        background:
            linear-gradient(145deg, rgba(30,17,105,0.95) 0%, rgba(73,42,180,0.91) 100%),
            url("https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1600&q=80");
        background-size: cover;
        background-position: center;
        border-radius: 0 42% 42% 0 / 0 100% 100% 0;
        position: relative;
        overflow: hidden;
    }

    .admin-login-visual::after {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 72% 82%, rgba(255,255,255,0.2), transparent 18%);
        pointer-events: none;
    }

    .login-brand-lockup,
    .login-copy,
    .login-feature-list,
    .login-device-preview,
    .login-copyright {
        position: relative;
        z-index: 1;
    }

    .login-brand-lockup {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .login-logo,
    .login-card-logo {
        width: 64px;
        height: 64px;
        border-radius: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #9c7bff 0%, #5532dc 100%);
        box-shadow: 0 18px 42px rgba(45,23,125,0.34);
        font-size: 1.35rem;
    }

    .login-brand-lockup h1 {
        margin: 0;
        font-size: 1.32rem;
        font-weight: 900;
    }

    .login-brand-lockup p {
        margin: 4px 0 0;
        color: rgba(255,255,255,0.82);
        font-size: 0.76rem;
        font-weight: 700;
    }

    .login-copy {
        max-width: 520px;
        margin-top: 88px;
    }

    .login-copy h2 {
        margin: 0;
        font-size: 2.42rem;
        line-height: 1.2;
        font-weight: 900;
    }

    .login-copy h2 span {
        color: #9b7dff;
    }

    .login-copy p {
        max-width: 460px;
        margin: 26px 0 0;
        color: rgba(255,255,255,0.85);
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.65;
    }

    .login-feature-list {
        display: grid;
        gap: 18px;
        margin-top: 54px;
    }

    .login-feature-list div {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .login-feature-list i {
        width: 52px;
        height: 52px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: rgba(139,98,255,0.82);
        flex: 0 0 auto;
    }

    .login-feature-list strong,
    .login-feature-list em {
        display: block;
    }

    .login-feature-list strong {
        font-size: 0.82rem;
        font-weight: 900;
    }

    .login-feature-list em {
        margin-top: 6px;
        color: rgba(255,255,255,0.78);
        font-size: 0.68rem;
        font-style: normal;
        font-weight: 700;
    }

    .login-device-preview {
        width: min(430px, 78%);
        height: 150px;
        border: 1px solid rgba(255,255,255,0.28);
        border-radius: 22px;
        margin-top: 42px;
        overflow: hidden;
        box-shadow: 0 22px 52px rgba(10,7,42,0.34);
    }

    .login-device-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: saturate(0.8) hue-rotate(12deg);
    }

    .login-copyright {
        position: absolute;
        left: 70px;
        bottom: 28px;
        color: rgba(255,255,255,0.74);
        font-size: 0.7rem;
        font-weight: 700;
    }

    .admin-login-panel {
        min-height: 100vh;
        padding: 52px 7vw;
        display: grid;
        align-content: center;
        justify-items: center;
        position: relative;
    }

    .language-pill {
        position: absolute;
        top: 52px;
        right: 7vw;
        min-height: 48px;
        border: 1px solid #edf0f8;
        border-radius: 16px;
        padding: 0 18px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #20264a;
        background: rgba(255,255,255,0.92);
        box-shadow: 0 14px 36px rgba(31,35,85,0.07);
        font-size: 0.76rem;
        font-weight: 900;
    }

    .admin-login-card {
        width: min(100%, 560px);
        min-height: 640px;
        border: 1px solid rgba(233,236,246,0.96);
        border-radius: 28px;
        padding: 56px 58px;
        background: rgba(255,255,255,0.94);
        box-shadow: 0 26px 70px rgba(31,35,85,0.12);
        text-align: center;
    }

    .login-card-logo {
        margin-bottom: 26px;
    }

    .admin-login-card h2 {
        margin: 0;
        color: #111635;
        font-size: 1.38rem;
        font-weight: 900;
    }

    .admin-login-card > p {
        margin: 12px 0 38px;
        color: #66708f;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .login-field-label {
        display: block;
        margin: 0 0 10px;
        color: #111635;
        font-size: 0.74rem;
        font-weight: 900;
        text-align: left;
    }

    .login-field {
        min-height: 58px;
        border: 1px solid #dfe4f1;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0 16px;
        margin-bottom: 26px;
        background: #fff;
    }

    .login-field i {
        color: #4d5578;
    }

    .login-field input {
        width: 100%;
        border: 0;
        outline: 0;
        color: #111635;
        font-size: 0.86rem;
        font-weight: 800;
    }

    .login-eye {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 10px;
        color: #4d5578;
        background: #f6f7fc;
        flex: 0 0 auto;
    }

    .login-options-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin: 0 0 28px;
        color: var(--primary);
        font-size: 0.74rem;
        font-weight: 900;
    }

    .login-options-row label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #4d5578;
        margin: 0;
    }

    .login-options-row input {
        accent-color: var(--primary);
    }

    .login-submit-btn {
        width: 100%;
        min-height: 60px;
        border: 0;
        border-radius: 12px;
        color: #fff;
        background: linear-gradient(135deg, #8b4dff 0%, #5532dc 100%);
        box-shadow: 0 16px 34px rgba(108,63,242,0.24);
        font-weight: 900;
    }

    .admin-contact {
        margin-top: 30px;
        color: #66708f;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .admin-contact strong {
        color: var(--primary);
    }

    @media (min-width: 992px) and (max-height: 820px) {
        .admin-login-visual {
            padding: 34px 58px 28px;
        }

        .login-copy {
            margin-top: 54px;
        }

        .login-copy h2 {
            font-size: 2.08rem;
        }

        .login-copy p {
            margin-top: 18px;
            font-size: 0.9rem;
        }

        .login-feature-list {
            gap: 12px;
            margin-top: 32px;
        }

        .login-feature-list i {
            width: 44px;
            height: 44px;
        }

        .login-device-preview {
            display: none;
        }

        .login-copyright {
            left: 58px;
            bottom: 20px;
        }

        .admin-login-panel {
            padding-top: 34px;
            padding-bottom: 34px;
        }

        .language-pill {
            top: 28px;
        }

        .admin-login-card {
            min-height: auto;
            padding: 38px 52px;
        }

        .login-card-logo {
            width: 56px;
            height: 56px;
            margin-bottom: 18px;
        }

        .admin-login-card > p {
            margin-bottom: 28px;
        }

        .login-field {
            min-height: 52px;
            margin-bottom: 20px;
        }

        .login-submit-btn {
            min-height: 54px;
        }
    }

    @media (max-width: 991.98px) {
        .admin-login-screen {
            grid-template-columns: 1fr;
        }

        .admin-login-visual {
            min-height: auto;
            border-radius: 0 0 32px 32px;
            padding: 28px 22px 34px;
        }

        .login-copy {
            margin-top: 40px;
        }

        .login-copy h2 {
            font-size: 1.85rem;
        }

        .login-feature-list,
        .login-device-preview,
        .login-copyright {
            display: none;
        }

        .admin-login-panel {
            min-height: auto;
            padding: 28px 18px 42px;
        }

        .language-pill {
            position: static;
            margin: 0 0 18px auto;
            justify-self: end;
        }

        .admin-login-card {
            min-height: auto;
            padding: 34px 22px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.getElementById('adminPasswordToggle')?.addEventListener('click', function() {
        const input = document.getElementById('adminPassword');
        const icon = this.querySelector('i');
        const hidden = input.type === 'password';
        input.type = hidden ? 'text' : 'password';
        icon.className = hidden ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
</script>
@endpush
