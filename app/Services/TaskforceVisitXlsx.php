<?php
namespace App\Services;

use App\Exports\TaskforceVisitWorkbook;
use App\Exports\TaskforceVisitSheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use XMLWriter;
use ZipArchive;

/** Inline-string XML + disk-backed ZIP: memory does not grow with worksheet cells. */
class TaskforceVisitXlsx
{
    private const NS='http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    public function write(TaskforceVisitWorkbook $workbook): string
    {
        $dir=storage_path('app/private/taskforce-exports');
        if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new \RuntimeException('Penyimpanan export tidak tersedia.');
        $path=tempnam($dir,'taskforce-');$parts=[];$success=false;$zip=new ZipArchive();
        try {
            if($zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new \RuntimeException('File export gagal dibuat.');
            $sheets=$workbook->sheets();
            DB::transaction(function()use($sheets,&$parts,$dir,$zip){
                foreach($sheets as$i=>$sheet){$xml=tempnam($dir,'sheet-');$parts[]=$xml;$this->sheet($sheet,$xml);$zip->addFile($xml,'xl/worksheets/sheet'.($i+1).'.xml');}
            });
            $zip->addFromString('[Content_Types].xml',$this->contentTypes(count($sheets)));
            $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml',$this->workbook($sheets));
            $zip->addFromString('xl/_rels/workbook.xml.rels',$this->relationships(count($sheets)));
            $zip->addFromString('xl/styles.xml',$this->styles());
            if(!$zip->close())throw new \RuntimeException('File export gagal diselesaikan.');
            $success=true;return $path;
        }finally{
            if(!$success){try{$zip->close();}catch(\Throwable $ignored){}if(is_file($path))unlink($path);}
            foreach($parts as$part)if(is_file($part))unlink($part);
        }
    }

    private function sheet(TaskforceVisitSheet $sheet,string $path): void
    {
        $x=new XMLWriter();if(!$x->openUri($path))throw new \RuntimeException('File worksheet gagal dibuat.');
        try {
            $x->startDocument('1.0','UTF-8');$x->startElement('worksheet');$x->writeAttribute('xmlns',self::NS);
            $x->startElement('sheetViews');$x->startElement('sheetView');$x->writeAttribute('workbookViewId','0');
            $x->startElement('pane');foreach(['xSplit'=>'2','ySplit'=>'4','topLeftCell'=>'C5','activePane'=>'bottomRight','state'=>'frozen']as$k=>$v)$x->writeAttribute($k,$v);$x->endElement();
            $x->startElement('selection');$x->writeAttribute('pane','bottomRight');$x->writeAttribute('activeCell','C5');$x->writeAttribute('sqref','C5');$x->endElement();$x->endElement();$x->endElement();
            $x->startElement('sheetFormatPr');$x->writeAttribute('defaultRowHeight','18');$x->endElement();
            $x->startElement('cols');foreach($sheet->widths()as$i=>$width){$x->startElement('col');foreach(['min'=>$i+1,'max'=>$i+1,'width'=>$width,'customWidth'=>1]as$k=>$v)$x->writeAttribute($k,(string)$v);$x->endElement();}$x->endElement();
            $x->startElement('sheetData');$row=0;
            foreach($sheet->headings()as$values){$row++;$this->row($x,$row,$values,[], $row===1?1:($row===4?2:0));}
            foreach($sheet->query()->lazy(500)as$record){
                $row++;if($row>1048576)throw ValidationException::withMessages(['visit_from'=>'Riwayat terlalu besar untuk satu sheet. Batasi rentang tanggal visit.']);
                $this->row($x,$row,$sheet->map($record),$sheet->columnFormats());
                if($row%500===0)$x->flush();
            }
            $x->endElement();$last=$this->column(count($sheet->headings()[3]));
            $x->startElement('autoFilter');$x->writeAttribute('ref','A4:'.$last.$row);$x->endElement();
            $x->startElement('mergeCells');$x->writeAttribute('count','3');foreach([1,2,3]as$n){$x->startElement('mergeCell');$x->writeAttribute('ref','A'.$n.':'.$last.$n);$x->endElement();}$x->endElement();
            $x->endElement();$x->endDocument();$x->flush();
        }finally{unset($x);}
    }
    private function row(XMLWriter $x,int $number,array $values,array $formats,int $style=0): void
    {
        $x->startElement('row');$x->writeAttribute('r',(string)$number);
        if($number<=4){$x->writeAttribute('ht',$number===4?'36':'24');$x->writeAttribute('customHeight','1');}
        foreach(array_values($values)as$i=>$value){
            $format=$formats[$i+1]??null;$s=$style?:match($format){'#,##0'=>3,'dd/mm/yyyy'=>4,'mmm yyyy'=>5,'dd/mm/yyyy hh:mm'=>6,default=>0};
            $x->startElement('c');$x->writeAttribute('r',$this->column($i+1).$number);$x->writeAttribute('s',(string)$s);
            if(is_int($value)||is_float($value)){$x->writeAttribute('t','n');$x->writeElement('v',(string)$value);}
            else{$x->writeAttribute('t','inlineStr');$x->startElement('is');$x->startElement('t');$x->writeAttribute('xml:space','preserve');
                $text=preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u','',(string)($value??''));$x->text($text??'');$x->endElement();$x->endElement();}
            $x->endElement();
        }$x->endElement();
    }
    private function column(int $index): string
    {
        $name='';while($index>0){$index--;$name=chr(65+$index%26).$name;$index=intdiv($index,26);}return $name;
    }
    private function workbook(array $sheets): string
    {
        $xml='<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="'.self::NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach($sheets as$i=>$sheet)$xml.='<sheet name="'.htmlspecialchars($sheet->title(),ENT_XML1|ENT_QUOTES,'UTF-8').'" sheetId="'.($i+1).'" r:id="rId'.($i+1).'"/>';
        return $xml.'</sheets></workbook>';
    }
    private function relationships(int $count): string
    {
        $xml='<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for($i=1;$i<=$count;$i++)$xml.='<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        return $xml.'<Relationship Id="rId'.($count+1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }
    private function contentTypes(int $count): string
    {
        $xml='<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for($i=1;$i<=$count;$i++)$xml.='<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        return $xml.'</Types>';
    }
    private function styles(): string
    {
        $xml='<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="'.self::NS.'"><numFmts count="4"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/><numFmt numFmtId="166" formatCode="mmm yyyy"/><numFmt numFmtId="167" formatCode="dd/mm/yyyy hh:mm"/></numFmts><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="15"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFC8102E"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="7">';
        foreach([[0,0,0],[0,1,0],[0,2,2],[164,0,0],[165,0,0],[166,0,0],[167,0,0]]as[$format,$font,$fill])$xml.='<xf numFmtId="'.$format.'" fontId="'.$font.'" fillId="'.$fill.'" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>';
        return $xml.'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
