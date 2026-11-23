<?php

namespace App\Console\Commands;

use App\Services\ProductTrackingFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDigiposSellThru extends Command
{
    protected $signature = 'product-tracking:import-digipos-sell-thru {file : CSV hasil download Sell Thru DigiPOS} {--dry-run : Baca dan validasi file tanpa menulis ke database}';

    protected $description = 'Impor otomatis CSV Sell Thru DigiPOS dengan mapping header tetap dan deduplikasi SN';

    public function handle(ProductTrackingFile $reader): int
    {
        $path = $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error('File CSV tidak ditemukan atau tidak dapat dibaca.');
            return self::FAILURE;
        }

        try {
            $headers = $reader->headers($path);
            $mapping = $this->mapHeaders($headers);
            $records = [];
            $seen = [];
            $skipped = 0;

            foreach ($reader->rows($path, $headers) as $rowNumber => $values) {
                $serial = trim((string) ($values[$mapping['sn']] ?? ''));
                if ($serial === '' || in_array($serial, ['-', '—'], true)) continue;
                if (! preg_match('/^\d{1,24}$/', $serial)) {
                    throw new \RuntimeException("Baris {$rowNumber}: SN harus angka maksimal 24 digit.");
                }

                $dateValue = trim((string) ($values[$mapping['date']] ?? ''));
                $date = $this->parseDate($dateValue);
                if (! $date) throw new \RuntimeException("Baris {$rowNumber}: tanggal tidak terbaca ({$dateValue}).");

                $outlet = trim((string) ($values[$mapping['outlet']] ?? ''));
                if ($outlet === '' || strlen($outlet) > 50 || ! preg_match('/^[A-Za-z0-9_-]+$/', $outlet)) {
                    throw new \RuntimeException("Baris {$rowNumber}: ID DigiPOS tidak valid.");
                }

                $canonical = $this->canonicalSerial($serial);
                if (isset($seen[$canonical]) || (! $this->option('dry-run') && $this->serialAlreadyImported($serial))) {
                    $skipped++;
                    continue;
                }
                $seen[$canonical] = true;
                $records[$date][] = [
                    'serial_start' => $serial,
                    'serial_end' => $serial,
                    'serial_count' => 1,
                    'id_outlet' => $outlet,
                    'sf' => mb_substr(trim((string) ($values[$mapping['sf']] ?? '')), 0, 150),
                    'sold_at' => $date,
                    'product_name' => mb_substr(trim((string) ($values[$mapping['product']] ?? '')), 0, 200),
                ];
            }

            $count = array_sum(array_map('count', $records));
            if ($count === 0) {
                $this->info("Tidak ada SN baru untuk diimpor. Duplikat dilewati: {$skipped}.");
                return self::SUCCESS;
            }

            if ($this->option('dry-run')) {
                $this->info('Dry run berhasil: '.$count.' SN baru, '.$skipped.' duplikat dilewati, '.count($records).' tanggal.');
                return self::SUCCESS;
            }

            DB::transaction(function () use ($records, $path): void {
                $now = now();
                foreach ($records as $date => $rows) {
                    $uploadId = DB::table('product_tracking_uploads')->insertGetId([
                        'kind' => 'scan',
                        'scan_date' => $date,
                        'filename' => mb_substr(basename($path).' · DigiPOS Sell Thru '.$date, 0, 255),
                        'headers' => null,
                        'storage_path' => null,
                        'status' => 'complete',
                        'row_count' => count($rows),
                        'username' => 'stockbot-digipos',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    foreach (array_chunk($rows, 500) as $batch) {
                        $insertRows = array_map(fn (array $row) => [
                            'upload_id' => $uploadId,
                            'kind' => 'scan',
                            'scan_date' => $date,
                            ...$row,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ], $batch);
                        DB::table('product_tracking_digipos')->insert($insertRows);
                    }
                }
            });

            $this->info("Impor berhasil: {$count} SN baru; {$skipped} duplikat dilewati.");
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    private function mapHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $column => $label) {
            $normalized[preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $label)))] = (string) $column;
        }

        $aliases = [
            'date' => ['date'],
            'outlet' => ['iddigipos'],
            'sf' => ['userloginsf'],
            'sn' => ['sn'],
            'product' => ['itempaket'],
        ];
        $mapping = [];
        foreach ($aliases as $key => $candidates) {
            foreach ($candidates as $candidate) {
                if (isset($normalized[$candidate])) {
                    $mapping[$key] = $normalized[$candidate];
                    break;
                }
            }
            if (! isset($mapping[$key])) throw new \RuntimeException('Header wajib tidak ditemukan: '.strtoupper($key).'. File harus berisi Date, ID DigiPOS, User Login SF, SN, dan Item Paket.');
        }

        return $mapping;
    }

    private function canonicalSerial(string $serial): string
    {
        return strlen($serial) === 16 && str_starts_with($serial, '0') ? substr($serial, 1) : $serial;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^\d{1,2}-[A-Za-z]{3}-\d{4}/', $value, $match)) {
            return ProductTrackingFile::scanDate($match[0]);
        }
        if (preg_match('/^\d{1,2}[\/-]\d{1,2}[\/-]\d{4}/', $value, $match)) {
            return ProductTrackingFile::date(str_replace('-', '/', $match[0]));
        }
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}/', $value, $match)) {
            return ProductTrackingFile::date($match[0]);
        }

        return ProductTrackingFile::date($value);
    }

    private function serialAlreadyImported(string $serial): bool
    {
        $variants = [$serial];
        if (strlen($serial) === 16 && str_starts_with($serial, '0')) $variants[] = substr($serial, 1);
        elseif (strlen($serial) === 15) $variants[] = '0'.$serial;

        return DB::table('product_tracking_digipos as d')
            ->join('product_tracking_uploads as u', 'u.id', '=', 'd.upload_id')
            ->where('d.kind', 'scan')->where('u.kind', 'scan')->where('u.status', 'complete')
            ->where(function ($query) use ($variants): void {
                foreach ($variants as $variant) {
                    $query->orWhere(function ($match) use ($variant): void {
                        $match->whereRaw('CHAR_LENGTH(d.serial_start) = ?', [strlen($variant)])
                            ->whereRaw('CHAR_LENGTH(d.serial_end) = ?', [strlen($variant)])
                            ->where('d.serial_start', '<=', $variant)
                            ->where('d.serial_end', '>=', $variant);
                    });
                }
            })->exists();
    }
}
