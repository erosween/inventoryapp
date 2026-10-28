<?php
namespace App\Services;
use App\Support\DenomGrouping;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ValidityMom {
 public function data(Request $request,Carbon $cutoff,Carbon $previousEnd,callable $scope):array {
  $input=$request->validate(['validity_product'=>'nullable|in:pv_all,pv_reg,pv_byu,sa_all,sa_simpati,sa_byu','validity_tab'=>'nullable|in:sales,inject']);
  $product=$input['validity_product']??'pv_all';$tab=$input['validity_tab']??'sales';
  $start=$cutoff->copy()->startOfMonth();$prev=$previousEnd->copy()->startOfMonth();$quantities=[];$groups=[];
  foreach($tab==='sales'?['keluarsf','mobile_penjualan']:['injectvf'] as$table){
   $q=DB::table($table.' as t')->leftJoin('denom as d','d.iddenom','=','t.iddenom')->whereBetween('t.tgl',[$prev,$cutoff]);
   if($table==='mobile_penjualan')$q->where('t.status','approved');
   DenomGrouping::filterDenoms($q,$product,'d');$scope($q,'t.idtap');
   $rows=$q->selectRaw("t.idtap, COALESCE(NULLIF(TRIM(d.group_name),''),'LAINNYA') AS validity, SUM(CASE WHEN t.tgl BETWEEN ? AND ? THEN t.qty ELSE 0 END) AS current_qty, SUM(CASE WHEN t.tgl BETWEEN ? AND ? THEN t.qty ELSE 0 END) AS previous_qty",[$start,$cutoff,$prev,$previousEnd])->groupBy('t.idtap','d.group_name')->get();
   foreach($rows as$row){$name=$this->label($row->validity);$groups[$name]=true;$quantities[$row->idtap][$name]['current']=($quantities[$row->idtap][$name]['current']??0)+(int)$row->current_qty;$quantities[$row->idtap][$name]['previous']=($quantities[$row->idtap][$name]['previous']??0)+(int)$row->previous_qty;}
  }
  $columns=array_keys($groups);usort($columns,function($a,$b){$weight=fn($s)=>preg_match('/^(\d+)D$/',$s,$m)?(int)$m[1]:1000;return ($weight($a)<=>$weight($b))?:strnatcasecmp($a,$b);});
  $roster=DB::table('idsf')->select('idtap')->distinct()->whereNotNull('idtap');$scope($roster,'idtap');
  $taps=$roster->pluck('idtap')->merge(array_keys($quantities))->unique()->sort()->values();$rows=[];$total=[];
  foreach($taps as$tap){$values=[];foreach($columns as$column){$cell=$quantities[$tap][$column]??['current'=>0,'previous'=>0];$values[$column]=$cell;$total[$column]['current']=($total[$column]['current']??0)+$cell['current'];$total[$column]['previous']=($total[$column]['previous']??0)+$cell['previous'];}$rows[]=['tap'=>$tap,'values'=>$values];}
  return ['product'=>$product,'tab'=>$tab,'columns'=>$columns,'rows'=>$rows,'total'=>$total,'cutoff'=>$cutoff,'previousEnd'=>$previousEnd];
 }
 private function label(string $group):string {$g=strtoupper(trim($group));if(preg_match('/^(\d+)\s*(?:HARI|DAYS?|D)$/',$g,$m))return (int)$m[1].'D';return $g;}
}
