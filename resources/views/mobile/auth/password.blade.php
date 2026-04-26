@extends('layout.mobile_layout')

@section('title', 'Ganti Password')

@section('content')
<div class="animate-up">
    <div class="mobile-card shadow-sm border-0">
        <div class="section-title mb-4 text-center">
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                <i class="fas fa-lock text-primary fs-3"></i>
            </div>
            <h5 class="fw-800 text-primary mb-1">Update Password</h5>
            <p class="small text-muted">Amankan akun Sales Force Anda</p>
        </div>

        <form action="{{ route('mobile.password.update') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="form-label small fw-800 text-muted">PASSWORD SAAT INI</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-2 border-end-0" style="border-radius: 15px 0 0 15px;">
                        <i class="fas fa-key text-muted"></i>
                    </span>
                    <input type="password" name="current_password" class="form-control border-start-0" placeholder="••••••••" style="border-radius: 0 15px 15px 0;" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-800 text-muted">PASSWORD BARU</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-2 border-end-0" style="border-radius: 15px 0 0 15px;">
                        <i class="fas fa-shield-alt text-muted"></i>
                    </span>
                    <input type="password" name="new_password" class="form-control border-start-0" placeholder="Min. 3 karakter" style="border-radius: 0 15px 15px 0;" required>
                </div>
            </div>

            <div class="mb-5">
                <label class="form-label small fw-800 text-muted">KONFIRMASI PASSWORD BARU</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-2 border-end-0" style="border-radius: 15px 0 0 15px;">
                        <i class="fas fa-check-double text-muted"></i>
                    </span>
                    <input type="password" name="new_password_confirmation" class="form-control border-start-0" placeholder="Ulangi password baru" style="border-radius: 0 15px 15px 0;" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mb-3">
                <i class="fas fa-save me-2"></i> UPDATE PASSWORD
            </button>
            
            <a href="{{ route('mobile.index') }}" class="btn btn-light w-100 py-3 rounded-4 fw-bold text-muted border-0">
                BATAL
            </a>
        </form>
    </div>
</div>
@endsection
