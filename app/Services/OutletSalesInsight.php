<?php
namespace App\Services;
use App\Support\DenomGrouping;
use App\Support\SalesSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OutletSalesInsight
{
 public function data($outlets,Carbon $cutoff,string $product):array
 {
  $family=str_starts_with($product,'sa_')?'SA':'PV';
  $variant=match($product){'pv_reg'=>'REGULER','pv_byu','sa_byu'=>'BYU','sa_simpati'=>'SIMPATI',default=>null};
  $end=$cutoff->copy()->endOfDay();$start=$end->copy()->startOfMonth();$prevStart=$start->copy()->subMonth();$prevEnd=$prevStart->copy()->day(min($end->day,$prevStart->daysInMonth))->endOfDay();
  $master=$outlets->keyBy(fn($o)=>(string)$o->id_outlet);$taps=[];$districts=[];$columns=[];
  foreach($master as$o){$tap=trim((string)$o->tap)?:'Belum dipetakan';$district=trim((string)$o->kecamatan)?:'Belum dipetakan';$key=json_encode([$tap,$district]);
   $taps[$tap]??=$this->area($tap,$tap);$districts[$key]??=$this->area($district,$tap);
  }
  if($master->isNotEmpty())foreach(['keluarsf','mobile_penjualan']as$table){
   $q=DB::table($table.' as t')->join('denom as d','d.iddenom','=','t.iddenom')->whereIn('t.id_outlet',$master->keys()->all())->whereBetween('t.tgl',[$prevStart,$end]);
   if($table==='keluarsf')SalesSource::excludeApprovalCopies($q,'t');else$q->where('t.status','approved');
   DenomGrouping::filterDenoms($q,strtolower($family).'_all','d');
   $data=$q->select('t.id_outlet','d.group_name','d.product_variant')->selectRaw('SUM(CASE WHEN t.tgl BETWEEN ? AND ? THEN t.qty ELSE 0 END) AS current_qty,SUM(CASE WHEN t.tgl BETWEEN ? AND ? THEN t.qty ELSE 0 END) AS previous_qty',[$start,$end,$prevStart,$prevEnd])->groupBy('t.id_outlet','d.group_name','d.product_variant')->get();
   foreach($data as$r){$o=$master->get((string)$r->id_outlet);$tap=trim((string)$o->tap)?:'Belum dipetakan';$district=trim((string)$o->kecamatan)?:'Belum dipetakan';$key=json_encode([$tap,$district]);$g=$this->validity($r->group_name);
    foreach(['current'=>(int)$r->current_qty,'previous'=>(int)$r->previous_qty]as$p=>$value){$taps[$tap]['denominator'][$p]+=$value;$districts[$key]['denominator'][$p]+=$value;
     if($variant===null||$variant===$r->product_variant){$columns[$g]=true;$taps[$tap]['values'][$g][$p]=($taps[$tap]['values'][$g][$p]??0)+$value;$districts[$key]['values'][$g][$p]=($districts[$key]['values'][$g][$p]??0)+$value;}
    }
   }
  }
  $columns=array_keys($columns);usort($columns,fn($a,$b)=>($this->weight($a)<=>$this->weight($b))?:strnatcasecmp($a,$b));
  $tapRows=array_values($taps);usort($tapRows,fn($a,$b)=>strnatcasecmp($a['name'],$b['name']));$districtRows=array_values($districts);usort($districtRows,fn($a,$b)=>strnatcasecmp($a['tap'].' '.$a['name'],$b['tap'].' '.$b['name']));
  $grand=$this->sum($tapRows,'Grand Total');
  $clusters=[];foreach(['Dumai Bengkalis'=>['DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING'],'Rokan Hilir'=>['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG']]as$name=>$members){$rows=array_values(array_filter($tapRows,fn($r)=>in_array($r['tap'],$members,true)));if($rows){$row=$this->sum($rows,$name);$row['cluster']=true;$clusters[]=$row;}}
  return compact('family','product','cutoff','prevEnd','columns','tapRows','districtRows','grand','clusters')+['outletCount'=>$master->count()];
 }
 private function area(string$name,string$tap):array{return ['name'=>$name,'tap'=>$tap,'denominator'=>['current'=>0,'previous'=>0],'values'=>[]];}
 private function sum(array$rows,string$name):array{$total=$this->area($name,'');foreach($rows as$row){foreach(['current','previous']as$p){$total['denominator'][$p]+=$row['denominator'][$p];foreach($row['values']as$g=>$values)$total['values'][$g][$p]=($total['values'][$g][$p]??0)+($values[$p]??0);}}return$total;}
 private function validity(?string$name):string{$name=strtoupper(trim((string)$name));if(preg_match('/^(\d+)\s*(HARI|DAYS?|D)$/D',$name,$m))return (int)$m[1].'D';return$name?:'LAINNYA';}
 private function weight(string$g):int{return preg_match('/^(\d+)D$/D',$g,$m)?(int)$m[1]:1000;}
}
