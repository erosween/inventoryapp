<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SuryaTargetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $idsf = trim((string) ($row['sfcode'] ?? $row['idsf'] ?? ''));
            if ($idsf === '') continue;

            foreach ($row as $heading => $value) {
                $key = strtolower(str_replace([' ', '-', '.'], '_', (string) $heading));
                if (! str_starts_with($key, 'target_')) continue;

                [$target, $family, $validity] = array_pad(explode('_', $key, 3), 3, null);
                if (! in_array($family, ['pv', 'sa'], true) || ! $validity) continue;

                $normalizedValidity = strtoupper(str_replace('_', ' ', $validity));
                if ($family === 'pv' && preg_match('/^(\d+)D$/', $normalizedValidity, $match)) {
                    $normalizedValidity = $match[1] . ' HARI';
                }

                DB::table('surya_sf_targets')->updateOrInsert(
                    ['idsf' => $idsf, 'product_family' => strtoupper($family), 'validity' => $normalizedValidity],
                    ['target_qty' => max(0, (int) $value), 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }
}
