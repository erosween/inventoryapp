<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SuryaMetricsImport
{
    public const CHUNK_SIZE = 1000;
    private const METRICS = ['ss', 'hot_promo', 'cvm', 'insak', 'comsak', 'digital'];

    public function inspect(string $path, ?string $filename = null): array
    {
        $kind = $filename === null ? null : $this->fileKind($filename);
        if ($filename !== null) {
            if (strtolower(pathinfo($filename,PATHINFO_EXTENSION)) !== 'csv') throw ValidationException::withMessages(['metrics_file'=>'Upload TRX CVM dan Biometrik sekarang wajib CSV. Di Excel pilih Save As → CSV UTF-8 (.csv).']);
            return app(SuryaCsvReader::class)->inspect($path,$kind);
        }
        $readerType = IOFactory::identify($path);
        $reader = $this->reader($path, 1, 30, $readerType);
        $info = $reader->listWorksheetInfo($path);
        $book = $reader->load($path);
        try {
            $sheet = $book->getActiveSheet();
            $sheetInfo = collect($info)->firstWhere('worksheetName', $sheet->getTitle());
            $lastColumn = $sheetInfo['lastColumnLetter'] ?? $sheet->getHighestDataColumn();
            $rows = $this->rows($sheet, 1, min(30, $sheetInfo['totalRows'] ?? 30), $lastColumn);
            $header = $rows[1] ?? [];
            $headers = array_map(function ($value) {
                return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $value))), '_');
            }, $header);
            $columns = array_values(array_intersect(array_values($headers), $this->metricColumns()));
            $named = in_array('id_outlet', $headers, true) && $columns !== [];
            $first = collect(array_slice($rows, 1))->first(fn ($row) => trim((string) ($row['A'] ?? '')) !== '');
            $fourColumns = count(array_filter($header, fn ($value) => trim((string) $value) !== '')) === 4;
            $type = $named ? 'metrics' : ($fourColumns
                ? ($this->parseUploadedSalesDate($first['A'] ?? null) ? 'sales' : 'bio')
                : null);
            if ($type === null) {
                throw ValidationException::withMessages(['metrics_file' => 'Header tidak dikenali. Gunakan ID outlet dan kolom transaksi, atau format empat kolom historis SA/PV atau biometrik.']);
            }
            if ($kind !== null) {
                $expected = $type === 'bio' ? 'biometrik' : 'cvm';
                if ($kind !== $expected) throw ValidationException::withMessages(['metrics_file' => 'Nama file tidak sesuai isi. Gunakan cvm untuk TRX CVM dan biometrik untuk Biometrik.']);
                if ($type === 'metrics' && array_filter($headers, fn ($column) => str_starts_with($column, 'bio_'))) throw ValidationException::withMessages(['metrics_file' => 'Kolom Biometrik harus diupload melalui file biometrik terpisah.']);
            }
            return [
                'sheet' => $sheet->getTitle(), 'reader_type' => $readerType, 'headers' => $headers, 'columns' => $columns,
                'type' => $type, 'total_rows' => (int) ($sheetInfo['totalRows'] ?? $sheet->getHighestDataRow()),
                'last_column' => $lastColumn, 'import_date' => now('Asia/Jakarta')->toDateString(),
            ];
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function importChunk(string $path, array $meta, int $start): array
    {
        $end = min($start + self::CHUNK_SIZE - 1, $meta['total_rows']);
        if ($end < $start) {
            return ['imported' => 0, 'skipped' => 0, 'next_row' => $start];
        }
        $csvOffset = null;
        if (($meta['reader_type']??'') === 'CsvStream') {
            [$rows,$csvOffset] = app(SuryaCsvReader::class)->chunk($path,$meta,$start,$end);
        } else {
        $reader = $this->reader($path, $start, $end, $meta['reader_type']);
        $reader->setLoadSheetsOnly($meta['sheet']);
        $book = $reader->load($path);
        try {
            $rows = $this->rows($book->getSheetByName($meta['sheet']), $start, $end, $meta['last_column']);
        } finally {
            $book->disconnectWorksheets();
        }
        }
        $batch = [];
        $imported = $skipped = 0;
        $now = now();
        foreach ($rows as $row) {
            if ($meta['type'] === 'sales') {
                $date = $this->parseUploadedSalesDate($row['A'] ?? null);
                $id = trim((string) ($row['B'] ?? ''));
                $values = ['sa' => max(0, (int) ($row['C'] ?? 0)), 'pv' => max(0, (int) ($row['D'] ?? 0))];
            } elseif ($meta['type'] === 'bio') {
                $date = $meta['import_date'];
                $id = trim((string) ($row['A'] ?? ''));
                $values = ['bio_fm1' => max(0, (int) ($row['B'] ?? 0)), 'bio_m1' => max(0, (int) ($row['C'] ?? 0)), 'bio_m' => max(0, (int) ($row['D'] ?? 0))];
            } else {
                $data = [];
                foreach ($meta['headers'] as $letter => $name) {
                    if ($name !== '') $data[$name] = $row[$letter] ?? null;
                }
                $date = empty($data['update']) ? $meta['import_date'] : $this->parseMetricsDate($data['update']);
                $id = trim((string) ($data['id_outlet'] ?? ''));
                $values = [];
                foreach ($meta['columns'] as $column) {
                    $values[$column] = max(0, (int) ($data[$column] ?? 0));
                }
            }
            if ($id === '' || $date === null) {
                $skipped++;
                continue;
            }
            $dateColumn = $meta['type'] === 'sales' ? 'transaction_date' : 'metric_date';
            $batch[isset($meta['snapshot_id']) ? $id : $date.'|'.$id] = array_merge($values, [
                'id_outlet' => $id, $dateColumn => $date, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $imported++;
        }
        if (isset($meta['snapshot_id'])) {
            $staged = [];
            foreach ($batch as $values) {
                $values['created_at'] = $now->toDateTimeString();
                $values['updated_at'] = $now->toDateTimeString();
                $values['source_updated_at'] = $values['metric_date'];
                $values['uploaded_metrics'] = $meta['type'] === 'bio' ? 'bio' : implode(',', self::METRICS);
                $staged[$values['id_outlet']] = ['upload_id'=>$meta['snapshot_id'], 'id_outlet'=>$values['id_outlet'], 'data'=>json_encode($values, JSON_THROW_ON_ERROR)];
            }
            foreach (array_chunk(array_values($staged), 100) as $chunk) DB::table('surya_transaction_staging')->upsert($chunk, ['upload_id','id_outlet'], ['data']);
            return ['imported'=>$imported, 'skipped'=>$skipped, 'next_row'=>$end+1, 'csv_offset'=>$csvOffset];
        }
        $targetTable = $meta['type'] === 'bio' ? 'surya_biometrik' : 'surya_trx_cvm';
        if ($batch !== []) {
            DB::transaction(function () use ($batch, $meta, $targetTable) {
                if ($meta['type'] === 'sales') {
                    foreach (array_chunk(array_values($batch), 500) as $chunk) {
                        DB::table('surya_outlet_sales_history')->upsert($chunk, ['transaction_date', 'id_outlet'], ['sa', 'pv', 'updated_at']);
                    }
                    return;
                }
                // One lookup per batch; partial uploads must preserve other KPI values and markers.
                $existing = DB::table($targetTable)->where(function ($query) use ($batch) {
                    foreach (collect($batch)->groupBy('metric_date') as $date => $records) {
                        $query->orWhere(fn ($q) => $q->where('metric_date', $date)->whereIn('id_outlet', $records->pluck('id_outlet')));
                    }
                })->lockForUpdate()->get()->keyBy(fn ($row) => $row->metric_date.'|'.$row->id_outlet);
                $metrics = $meta['type'] === 'bio' ? ['bio'] : array_values(array_filter(self::METRICS, function ($metric) use ($meta) {
                    return array_intersect($meta['columns'], [$metric.'_fm1', $metric.'_m1', $metric.'_m']) !== [];
                }));
                foreach ($batch as $key => &$values) {
                    $values['source_updated_at'] = $values['metric_date'];
                    $values['uploaded_metrics'] = $this->mergeUploadedMetrics($existing->get($key), $metrics);
                }
                unset($values);
                $updates = array_merge($meta['type'] === 'bio' ? ['bio_fm1', 'bio_m1', 'bio_m'] : $meta['columns'],
                    ['source_updated_at', 'uploaded_metrics', 'updated_at']);
                foreach (array_chunk(array_values($batch), 500) as $chunk) {
                    DB::table($targetTable)->upsert($chunk, ['id_outlet', 'metric_date'], $updates);
                }
            }, 3);
        }
        return ['imported' => $imported, 'skipped' => $skipped, 'next_row' => $end + 1, 'csv_offset'=>$csvOffset];
    }

    public function start(UploadedFile $file, int $owner): array
    {
        $id = (string) Str::uuid();
        $directory = 'surya-metric-imports/'.$id;
        $disk = Storage::disk('local');
        $path = $file->storeAs($directory, 'source.'.$file->getClientOriginalExtension(), 'local');
        try {
            $meta = array_merge($this->inspect($disk->path($path), $file->getClientOriginalName()), [
                'id' => $id, 'owner' => $owner, 'path' => $path,
                'next_row' => 2, 'imported' => 0, 'skipped' => 0, 'status' => 'processing',
            ]);
            if ($meta['type'] !== 'sales') {
                $meta['snapshot_id'] = DB::table('surya_transaction_uploads')->insertGetId(['kind'=>$meta['type']==='bio'?'biometrik':'cvm','created_at'=>now(),'updated_at'=>now()]);
            }
            if (! $disk->put($directory.'/state.json', json_encode($meta, JSON_THROW_ON_ERROR))) {
                throw new \RuntimeException('Upload progress could not be saved.');
            }
            return $this->publicState($meta);
        } catch (\Throwable $e) {
            $disk->deleteDirectory($directory);
            throw $e;
        }
    }

    public function advance(string $id, int $owner, ?int $expectedRow, bool $cancel = false): array
    {
        $disk = Storage::disk('local');
        $directory = 'surya-metric-imports/'.$id;
        abort_unless($disk->exists($directory.'/state.json'), 404, 'Upload tidak ditemukan. Silakan upload ulang.');
        $lock = fopen($disk->path($directory.'/process.lock'), 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) fclose($lock);
            abort(409, 'Batch masih diproses. Coba lanjutkan sebentar lagi.');
        }
        try {
            $meta = json_decode($disk->get($directory.'/state.json'), true, 512, JSON_THROW_ON_ERROR);
            abort_unless((int) $meta['owner'] === $owner, 403);
            if ($cancel && $meta['status'] === 'processing') {
                $meta['status'] = 'cancelled';
                if (isset($meta['snapshot_id'])) {
                    DB::table('surya_transaction_staging')->where('upload_id',$meta['snapshot_id'])->delete();
                    DB::table('surya_transaction_uploads')->where('id',$meta['snapshot_id'])->update(['status'=>'cancelled','updated_at'=>now()]);
                }
                $disk->delete($meta['path']);
                if (! $disk->put($directory.'/state.json', json_encode($meta, JSON_THROW_ON_ERROR))) {
                    throw new \RuntimeException('Upload cancellation could not be saved.');
                }
            }
            // Null means status only. A stale cursor returns progress without advancing a second batch.
            if ($expectedRow === null || $expectedRow !== $meta['next_row'] || $meta['status'] !== 'processing') {
                return $this->publicState($meta);
            }
            if (($meta['reader_type']??'') !== 'CsvStream') throw ValidationException::withMessages(['metrics_file'=>'Pilih File Lain, lalu upload ulang sebagai CSV UTF-8 agar proses lebih ringan.']);
            if ($meta['type'] !== 'sales' && !isset($meta['snapshot_id'])) throw ValidationException::withMessages(['metrics_file'=>'Aturan upload berubah. Pilih dan upload ulang file cvm atau biometrik agar seluruh data diganti dengan aman.']);
            $result = $this->importChunk($disk->path($meta['path']), $meta, $meta['next_row']);
            $meta['next_row'] = $result['next_row'];
            if (isset($result['csv_offset'])) $meta['csv_offset'] = $result['csv_offset'];
            $meta['imported'] += $result['imported'];
            $meta['skipped'] += $result['skipped'];
            if ($meta['next_row'] > $meta['total_rows']) {
                if (isset($meta['snapshot_id'])) $this->publishSnapshot($meta);
                $meta['status'] = 'complete';
            }
            // Rename prevents a reader observing partially written metadata after an interrupted write.
            if (! $disk->put($directory.'/state.tmp', json_encode($meta, JSON_THROW_ON_ERROR))
                || ! $disk->move($directory.'/state.tmp', $directory.'/state.json')) {
                throw new \RuntimeException('Upload progress could not be saved.');
            }
            if ($meta['status'] === 'complete') $disk->delete($meta['path']);
            return $this->publicState($meta);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function publishSnapshot(array $meta): void
    {
        $kind = $meta['type'] === 'bio' ? 'biometrik' : 'cvm';
        $table = $kind === 'biometrik' ? 'surya_biometrik' : 'surya_trx_cvm';
        DB::transaction(function () use ($meta, $kind, $table) {
            $current = DB::table('surya_transaction_current')->where('kind',$kind)->lockForUpdate()->first();
            if ((int)$current->upload_id >= (int)$meta['snapshot_id']) return;
            if (!DB::table('surya_transaction_staging')->where('upload_id',$meta['snapshot_id'])->exists()) throw ValidationException::withMessages(['metrics_file'=>'Tidak ada data outlet valid. Data sebelumnya tetap digunakan.']);
            DB::table($table)->delete();
            DB::table('surya_transaction_staging')->where('upload_id',$meta['snapshot_id'])->orderBy('id')->chunkById(100,function($rows)use($table){
                $values = $rows->map(fn($r)=>json_decode($r->data,true,512,JSON_THROW_ON_ERROR))->all();
                DB::table($table)->insert($values);
            });
            DB::table('surya_transaction_current')->where('kind',$kind)->update(['upload_id'=>$meta['snapshot_id']]);
            DB::table('surya_transaction_uploads')->where('id',$meta['snapshot_id'])->update(['status'=>'complete','updated_at'=>now()]);
            DB::table('surya_transaction_staging')->where('upload_id',$meta['snapshot_id'])->delete();
        });
    }

    public function fileKind(string $filename): string
    {
        $stem = strtolower(pathinfo(basename($filename), PATHINFO_FILENAME));
        $cvm = str_contains($stem, 'cvm');
        $bio = str_contains($stem, 'biometrik');
        if ($cvm === $bio) throw ValidationException::withMessages(['metrics_file' => 'Nama file wajib mengandung salah satu: cvm atau biometrik. Contoh: trx_cvm.csv atau biometrik.csv.']);
        return $bio ? 'biometrik' : 'cvm';
    }

    private function publicState(array $meta): array
    {
        return array_intersect_key($meta, array_flip(['id', 'next_row', 'total_rows', 'imported', 'skipped', 'status', 'type']));
    }

    private function reader(string $path, int $start, int $end, string $type)
    {
        $reader = IOFactory::createReader($type);
        // Keep formulas as cells so their cached Excel results can be used without recalculating unloaded rows.
        $reader->setReadDataOnly(false);
        $reader->setReadEmptyCells(false);
        $reader->setReadFilter(new class($start, $end) implements IReadFilter {
            public function __construct(private int $start, private int $end) {}
            public function readCell($columnAddress, $row, $worksheetName = '')
            {
                return $row >= $this->start && $row <= $this->end;
            }
        });
        return $reader;
    }

    private function rows($sheet, int $start, int $end, string $lastColumn): array
    {
        $rows = $sheet->rangeToArray("A{$start}:{$lastColumn}{$end}", null, false, false, true);
        foreach ($rows as $number => &$row) {
            foreach ($row as $letter => &$value) {
                if (is_string($value) && str_starts_with($value, '=')) {
                    $cell = $sheet->getCell($letter.$number);
                    if ($cell->isFormula()) {
                        $value = $cell->getOldCalculatedValue();
                        if ($value === null) {
                            throw ValidationException::withMessages(['metrics_file' => "Rumus di {$letter}{$number} belum memiliki hasil tersimpan. Buka dan simpan ulang file di Excel atau ubah rumus menjadi nilai."]);
                        }
                    }
                }
            }
            unset($value);
        }
        unset($row);
        return $rows;
    }

    private function metricColumns(): array
    {
        $columns = [];
        foreach (self::METRICS as $metric) {
            foreach (['fm1', 'm1', 'm'] as $period) $columns[] = $metric.'_'.$period;
        }
        return $columns;
    }

    private function mergeUploadedMetrics(?object $existing, array $metrics): string
    {
        $metricColumns = [
            'ss' => ['ss_fm1', 'ss_m1', 'ss_m'],
            'hot_promo' => ['hot_promo_fm1', 'hot_promo_m1', 'hot_promo_m'],
            'cvm' => ['cvm_fm1', 'cvm_m1', 'cvm_m'],
            'insak' => ['insak_fm1', 'insak_m1', 'insak_m'],
            'comsak' => ['comsak_fm1', 'comsak_m1', 'comsak_m'],
            'digital' => ['digital_fm1', 'digital_m1', 'digital_m'],
            'bio' => ['bio_fm1', 'bio_m1', 'bio_m'],
        ];
        $existingMetrics = collect(explode(',', (string) ($existing->uploaded_metrics ?? '')))
            ->map(fn ($metric) => trim((string) $metric))
            ->filter()
            ->unique();

        // Migrasi awal menandai row nol yang ambigu sebagai seluruh KPI. Saat file
        // biometrik masuk ke row tersebut, marker legacy itu membuat Detail Outlet
        // menganggap nilai transaksi nol sebagai upload terbaru dan menutupi histori.
        // Marker eksplisit parsial tetap dipertahankan, sedangkan marker kosong/penuh
        // dinormalisasi dari kolom yang benar-benar berisi data sebelum KPI baru digabung.
        $allMetrics = collect(array_keys($metricColumns));
        if ($existing && ($existingMetrics->isEmpty() || $allMetrics->diff($existingMetrics)->isEmpty())) {
            $existingMetrics = $allMetrics->filter(function ($metric) use ($existing, $metricColumns) {
                return collect($metricColumns[$metric])->contains(
                    fn ($column) => (int) ($existing->{$column} ?? 0) !== 0
                );
            });
        }

        return $existingMetrics
            ->concat($metrics)
            ->map(fn ($metric) => trim((string) $metric))
            ->filter(fn ($metric) => array_key_exists($metric, $metricColumns))
            ->unique()
            ->implode(',');
    }

    private function parseUploadedSalesDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial < 20000 || $serial > 80000) {
                return null;
            }

            try {
                return ExcelDate::excelToDateTimeObject($serial)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $text = trim((string) $value);
        if (! preg_match('/^\d{1,4}[\/-]\d{1,2}[\/-]\d{1,4}$/', $text)) {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $text);
                if ($date && $date->format($format) === $text) {
                    return $date->toDateString();
                }
            } catch (\Throwable $e) {
                // Coba format tanggal berikutnya.
            }
        }

        return null;
    }

    private function parseMetricsDate($value): string
    {
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // Fallback to the application date below.
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable $e) {
            return now('Asia/Jakarta')->toDateString();
        }
    }


}

