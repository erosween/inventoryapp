<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SuryaWorkbookExport implements WithMultipleSheets
{
    public function __construct(private readonly Collection $visitRows, private readonly Collection $salesRows)
    {
    }

    public function sheets(): array
    {
        return [
            new SuryaVisitExport($this->visitRows),
            new SuryaSalesProductExport($this->salesRows),
        ];
    }
}
