<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EmployeeAttendanceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Session::has('attendance_employee_id')) {
            return redirect()->route('presensi.login')->with('error', 'Silakan login presensi terlebih dahulu.');
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
