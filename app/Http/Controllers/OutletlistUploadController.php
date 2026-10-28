<?php
namespace App\Http\Controllers;

use App\Services\OutletlistImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OutletlistUploadController extends Controller
{
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
