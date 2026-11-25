<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SuryaWebAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $username = strtolower(trim((string) optional(Auth::user())->username));
        $allowed = $request->session()->has('monita_leader_auth')
            || Auth::user()?->isSuperAdmin()
            || in_array($username, ['gm', 'gm_cluster', 'branch_dumai', 'admin_cluster', 'cluster_dumai', 'cluster_rohil'], true)
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

            $response = redirect()->route('/');
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        }

        $response = $next($request);
        $this->addPerformanceHeaders($request, $response, $startedAt, 'surya-web');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    private function addPerformanceHeaders(Request $request, Response $response, float $startedAt, string $scope): void
    {
        $durationMs = (microtime(true) - $startedAt) * 1000;
        $current = trim((string) $response->headers->get('Server-Timing', ''));
        $metric = sprintf('%s;dur=%.1f', $scope, $durationMs);
        $response->headers->set('Server-Timing', $current === '' ? $metric : $current.', '.$metric);

        if ($durationMs >= 2500) {
            Log::warning('Slow SURYA request', [
                'scope' => $scope,
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'method' => $request->method(),
                'duration_ms' => round($durationMs, 1),
                'status' => $response->getStatusCode(),
            ]);
        }
    }
}
