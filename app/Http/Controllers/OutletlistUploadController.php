<?php
namespace App\Http\Controllers;

use App\Services\OutletlistImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OutletlistUploadController extends Controller
{
    public function notaStore(Request $request, \App\Services\OutletNotaImport $importer)
    {
        $this->authorizeUpload();
        $request->validate(['nota_file'=>['required','file','mimes:csv,txt','extensions:csv','max:20480']]);
        try { $count = $importer->import($request->file('nota_file')); }
        catch (ValidationException $e) { throw $e; }
        catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['nota_file'=>'Upload gagal. Histori sebelumnya tetap tersimpan.']);
        }
        return redirect()->route('surya.transaction-upload')->with('success', "Histori ST Nota: $count baris menggantikan data 1–15 Oktober 2026 untuk Detail Outlet.");
    }

    public function notaTemplate()
    {
        $this->authorizeUpload();
        return response()->streamDownload(function () {
            $h=fopen('php://output','wb'); fwrite($h,"\xEF\xBB\xBF");
            fputcsv($h, \App\Services\OutletNotaImport::HEADERS, ',', '"', ''); fclose($h);
        }, 'template-st-nota-1-15-oktober-2026.csv', ['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function categoryStore(Request $request, \App\Services\OutletCategoryImport $importer)
    {
        $this->authorizeUpload();
        $request->validate(['category_file' => ['required','file','mimes:csv,txt','extensions:csv','max:20480']]);
        try { $count = $importer->import($request->file('category_file')); }
        catch (ValidationException $e) { throw $e; }
        catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['category_file' => 'Upload gagal. Kategori sebelumnya tetap tersimpan.']);
        }
        return redirect()->route('surya.transaction-upload')->with('success', "$count outlet Top 20 TAP diperbarui. Outlet lainnya Reguler.");
    }

    public function categoryTemplate()
    {
        $this->authorizeUpload();
        return response()->streamDownload(function () {
            $h = fopen('php://output', 'wb'); fwrite($h, "\xEF\xBB\xBF");
            fputcsv($h, ['id_outlet', 'kategori_outlet'], ',', '"', ''); fclose($h);
        }, 'template-kategori-outlet.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeUpload(): void
    {
        abort_unless(auth()->check() && strtolower((string)auth()->user()->username) === 'admin_super', 403);
    }

    public function store(Request $request, OutletlistImport $importer)
    {
        $this->authorizeUpload();
        $request->validate(['outletlist_file' => ['required','file','mimes:csv,txt','extensions:csv','max:20480']]);
        try { $result = $importer->import($request->file('outletlist_file')); }
        catch (ValidationException $e) { throw $e; }
        catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['outletlist_file'=>'Upload gagal. Data outlet sebelumnya tetap tersimpan.']);
        }
        return redirect()->route('surya.transaction-upload')->with('success', 'Outletlist: '.number_format($result['count'],0,',','.').' outlet diperbarui.');
    }

    public function template()
    {
        $this->authorizeUpload();
        return response()->streamDownload(function () {
            $h = fopen('php://output','wb'); fwrite($h,"\xEF\xBB\xBF");
            fputcsv($h, array_merge(OutletlistImport::COLUMNS, OutletlistImport::PROTECTED_COLUMNS), ',', '"', '');
            fclose($h);
        }, 'template-outletlist.csv', ['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
