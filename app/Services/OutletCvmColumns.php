<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class OutletCvmColumns extends OutletTransactionColumns
{
    public const IDENTITY = [
        'id_outlet' => 'ID Outlet',
        'nama_outlet' => 'Nama Outlet',
        'tap' => 'TAP',
        'cluster' => 'Cluster',
        'nomor_rs' => 'Nomor RS',
        'kecamatan' => 'Kecamatan',
        'sf' => 'SF',
        'pjp' => 'Hari PJP',
        'kategori_outlet' => 'Kategori Outlet',
    ];

    public function keys(): array
    {
        $identity = array_keys(self::IDENTITY);
        $periods = array_diff(parent::keys(), $identity);
        $keys = $identity;
        foreach (array_keys(OutletCvmImport::METRICS) as $metric) {
            foreach ($periods as $period) {
                $keys[] = $metric.'_'.$period;
            }
        }

        return $keys;
    }

    public function activeKeys(array $options): array
    {
        return array_unique([
            ...array_keys($options['columns'] ?? []),
            ...array_keys($options['exclude'] ?? []),
            ...array_filter([$options['sort'] ?? null]),
        ]);
    }

    public function rows(Builder $profiles, OutletCvmReport $report, Carbon $cutoff, array $keys): Collection
    {
        $requested = array_diff($keys, array_keys(self::IDENTITY));
        $metrics = array_filter(array_keys(OutletCvmImport::METRICS), fn ($metric) => collect($requested)
            ->contains(fn ($key) => str_starts_with($key, $metric.'_')));
        $details = $metrics === [] ? collect() : $report->details($profiles, $cutoff, $metrics);

        return $profiles->get(array_keys(self::IDENTITY))->map(function ($profile) use ($details, $requested) {
            $row = (array) $profile;
            $row['kategori_outlet'] = $row['kategori_outlet'] ?: 'Reguler';
            foreach ($details->get($profile->id_outlet, []) as $metric => $series) {
                foreach ($series as $period => $value) {
                    $column = $metric.'_'.$period;
                    if (in_array($column, $requested, true)) {
                        $row[$column] = $value;
                    }
                }
            }

            return $row;
        });
    }

    protected function defaultSort(): string
    {
        return 'nama_outlet';
    }

    protected function isNumeric(string $column): bool
    {
        return !array_key_exists($column, self::IDENTITY);
    }
}
