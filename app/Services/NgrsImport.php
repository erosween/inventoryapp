<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NgrsImport
{
    public const HEADERS=['TANGGAL','RS','TOP ORGANIZATION','TRX','RECH','TAP','CLUSTER'];
    public const TAPS=['BAGAN BATU','BAGAN SIAPI-API','BENGKALIS','DUMAI','DURI','RUPAT','SEI PAKNING','UJUNG TANJUNG'];

    // Simpan baris detail dengan ID upload baru. Perbarui pointer upload aktif per bulan setelah
    // seluruh file lolos validasi.
    public function import(UploadedFile $file, ?int $userId): array
    {
        $lock=Cache::lock('surya-ngrs-import',180);
        if(!$lock->get())$this->fail('Upload NGRS lain masih diproses.');
        $id=(string)Str::uuid();$created=false;$h=null;
        try {
            if(function_exists('set_time_limit'))@set_time_limit(120);
            $h=fopen($file->getRealPath(),'rb');if(!$h)$this->fail('File tidak dapat dibuka.');
            $best=0;$delimiter=',';
            foreach([',',';',"\t"]as$d){rewind($h);$header=fgetcsv($h,0,$d,'"','');if(is_array($header)&&count($header)>$best){$best=count($header);$delimiter=$d;}}
            rewind($h);$header=fgetcsv($h,0,$delimiter,'"','');if(!$header)$this->fail('File kosong.');
            $header[0]=preg_replace('/^\xEF\xBB\xBF/','',(string)$header[0]);
            $header=array_map(fn($v)=>mb_strtoupper(preg_replace('/\s+/u',' ',trim((string)$v))),$header);
            $named=array_values(array_filter($header,fn($v)=>$v!==''));
            if(count($named)!==7||array_diff(self::HEADERS,$named))$this->fail('Header harus TANGGAL,RS,TOP ORGANIZATION,TRX,RECH,TAP,CLUSTER.');
            DB::table('surya_ngrs_uploads')->insert(['id'=>$id,'filename'=>mb_substr(basename($file->getClientOriginalName()),0,255),'status'=>'processing','uploaded_by'=>$userId,'created_at'=>now(),'updated_at'=>now()]);$created=true;
            $batch=[];$months=[];$seen=[];$count=0;$number=1;
            while(($values=fgetcsv($h,0,$delimiter,'"',''))!==false){
                $number++;if(!array_filter($values,fn($v)=>trim((string)$v)!==''))continue;
                if(count($values)!==count($header))$this->fail("Jumlah kolom salah pada baris $number.");
                $row=[];foreach($header as$i=>$key){$value=trim((string)$values[$i]);if(!mb_check_encoding($value,'UTF-8')||str_contains($value,"\0")||mb_strlen($value)>255)$this->fail("Nilai tidak valid pada baris $number.");if($key===''){if($value!=='')$this->fail("Kolom tanpa header terisi pada baris $number.");continue;}$row[$key]=$value;}
                $date=null;foreach(['!d/m/Y','!Y-m-d']as$format){$d=\DateTimeImmutable::createFromFormat($format,$row['TANGGAL']);if($d&&$d->format(substr($format,1))===$row['TANGGAL']){$date=$d->format('Y-m-d');break;}}
                if(!$date||$date<'2026-01-01'||$date>now('Asia/Jakarta')->toDateString())$this->fail("Tanggal baris $number tidak valid. Gunakan DD/MM/YYYY atau YYYY-MM-DD sejak Januari 2026, maksimal hari ini.");
                $rs=self::normalizeRs($row['RS']);if(!$rs)$this->fail("RS baris $number tidak valid. Gunakan nomor utuh, bukan notasi ilmiah; format kolom RS sebagai Text.");
                $tap=mb_strtoupper(preg_replace('/\s+/u',' ',$row['TAP']));$cluster=mb_strtoupper(preg_replace('/\s+/u',' ',$row['CLUSTER']));
                $tap=['BAGAN SIAPI API'=>'BAGAN SIAPI-API','BAGANSIAPIAPI'=>'BAGAN SIAPI-API'][$tap]??$tap;
                if($tap==='INFILTRASI'){
                    $tap=match($cluster){'INF DB'=>'INFILTRASI DUMAI','INF RH'=>'INFILTRASI ROHIL',default=>''};
                }
                if(!in_array($tap,array_merge(self::TAPS,['INFILTRASI DUMAI','INFILTRASI ROHIL']),true)||!in_array($cluster,['DUMAI BENGKALIS','ROKAN HILIR','INF DB','INF RH'],true))$this->fail("TAP/CLUSTER baris $number tidak dikenali. Infiltrasi memakai TAP INFILTRASI dan cluster INF DB atau INF RH.");
                if(($tap==='INFILTRASI DUMAI')!==($cluster==='INF DB')||($tap==='INFILTRASI ROHIL')!==($cluster==='INF RH'))$this->fail("TAP dan cluster infiltrasi tidak cocok pada baris $number.");
                if($row['TOP ORGANIZATION']==='')$this->fail("TOP ORGANIZATION kosong pada baris $number.");
                $trx=$this->whole($row['TRX'],$number,'TRX');$rech=$this->whole($row['RECH'],$number,'RECH');
                $key=hash('sha256',json_encode([$date,$rs,$row['TOP ORGANIZATION'],$tap,$cluster]));
                if(isset($seen[$key]))$this->fail("Baris $number mengulang tanggal, RS, organisasi, TAP dan cluster. Gabungkan menjadi satu baris agar tidak dobel.");$seen[$key]=true;
                $month=substr($date,0,7).'-01';$months[$month]=true;
                $batch[]=['upload_id'=>$id,'month'=>$month,'transaction_date'=>$date,'rs'=>$rs,'organization'=>$row['TOP ORGANIZATION'],'tap'=>$tap,'cluster'=>$cluster,'trx'=>$trx,'rech'=>$rech];
                $count++;if($count>150000)$this->fail('Maksimal 150.000 baris per file. Pisahkan file per bulan.');
                if(count($batch)===500){DB::table('surya_ngrs_transactions')->insert($batch);$batch=[];}
            }
            if(!$count)$this->fail('File tidak berisi transaksi. Data sebelumnya tetap tersimpan.');
            if($batch)DB::table('surya_ngrs_transactions')->insert($batch);
            DB::transaction(function()use($id,$months,$count){
                $rollup=DB::table('surya_ngrs_transactions')->where('upload_id',$id)->select('upload_id','month','rs','tap','cluster')->selectRaw('SUM(trx) AS trx,SUM(rech) AS rech')->groupBy('upload_id','month','rs','tap','cluster');
                DB::table('surya_ngrs_monthly')->insertUsing(['upload_id','month','rs','tap','cluster','trx','rech'],$rollup);
                foreach(array_keys($months)as$month)DB::table('surya_ngrs_months')->updateOrInsert(['month'=>$month],['upload_id'=>$id,'updated_at'=>now()]);
                DB::table('surya_ngrs_uploads')->where('id',$id)->update(['status'=>'complete','row_count'=>$count,'updated_at'=>now()]);
            });
            return ['rows'=>$count,'months'=>count($months)];
        } catch(\Throwable $e){
            if($created){DB::table('surya_ngrs_monthly')->where('upload_id',$id)->delete();DB::table('surya_ngrs_transactions')->where('upload_id',$id)->delete();DB::table('surya_ngrs_uploads')->where('id',$id)->update(['status'=>'failed','updated_at'=>now()]);}
            throw $e;
        } finally {if(is_resource($h))fclose($h);$lock->release();}
    }
    public static function normalizeRs(string $value): ?string
    {
        $value=ltrim(trim($value),"'");if(!preg_match('/^[0-9+ ()-]+$/D',$value))return null;
        $value=preg_replace('/[^0-9]/','',$value);if(str_starts_with($value,'0'))$value='62'.substr($value,1);elseif(str_starts_with($value,'8'))$value='62'.$value;
        return preg_match('/^62[0-9]{7,14}$/D',$value)?$value:null;
    }
    private function whole(string $value,int $line,string $column): int
    {
        if(!preg_match('/^[0-9]+$|^[0-9]{1,3}(?:,[0-9]{3})+$|^[0-9]{1,3}(?:\.[0-9]{3})+$/D',$value))$this->fail("$column baris $line harus angka utuh; pemisah ribuan koma atau titik diperbolehkan.");
        $value=str_replace([',','.'],'',$value);if(strlen(ltrim($value,'0'))>12)$this->fail("$column terlalu besar pada baris $line.");return(int)$value;
    }
    private function fail(string $message): never {throw ValidationException::withMessages(['ngrs_file'=>$message]);}
}
