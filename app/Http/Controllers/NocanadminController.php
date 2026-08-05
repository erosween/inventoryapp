<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NocanadminController extends Controller
{

    public function index(Request $request)
    {

        $idtap = session('idtap');

        // Simpan nilai filter ke dalam session
        $data = DB::table('nocan')
            ->select("*")
            ->where('status', '!=', 'ready')
            ->get();

        return view('nocanadmin', compact('data', 'idtap'));
    }

    public function edit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'harga' => ['required', 'numeric', 'min:0'],
            'tanggal' => ['required', 'date'],
            'tap' => ['required', 'string'],
            'penjual' => ['required', 'string'],
            'status' => ['required', 'in:PAID,SOLD,BOOKING'],
            'divisi' => ['required', 'in:karyawan,outlet,ds'],
            'outlet' => ['nullable', 'integer'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->input('divisi') !== 'outlet') {
                return;
            }

            $outlet = (string) $request->input('outlet', '');
            if ($outlet === '') {
                $validator->errors()->add('outlet', 'ID Outlet wajib diisi untuk divisi Outlet.');
            } elseif (in_array($outlet, ['1', '123'], true)) {
                $validator->errors()->add('outlet', 'ID 1 khusus Karyawan dan ID 123 khusus DS.');
            } elseif ((int) $outlet <= 0) {
                $validator->errors()->add('outlet', 'ID Outlet harus lebih dari 0.');
            }
        });

        $validated = $validator->validate();
        $outlet = match ($validated['divisi']) {
            'karyawan' => 1,
            'ds' => 123,
            default => $validated['outlet'],
        };

        DB::table('nocan')
            ->where('id', $id)
            ->update([
                'tanggal' => $validated['tanggal'],
                'harga' => $validated['harga'],
                'tap' => $validated['tap'],
                'booked' => $validated['penjual'],
                'status' => $validated['status'],
                'outlet' => $outlet,
            ]);
        return redirect('nocanadmin')->with('status', 'Data Berhasil Diperbaharui!');
    }

    public function reset($id)
    {

        DB::table('nocan')
            ->where('id', $id)
            ->update([
                'tanggal' => null,
                'booked' => null,
                'outlet' => null,
                'status' => "READY",
            ]);
        return redirect('nocanadmin')->with('status', 'Data Berhasil Diperbaharui!');
    }
}
