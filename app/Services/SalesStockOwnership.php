<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Resolves SF serial ownership from SF Masuk, the active upload snapshot, and manual label corrections. */
class SalesStockOwnership
{
    public function ownsRange(string $idsf, string $iddenom, string $start, string $end, ?int $ignoreReturnId = null): bool
    {
        if (!ctype_digit($start) || !ctype_digit($end) || strlen($start) !== strlen($end) || (int) $end < (int) $start) return false;
        if (strlen($start) === 16 && str_starts_with($start, '0')) {
            $start = substr($start, 1);
            $end = substr($end, 1);
        }

        $ranges = [];
        $masuk = DB::table('masuksf')->where('idsf', $idsf)->where('iddenom', $iddenom)
            ->whereNotNull('sn')->where('sn', '<>', '')->get(['sn', 'qty']);
        foreach ($masuk as $row) {
            $raw = trim((string) $row->sn);
            if (preg_match('/^(\d+)(?:\s*[-–—]\s*|\s+)(\d+)$/u', $raw, $m)) [$a, $b] = [$m[1], $m[2]];
            elseif (preg_match('/^\d+$/', $raw)) $a = $b = $raw;
            else continue;
            if (strlen($a) === 16 && str_starts_with($a, '0') && str_starts_with($b, '0')) { $a = substr($a, 1); $b = substr($b, 1); }
            if (strlen($a) !== strlen($b) || (int) $b < (int) $a) continue;
            $qty = (int) $b - (int) $a + 1;
            if ($qty > 2_000_000 || ($a !== $b && $qty !== (int) $row->qty)) continue;
            $ranges[] = [(int) $a, (int) $b];
        }

        if (Schema::hasTable('product_tracking_sales_stock_ranges') && Schema::hasTable('product_tracking_sales_stock_uploads')) {
            $latestUpload = DB::table('product_tracking_sales_stock_uploads')->where('filename', 'not like', 'MANUAL:%')->max('id');
            $uploaded = DB::table('product_tracking_sales_stock_ranges as r')
                ->join('product_tracking_sales_stock_uploads as u', 'u.id', '=', 'r.upload_id')
                ->where('r.sfcode', $idsf)->where('r.iddenom', $iddenom)
                ->where(function ($q) use ($latestUpload) {
                    $q->where('u.filename', 'like', 'MANUAL:%');
                    if ($latestUpload) $q->orWhere('r.upload_id', $latestUpload);
                })->get(['r.id', 'r.serial_start', 'r.serial_end', 'u.filename']);
            foreach ($uploaded as $row) {
                $a = (string) $row->serial_start; $b = (string) $row->serial_end;
                if (strlen($a) === 16 && str_starts_with($a, '0') && str_starts_with($b, '0')) { $a = substr($a, 1); $b = substr($b, 1); }
                if (ctype_digit($a) && ctype_digit($b) && strlen($a) === strlen($b) && (int) $b >= (int) $a) $ranges[] = [(int) $a, (int) $b];
            }

            $corrections = DB::table('product_tracking_sales_stock_ranges as r')
                ->join('product_tracking_sales_stock_uploads as u', 'u.id', '=', 'r.upload_id')
                ->where('u.filename', 'like', 'MANUAL:%')->where('r.iddenom', $iddenom)
                ->when($ignoreReturnId, fn ($q) => $q->where('u.filename', '!=', 'MANUAL: RETUR SF KE SF #'.$ignoreReturnId))
                ->orderBy('r.id')->get(['r.sfcode', 'r.serial_start', 'r.serial_end']);
            foreach ($corrections as $row) {
                $a = (string) $row->serial_start; $b = (string) $row->serial_end;
                if (strlen($a) === 16 && str_starts_with($a, '0') && str_starts_with($b, '0')) { $a = substr($a, 1); $b = substr($b, 1); }
                if (!ctype_digit($a) || !ctype_digit($b) || strlen($a) !== strlen($b) || (int) $b < (int) $a) continue;
                $lo = (int) $a; $hi = (int) $b;
                $ranges = array_reduce($ranges, function (array $kept, array $range) use ($lo, $hi): array {
                    if ($range[1] < $lo || $range[0] > $hi) $kept[] = $range;
                    else {
                        if ($range[0] < $lo) $kept[] = [$range[0], $lo - 1];
                        if ($range[1] > $hi) $kept[] = [$hi + 1, $range[1]];
                    }
                    return $kept;
                }, []);
                if ((string) $row->sfcode === $idsf) $ranges[] = [$lo, $hi];
            }
        }

        usort($ranges, fn ($a, $b) => $a[0] <=> $b[0]);
        $wantedStart = (int) $start; $wantedEnd = (int) $end; $cursor = $wantedStart;
        foreach ($ranges as [$lo, $hi]) {
            if ($hi < $cursor) continue;
            if ($lo > $cursor) return false;
            $cursor = max($cursor, $hi + 1);
            if ($cursor > $wantedEnd) return true;
        }
        return false;
    }
}
