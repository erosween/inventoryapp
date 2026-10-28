<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutletlistImport
{
    public const COLUMNS = ['id_outlet','nama_outlet','nomor_rs','kecamatan','cluster','sf','tap','longitude','latitude','pjp'];
    public const PROTECTED_COLUMNS = ['latitude_checkin','longitude_checkin'];

    public function import(UploadedFile $file): array
    {
        $lock = Cache::lock('surya-outletlist-import', 120);
        if (!$lock->get()) $this->error('Upload Outletlist lain masih diproses.');
        try {
            $path = $file->getRealPath();
            [$delimiter, $headers] = $this->header($path);
            // Validate the complete file before writing any master data.
            $seen = [];
            foreach ($this->rows($path, $delimiter, $headers) as $number => $row) {
                if (isset($seen[$row['id_outlet']])) $this->error("ID outlet berulang pada baris $number.");
                $seen[$row['id_outlet']] = true;
                if (count($seen) > 50000) $this->error('Maksimal 50.000 outlet per file.');
            }
            if (!$seen) $this->error('File tidak berisi outlet.');
            $count = count($seen);
            unset($seen);
            DB::transaction(function () use ($path, $delimiter, $headers) {
                $batch = [];
                foreach ($this->rows($path, $delimiter, $headers) as $row) {
                    $batch[] = $row;
                    if (count($batch) === 500) { $this->save($batch); $batch = []; }
                }
                if ($batch) $this->save($batch);
            });
            return ['count' => $count];
        } finally { $lock->release(); }
    }

    private function save(array $rows): void
    {
        // Check-in coordinates are deliberately absent from both insert and update.
        DB::table('appsdumais')->upsert($rows, ['id_outlet'], array_values(array_diff(self::COLUMNS, ['id_outlet'])));
    }

    private function header(string $path): array
    {
        $h = fopen($path, 'rb');
        if (!$h) $this->error('File tidak dapat dibuka.');
        try {
            $prefix = fread($h, 4);
            if (str_starts_with($prefix, 'PK') || str_starts_with($prefix, "\xD0\xCF") || str_starts_with($prefix, "\xFF\xFE") || str_starts_with($prefix, "\xFE\xFF")) $this->error('Gunakan CSV UTF-8 asli.');
            $delimiter = ','; $best = 0;
            foreach ([',',';',"\t"] as $candidate) {
                rewind($h); $row = fgetcsv($h, 0, $candidate, '"', '');
                if (is_array($row) && count($row) > $best) { $best = count($row); $delimiter = $candidate; }
            }
            rewind($h); $headers = fgetcsv($h, 0, $delimiter, '"', '');
            if (!$headers) $this->error('File kosong.');
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headers[0]);
            $headers = array_map(fn ($v) => strtolower(trim((string)$v)), $headers);
            if (count($headers) !== count(array_unique($headers)) || array_diff(self::COLUMNS, $headers) || array_diff($headers, array_merge(self::COLUMNS, self::PROTECTED_COLUMNS))) {
                $this->error('Header harus mengikuti template Outletlist: '.implode(', ', self::COLUMNS).'.');
            }
            return [$delimiter, $headers];
        } finally { fclose($h); }
    }

    private function rows(string $path, string $delimiter, array $headers): \Generator
    {
        $h = fopen($path, 'rb');
        if (!$h) $this->error('File tidak dapat dibuka.');
        try {
            fgetcsv($h, 0, $delimiter, '"', ''); $number = 1;
            while (($values = fgetcsv($h, 0, $delimiter, '"', '')) !== false) {
                $number++;
                if (!array_filter($values, fn ($v) => trim((string)$v) !== '')) continue;
                if (count($values) !== count($headers)) $this->error("Jumlah kolom tidak sesuai pada baris $number.");
                $row = [];
                foreach ($headers as $index => $column) {
                    if (in_array($column, self::PROTECTED_COLUMNS, true)) continue;
                    $value = trim((string)$values[$index]);
                    if (!mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > 255 || str_contains($value, "\0")) $this->error("Nilai $column tidak valid pada baris $number.");
                    $row[$column] = $value === '' ? null : $value;
                }
                if (!preg_match('/^[1-9][0-9]{0,17}$/D', (string)$row['id_outlet'])) $this->error("ID outlet tidak valid pada baris $number. Gunakan angka utuh, bukan notasi ilmiah.");
                if ($row['nama_outlet'] === null) $this->error("Nama outlet kosong pada baris $number.");
                if ($row['nomor_rs'] !== null && !preg_match('/^[0-9]{1,18}$/D', $row['nomor_rs'])) $this->error("Nomor RS tidak valid pada baris $number.");
                if (($row['latitude'] === null) !== ($row['longitude'] === null)) $this->error("Latitude dan longitude harus berpasangan pada baris $number.");
                foreach (['latitude'=>90, 'longitude'=>180] as $column => $limit) {
                    if ($row[$column] === null) continue;
                    $value = str_replace(',', '.', $row[$column]);
                    if (!preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D', $value) || abs((float)$value) > $limit) $this->error("Koordinat $column tidak valid pada baris $number.");
                    $row[$column] = $value;
                }
                foreach (['tap','cluster'] as $column) if ($row[$column] !== null) $row[$column] = mb_strtoupper($row[$column]);
                yield $number => $row;
            }
        } finally { fclose($h); }
    }

    private function error(string $message): never
    {
        throw ValidationException::withMessages(['outletlist_file' => $message]);
    }
}
