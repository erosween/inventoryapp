<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockMovementController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | HELPER: GET STOCK REALTIME
    |--------------------------------------------------------------------------
    */
    private function getStock($location, $iddenom)
    {
        return DB::table('stock_movements')
            ->where('iddenom', $iddenom)
            ->selectRaw("
                SUM(
                    CASE
                        WHEN to_location = ? THEN qty
                        WHEN from_location = ? THEN -qty
                        ELSE 0
                    END
                ) as stock
            ", [$location, $location])
            ->value('stock') ?? 0;
    }


    /*
    |--------------------------------------------------------------------------
    | TRANSFER (SF MASUK)
    | TAP -> SF
    |--------------------------------------------------------------------------
    */
    public function transfer(Request $request)
    {
        $request->validate([
            'trx_id'  => 'required',
            'idtap'   => 'required',
            'idsf'    => 'required',
            'iddenom' => 'required',
            'qty'     => 'required|integer|min:1',
            'tgl'     => 'required|date'
        ]);

        try {

            DB::transaction(function () use ($request) {

                // cek stok TAP cukup
                $stockTap = $this->getStock($request->idtap, $request->iddenom);

                if ($stockTap < $request->qty) {
                    throw new \Exception('Stok TAP tidak cukup');
                }

                DB::table('stock_movements')->insert([
                    'trx_id'        => $request->trx_id,
                    'movement_type' => 'TRANSFER',
                    'from_location' => $request->idtap,
                    'to_location'   => $request->idsf,
                    'iddenom'       => $request->iddenom,
                    'qty'           => $request->qty,
                    'note'          => $request->sn,
                    'created_at'    => $request->tgl
                ]);
            });

            return back()->with('success', 'Stok berhasil dipindahkan');

        } catch (\Illuminate\Database\QueryException $e) {

            // anti double submit
            if ($e->errorInfo[1] == 1062) {
                return back()->with('error', 'Transaksi sudah diproses');
            }

            return back()->with('error', $e->getMessage());
        }
    }



    /*
    |--------------------------------------------------------------------------
    | OUT (SF KELUAR)
    | SF -> OUTSIDE
    |--------------------------------------------------------------------------
    */
    public function out(Request $request)
    {
        $request->validate([
            'trx_id'  => 'required',
            'idsf'    => 'required',
            'iddenom' => 'required',
            'qty'     => 'required|integer|min:1',
            'tgl'     => 'required|date'
        ]);

        try {

            DB::transaction(function () use ($request) {

                $stock = $this->getStock($request->idsf, $request->iddenom);

                if ($stock < $request->qty) {
                    throw new \Exception('Stok SF tidak mencukupi');
                }

                DB::table('stock_movements')->insert([
                    'trx_id'        => $request->trx_id,
                    'movement_type' => 'OUT',
                    'from_location' => $request->idsf,
                    'to_location'   => null,
                    'iddenom'       => $request->iddenom,
                    'qty'           => $request->qty,
                    'note'          => $request->tambahanket,
                    'created_at'    => $request->tgl
                ]);
            });

            return back()->with('success', 'Stok keluar berhasil');

        } catch (\Illuminate\Database\QueryException $e) {

            if ($e->errorInfo[1] == 1062) {
                return back()->with('error', 'Transaksi sudah diproses');
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
