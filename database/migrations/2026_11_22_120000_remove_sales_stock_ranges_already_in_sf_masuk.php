<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_tracking_sales_stock_uploads')
            || ! Schema::hasTable('product_tracking_sales_stock_ranges')
            || ! Schema::hasTable('masuksf')) {
            return;
        }

        $uploads = DB::table('product_tracking_sales_stock_uploads')
            ->whereDate('created_at', '2026-11-21')
            ->where('filename', 'not like', 'MANUAL:%')
            ->orderBy('id')
            ->pluck('id');

        foreach ($uploads as $uploadId) {
            $rows = DB::table('product_tracking_sales_stock_ranges')->where('upload_id', $uploadId)->get();
            $adminRangesByOwner = [];

            foreach ($rows as $row) {
                $key = $row->sfcode."\x1f".$row->iddenom;
                if (! array_key_exists($key, $adminRangesByOwner)) {
                    $adminRangesByOwner[$key] = [];
                    foreach (DB::table('masuksf')->where('idsf', $row->sfcode)->where('iddenom', $row->iddenom)->get(['sn', 'qty']) as $adminRow) {
                        $sn = trim((string) $adminRow->sn);
                        if (preg_match('/^(\d+)(?:\s*[-–—]\s*|\s+)(\d+)$/u', $sn, $match)) {
                            [$first, $last] = [$match[1], $match[2]];
                        } elseif (preg_match('/^\d+$/', $sn)) {
                            $first = $last = $sn;
                        } else {
                            continue;
                        }
                        if (strlen($first) !== strlen($last) || strcmp($last, $first) < 0) continue;
                        $qty = (int) $last - (int) $first + 1;
                        if ($qty > 2_000_000 || ($first !== $last && $qty !== (int) $adminRow->qty)) continue;
                        $adminRangesByOwner[$key][] = [(int) $first, (int) $last];
                    }
                    usort($adminRangesByOwner[$key], fn ($a, $b) => $a[0] <=> $b[0]);
                }

                $start = (int) $row->serial_start;
                $end = (int) $row->serial_end;
                $width = strlen((string) $row->serial_start);
                $remaining = [[$start, $end]];
                foreach ($adminRangesByOwner[$key] as [$adminStart, $adminEnd]) {
                    $adminWidth = strlen((string) $adminStart);
                    if (($width === 12 && $adminWidth !== 12) || ($width !== 12 && ! in_array($adminWidth, [15, 16], true))) continue;
                    $next = [];
                    foreach ($remaining as [$rangeStart, $rangeEnd]) {
                        if ($adminEnd < $rangeStart || $adminStart > $rangeEnd) {
                            $next[] = [$rangeStart, $rangeEnd];
                            continue;
                        }
                        if ($rangeStart < $adminStart) $next[] = [$rangeStart, $adminStart - 1];
                        if ($rangeEnd > $adminEnd) $next[] = [$adminEnd + 1, $rangeEnd];
                    }
                    $remaining = $next;
                    if ($remaining === []) break;
                }

                if ($remaining === []) {
                    DB::table('product_tracking_sales_stock_ranges')->where('id', $row->id)->delete();
                    continue;
                }

                $segments = [];
                foreach ($remaining as [$rangeStart, $rangeEnd]) {
                    $segments[] = [
                        'serial_start' => str_pad((string) $rangeStart, $width, '0', STR_PAD_LEFT),
                        'serial_end' => str_pad((string) $rangeEnd, $width, '0', STR_PAD_LEFT),
                        'qty' => $rangeEnd - $rangeStart + 1,
                    ];
                }
                $firstSegment = array_shift($segments);
                DB::table('product_tracking_sales_stock_ranges')->where('id', $row->id)->update($firstSegment + ['updated_at' => now()]);
                foreach ($segments as $segment) {
                    $copy = (array) $row;
                    unset($copy['id']);
                    DB::table('product_tracking_sales_stock_ranges')->insert(array_merge($copy, $segment, ['created_at' => $row->created_at, 'updated_at' => now()]));
                }
            }

            $remainingCount = DB::table('product_tracking_sales_stock_ranges')->where('upload_id', $uploadId)->count();
            DB::table('product_tracking_sales_stock_uploads')->where('id', $uploadId)->update([
                'row_count' => $remainingCount,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Removed duplicate uploaded ranges cannot be restored without reintroducing duplicates.
    }
};
