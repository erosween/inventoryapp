@extends('presensi.admin.layout')

@section('title', 'Login Admin Presensi')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card premium-card border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="brand-mark mx-auto mb-3"><i class="fas fa-user-shield"></i></div>
                    <h2 class="font-weight-bold mb-1">Masuk Admin Presensi</h2>
                    <p class="text-muted font-weight-bold mb-0" style="font-size: 0.82rem;">Gunakan akun super admin presensi.</p>
                </div>

                <form method="POST" action="{{ route('admin-presensi.login.post') }}">
                    @csrf
                    <div class="form-group form-group-default mb-3">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="{{ old('username') }}" placeholder="admin_cluster" required autofocus>
                    </div>
                    <div class="form-group form-group-default mb-4">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-round py-3">
                        <i class="fas fa-right-to-bracket mr-2"></i>Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
