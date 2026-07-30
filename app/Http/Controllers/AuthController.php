<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\AuditLogger;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('/index');
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
        if (Auth::check()) {
            AuditLogger::log('LOGOUT', 'Authentication', auth()->id(), [
                'idtap' => auth()->user()->idtap,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
