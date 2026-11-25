<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

class MobileSFAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        if (!Session::has('mobile_sf_id')) {
            if ($request->isMethodSafe()) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Sesi SURYA telah berakhir. Silakan login kembali.',
                    'redirect' => route('mobile.login', ['timeout' => 1]),
                ], 401);
            }

            return redirect()->route('mobile.login', ['timeout' => 1]);
        }

        $response = $next($request);
        $durationMs = (microtime(true) - $startedAt) * 1000;
        $currentTiming = trim((string) $response->headers->get('Server-Timing', ''));
        $metric = sprintf('surya-mobile;dur=%.1f', $durationMs);
        $response->headers->set('Server-Timing', $currentTiming === '' ? $metric : $currentTiming.', '.$metric);

        if ($durationMs >= 2500) {
            Log::warning('Slow SURYA request', [
                'scope' => 'surya-mobile',
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'method' => $request->method(),
                'duration_ms' => round($durationMs, 1),
                'status' => $response->getStatusCode(),
            ]);
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
