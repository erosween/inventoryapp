<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
class NgrsAnnual
{
 public function refresh(int $year):void
 {
  $version=hash('sha256',DB::table('surya_ngrs_months')->whereBetween('month',["$year-01-01","$year-12-01"])->orderBy('month')->pluck('upload_id','month')->toJson());
  $key='surya-ngrs-annual-v1-'.$year;
  if(Cache::get($key)===$version)return;
  Cache::lock($key.'-lock',90)->block(30,function()use($year,$version,$key){
   if(Cache::get($key)===$version)return;
   DB::transaction(function()use($year){
    $q=DB::table('surya_ngrs_monthly as n')->join('surya_ngrs_months as a',fn($j)=>$j->on('a.month','=','n.month')->on('a.upload_id','=','n.upload_id'))->whereBetween('n.month',["$year-01-01","$year-12-01"])->selectRaw('? AS year',[$year])->addSelect('n.rs','n.tap','n.cluster');
    $columns=['year','rs','tap','cluster'];
    foreach(['trx','rech']as$metric){foreach(range(1,12)as$m){$column=$metric.'_m'.$m;$q->selectRaw("SUM(CASE WHEN n.month=? THEN n.$metric ELSE 0 END) AS $column",[sprintf('%04d-%02d-01',$year,$m)]);$columns[]=$column;}$q->selectRaw("SUM(n.$metric) AS {$metric}_total");$columns[]=$metric.'_total';}
    $q->groupBy('n.rs','n.tap','n.cluster');
    DB::table('surya_ngrs_annual')->where('year',$year)->delete();DB::table('surya_ngrs_annual')->insertUsing($columns,$q);
   });
   Cache::put($key,$version,86400);
  });
 }
}
