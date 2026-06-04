@extends('presensi.layout')

@section('title', 'Login Presensi')

@section('content')
<main class="login-screen">
    <section class="login-brand">
        <div class="brand-lockup">
            <div class="brand-icon"><i class="fas fa-check-double"></i></div>
            <div>
                <h1>PresensiKu</h1>
                <p>Aplikasi Presensi Karyawan</p>
            </div>
        </div>
        <h2>Selamat Datang</h2>
        <p>Silakan masuk untuk melanjutkan presensi karyawan MSP.</p>
    </section>

    <section class="presence-card p-4 login-panel">
        <form action="{{ route('presensi.login.post') }}" method="POST">
            @csrf
            <label class="field-label" for="employee_code">NIP / Kode Karyawan</label>
            <div class="field-wrap mb-3">
                <i class="fas fa-id-badge"></i>
                <input type="text" name="employee_code" id="employee_code" value="{{ old('employee_code') }}" class="presence-input" placeholder="EMP001" required autofocus>
            </div>

            <label class="field-label" for="password">Password</label>
            <div class="field-wrap mb-3">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" class="presence-input" placeholder="Password" required>
                <button type="button" class="password-toggle" id="passwordToggle" aria-label="Tampilkan password"><i class="fas fa-eye"></i></button>
            </div>

            <div class="login-options mb-4">
                <label>
                    <input type="checkbox" checked>
                    <span>Ingat saya</span>
                </label>
                <span>Lupa password?</span>
            </div>

            <button type="submit" class="primary-btn w-100">Masuk</button>
        </form>

        <a href="{{ route('mobile.login') }}" class="sales-link">
            Masuk sebagai user jualan
        </a>
    </section>

    <section class="security-card gradient-card p-4">
        <i class="fas fa-shield-halved"></i>
        <strong>Aman - Akurat - Terpercaya</strong>
        <span>Validasi wajah karyawan dilakukan dengan data enrollment awal.</span>
    </section>
</main>
@endsection

@push('styles')
<style>
    .login-screen {
        min-height: 100vh;
        padding: 28px 0 40px;
        display: grid;
        align-content: start;
        gap: 18px;
    }

    .login-brand {
        padding: 4px 4px 0;
    }

    .login-brand h1 {
        margin: 0;
        color: var(--ink);
        font-size: 1.12rem;
        font-weight: 900;
    }

    .login-brand p {
        margin: 2px 0 0;
        color: var(--muted);
        font-size: 0.72rem;
        font-weight: 800;
    }

    .login-brand h2 {
        margin: 44px 0 8px;
        color: var(--ink);
        font-size: 1.65rem;
        font-weight: 900;
        line-height: 1.18;
    }

    .login-brand > p:last-child {
        max-width: 290px;
        line-height: 1.55;
    }

    .field-wrap {
        position: relative;
    }

    .field-wrap > i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #9aa1bb;
        font-size: 0.9rem;
        z-index: 1;
    }

    .field-wrap .presence-input {
        padding-left: 44px;
        padding-right: 48px;
    }

    .password-toggle {
        position: absolute;
        right: 8px;
        top: 7px;
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 13px;
        background: #f2f4fb;
        color: #737997;
    }

    .login-options {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: var(--primary);
        font-size: 0.68rem;
        font-weight: 900;
    }

    .login-options label {
        color: var(--ink);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .login-options input {
        accent-color: var(--primary);
    }

    .sales-link {
        min-height: 48px;
        margin-top: 14px;
        color: var(--primary);
        text-decoration: none;
        font-size: 0.72rem;
        font-weight: 900;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .security-card {
        text-align: center;
    }

    .security-card i {
        width: 54px;
        height: 54px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        background: rgba(255,255,255,0.16);
    }

    .security-card strong,
    .security-card span {
        display: block;
    }

    .security-card strong {
        font-size: 0.92rem;
        font-weight: 900;
    }

    .security-card span {
        margin-top: 6px;
        color: rgba(255,255,255,0.76);
        font-size: 0.66rem;
        font-weight: 800;
        line-height: 1.5;
    }
</style>
@endpush

@push('scripts')
<script>
    document.getElementById('passwordToggle')?.addEventListener('click', function() {
        const input = document.getElementById('password');
        const icon = this.querySelector('i');
        const hidden = input.type === 'password';
        input.type = hidden ? 'text' : 'password';
        icon.className = hidden ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
</script>
@endpush
