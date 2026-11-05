<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
class NgrsAnnual
{
 public function refresh(int $year):void
 {
  $version=hash('sha256',now('Asia/Jakarta')->format('Y-m').'|'.DB::table('surya_ngrs_months')->whereBetween('month',["$year-01-01","$year-12-01"])->orderBy('month')->pluck('upload_id','month')->toJson());
  $key='surya-ngrs-annual-v2-'.$year;
  if(Cache::get($key)===$version)return;
  Cache::lock($key.'-lock',90)->block(30,function()use($year,$version,$key){
   if(Cache::get($key)===$version)return;
   DB::transaction(function()use($year){
    $q=DB::table('surya_ngrs_monthly as n')->join('surya_ngrs_months as a',fn($j)=>$j->on('a.month','=','n.month')->on('a.upload_id','=','n.upload_id'))->whereBetween('n.month',["$year-01-01","$year-12-01"])->selectRaw('? AS year',[$year])->addSelect('n.rs','n.tap','n.cluster');
    $columns=['year','rs','tap','cluster'];
    foreach(['trx','rech']as$metric){foreach(range(1,12)as$m){$column=$metric.'_m'.$m;$q->selectRaw("SUM(CASE WHEN n.month=? THEN n.$metric ELSE 0 END) AS $column",[sprintf('%04d-%02d-01',$year,$m)]);$columns[]=$column;}$q->selectRaw("SUM(n.$metric) AS {$metric}_total");$columns[]=$metric.'_total';}
    $comparison=app(NgrsComparison::class)->plan($year);
    if($comparison&&$comparison['previous_available']){
     $daily=DB::table('surya_ngrs_transactions as t')->join('surya_ngrs_months as a',fn($j)=>$j->on('a.month','=','t.month')->on('a.upload_id','=','t.upload_id'))->where('t.month',$comparison['previous_start'])->whereBetween('t.transaction_date',[$comparison['previous_start'],$comparison['previous_end']])->select('t.rs','t.tap','t.cluster')->selectRaw('SUM(t.trx) AS trx,SUM(t.rech) AS rech')->groupBy('t.rs','t.tap','t.cluster');
     $q->leftJoinSub($daily,'c',fn($j)=>$j->on('c.rs','=','n.rs')->on('c.tap','=','n.tap')->on('c.cluster','=','n.cluster'));
     $q->selectRaw('MAX(COALESCE(c.trx,0)) AS compare_trx,MAX(COALESCE(c.rech,0)) AS compare_rech');
    }else{$q->selectRaw('0 AS compare_trx,0 AS compare_rech');}
    $columns[]='compare_trx';$columns[]='compare_rech';
    $q->groupBy('n.rs','n.tap','n.cluster');
    DB::table('surya_ngrs_annual')->where('year',$year)->delete();DB::table('surya_ngrs_annual')->insertUsing($columns,$q);
   });
   Cache::put($key,$version,86400);
  });
 }
}
