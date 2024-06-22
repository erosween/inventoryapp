<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('/index');
    }


    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            // Jika autentikasi berhasil, simpan idtap ke dalam session
            session(['idtap' => auth()->user()->idtap]);

            // Cek nilai idtap dan arahkan pengguna ke halaman yang sesuai
            if (auth()->user()->idtap === 'SB DUMAI') {
                return redirect('/homenocan');
            } elseif (auth()->user()->idtap === 'SB SIDEMPUAN') {
                return redirect('/homenocan');
            } else {
                return redirect('/home');
            }
        } else {
            // Jika autentikasi gagal, redirect ke halaman login dengan error message
            return redirect()->back()->with('error', 'Username atau Password Salah!');
        }
    }


    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
