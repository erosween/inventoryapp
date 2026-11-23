<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Range-based timing; never expands a serial interval into individual SN rows. */
class StockOutletTiming
{
    public function daysToOutlet(Collection $salesRanges, string $product): array
    {
        if ($salesRanges->isEmpty()) return [];

        $owners = $salesRanges->pluck('owner_sf')->filter()->unique()->values()->all();
        $denoms = $salesRanges->pluck('iddenom')->filter()->unique()->values()->all();
        if ($owners === [] || $denoms === []) return [];

        $candidates = [];
        foreach (DB::table('masuksf')->whereIn('idsf', $owners)->whereIn('iddenom', $denoms)
            ->whereNotNull('sn')->whereNotNull('tgl')->orderBy('tgl')->orderBy('idmasuk')
            ->get(['idsf', 'iddenom', 'sn', 'qty', 'tgl']) as $row) {
            $range = $this->parseRange((string) $row->sn, $product);
            $date = $this->date((string) $row->tgl);
            if (! $range || ! $date) continue;
            [$start, $end] = $range;
            if ($start !== $end && $end - $start + 1 !== (int) $row->qty) continue;
            $this->addCandidate($candidates, (string) $row->idsf, (string) $row->iddenom, $start, $end, $date, 0);
        }

        foreach (DB::table('product_tracking_sales_stock_ranges')->whereIn('sfcode', $owners)->whereIn('iddenom', $denoms)
            ->whereNotNull('created_at')->get(['sfcode', 'iddenom', 'serial_start', 'serial_end', 'created_at']) as $row) {
            $range = $this->parseRange((string) $row->serial_start.'-'.(string) $row->serial_end, $product);
            $date = $this->date((string) $row->created_at);
            if (! $range || ! $date) continue;
            [$start, $end] = $range;
            $this->addCandidate($candidates, (string) $row->sfcode, (string) $row->iddenom, $start, $end, $date, 1);
        }

        $origins = [];
        foreach ($candidates as $key => $ranges) {
            usort($ranges, fn ($a, $b) => ($a['priority'] <=> $b['priority'])
                ?: ($a['start'] <=> $b['start']) ?: ($a['end'] <=> $b['end']));
            $accepted = [];
            foreach ($ranges as $range) {
                $cursor = $range['start'];
                $segments = [];
                foreach ($accepted as $existing) {
                    if ($existing['end'] < $cursor) continue;
                    if ($existing['start'] > $range['end']) break;
                    if ($existing['start'] > $cursor) {
                        $segments[] = array_merge($range, ['start' => $cursor, 'end' => min($range['end'], $existing['start'] - 1)]);
                    }
                    $cursor = max($cursor, $existing['end'] + 1);
                    if ($cursor > $range['end']) break;
                }
                if ($cursor <= $range['end']) $segments[] = array_merge($range, ['start' => $cursor, 'end' => $range['end']]);
                if ($segments !== []) {
                    $accepted = array_merge($accepted, $segments);
                    usort($accepted, fn ($a, $b) => $a['start'] <=> $b['start']);
                }
            }
            $origins[$key] = $accepted;
        }

        $totals = [];
        $dateTimestamps = [];
        foreach ($salesRanges as $sale) {
            $saleRange = $this->parseRange((string) $sale->serial_start.'-'.(string) $sale->serial_end, $product);
            $saleDate = $this->date((string) $sale->st_date);
            if (! $saleRange || ! $saleDate) continue;
            $key = $this->key((string) $sale->owner_sf, (string) $sale->iddenom);
            $ranges = $origins[$key] ?? [];
            if ($ranges === []) continue;

            [$saleStart, $saleEnd] = $saleRange;
            $low = 0;
            $high = count($ranges);
            while ($low < $high) {
                $middle = intdiv($low + $high, 2);
                if ($ranges[$middle]['end'] < $saleStart) $low = $middle + 1;
                else $high = $middle;
            }

            $outlet = (string) $sale->id_outlet;
            $totals[$outlet] ??= ['qty' => 0, 'weighted_days' => 0];
            for ($i = $low, $count = count($ranges); $i < $count && $ranges[$i]['start'] <= $saleEnd; $i++) {
                $overlap = min($saleEnd, $ranges[$i]['end']) - max($saleStart, $ranges[$i]['start']) + 1;
                if ($overlap <= 0) continue;
                $dateTimestamps[$saleDate] ??= strtotime($saleDate);
                $dateTimestamps[$ranges[$i]['date']] ??= strtotime($ranges[$i]['date']);
                $days = max(0, intdiv($dateTimestamps[$saleDate] - $dateTimestamps[$ranges[$i]['date']], 86400));
                $totals[$outlet]['qty'] += $overlap;
                $totals[$outlet]['weighted_days'] += $overlap * $days;
            }
        }

        $result = [];
        foreach ($totals as $outlet => $total) {
            if ($total['qty'] > 0) $result[$outlet] = [
                'qty' => (int) $total['qty'],
                'weighted_days' => (int) $total['weighted_days'],
                'average_days' => (int) round($total['weighted_days'] / $total['qty']),
            ];
        }

        return $result;
    }

    private function addCandidate(array &$candidates, string $owner, string $denom, int $start, int $end, string $date, int $priority): void
    {
        $candidates[$this->key($owner, $denom)][] = compact('start', 'end', 'date', 'priority');
    }

    private function parseRange(string $value, string $product): ?array
    {
        $value = trim(ltrim($value, "'\""));
        if (preg_match('/^(\d+)\s*(?:[-–—]\s*|\s+)(\d+)$/u', $value, $match)) {
            [$start, $end] = [$match[1], $match[2]];
        } elseif (preg_match('/^\d+$/D', $value)) {
            $start = $end = $value;
        } else {
            return null;
        }

        if (strlen($start) !== strlen($end) || strcmp($start, $end) > 0
            || ! $this->validSerial($start, $product) || ! $this->validSerial($end, $product)) return null;

        return [(int) $start, (int) $end];
    }

    private function validSerial(string $serial, string $product): bool
    {
        if (! ctype_digit($serial)) return false;
        if ($product === 'PV') return strlen($serial) === 12;
        return (strlen($serial) === 15 && ! str_starts_with($serial, '0'))
            || (strlen($serial) === 16 && str_starts_with($serial, '0'));
    }

    private function date(string $value): ?string
    {
        $date = substr(trim($value), 0, 10);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) && strtotime($date) !== false ? $date : null;
    }

    private function key(string $owner, string $denom): string
    {
        return strtoupper(trim($owner))."\x1f".trim($denom);
    }
}
