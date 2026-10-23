<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SuryaOutletWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $digiposRows,
        private readonly Collection $nonDigiposRows,
    ) {}

    public function sheets(): array
    {
        return [
            new SuryaOutletDetailExport($this->digiposRows),
            new SuryaNonDigiposOutletExport($this->nonDigiposRows),
        ];
    }
}
