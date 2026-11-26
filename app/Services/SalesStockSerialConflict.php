<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared global serial-range validation for the three SF stock entry paths.
 * SA serials with a leading zero are compared in their canonical 15-digit form.
 */
class SalesStockSerialConflict
{
    public function assertAvailable(string $start, string $end, ?int $ignoreManualRangeId = null, ?int $ignoreMasukId = null, bool $includeActiveUpload = true, ?int $ignoreDoId = null): void
    {
        $isPv = strlen($start) === 12;
        $canonicalStart = $this->canonical($start);
        $canonicalEnd = $this->canonical($end);

        $normalizedDoSn = "REPLACE(REPLACE(TRIM(do_row.sn), CHAR(9), '-'), ' ', '')";
        $doStart = "SUBSTRING_INDEX({$normalizedDoSn}, '-', 1)";
        $doEnd = "SUBSTRING_INDEX({$normalizedDoSn}, '-', -1)";
        $canonicalDoStart = "CASE WHEN CHAR_LENGTH({$doStart}) = 16 AND LEFT({$doStart}, 1) = '0' THEN SUBSTRING({$doStart}, 2) ELSE {$doStart} END";
        $canonicalDoEnd = "CASE WHEN CHAR_LENGTH({$doEnd}) = 16 AND LEFT({$doEnd}, 1) = '0' THEN SUBSTRING({$doEnd}, 2) ELSE {$doEnd} END";
        $doRows = DB::table('masuk as do_row')
            ->where('do_row.pengirim', 'DO')
            ->whereRaw("{$normalizedDoSn} REGEXP '^[0-9]+(-[0-9]+)?$'")
            ->whereRaw("CHAR_LENGTH({$canonicalDoStart}) = ? AND CHAR_LENGTH({$canonicalDoEnd}) = ?", [strlen($canonicalStart), strlen($canonicalStart)])
            ->whereRaw("CAST({$canonicalDoStart} AS DECIMAL(24,0)) <= ? AND CAST({$canonicalDoEnd} AS DECIMAL(24,0)) >= ?", [$canonicalEnd, $canonicalStart]);
        if ($ignoreDoId !== null) $doRows->where('do_row.idmasuk', '!=', $ignoreDoId);
        if ($doRows->exists()) $this->conflict('SN sudah tercatat pada Input DO (Stok Gudang).');

        $normalizedSn = "REPLACE(REPLACE(TRIM(m.sn), CHAR(9), '-'), ' ', '')";
        $existingStart = "SUBSTRING_INDEX({$normalizedSn}, '-', 1)";
        $existingEnd = "SUBSTRING_INDEX({$normalizedSn}, '-', -1)";
        $admin = DB::table('masuksf as m')
            ->whereRaw("{$normalizedSn} REGEXP '^[0-9]+(-[0-9]+)?$'")
            ->whereRaw("CHAR_LENGTH({$existingStart}) IN (".($isPv ? '12' : '15, 16').')')
            // Ignore malformed legacy rows: both endpoints must use the same
            // serial width, be ordered, and match the recorded quantity.
            ->whereRaw("CHAR_LENGTH({$existingStart}) = CHAR_LENGTH({$existingEnd})")
            ->whereRaw("m.qty BETWEEN 1 AND 2000000 AND CAST({$existingEnd} AS DECIMAL(24,0)) >= CAST({$existingStart} AS DECIMAL(24,0))")
            ->whereRaw("CAST({$existingEnd} AS DECIMAL(24,0)) - CAST({$existingStart} AS DECIMAL(24,0)) + 1 = m.qty")
            ->whereRaw("CAST({$existingStart} AS DECIMAL(24,0)) <= ?", [$canonicalEnd])
            ->whereRaw("CAST({$existingEnd} AS DECIMAL(24,0)) >= ?", [$canonicalStart]);
        if ($ignoreMasukId !== null) $admin->where('m.idmasuk', '!=', $ignoreMasukId);
        if ($admin->exists()) $this->conflict('SN sudah tercatat di menu SF Masuk.');

        if (!\Illuminate\Support\Facades\Schema::hasTable('product_tracking_sales_stock_ranges')
            || !\Illuminate\Support\Facades\Schema::hasTable('product_tracking_sales_stock_uploads')) return;

        if ($includeActiveUpload) {
            $latestUploadId = DB::table('product_tracking_sales_stock_uploads')
                ->where('filename', 'not like', 'MANUAL:%')->max('id');
            if ($latestUploadId) {
                $uploaded = DB::table('product_tracking_sales_stock_ranges')
                    ->where('upload_id', $latestUploadId)
                    ->whereRaw('CHAR_LENGTH(serial_start) IN ('.($isPv ? '12' : '15, 16').')')
                    ->whereRaw('CAST(serial_start AS DECIMAL(24,0)) <= ? AND CAST(serial_end AS DECIMAL(24,0)) >= ?', [$canonicalEnd, $canonicalStart]);
                if ($uploaded->exists()) $this->conflict('SN sudah ada di file upload stok SF yang aktif.');
            }
        }

        $manual = DB::table('product_tracking_sales_stock_ranges as stock')
            ->join('product_tracking_sales_stock_uploads as source', 'source.id', '=', 'stock.upload_id')
            ->where('source.filename', 'like', 'MANUAL:%')
            ->whereRaw('CHAR_LENGTH(stock.serial_start) IN ('.($isPv ? '12' : '15, 16').')')
            ->whereRaw('CAST(stock.serial_start AS DECIMAL(24,0)) <= ? AND CAST(stock.serial_end AS DECIMAL(24,0)) >= ?', [$canonicalEnd, $canonicalStart]);
        if ($ignoreManualRangeId !== null) $manual->where('stock.id', '!=', $ignoreManualRangeId);
        if ($manual->exists()) $this->conflict('SN sudah dipetakan melalui menu Add Label SN.');
    }

    public function assertFileRangesDoNotOverlap(array $rows): void
    {
        $ranges = [];
        foreach ($rows as $index => $row) {
            $start = $this->canonical((string) $row['serial_start']);
            $end = $this->canonical((string) $row['serial_end']);
            $ranges[strlen($start)][] = ['start' => $start, 'end' => $end, 'row' => $index + 1];
        }
        foreach ($ranges as $items) {
            usort($items, fn ($a, $b) => strcmp($a['start'], $b['start']));
            $end = null;
            $priorRow = null;
            foreach ($items as $item) {
                if ($end !== null && strcmp($item['start'], $end) <= 0) {
                    $this->conflict("File upload memiliki SN bertumpuk pada baris data {$priorRow} dan {$item['row']}.");
                }
                $end = $item['end'];
                $priorRow = $item['row'];
            }
        }
    }

    public function lock(): void
    {
        // All three writers use the same DB advisory lock to close the check/insert race.
        $result = DB::selectOne("SELECT GET_LOCK('surya_sales_stock_serial_write', 10) AS acquired");
        if ((int) ($result->acquired ?? 0) !== 1) {
            throw new \RuntimeException('Tidak bisa mengunci validasi SN. Coba ulang beberapa saat lagi.');
        }
    }

    public function unlock(): void
    {
        DB::selectOne("SELECT RELEASE_LOCK('surya_sales_stock_serial_write') AS released");
    }

    private function canonical(string $serial): string
    {
        return strlen($serial) === 16 && str_starts_with($serial, '0') ? substr($serial, 1) : $serial;
    }

    private function conflict(string $message): never
    {
        throw ValidationException::withMessages(['serial_start' => $message]);
    }
}
