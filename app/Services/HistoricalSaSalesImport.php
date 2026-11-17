<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Imports\HistoricalSaSalesSheet;
use App\Support\DenomGrouping;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class HistoricalSaSalesImport
{
    private const START_DATE = '2026-01-01';
    private const END_DATE = '2026-10-15';

    public function import(UploadedFile $file): array
    {
        $sheet = new HistoricalSaSalesSheet();
        $rows = Excel::toCollection($sheet, $file)->first() ?? collect();
        if ($rows->isEmpty()) {
            $this->fail('File kosong atau baris header tidak terbaca.');
        }

        $columns = $this->columns($rows->first());
        $missing = array_diff(['date', 'sf', 'tap', 'package', 'qty'], array_keys(array_filter($columns)));
        if ($missing) {
            $this->fail('Header wajib: Tanggal Jual, Nama SF, TAP, Paket SA/BYU, dan Qty.');
        }
        if ($rows->count() > 200000) {
            $this->fail('Maksimal 200.000 baris per file.');
        }

        $taps = DB::table('kodetap')->pluck('idtap')->mapWithKeys(fn ($tap) => [$this->key($tap) => trim($tap)])->all();
        $denoms = $this->saDenoms();
        $roster = $this->salesRoster();
        $normalizedRows = [];
        $issues = [];

        foreach ($rows as $index => $row) {
            $values = $row instanceof \Illuminate\Support\Collection ? $row->all() : (array) $row;
            if (collect($values)->every(fn ($value) => $value === null || trim((string) $value) === '')) continue;

            $line = $index + 2;
            try {
                $date = $this->parseDate($values[$columns['date']] ?? null);
                if ($date < self::START_DATE || $date > self::END_DATE) {
                    throw new \InvalidArgumentException('tanggal harus 1 Jan–15 Okt 2026');
                }
                $name = mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) ($values[$columns['sf']] ?? ''))));
                if ($name === '') throw new \InvalidArgumentException('Nama SF kosong');
                if (mb_strlen($name) > 96) throw new \InvalidArgumentException('Nama SF melebihi 96 karakter');

                $tapKey = $this->key($values[$columns['tap']] ?? '');
                if ($tapKey === '' || ! isset($taps[$tapKey])) throw new \InvalidArgumentException('TAP tidak ditemukan di master');
                $tap = $taps[$tapKey];

                $packageKey = $this->key($values[$columns['package']] ?? '');
                if (! isset($denoms[$packageKey])) throw new \InvalidArgumentException('Paket tidak cocok dengan denom SA di master');
                $qty = $this->parseQty($values[$columns['qty']] ?? null);
                if ($qty < 1) throw new \InvalidArgumentException('Qty harus lebih dari 0');

                $sfKey = $this->key($tap).'|'.$this->key($name);
                $syntheticName = 'N/A '.$name;
                $idsf = $roster[$sfKey] ?? $roster[$this->key($tap).'|'.$this->key($syntheticName)] ?? null;
                $normalizedRows[] = [
                    'date' => $date,
                    'tap' => $tap,
                    'sf_name' => $name,
                    'idsf' => $idsf,
                    'synthetic_name' => $syntheticName,
                    'iddenom' => $denoms[$packageKey],
                    'qty' => $qty,
                ];
            } catch (\Throwable $error) {
                $issues[] = 'Baris '.$line.': '.$error->getMessage();
                if (count($issues) >= 10) break;
            }
        }

        if ($issues) $this->fail(implode(' · ', $issues));
        if (! $normalizedRows) $this->fail('Tidak ada baris penjualan yang bisa diimpor.');

        $canonicalRows = collect($normalizedRows)->map(fn ($row) => [
            $row['date'], $row['tap'], $row['sf_name'], $row['iddenom'], $row['qty'],
        ])->sortBy(fn ($row) => implode('|', $row))->values()->all();
        $fingerprint = substr(hash('sha256', json_encode($canonicalRows, JSON_THROW_ON_ERROR)), 0, 20);
        $marker = 'Histori SA 2026-01-01..2026-10-15 #'.$fingerprint;
        if (Schema::hasColumn('keluarsf', 'tambahanket') && DB::table('keluarsf')->where('tambahanket', $marker)->exists()) {
            $this->fail('File ini sudah pernah diimpor. Tidak ada data yang ditambahkan ulang.');
        }

        $result = DB::transaction(function () use ($normalizedRows, $marker) {
            $newSales = [];
            $newSfs = [];
            $now = now();

            foreach ($normalizedRows as $row) {
                $idsf = $row['idsf'];
                if ($idsf === null) {
                    $rosterKey = $this->key($row['tap']).'|'.$this->key($row['synthetic_name']);
                    if (! isset($newSfs[$rosterKey])) {
                        $idsf = $this->newHistoricalSfId();
                        $newSfs[$rosterKey] = ['idsf' => $idsf, 'idtap' => $row['tap'], 'namasf' => $row['synthetic_name']];
                    } else {
                        $idsf = $newSfs[$rosterKey]['idsf'];
                    }
                }

                $sale = [
                    'tgl' => $row['date'].' 12:00:00',
                    'idtap' => $row['tap'],
                    'idsf' => $idsf,
                    'iddenom' => $row['iddenom'],
                    'qty' => $row['qty'],
                    'id_outlet' => null,
                    'tambahanket' => $marker,
                ];
                if (Schema::hasColumn('keluarsf', 'created_at')) $sale['created_at'] = $now;
                if (Schema::hasColumn('keluarsf', 'updated_at')) $sale['updated_at'] = $now;
                $newSales[] = $sale;
            }

            foreach ($newSfs as $sf) {
                $record = ['idsf' => $sf['idsf'], 'idtap' => $sf['idtap'], 'namasf' => $sf['namasf']];
                if (Schema::hasColumn('idsf', 'is_hidden')) $record['is_hidden'] = true;
                if (Schema::hasColumn('idsf', 'login_code')) $record['login_code'] = null;
                if (Schema::hasColumn('idsf', 'password')) $record['password'] = null;
                if (Schema::hasColumn('idsf', 'digipos_id')) $record['digipos_id'] = null;
                if (Schema::hasColumn('idsf', 'created_at')) $record['created_at'] = $now;
                if (Schema::hasColumn('idsf', 'updated_at')) $record['updated_at'] = $now;
                DB::table('idsf')->insert($record);
            }

            foreach (array_chunk($newSales, 500) as $batch) DB::table('keluarsf')->insert($batch);

            return ['rows' => count($newSales), 'sfs' => array_values(array_column($newSfs, 'namasf')), 'qty' => array_sum(array_column($newSales, 'qty'))];
        });

        AuditLogger::log('INSERT', 'Histori Penjualan SA', $fingerprint, null, $result + ['marker' => $marker]);
        return $result;
    }

    private function columns($row): array
    {
        $normalized = [];
        $headings = $row instanceof \Illuminate\Support\Collection ? $row->keys()->all() : array_keys((array) $row);
        foreach ($headings as $heading) {
            $key = preg_replace('/[^a-z0-9]/', '', strtolower((string) $heading));
            if (in_array($key, ['tanggaljual', 'tgljual', 'tanggal', 'tgl'], true)) $normalized['date'] = $heading;
            elseif (in_array($key, ['namasf', 'salesforce', 'sf'], true)) $normalized['sf'] = $heading;
            elseif ($key === 'tap') $normalized['tap'] = $heading;
            elseif (str_starts_with($key, 'paket') || in_array($key, ['denom', 'produk'], true)) $normalized['package'] = $heading;
            elseif (in_array($key, ['qty', 'quantity', 'jumlah'], true)) $normalized['qty'] = $heading;
        }
        return $normalized;
    }

    private function saDenoms(): array
    {
        $lookup = [];
        foreach (DB::table('denom')->get() as $denom) {
            $product = $denom->product ?? DenomGrouping::infer($denom)['product'];
            if (strtoupper((string) $product) !== 'SA') continue;
            $lookup[$this->key($denom->iddenom)] = $denom->iddenom;
            $lookup[$this->key($denom->denom)] = $denom->iddenom;
        }
        return $lookup;
    }

    private function salesRoster(): array
    {
        $roster = [];
        foreach (DB::table('idsf')->get(['idsf', 'idtap', 'namasf']) as $sf) {
            $roster[$this->key($sf->idtap).'|'.$this->key($sf->namasf)] = $sf->idsf;
        }
        return $roster;
    }

    private function parseDate($value): string
    {
        if ($value instanceof DateTimeInterface) return Carbon::instance($value)->toDateString();
        if (is_numeric($value) && (float) $value > 1000) return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        $value = trim((string) $value);
        if ($value === '') throw new \InvalidArgumentException('Tanggal jual kosong');
        foreach (['!d/m/Y', '!d-m-Y', '!Y-m-d', '!d/m/y', '!d-m-y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date && $date->format(substr($format, 1)) === $value) return $date->toDateString();
            } catch (\Throwable) {
            }
        }
        return Carbon::parse($value)->toDateString();
    }

    private function parseQty($value): int
    {
        if (is_numeric($value)) {
            $number = (float) $value;
            if ($number < 0 || floor($number) !== $number) throw new \InvalidArgumentException('Qty bukan bilangan bulat');
            return (int) $number;
        }
        $digits = preg_replace('/[,.\s]/u', '', trim((string) $value));
        if ($digits === '' || ! ctype_digit($digits)) throw new \InvalidArgumentException('Qty tidak valid');
        return (int) $digits;
    }

    private function newHistoricalSfId(): string
    {
        do {
            $id = 'SFH'.strtoupper(Str::random(10));
        } while (DB::table('idsf')->where('idsf', $id)->exists());
        return $id;
    }

    private function key($value): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $value)));
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['historical_sa_file' => $message]);
    }
}
