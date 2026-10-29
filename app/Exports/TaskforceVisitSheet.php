<?php
namespace App\Exports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class TaskforceVisitSheet
{
    public function __construct(private readonly mixed $source,private readonly array $headers,private readonly string $type,private readonly Carbon $cutoff,private readonly array $options)
    {}
    public function query() { return clone $this->source; }
    public function title(): string { return $this->type==='summary'?'Ringkasan Outlet':'Riwayat Visit'; }
    private function date($value) { return $value?Date::dateTimeToExcel(Carbon::parse($value)):null; }
    public function map($row): array
    {
        if($this->type==='history')return [(string)$row->id_outlet,$row->nama_outlet,$row->tap,$row->sf,
            $this->date($row->visited_at),$row->username,$row->notes,$row->follow_up,$this->date($row->period_month),$this->date($row->created_at)];
        $cells=$row->cells?json_decode($row->cells,true,512,JSON_THROW_ON_ERROR):[(string)$row->id_outlet];
        $cells=array_slice(array_pad($cells,count($this->headers),''),0,count($this->headers));
        foreach($this->headers as$i=>$header)$cells[$i]=\App\Support\TaskforceCellFormat::forExport($header,$cells[$i]);
        return array_merge($cells,
            [$row->visited?'Sudah visit':'Belum visit',(int)$row->visit_count,$this->date($row->visited_at),$row->notes,$row->follow_up,$row->action_by]);
    }
    public function headings(): array
    {
        $headers=$this->type==='summary'?array_merge($this->headers,['Status Visit','Jumlah Visit','Visit Terakhir','Temuan Terakhir','Tindak Lanjut','Petugas Terakhir'])
            :['ID Outlet','Nama Outlet','TAP','SF','Tanggal Visit','Petugas','Temuan','Tindak Lanjut','Periode Taskforce','Dicatat Pada'];
        return [[$this->title().' · TASKFORCE'],['Periode taskforce: '.$this->cutoff->format('m/Y')],
            ['Tanggal visit: '.($this->options['visit_from']??'Semua').' s/d '.($this->options['visit_to']??'Semua').' | Sesuai filter dan wilayah akses'], $headers];
    }
    public function columnFormats(): array
    {
        if($this->type==='history')return [5=>'dd/mm/yyyy',9=>'mmm yyyy',10=>'dd/mm/yyyy hh:mm'];
        $formats=[count($this->headers)+2=>'#,##0',count($this->headers)+3=>'dd/mm/yyyy'];
        foreach($this->headers as$i=>$header)if(\App\Support\TaskforceCellFormat::isQuantityHeader($header))$formats[$i+1]='#,##0';
        return $formats;
    }
    public function widths(): array
    {
        return array_map(fn($header)=>preg_match('/catatan|temuan|tindak|nama/i',$header)?42:22,$this->headings()[3]);
    }
}
