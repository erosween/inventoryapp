<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class EmployeeAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Session::has('attendance_employee_id')) {
            return redirect()->route('presensi.index');
        }

        return view('presensi.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'employee_code' => ['required', 'string', 'max:40'],
            'password' => ['required', 'string'],
        ]);

        $employee = AttendanceEmployee::where('employee_code', strtoupper($request->employee_code))
            ->where('status', 'active')
            ->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return back()->withInput()->with('error', 'NIP atau password presensi belum sesuai.');
        }

        $request->session()->forget([
            'attendance_employee_id',
            'attendance_employee_name',
            'attendance_employee_code',
            'attendance_employee_role',
            'attendance_show_pwa_prompt',
        ]);
        $request->session()->regenerate();

        $showPwaPrompt = !$employee->pwa_prompted_at;
        if ($showPwaPrompt) {
            $employee->forceFill(['pwa_prompted_at' => now()])->save();
        }

        Session::put('attendance_employee_id', $employee->id);
        Session::put('attendance_employee_name', $employee->name);
        Session::put('attendance_employee_code', $employee->employee_code);
        Session::put('attendance_employee_role', $employee->role);
        Session::put('attendance_show_pwa_prompt', $showPwaPrompt);

        return redirect()->route('presensi.index');
    }

    public function logout(Request $request)
    {
        Session::forget([
            'attendance_employee_id',
            'attendance_employee_name',
            'attendance_employee_code',
            'attendance_employee_role',
            'attendance_show_pwa_prompt',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('presensi.login');
    }
}
