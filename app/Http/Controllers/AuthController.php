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

        session(['idtap' => auth()->user()->idtap]);

        if (auth()->user()->idtap === 'SB DUMAI') {
            return redirect('/homenocan');
        } elseif (auth()->user()->idtap === 'SB SIDEMPUAN') {
            return redirect('/homenocan');
        } else {
            return redirect('/home');
        }

    } else {
        // ⬇️ INI KUNCINYA
        return redirect()
            ->back()
            ->withInput($request->only('username')) // ⬅️ SIMPAN USERNAME
            ->with('error', 'Username atau Password Salah!');
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
