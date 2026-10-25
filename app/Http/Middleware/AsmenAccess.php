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
            'sisastock.index',
            'sisastock.data',
            'surya.dashboard',
            'surya.inventory-dashboard',
            'surya.outlet-detail',
            'surya.outlet-detail.data',
            'surya.outlet-detail.export',
            'surya.visits.export',
            'surya.visits.report',
            'surya.field-reports',
            'logout',
        ];

        $allowedPaths = [
            'home',
            'stock',
            'stocktap',
            'stocksf',
            'sisastock',
            'sisastock/data',
            'chart/sales',
            'surya',
            'surya/detail-outlet',
            'surya/detail-outlet/data',
            'surya/detail-outlet/export',
            'surya/export-visits',
            'surya/visits',
            'surya/field-reports',
            'logout',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes, true)
            || in_array($request->path(), $allowedPaths, true)) {
            return $next($request);
        }

        abort(403, 'Akses ASMEN hanya tersedia untuk Dashboard dan Stock Gudang.');
    }
}
