<?php
namespace App\Http\Controllers;

use App\Services\OutletTransactionColumns;
use App\Services\OutletTransactionReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class OutletTransactionController extends Controller
{
    private function report(Request $request, string $family): array
    {
        $v=$request->validate(['cutoff'=>'nullable|date_format:Y-m-d|after_or_equal:2026-01-01|before_or_equal:'.now('Asia/Jakarta')->toDateString(),
            'tap'=>'nullable|string|max:100','q'=>'nullable|string|max:150','per_page'=>'nullable|in:25,50,100','page'=>'nullable|integer|min:1']);
        $cutoff=Carbon::parse($v['cutoff']??\App\Support\OutletSalesCutoff::latest()->toDateString(),'Asia/Jakarta')->startOfDay();
        $filters=['cutoff'=>$cutoff->toDateString(),'tap'=>trim($v['tap']??''),'q'=>trim($v['q']??''),'per_page'=>(int)($v['per_page']??25)];
        $columns=app(OutletTransactionColumns::class); $options=$columns->options($request);
        $data=app(OutletTransactionReport::class)->data($family,$cutoff,$filters['tap'],$filters['q']);
        return $data+compact('family','filters','columns','options');
    }

    public function index(Request $request, string $family)
    {
        if ($family==='cvm') return view('surya.outlet-transactions-cvm');
        $data=$this->report($request,$family);
        $rows=$data['columns']->apply($data['rows'],$data['options']);
        $page=(int)$request->input('page',1); $size=$data['filters']['per_page'];
        $data['outlets']=new LengthAwarePaginator($rows->slice(($page-1)*$size,$size)->values(),$rows->count(),$size,$page,
            ['path'=>$request->url(),'query'=>$request->query()]);
        return view('surya.outlet-transactions',$data);
    }

    public function values(Request $request, string $family)
    {
        $request->validate(['column'=>'required|string','q'=>'nullable|string|max:150','outlet_q'=>'nullable|string|max:150','value_page'=>'nullable|integer|min:1|max:10000']);
        $search=$request->input('q',''); $source=$request->duplicate(); $source->merge(['q'=>$request->input('outlet_q','')]);
        $data=$this->report($source,$family);$key=$request->input('column');$data['columns']->check($key);
        $values=$data['columns']->apply($data['rows'],$data['options'],$key)->pluck($key)->map(fn($v)=>(string)($v??''))->unique()
            ->filter(fn($v)=>$search===''||mb_stripos($v,$search)!==false)->sort(SORT_NATURAL)->values()
            ->slice(((int)$request->input('value_page',1)-1)*100,101)->values();
        return response()->json(['values'=>$values->take(100)->values(),'has_more'=>$values->count()>100]);
    }

    public function export(Request $request, string $family)
    {
        $data=$this->report($request,$family);
        $summary=$request->boolean('summary');
        $rows=$summary?$data['summary']->concat($data['clusters'])->push($data['grand']):$data['columns']->apply($data['rows'],$data['options']);
        $keys=$summary?['tap']:['id_outlet','nama_outlet','nomor_rs','tap','cluster','kecamatan','sf','pjp','kategori_outlet'];
        $headers=$summary?['TAP']:['ID Outlet','Nama Outlet','Nomor RS','TAP','Cluster','Kecamatan','SF','Hari PJP','Kategori Outlet'];
        foreach(range(1,12) as $m){$keys[]='m'.$m;$headers[]=sprintf('%04d-%02d',$data['cutoff']->year,$m);
            if($data['comparison']&&$m===$data['cutoff']->month-1){$keys[]='compare';$headers[]=$data['previousEnd']->format('Y-m').' MoM 1-'.$data['previousEnd']->day;}}
        $keys[]='total';$headers[]='Total '.strtoupper($family);
        foreach(range(2,12) as $m){$keys[]='gap_m'.$m;$headers[]='GAP '.sprintf('%02d',$m);$keys[]='mom_pct_m'.$m;$headers[]='MoM % '.sprintf('%02d',$m);}
        return response()->streamDownload(function()use($headers,$keys,$rows){
            $h=fopen('php://output','wb');fwrite($h,"\xEF\xBB\xBF");fputcsv($h,$headers,',','"','');
            foreach($rows as $row){$values=array_map(function($key)use($row){$v=$row[$key]??'';return is_string($v)&&preg_match('/^[=+@\t\r\n-]/',$v)?"'".$v:$v;},$keys);fputcsv($h,$values,',','"','');}fclose($h);
        },($summary?'Rekap_TAP_':'Detail_Outlet_').strtoupper($family).'_'.$data['filters']['cutoff'].'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
