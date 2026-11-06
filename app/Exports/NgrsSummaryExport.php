<?php
namespace App\Exports;

use App\Support\NgrsFormat;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NgrsSummaryExport extends DefaultValueBinder implements FromArray,WithHeadings,WithTitle,WithStyles,WithCustomValueBinder,WithStrictNullComparison
{
    private array $rows=[];
    private array $columns=[];
    private int $tapCount;
    public function __construct(private array $summary,private array $days,private ?array $comparison,private int $year,private string $period,private string $metric)
    {
        $names=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
        foreach(range(1,12)as$m){
            $this->columns[]=['kind'=>'month','month'=>$m,'label'=>$names[$m-1]];
            if($comparison&&$m===$comparison['previous'])$this->columns[]=['kind'=>'compare','label'=>$names[$m-1].'-MoM (1-'.(int)substr($comparison['previous_end'],8,2).')'];
        }
        $this->columns[]=['kind'=>'total','label'=>$period==='average'?'Rata-rata / hari':'Total'];
        foreach(range(2,12)as$m){$this->columns[]=['kind'=>'gap','month'=>$m,'label'=>'GAP '.$names[$m-1]];$this->columns[]=['kind'=>'percent','month'=>$m,'label'=>'MoM % '.$names[$m-1]];}
        foreach($summary['rows']as$row)$this->rows[]=['kind'=>'tap','data'=>$row];$this->tapCount=count($this->rows);
        foreach($summary['clusters']as$row)$this->rows[]=['kind'=>'cluster','data'=>$row];$this->rows[]=['kind'=>'grand','data'=>$summary['grand']];
    }
    public function title():string {return 'Rekap TAP NGRS';}
    public function headings():array
    {
        $label=$this->metric==='rech'?'Recharge (Rp)':'Transaksi';
        $cutoff=$this->comparison?'Cutoff '.date('d/m/Y',strtotime($this->comparison['cutoff'])).' | Pembanding 1-'.date('d/m/Y',strtotime($this->comparison['previous_end'])):'Bulan yang sudah selesai dibandingkan full bulan sebelumnya.';
        return [['Rekap TAP NGRS '.$this->year.' · '.$label.' · '.($this->period==='average'?'Average / hari':'Monthly')],[$cutoff],['Sesuai wilayah akses dan filter TAP / pencarian.'],['TAP',...array_column($this->columns,'label')]];
    }
    private function quantity(int $trx,int $recharge,int $days):int|float|null
    {
        if($days<1)return null;
        if($this->metric==='rech')return $this->period==='average'?$recharge/$days:$recharge;
        return NgrsFormat::numericValue($trx,$recharge,$days,$this->period,$this->metric);
    }
    private function value(array $row,array $column):int|float|null
    {
        $kind=$column['kind'];$m=$column['month']??0;
        $compareDays=$this->comparison&&$this->comparison['previous_available']?(int)substr($this->comparison['previous_end'],8,2):0;
        if($kind==='compare')return $this->quantity($row['compare_trx']??0,$row['compare_rech']??0,$compareDays);
        if($kind==='total')return $this->quantity($row['total'],$row['total_recharge']??0,array_sum($this->days));
        if($kind==='month')return $this->quantity($row['months'][$m]??0,$row['recharge'][$m]??0,$this->days[$m]??0);
        $fair=$this->comparison&&$this->comparison['month']===$m;
        if($kind==='gap'&&$this->metric==='rech'){
            $current=$this->quantity(0,$row['recharge'][$m]??0,$this->days[$m]??0);
            $previous=$this->quantity(0,$fair?($row['compare_rech']??0):($row['recharge'][$m-1]??0),$fair?$compareDays:($this->days[$m-1]??0));
            return $current===null||$previous===null?null:$current-$previous;
        }
        $method=$kind==='percent'?'percent':'gap';$value=NgrsFormat::$method($row['months'][$m]??0,$row['recharge'][$m]??0,$this->days[$m]??0,
            $fair?($row['compare_trx']??0):($row['months'][$m-1]??0),$fair?($row['compare_rech']??0):($row['recharge'][$m-1]??0),$fair?$compareDays:($this->days[$m-1]??0),$this->period,$this->metric)['value'];return $kind==='percent'&&$value!==null?$value/100:$value;
    }
    public function array():array
    {
        return array_map(function($entry){$row=$entry['data'];$values=[$entry['kind']==='grand'?'GRAND TOTAL':($entry['kind']==='cluster'?'CLUSTER · ':'').$row['tap']];foreach($this->columns as$column)$values[]=$this->value($row,$column)??'—';return $values;},$this->rows);
    }
    public function bindValue(Cell $cell,$value):bool
    {
        if($cell->getRow()>=5&&$cell->getColumn()!=='A'&&is_numeric($value)){$cell->setValueExplicit($value+0,DataType::TYPE_NUMERIC);return true;}
        if(is_string($value)){$cell->setValueExplicit($value,DataType::TYPE_STRING);return true;}
        return parent::bindValue($cell,$value);
    }
    public function styles(Worksheet $sheet):array
    {
        $last=Coordinate::stringFromColumnIndex(count($this->columns)+1);$end=4+count($this->rows);
        $sheet->freezePane('B5');$sheet->setAutoFilter('A4:'.$last.(4+$this->tapCount));$sheet->getColumnDimension('A')->setWidth(29);
        $decimal=$this->metric==='rech'&&$this->period==='average';$format=$decimal?'#,##0.00':'#,##0';$gapFormat=$format;
        $sheet->getStyle('B5:'.$last.$end)->getNumberFormat()->setFormatCode($format);
        $sheet->getStyle('A4:'.$last.'4')->getAlignment()->setWrapText(true);$sheet->getRowDimension(4)->setRowHeight(34);
        foreach($this->columns as$i=>$column)$sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i+2))->setWidth($this->metric==='rech'?24:($column['kind']==='compare'?22:17));
        foreach($this->rows as$i=>$entry){
            $line=$i+5;$range='A'.$line.':'.$last.$line;
            if($entry['kind']!=='tap')$sheet->getStyle($range)->applyFromArray(['font'=>['bold'=>true],'fill'=>['fillType'=>'solid','startColor'=>['rgb'=>$entry['kind']==='grand'?'E7F2FF':'F2F5F9']]]);
            foreach($this->columns as$j=>$column){$coordinate=Coordinate::stringFromColumnIndex($j+2).$line;$value=$this->value($entry['data'],$column);
                if($column['kind']==='compare')$sheet->getStyle($coordinate)->getFill()->setFillType('solid')->getStartColor()->setRGB('EFF8FF');
                if(!in_array($column['kind'],['gap','percent'],true)||$value===null)continue;
                $sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode($column['kind']==='percent'?'"▲ +"0.0%;"▼ -"0.0%;0.0%':$gapFormat);$sheet->getStyle($coordinate)->getFont()->setBold(true)->getColor()->setRGB($value>0?'12805C':($value<0?'D92D20':'172033'));
                if($value==0)$sheet->getStyle($coordinate)->getFill()->setFillType('solid')->getStartColor()->setRGB('EEF2F6');
            }
        }
        return [1=>['font'=>['bold'=>true,'size'=>15]],2=>['font'=>['color'=>['rgb'=>'667085']]],3=>['font'=>['color'=>['rgb'=>'667085']]],4=>['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>'solid','startColor'=>['rgb'=>'C8102E']]]];
    }
}
