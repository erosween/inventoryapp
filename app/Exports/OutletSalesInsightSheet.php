<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OutletSalesInsightSheet extends DefaultValueBinder implements FromArray, WithHeadings, WithTitle, WithStyles, WithCustomValueBinder, WithColumnFormatting
{
    private array $rows;
    public function __construct(private readonly array $insight,private readonly string $type)
    {
        $this->rows=$type==='tap'?array_merge($insight['tapRows'],$insight['clusters']):$insight['districtRows'];
        $this->rows[]=$insight['grand'];
    }
    public function title(): string { return $this->type==='tap'?'Summary TAP':'Summary Kecamatan'; }
    public function headings(): array
    {
        $labels=['pv_all'=>'PV All','pv_reg'=>'PV Reguler','pv_byu'=>'PV Byu','sa_all'=>'SA All','sa_simpati'=>'SA Simpati','sa_byu'=>'SA Byu'];
        $head=[$this->type==='tap'?'TAP':'Kecamatan'];
        foreach(['M-1','MTD','MoM','Kontribusi MTD']as$block)foreach(array_merge($this->insight['columns'],['Total'])as$validity)$head[]=$block.' · '.$validity;
        return [[$this->title().' · '.$labels[$this->insight['product']]],
            ['MTD 1–'.$this->insight['cutoff']->format('d/m/Y').' | M-1 1–'.$this->insight['prevEnd']->format('d/m/Y')],
            [$this->insight['outletCount'].' outlet sesuai filter | Kontribusi terhadap '.$this->insight['family'].' All wilayah'], $head];
    }
    public function array(): array
    {
        return array_map(function($area){
            $row=[$area['name']];$cells=$area['values'];
            $cells['Total']=['current'=>array_sum(array_column($cells,'current')),'previous'=>array_sum(array_column($cells,'previous'))];
            foreach(['previous','current','mom','contribution']as$period)foreach(array_merge($this->insight['columns'],['Total'])as$validity){
                $cell=$cells[$validity]??[];$curr=$cell['current']??0;$prev=$cell['previous']??0;
                $row[]=match($period){'mom'=>$prev>0?$curr/$prev-1:'—','contribution'=>$area['denominator']['current']>0?$curr/$area['denominator']['current']:'—',default=>(int)($cell[$period]??0)};
            }
            return $row;
        },$this->rows);
    }
    public function bindValue(Cell $cell,$value): bool
    {
        if($cell->getRow()>=5 && $cell->getColumn()!=='A' && is_numeric($value)){
            $cell->setValueExplicit($value+0,DataType::TYPE_NUMERIC);return true;
        }
        if(is_string($value)){$cell->setValueExplicit($value,DataType::TYPE_STRING);return true;}
        return parent::bindValue($cell,$value);
    }
    public function columnFormats(): array
    {
        $formats=[];$width=count($this->insight['columns'])+1;
        for($i=0;$i<$width*4;$i++)$formats[Coordinate::stringFromColumnIndex($i+2)]=$i<$width*2?'#,##0':($i<$width*3?'+0.0%;-0.0%;0.0%':'0.0%');
        return $formats;
    }
    public function styles(Worksheet $sheet): array
    {
        $last=Coordinate::stringFromColumnIndex(count($this->columnFormats())+1);$end=4+count($this->rows);
        $sheet->freezePane('B5');$sheet->setAutoFilter('A4:'.$last.$end);
        $sheet->getColumnDimension('A')->setWidth(30);
        foreach(array_keys($this->columnFormats())as$column)$sheet->getColumnDimension($column)->setWidth(18);
        $sheet->getStyle('A4:'.$last.'4')->getAlignment()->setWrapText(true);$sheet->getRowDimension(4)->setRowHeight(35);
        foreach($this->rows as$i=>$row)if(!empty($row['cluster']))$sheet->getStyle('A'.($i+5).':'.$last.($i+5))->applyFromArray(['font'=>['bold'=>true],'fill'=>['fillType'=>'solid','startColor'=>['rgb'=>'F1F5F9']]]);
        $sheet->getStyle('A'.$end.':'.$last.$end)->applyFromArray(['font'=>['bold'=>true,'color'=>['rgb'=>'174E95']],'fill'=>['fillType'=>'solid','startColor'=>['rgb'=>'EAF3FF']]]);
        return [1=>['font'=>['bold'=>true,'size'=>15]],2=>['font'=>['color'=>['rgb'=>'667085']]],3=>['font'=>['color'=>['rgb'=>'667085']]],4=>['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>'solid','startColor'=>['rgb'=>'C8102E']]]];
    }
}
