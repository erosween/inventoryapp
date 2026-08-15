<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureInventoryRequestIsIdempotent
{
    /** Controller actions which can change a materialized stock balance. */
    private const PROTECTED_ACTIONS = [
        'StockAdjustmentController@update',
        'MasukController@masuk',
        'InjectController@bulkDelete', 'InjectController@delete',
        'FormInjectsegelController@injectProses', 'FormInjectbyuController@injectProses',
        'KeluarController@cancel', 'FormKeluartapController@proseskeluartapform',
        'InputDOController@masukproses', 'InputDOController@delete', 'InputDOController@update',
        'BOController@proseskeluarboform', 'BOController@delete', 'BOController@update',
        'VrusakController@vrusakproses', 'VrusakController@delete', 'VrusakController@update',
        'SfmasukController@masuksfproses', 'SfmasukController@updateSfMasuk',
        'SfmasukController@bulkDelete', 'SfmasukController@delete',
        'SfkeluarController@keluarsfproses', 'SfkeluarController@updateSfKeluar',
        'SfkeluarController@bulkDelete', 'SfkeluarController@delete',
        'ReturSfController@store', 'ReturSfController@update',
        'ReturSfController@bulkDelete', 'ReturSfController@delete',
        'StockMovementController@transfer', 'StockMovementController@out',
        'MobileApprovalController@approve', 'MobileApprovalController@bulkApprove',
        'MobileApprovalController@bulkReject', 'MobileApprovalController@reject',
        'MobileSalesController@store', 'MobileSalesController@update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $action = class_basename((string) $request->route()?->getControllerClass())
            .'@'.((string) $request->route()?->getActionMethod());

        if (!in_array($action, self::PROTECTED_ACTIONS, true)) {
            return $next($request);
        }

        $key = (string) ($request->header('Idempotency-Key') ?: $request->input('_idempotency_key'));
        if (!Str::isUuid($key)) {
            return $this->invalidTokenResponse($request);
        }

        $payload = $request->except(['_token', '_idempotency_key']);
        $hash = hash('sha256', $action.'|'.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        try {
            return DB::transaction(function () use ($request, $next, $key, $action, $hash) {
                DB::table('idempotency_keys')->insert([
                    'key' => $key,
                    'action' => $action,
                    'user_id' => auth()->id(),
                    'request_hash' => $hash,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $next($request);
            }, 3);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }

            $existing = DB::table('idempotency_keys')->where('key', $key)->first();
            if (!$existing) {
                throw $exception;
            }
            if (!hash_equals($existing->request_hash, $hash)) {
                return $this->conflictResponse($request, 'Token transaksi sudah digunakan untuk data yang berbeda.');
            }

            return $this->conflictResponse($request, 'Transaksi ini sudah diproses sebelumnya. Stok tidak diubah lagi.');
        }
    }

    private function invalidTokenResponse(Request $request): Response
    {
        $message = 'Token transaksi tidak valid. Muat ulang halaman lalu coba kembali.';

        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : back()->withInput()->with('error', $message);
    }

    private function conflictResponse(Request $request, string $message): Response
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'duplicate' => true, 'message' => $message], 409)
            : back()->with('info', $message);
    }
}
