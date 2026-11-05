<?php
namespace App\Services;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class OutletColumnFilters {
 public const TEXT=['id_outlet','nama_outlet','kecamatan','sf','tap','pjp','kategori_outlet'];
 public const GROUPS=['st_sa','st_pv','cvm','super_seru','comsak','hot_promo','biometrik'];
 public function keys():array { $keys=self::TEXT;foreach(self::GROUPS as $g)foreach(['fm1','m1','mtd','gap','mom_pct']as$p)$keys[]=$g.'_'.$p;return $keys; }
 public function options(Request $r):array {
  $o=$r->validate(['columns'=>'nullable|array|max:42','columns.*'=>'array|min:1|max:100','columns.*.*'=>'nullable|string|max:5000','exclude'=>'nullable|array|max:42','exclude.*'=>'array|min:1|max:100','exclude.*.*'=>'nullable|string|max:5000','sort'=>'nullable|string','direction'=>'nullable|in:asc,desc']);
  foreach(['columns','exclude']as$mode)foreach($o[$mode]??[]as$key=>$values){$this->check($key);$o[$mode][$key]=array_map(fn($v)=>(string)$v,$values);}
  if(!empty($o['sort']))$this->check($o['sort']);return $o;
 }
 public function check(string $key):void {if(!in_array($key,$this->keys(),true))throw ValidationException::withMessages(['column'=>'Kolom tidak valid.']);}
 public function query($base,Carbon $cutoff,array $options,?string $facet=null){
  $needed=array_merge(array_keys($options['columns']??[]),array_keys($options['exclude']??[]),array_filter([$options['sort']??null,$facet]));
  $numeric=array_diff($needed,self::TEXT)!==[];
  $q=$base->select(array_map(fn($c)=>'outlet.'.$c,self::TEXT));
  if($numeric){
   $outletId = DB::raw('CAST(outlet.id_outlet AS '.(DB::getDriverName()==='sqlite'?'TEXT':'CHAR').')');
   $start=$cutoff->copy()->startOfMonth();$prev=$start->copy()->subMonth();$end=$prev->copy()->endOfMonth();$compare=$prev->copy()->day(min($cutoff->day,$end->day));
   $salesNeeded = collect($needed)->contains(fn($key)=>str_starts_with($key,'st_'));
   if ($salesNeeded) {
   $nota=app(OutletNotaSales::class);
   $q->leftJoinSub($nota->totals($start,$cutoff),'k',fn($j)=>$j->on('k.id_outlet','=',$outletId));
   $q->leftJoinSub($nota->totals($prev,$end,null,true),'hf',fn($j)=>$j->on('hf.id_outlet','=',$outletId));
   $q->leftJoinSub($nota->totals($prev,$compare,null,true),'hp',fn($j)=>$j->on('hp.id_outlet','=',$outletId));
   }
   $q->leftJoin('surya_trx_cvm as c','c.id_outlet','=',$outletId)->leftJoin('surya_biometrik as b','b.id_outlet','=',$outletId);
   foreach(self::GROUPS as$g){
    if (str_starts_with($g,'st_') && !$salesNeeded) continue;
    if(str_starts_with($g,'st_')){$p=substr($g,3);$fm="COALESCE(hf.{$p},0)";$m1="COALESCE(hp.{$p},0)";$m="COALESCE(k.$p,0)";}
    else{$column=['super_seru'=>'ss','biometrik'=>'bio'][$g]??$g;$alias=$g==='biometrik'?'b':'c';$marker = DB::getDriverName()==='sqlite' ? "(',' || COALESCE($alias.uploaded_metrics,'') || ',')" : "CONCAT(',',COALESCE($alias.uploaded_metrics,''),',')";
     $available="($alias.uploaded_metrics IS NULL OR $alias.uploaded_metrics='' OR $marker LIKE '%,$column,%')";
     $fm="CASE WHEN $available THEN COALESCE($alias.{$column}_fm1,0) ELSE 0 END";$m1="CASE WHEN $available THEN COALESCE($alias.{$column}_m1,0) ELSE 0 END";$m="CASE WHEN $available THEN COALESCE($alias.{$column}_m,0) ELSE 0 END";}
    $q->selectRaw("$fm AS {$g}_fm1, $m1 AS {$g}_m1, $m AS {$g}_mtd, ($m-$m1) AS {$g}_gap, CASE WHEN $m1=0 THEN NULL ELSE ROUND((1.0*$m/$m1-1)*100,1) END AS {$g}_mom_pct");
   }
  }
  $q=DB::query()->fromSub($q,'detail');
  foreach(['columns','exclude']as$mode)foreach($options[$mode]??[]as$key=>$values){$expr=$this->expression($key);$q->whereRaw($expr.($mode==='columns'?' IN (':' NOT IN (').implode(',',array_fill(0,count($values),'?')).')',$values);}
  return $q;
 }
 public function expression(string $key):string {$this->check($key);return "COALESCE(CAST(`$key` AS ".(DB::getDriverName()==='sqlite'?'TEXT':'CHAR')."),'')";}
 public function order($q,array $options){$sort=$options['sort']??'nama_outlet';$this->check($sort);return $q->orderByRaw("CASE WHEN `$sort` IS NULL THEN 1 ELSE 0 END")->orderBy($sort,$options['direction']??'asc')->orderBy('id_outlet');}
}
