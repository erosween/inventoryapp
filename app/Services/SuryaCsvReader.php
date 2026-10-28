<?php
namespace App\Services;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/** CSV records are read directly, without constructing a spreadsheet in memory. */
class SuryaCsvReader
{
 public function inspect(string $path,string $kind):array
 {
  $h=fopen($path,'rb');if(!$h)throw new \RuntimeException('CSV tidak dapat dibuka.');
  try{
   $prefix=fread($h,4);if(str_starts_with($prefix,'PK')||str_starts_with($prefix,"\xD0\xCF")||str_starts_with($prefix,"\xFF\xFE")||str_starts_with($prefix,"\xFE\xFF"))$this->error('Gunakan file CSV UTF-8 asli, bukan file Excel yang hanya diganti ekstensi.');
   $delimiter=',';$best=-1;
   foreach([',',';',"\t"]as$d){rewind($h);$row=fgetcsv($h,0,$d,'"','');$score=is_array($row)?count($row):0;if($score>$best){$best=$score;$delimiter=$d;}}
   rewind($h);$raw=fgetcsv($h,0,$delimiter,'"','');if(!$raw)$this->error('File CSV kosong.');
   $raw[0]=preg_replace('/^\xEF\xBB\xBF/','',(string)$raw[0]);
   if(count($raw)>60)$this->error('Maksimal 60 kolom per file CSV.');
   $headers=[];foreach($raw as$i=>$value)$headers[$this->letter($i)]=trim(preg_replace('/[^a-z0-9]+/','_',strtolower(trim((string)$value))),'_');
   $named=array_values(array_filter($headers));if(count($named)!==count(array_unique($named)))$this->error('Header CSV tidak boleh berulang.');
   $allowed=[];foreach(['ss','hot_promo','cvm','insak','comsak','digital']as$m)foreach(['fm1','m1','m']as$p)$allowed[]=$m.'_'.$p;
   $columns=array_values(array_intersect($headers,$allowed));$offset=ftell($h);$count=1;$first=null;
   while(($row=fgetcsv($h,0,$delimiter,'"',''))!==false){$count++;if($count>500001)$this->error('Maksimal 500.000 baris data per file.');if(count($row)>count($raw))$this->error('Jumlah kolom melebihi header pada baris '.$count.'.');if($first===null&&array_filter($row,fn($v)=>trim((string)$v)!==''))$first=$row;}
   if($first===null)$this->error('Tidak ada baris data di CSV. Data aktif tidak diganti.');
   $type=in_array('id_outlet',$headers,true)&&$columns!==[]?'metrics':(count($raw)===4?($kind==='biometrik'?'bio':($this->looksLikeDate($first[0]??'')?'sales':'bio')):null);
   if($type===null)$this->error('Header tidak dikenali. Gunakan header TRX CVM atau empat kolom Biometrik.');
   if(($type==='bio'?'biometrik':'cvm')!==$kind)$this->error('Nama file tidak sesuai isi. Gunakan cvm untuk TRX CVM dan biometrik untuk Biometrik.');
   if($type==='metrics'&&array_filter($headers,fn($c)=>str_starts_with($c,'bio_')))$this->error('Biometrik harus diupload melalui file biometrik terpisah.');
   return ['reader_type'=>'CsvStream','sheet'=>'CSV','headers'=>$headers,'columns'=>$columns,'type'=>$type,'total_rows'=>$count,'last_column'=>$this->letter(count($raw)-1),'delimiter'=>$delimiter,'csv_offset'=>$offset,'import_date'=>now('Asia/Jakarta')->toDateString()];
  }finally{fclose($h);}
 }
 public function chunk(string $path,array $meta,int $start,int $end):array
 {
  $h=fopen($path,'rb');if(!$h)throw new \RuntimeException('CSV tidak dapat dibuka.');
  try{
   if(fseek($h,(int)$meta['csv_offset'])!==0)throw new \RuntimeException('Posisi CSV tidak dapat dilanjutkan.');
   $rows=[];
   for($i=$start;$i<=$end;$i++){
    $raw=fgetcsv($h,0,$meta['delimiter'],'"','');if($raw===false)$this->error('CSV terpotong pada baris '.$i.'. Upload ulang file.');
    if(count($raw)>count($meta['headers']))$this->error('Jumlah kolom tidak sesuai pada baris '.$i.'.');
    $row=[];foreach($meta['headers']as$letter=>$header){$v=(string)($raw[count($row)]??'');if(!mb_check_encoding($v,'UTF-8'))$v=mb_convert_encoding($v,'UTF-8','Windows-1252');$row[$letter]=trim($v);}
    if(!array_filter($row,fn($v)=>$v!=='')){$rows[$i]=$row;continue;}
    $idColumn=$meta['type']==='sales'?'B':($meta['type']==='bio'?'A':array_search('id_outlet',$meta['headers'],true));
    $id=$row[$idColumn]??'';if(!preg_match('/^[a-zA-Z0-9_-]{1,255}$/D',$id)||preg_match('/^\d+(?:\.\d+)?[eE][+-]?\d+$/D',$id))$this->error('ID outlet tidak valid pada baris '.$i.'. Simpan ID sebagai teks, bukan notasi ilmiah.');
    foreach($row as$letter=>&$v){$header=$meta['headers'][$letter];$quantity=$meta['type']==='bio'?in_array($letter,['B','C','D'],true):($meta['type']==='sales'?in_array($letter,['C','D'],true):in_array($header,$meta['columns'],true));if($quantity)$v=$this->quantity($v,$i);if(($meta['type']==='metrics'&&$header==='update'&&$v!=='')||($meta['type']==='sales'&&$letter==='A'))$v=$this->date($v,$i);}
    unset($v);$rows[$i]=$row;
   }
   return [$rows,ftell($h)];
  }finally{fclose($h);}
 }
 private function quantity(string $value,int $row):string
 {
  if($value==='')return '0';
  if(preg_match('/^-?\d{1,3}(?:([., ])\d{3})(?:\1\d{3})*$/D',$value))$value=str_replace(['.',',',' '],'',$value);
  if(!preg_match('/^-?\d+$/D',$value)||(float)$value>4294967295)$this->error('Qty harus bilangan bulat yang valid pada baris '.$row.'.');
  return $value;
 }
 private function date(string $value,int $row):string
 {
  if(is_numeric($value)&&(float)$value>=20000&&(float)$value<=80000)return ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d');
  foreach(['Y-m-d','d/m/Y','d-m-Y','Y/m/d']as$format){try{$d=Carbon::createFromFormat('!'.$format,$value);if($d&&$d->format($format)===$value)return $d->toDateString();}catch(\Throwable $e){}}
  $this->error('Tanggal tidak valid pada baris '.$row.'. Gunakan YYYY-MM-DD atau DD/MM/YYYY.');
 }
 private function looksLikeDate(string $v):bool{return (is_numeric($v)&&(float)$v>=20000&&(float)$v<=80000)||preg_match('/^\d{1,4}[\/-]\d{1,2}[\/-]\d{1,4}$/D',$v);}
 private function letter(int $index):string{$s='';do{$s=chr(65+$index%26).$s;$index=intdiv($index,26)-1;}while($index>=0);return $s;}
 private function error(string $message):never{throw ValidationException::withMessages(['metrics_file'=>$message]);}
}
