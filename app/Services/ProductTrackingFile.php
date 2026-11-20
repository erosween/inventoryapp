<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProductTrackingFile
{
    public function headers(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $csvUpload = $file instanceof UploadedFile
            && in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true);
        if ($csvUpload || $this->isCsv($path)) {
            $handle = fopen($path, 'rb');
            if ($handle === false) throw new \RuntimeException('CSV tidak dapat dibuka.');
            try {
                $row = fgetcsv($handle, 0, $this->csvDelimiter($path));
                return $this->headerMap($row ?: []);
            } finally {
                fclose($handle);
            }
        }
        $book = $this->load($path, true);
        try {
            $sheet = $book->getSheet(0);
            $headers = [];
            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn(1));
            for ($column = 1; $column <= $highestColumn; $column++) {
                $value = trim((string) $sheet->getCellByColumnAndRow($column, 1)->getFormattedValue());
                if ($value !== '') $headers[(string) $column] = $value;
            }
            return $headers;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function preview(string $path, array $headers, int $limit = 8): array
    {
        if ($this->isCsv($path)) {
            $rows = [];
            foreach ($this->rows($path, $headers) as $values) {
                $rows[] = $values;
                if (count($rows) >= $limit) break;
            }
            return $rows;
        }
        $book = $this->load($path);
        try {
            $sheet = $book->getSheet(0);
            $rows = [];
            $lastRow = min((int) $sheet->getHighestDataRow(), $limit + 1);
            for ($row = 2; $row <= $lastRow; $row++) {
                $values = [];
                foreach ($headers as $column => $header) {
                    $values[$column] = $this->cellText($sheet->getCellByColumnAndRow((int) $column, $row));
                }
                if (collect($values)->contains(fn ($value) => $value !== '')) $rows[] = $values;
            }
            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function rows(string $path, array $headers): \Generator
    {
        if ($this->isCsv($path)) {
            $handle = fopen($path, 'rb');
            if ($handle === false) throw new \RuntimeException('CSV tidak dapat dibuka.');
            try {
                $delimiter = $this->csvDelimiter($path);
                fgetcsv($handle, 0, $delimiter); // header
                $rowNumber = 1;
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $rowNumber++;
                    $values = [];
                    foreach ($headers as $column => $header) {
                        $index = (int) $column - 1;
                        $values[$column] = trim((string) ($row[$index] ?? ''));
                    }
                    if (collect($values)->contains(fn ($value) => $value !== '')) yield $rowNumber => $values;
                }
            } finally {
                fclose($handle);
            }
            return;
        }
        $book = $this->load($path);
        try {
            $sheet = $book->getSheet(0);
            $lastRow = (int) $sheet->getHighestDataRow();
            for ($row = 2; $row <= $lastRow; $row++) {
                $values = [];
                foreach ($headers as $column => $header) {
                    $cell = $sheet->getCellByColumnAndRow((int) $column, $row);
                    $values[$column] = $this->cellText($cell);
                }
                if (collect($values)->contains(fn ($value) => $value !== '')) yield $row => $values;
            }
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public static function date(string $value): ?string
    {
        if ($value === '') return null;
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd.m.Y'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) return $date->format('Y-m-d');
        }
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        try { return \Carbon\Carbon::parse($value)->toDateString(); } catch (\Throwable) { return null; }
    }

    public static function scanDate(string $value): ?string
    {
        if (! preg_match('/^\d{2}-[A-Za-z]{3}-\d{4}$/', $value)) return null;
        $normalized = strtolower($value);
        $normalized = substr($normalized, 0, 3).ucfirst(substr($normalized, 3, 3)).substr($normalized, 6);
        $date = \DateTime::createFromFormat('!d-M-Y', $normalized);
        $errors = \DateTime::getLastErrors();
        if (! $date || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) return null;

        return $date->format('Y-m-d');
    }

    private function load(string $path, bool $readHeaderOnly = false)
    {
        $reader = IOFactory::createReaderForFile($path);
        if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter($this->csvDelimiter($path));
        }
        $reader->setReadDataOnly(false);
        if ($readHeaderOnly) {
            $reader->setReadFilter(new class implements IReadFilter {
                public function readCell($columnAddress, $row, $worksheetName = ''): bool { return $row === 1; }
            });
        }
        return $reader->load($path);
    }

    private function csvDelimiter(string $path): string
    {
        $handle = fopen($path, 'rb');
        try { $line = (string) fgets($handle); } finally { fclose($handle); }
        $best = ',';
        $count = 0;
        foreach ([',', ';', "\t", '|'] as $delimiter) {
            $columns = count(str_getcsv($line, $delimiter));
            if ($columns > $count) { $best = $delimiter; $count = $columns; }
        }
        return $best;
    }

    private function isCsv(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['csv', 'txt'], true);
    }

    private function headerMap(array $row): array
    {
        $headers = [];
        foreach ($row as $index => $value) {
            $value = trim((string) $value);
            if ($index === 0) $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            if ($value !== '') $headers[(string) ($index + 1)] = $value;
        }
        return $headers;
    }

    private function cellText(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): string
    {
        $value = trim((string) $cell->getFormattedValue());
        if ($value === '' || ! is_numeric($cell->getValue())) return $value;

        // Preserve visible leading zeroes from Excel formats such as 0000000000000000.
        $format = $cell->getStyle()->getNumberFormat()->getFormatCode();
        if (preg_match('/^0+$/', $format) && is_numeric($cell->getValue())) {
            return str_pad((string) (int) $cell->getValue(), strlen($format), '0', STR_PAD_LEFT);
        }
        return $value;
    }
}
