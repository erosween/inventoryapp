<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SfMasterExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [
            $row->idsf,
            $row->namasf,
            $row->idtap,
            $row->login_code,
        ]);
    }

    public function headings(): array
    {
        return ['SF Code', 'Nama SF', 'TAP', 'Login Code'];
    }
}
