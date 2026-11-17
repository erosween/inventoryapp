<?php

namespace App\Http\Controllers;

use App\Services\HistoricalSaSalesImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HistoricalSaSalesController extends Controller
{
    public function store(Request $request, HistoricalSaSalesImport $importer)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $request->validate([
            'historical_sa_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'extensions:xlsx,xls,csv', 'max:20480'],
        ]);

        try {
            $result = $importer->import($request->file('historical_sa_file'));
        } catch (ValidationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            report($error);
            throw ValidationException::withMessages([
                'historical_sa_file' => 'File gagal diproses. Pastikan header dan data sesuai; tidak ada stok yang diubah.',
            ]);
        }

        $missingSales = $result['sfs'] ? ' SF di luar master: '.implode(', ', $result['sfs']).'.' : '';
        return redirect()->route('surya.transaction-upload')->with(
            'success',
            'Histori SA berhasil dicatat: '.number_format($result['rows']).' baris, total '.number_format($result['qty']).' pcs. ID outlet kosong dan stok tidak berubah.'.$missingSales
        );
    }
}
