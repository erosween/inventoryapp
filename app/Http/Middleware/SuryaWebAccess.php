<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuryaWebAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = strtolower(trim((string) optional(Auth::user())->username));
        $allowed = $request->session()->has('monita_leader_auth')
            || in_array($username, ['gm', 'gm_cluster', 'branch_dumai', 'admin_super', 'admin_cluster', 'cluster_dumai', 'cluster_rohil'], true)
            || str_starts_with($username, 'asmen_');

        if (! $allowed) {
            if ($request->isMethodSafe()) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Sesi SURYA telah berakhir. Silakan login kembali.',
                    'redirect' => route('/'),
                ], 401);
            }

            return redirect()->route('/');
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
