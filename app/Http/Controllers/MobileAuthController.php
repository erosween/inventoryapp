<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class MobileAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Session::has('mobile_sf_id')) {
            return redirect()->route('mobile.index');
        }
        return view('mobile.auth.login');
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

            if ($request->has('remember')) {
                // Store idsf in cookie for 30 days
                Cookie::queue('mobile_remember_id', $sf->idsf, 43200);
            }

            return redirect()->route('mobile.index');
        }

        return back()->withInput()->with('error', 'Login Code atau Password salah!');
    }

    public function logout()
    {
        Session::forget(['mobile_sf_id', 'mobile_sf_name', 'idtap']);
        Cookie::queue(Cookie::forget('mobile_remember_id'));
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
