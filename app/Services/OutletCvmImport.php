<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutletCvmImport
{
    public const METRICS = [
        'cvm' => 'CVM',
        'comsak' => 'Comsak',
        'insak' => 'Insak',
        'hot_promo' => 'Hot Promo',
        'super_seru' => 'Super Seru',
        'byu' => 'BYU',
        'digital' => 'Digital',
    ];

    public const FILE_COLUMNS = [
        'cvm' => ['cvm'],
        'sub-cvm' => ['insak', 'byu', 'comsak', 'digital', 'hot_promo', 'super_seru'],
    ];

    public function import(UploadedFile $file, string $kind): int
    {
        $metrics = self::FILE_COLUMNS[$kind];
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            $this->fail('File tidak dapat dibuka.');
        }

        try {
            [$header, $delimiter] = $this->readHeader($handle, ['tanggal', 'id_outlet', ...$metrics]);
            return DB::transaction(function () use ($handle, $header, $delimiter, $metrics) {
                $batch = [];
                $seen = [];
                $line = 1;
                $today = now('Asia/Jakarta')->toDateString();

                while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                    $line++;
                    $values = array_map(fn ($value) => trim((string) $value), $values);
                    if (!array_filter($values, fn ($value) => $value !== '')) {
                        continue;
                    }
                    if (count($values) !== count($header)) {
                        $this->fail("Jumlah kolom salah pada baris {$line}.");
                    }

                    $row = array_combine($header, $values);
                    $date = $this->parseDate($row['tanggal'], $today, $line);
                    $id = $row['id_outlet'];
                    if (!preg_match('/^[A-Za-z0-9_-]{1,40}$/D', $id)
                        || preg_match('/^\d+e[+-]?\d+$/iD', $id)) {
                        $this->fail("ID Outlet tidak valid pada baris {$line}. Simpan ID sebagai teks.");
                    }

                    $key = $date.'|'.strtoupper($id);
                    if (isset($seen[$key])) {
                        $this->fail("Tanggal dan ID Outlet berulang pada baris {$line}.");
                    }
                    $seen[$key] = true;
                    if (count($seen) > 500000) {
                        $this->fail('Maksimal 500,000 baris per file.');
                    }

                    $record = ['transaction_date' => $date, 'id_outlet' => $id];
                    foreach ($metrics as $metric) {
                        $value = $row[$metric] === '' ? '0' : $row[$metric];
                        if (!preg_match('/^\d{1,10}$/D', $value) || (int) $value > 4294967295) {
                            $this->fail(self::METRICS[$metric]." pada baris {$line} harus angka bulat tanpa pemisah ribuan.");
                        }
                        $record[$metric] = (int) $value;
                    }
                    $batch[] = $record;
                    if (count($batch) === 500) {
                        DB::table('surya_outlet_cvm')->upsert($batch, ['transaction_date', 'id_outlet'], $metrics);
                        $batch = [];
                    }
                }

                if ($seen === []) {
                    $this->fail('File tidak berisi data.');
                }
                if ($batch !== []) {
                    DB::table('surya_outlet_cvm')->upsert($batch, ['transaction_date', 'id_outlet'], $metrics);
                }

                return count($seen);
            });
        } finally {
            fclose($handle);
        }
    }

    private function readHeader($handle, array $expected): array
    {
        foreach ([',', ';', "\t"] as $delimiter) {
            rewind($handle);
            $values = fgetcsv($handle, 0, $delimiter, '"', '');
            if (!$values) {
                break;
            }
            $values[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $values[0]);
            $header = array_map(
                fn ($value) => trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $value))), '_'),
                $values
            );
            if (count($header) === count($expected) && !array_diff($expected, $header)) {
                return [$header, $delimiter];
            }
        }

        $this->fail('Header CSV harus berisi '.implode(', ', $expected).'.');
    }

    private function parseDate(string $value, string $today, int $line): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts)) {
            [, $year, $month, $day] = $parts;
        } elseif (preg_match('~^(\d{1,2})/(\d{1,2})/(\d{4})$~D', $value, $parts)) {
            [, $day, $month, $year] = $parts;
        } else {
            $this->fail("Tanggal tidak valid pada baris {$line}. Gunakan YYYY-MM-DD atau DD/MM/YYYY.");
        }
        if ((int) $year >= 1000 && checkdate((int) $month, (int) $day, (int) $year)) {
            $iso = sprintf('%04d-%02d-%02d', $year, $month, $day);
            if ($iso <= $today) {
                return $iso;
            }
        }

        $this->fail("Tanggal tidak valid pada baris {$line}. Gunakan YYYY-MM-DD atau DD/MM/YYYY, maksimal hari ini.");
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['cvm_file' => $message]);
    }
}
