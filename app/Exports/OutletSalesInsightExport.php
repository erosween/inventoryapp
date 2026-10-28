<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OutletSalesInsightExport implements WithMultipleSheets
{
    public function __construct(private readonly array $insight) {}
    public function sheets(): array
    {
        return [new OutletSalesInsightSheet($this->insight,'tap'),new OutletSalesInsightSheet($this->insight,'district')];
    }
}
