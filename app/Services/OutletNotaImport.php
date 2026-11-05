<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutletNotaImport
{
    public const HEADERS = ['tanggal', 'id_outlet', 'st_sa', 'st_pv'];

    public function import(UploadedFile $file): int
    {
        $lock = Cache::lock('surya-outletlist-import', 120);
        if (!$lock->get()) $this->fail('Upload outlet lain masih diproses.');
        try {
            $h = fopen($file->getRealPath(), 'rb');
            if (!$h) $this->fail('File tidak dapat dibuka.');
            try {
                $best = 0; $delimiter = ',';
                foreach ([',', ';', "\t"] as $candidate) {
                    rewind($h); $header = fgetcsv($h, 0, $candidate, '"', '');
                    if (is_array($header) && count($header) > $best) { $best = count($header); $delimiter = $candidate; }
                }
                rewind($h); $header = fgetcsv($h, 0, $delimiter, '"', '');
                if (!$header) $this->fail('File kosong.');
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);
                $header = array_map(fn ($v) => strtolower(trim((string)$v)), $header);
                if (count($header) !== 4 || array_diff(self::HEADERS, $header)) $this->fail('Header harus tanggal,id_outlet,st_sa,st_pv.');
                $rows = []; $ids = []; $number = 1; $now = now();
                while (($values = fgetcsv($h, 0, $delimiter, '"', '')) !== false) {
                    $number++;
                    if (!array_filter($values, fn ($v) => trim((string)$v) !== '')) continue;
                    if (count($values) !== 4) $this->fail("Jumlah kolom salah pada baris $number.");
                    $row = array_combine($header, array_map('trim', $values));
                    if (!preg_match('/^2026-10-(0[1-9]|1[0-5])$/D', $row['tanggal'])) $this->fail("Tanggal baris $number harus 2026-10-01 sampai 2026-10-15 (YYYY-MM-DD).");
                    if (!preg_match('/^[1-9][0-9]{0,17}$/D', $row['id_outlet'])) $this->fail("ID outlet tidak valid pada baris $number.");
                    foreach (['st_sa','st_pv'] as $column) {
                        if (!preg_match('/^[0-9]{1,10}$/D', $row[$column]) || (int)$row[$column] > 4294967295) $this->fail("$column pada baris $number harus qty utuh tanpa pemisah ribuan, minimal 0.");
                    }
                    $key = $row['tanggal'].'|'.$row['id_outlet'];
                    if (isset($rows[$key])) $this->fail("Tanggal dan ID outlet berulang pada baris $number. Gabungkan qty menjadi satu baris.");
                    $rows[$key] = ['transaction_date'=>$row['tanggal'], 'id_outlet'=>$row['id_outlet'], 'sa'=>(int)$row['st_sa'], 'pv'=>(int)$row['st_pv'], 'created_at'=>$now, 'updated_at'=>$now];
                    $ids[$row['id_outlet']] = true;
                    if (count($rows) > 100000) $this->fail('Maksimal 100.000 baris per file.');
                }
                if (!$rows) $this->fail('File tidak berisi data. Histori sebelumnya tidak diubah.');
            } finally { fclose($h); }
            DB::transaction(function () use ($rows, $ids) {
                foreach (array_chunk(array_keys($ids), 500) as $batch) {
                    if (DB::table('appsdumais')->whereIn('id_outlet', $batch)->count() !== count($batch)) $this->fail('Ada ID outlet yang belum terdaftar. Upload Outletlist terlebih dahulu.');
                }
                DB::table('surya_outlet_nota_backfill')->whereBetween('transaction_date', [OutletNotaSales::FROM, OutletNotaSales::TO])->delete();
                foreach (array_chunk(array_values($rows), 500) as $batch) DB::table('surya_outlet_nota_backfill')->insert($batch);
            });
            return count($rows);
        } finally { $lock->release(); }
    }
    private function fail(string $message): never { throw ValidationException::withMessages(['nota_file'=>$message]); }
}
