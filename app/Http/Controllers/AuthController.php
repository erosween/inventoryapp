<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\AuditLogger;

// Login dan logout Web Inventory. Autentikasi SF untuk aplikasi mobile ditangani MobileAuthController.
class AuthController extends Controller
{
    public function showLoginForm()
    {
        return response()->view('/index')->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }


   public function login(Request $request)
{
    $throttleKey = \Illuminate\Support\Str::lower($request->input('username')) . '|' . $request->ip();

    if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
        $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
        return redirect()->back()
            ->withInput($request->only('username'))
            ->with('error', "Terlalu banyak percobaan. Akun diblokir sementara. Silakan coba lagi dalam $seconds detik.");
    }

    $credentials = $request->only('username', 'password');

    if (Auth::attempt($credentials)) {
        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        session(['idtap' => auth()->user()->idtap]);
        $request->session()->regenerate();

        AuditLogger::log('LOGIN', 'Authentication', auth()->id(), null, [
            'idtap' => auth()->user()->idtap,
            'level' => auth()->user()->level,
        ]);

        $username = strtolower(trim((string) auth()->user()->username));
        if (Auth::user()->isSuperAdmin() || in_array($username, ['gm_cluster', 'branch_dumai', 'cluster_dumai', 'cluster_rohil'], true)
            || str_starts_with($username, 'asmen_')) {
            return redirect()->intended(route('surya.inventory-dashboard'));
        }

        if (auth()->user()->idtap === 'SB DUMAI') {
            return redirect('/homenocan');
        } elseif (auth()->user()->idtap === 'SB SIDEMPUAN') {
            return redirect('/homenocan');
        } else {
            return redirect('/home');
        }

    } else {
        // ⬇️ INI KUNCINYA (Mencatat kegagalan)
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60); // 60 detik (1 menit) penalti setelah 5 batas

        return redirect()
            ->back()
            ->withInput($request->only('username')) // ⬅️ SIMPAN USERNAME
            ->with('error', 'Username atau Password Salah!');
    }
}



    public function logout(Request $request)
    {
        $guard = Auth::guard('web');
        if ($guard->check()) {
            AuditLogger::log('LOGOUT', 'Authentication', $guard->id(), [
                'idtap' => $guard->user()->idtap,
            ]);
        }

        $guard->logout();
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = redirect()->route('/');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
