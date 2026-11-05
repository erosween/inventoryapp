<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
class NgrsComparison
{
 public function plan(int $year):?array
 {
  $today=now('Asia/Jakarta');if($year!==$today->year||$today->month===1)return null;
  $month=$today->copy()->startOfMonth();$upload=DB::table('surya_ngrs_months')->where('month',$month->toDateString())->value('upload_id');if(!$upload)return null;
  $latest=Cache::remember('ngrs-cutoff-'.$upload.'-'.$month->toDateString(),86400,fn()=>DB::table('surya_ngrs_transactions')->where('upload_id',$upload)->where('month',$month->toDateString())->max('transaction_date'));
  if(!$latest)return null;
  $previous=$month->copy()->subMonth();$day=min((int)substr($latest,8,2),$previous->daysInMonth);
  return ['month'=>$today->month,'previous'=>$previous->month,'cutoff'=>$latest,'previous_start'=>$previous->toDateString(),'previous_end'=>$previous->copy()->day($day)->toDateString(),'previous_available'=>DB::table('surya_ngrs_months')->where('month',$previous->toDateString())->exists()];
 }
}
