<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutletCategoryImport
{
    public function import(UploadedFile $file): int
    {
        $lock = Cache::lock('surya-outletlist-import', 120);
        if (!$lock->get()) $this->fail('Upload Outletlist lain masih diproses.');
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
                if (count($header) !== 2 || array_diff(['id_outlet', 'kategori_outlet'], $header)) $this->fail('Header harus id_outlet,kategori_outlet.');
                $ids = []; $number = 1;
                while (($values = fgetcsv($h, 0, $delimiter, '"', '')) !== false) {
                    $number++;
                    if (!array_filter($values, fn ($v) => trim((string)$v) !== '')) continue;
                    if (count($values) !== 2) $this->fail("Jumlah kolom salah pada baris $number.");
                    $row = array_combine($header, array_map('trim', $values));
                    $id = $row['id_outlet'];
                    if (!preg_match('/^[1-9][0-9]{0,17}$/D', $id)) $this->fail("ID outlet tidak valid pada baris $number.");
                    $category = mb_strtolower(preg_replace('/\s+/u', ' ', $row['kategori_outlet']));
                    if (!in_array($category, ['top 20 tap', 'top 20 outlet tap'], true)) $this->fail("Kategori pada baris $number harus Top 20 TAP atau TOP 20 OUTLET TAP.");
                    if (isset($ids[$id])) $this->fail("ID outlet berulang pada baris $number.");
                    $ids[$id] = true;
                    if (count($ids) > 50000) $this->fail('Maksimal 50.000 outlet per file.');
                }
                if (!$ids) $this->fail('File tidak berisi outlet; kategori sebelumnya tidak diubah.');
            } finally { fclose($h); }
            DB::transaction(function () use ($ids) {
                foreach (array_chunk(array_keys($ids), 500) as $batch) {
                    if (DB::table('appsdumais')->whereIn('id_outlet', $batch)->count() !== count($batch)) $this->fail('Ada ID outlet yang belum terdaftar. Upload Outletlist terlebih dahulu.');
                }
                DB::table('appsdumais')->where('kategori_outlet', '<>', 'Reguler')->update(['kategori_outlet' => 'Reguler']);
                foreach (array_chunk(array_keys($ids), 500) as $batch) {
                    DB::table('appsdumais')->whereIn('id_outlet', $batch)->update(['kategori_outlet' => 'Top 20 TAP']);
                }
            });
            return count($ids);
        } finally { $lock->release(); }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['category_file' => $message]);
    }
}
