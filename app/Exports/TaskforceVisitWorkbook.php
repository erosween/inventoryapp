<?php
namespace App\Exports;

use Carbon\Carbon;

/** Query-backed sheets consumed by the bounded-memory XLSX writer. */
class TaskforceVisitWorkbook
{
    public function __construct(private readonly mixed $summary,private readonly mixed $history,private readonly array $headers,private readonly Carbon $cutoff,private readonly array $options) {}
    public function sheets(): array
    {
        return [new TaskforceVisitSheet($this->summary,$this->headers,'summary',$this->cutoff,$this->options),
            new TaskforceVisitSheet($this->history,[],'history',$this->cutoff,$this->options)];
    }
}
