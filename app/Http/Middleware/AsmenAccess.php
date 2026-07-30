<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AsmenAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !Str::startsWith(Str::lower($user->username), 'asmen_')) {
            return $next($request);
        }

        $allowedRoutes = [
            'home',
            'logout',
        ];

        $allowedPaths = [
            'home',
            'stock',
            'stocktap',
            'stocksf',
            'chart/sales',
            'logout',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes, true)
            || in_array($request->path(), $allowedPaths, true)) {
            return $next($request);
        }

        abort(403, 'Akses ASMEN hanya tersedia untuk Dashboard dan Stock Gudang.');
    }
}
