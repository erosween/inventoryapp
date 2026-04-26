<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class MobileAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Session::has('mobile_sf_id')) {
            return redirect()->route('mobile.index');
        }
        $rememberedCode = request()->cookie('remember_sf_code');
        return view('mobile.auth.login', compact('rememberedCode'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'login_code' => 'required',
            'password' => 'required',
        ]);

        $sf = DB::table('idsf')
            ->where('login_code', $request->login_code)
            ->first();

        if ($sf && Hash::check($request->password, $sf->password)) {
            // Set custom session for mobile SF
            Session::put('mobile_sf_id', $sf->idsf);
            Session::put('mobile_sf_name', $sf->namasf);
            Session::put('idtap', $sf->idtap);

            $response = redirect()->route('mobile.index');
            
            if ($request->has('remember')) {
                // Simpan login_code di cookie selama 30 hari (43200 menit)
                $response->withCookie(cookie('remember_sf_code', $request->login_code, 43200));
            }

            return $response;
        }

        return back()->with('error', 'Login Code atau Password salah!');
    }

    public function logout()
    {
        Session::forget(['mobile_sf_id', 'mobile_sf_name', 'idtap']);
        return redirect()->route('mobile.login');
    }

    public function showChangePasswordForm()
    {
        return view('mobile.auth.password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:3|confirmed',
        ]);

        $sf = DB::table('idsf')
            ->where('idsf', Session::get('mobile_sf_id'))
            ->first();

        if (!Hash::check($request->current_password, $sf->password)) {
            return back()->with('error', 'Password lama salah!');
        }

        DB::table('idsf')
            ->where('idsf', $sf->idsf)
            ->update([
                'password' => Hash::make($request->new_password)
            ]);

        return redirect()->route('mobile.index')->with('success', 'Password berhasil diubah!');
    }
}
