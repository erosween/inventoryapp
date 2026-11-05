<?php
namespace App\Http\Controllers;

use App\Services\NgrsImport;
use App\Services\NgrsReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NgrsController extends Controller
{
    private function admin(): void {abort_unless(auth()->check()&&strtolower((string)auth()->user()->username)==='admin_super',403);}
    private function filters(Request $r): array
    {
        $v=$r->validate(['year'=>'nullable|integer|min:2026|max:2100','metric'=>'nullable|in:trx,rech','tap'=>'nullable|string|max:40','q'=>'nullable|string|max:150','per_page'=>'nullable|in:25,50,100']);
        return ['year'=>(int)($v['year']??now('Asia/Jakarta')->year),'metric'=>$v['metric']??'trx','tap'=>trim($v['tap']??''),'q'=>trim($v['q']??''),'per_page'=>(int)($v['per_page']??25)];
    }
    public function index(Request $request,NgrsReport $report)
    {
        $filters=$this->filters($request);$scopes=$report->scopes();$taps=array_merge(NgrsImport::TAPS,['INFILTRASI DUMAI','INFILTRASI ROHIL']);if($scopes!==null)$taps=array_values(array_intersect($taps,$scopes));
        $months=DB::table('surya_ngrs_months')->whereBetween('month',[$filters['year'].'-01-01',$filters['year'].'-12-01'])->pluck('month')->map(fn($m)=>(int)substr($m,5,2))->all();
        $years=DB::table('surya_ngrs_months')->selectRaw('SUBSTR(month,1,4) AS year')->distinct()->pluck('year')->map(fn($y)=>(int)$y)->push(now('Asia/Jakarta')->year)->push($filters['year'])->unique()->sortDesc()->values();
        $summary=$report->summary($filters);$outlets=$report->details($filters)->paginate($filters['per_page'])->withQueryString();
        return view('surya.ngrs',compact('filters','taps','months','years','summary','outlets'));
    }
    public function store(Request $request,NgrsImport $importer)
    {
        $this->admin();$request->validate(['ngrs_file'=>['required','file','mimes:csv,txt','extensions:csv','max:20480']]);
        try{$r=$importer->import($request->file('ngrs_file'),auth()->id());}catch(ValidationException $e){throw$e;}catch(\Throwable $e){report($e);throw ValidationException::withMessages(['ngrs_file'=>'Upload NGRS gagal. Data aktif sebelumnya tetap tersimpan.']);}
        return redirect()->route('surya.transaction-upload')->with('success',"NGRS: {$r['rows']} baris berhasil. {$r['months']} bulan diperbarui; bulan lain tetap tersimpan.");
    }
    public function template()
    {
        $this->admin();return response()->streamDownload(function(){$h=fopen('php://output','wb');fwrite($h,"\xEF\xBB\xBF");fputcsv($h,NgrsImport::HEADERS,',','"','');fclose($h);},'template-ngrs.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    public function export(Request $request,NgrsReport $report)
    {
        $filters=$this->filters($request);
        $months=DB::table('surya_ngrs_months')->whereBetween('month',[$filters['year'].'-01-01',$filters['year'].'-12-01'])->pluck('month')->map(fn($m)=>(int)substr($m,5,2))->all();
        return response()->streamDownload(function()use($report,$filters,$months){$h=fopen('php://output','wb');fwrite($h,"\xEF\xBB\xBF");fputcsv($h,['RS','ID Outlet','Nama Outlet','Status Mapping','TAP','Cluster',...array_map(fn($m)=>sprintf('%04d-%02d',$filters['year'],$m),range(1,12)),'Total '.strtoupper($filters['metric'])],',','"','');
            foreach($report->details($filters)->cursor()as$r){$matched=(int)$r->candidates===1;$values=[$r->rs,$matched?$r->id_outlet:'',$matched?$r->nama_outlet:'Belum terpetakan',$matched?'Terpetakan':((int)$r->candidates>1?'RS ganda di master':'Belum terpetakan'),$r->tap,$r->cluster];foreach(range(1,12)as$m)$values[]=in_array($m,$months,true)?(int)$r->{'m'.$m}:'';$values[]=(int)$r->total;$values=array_map(fn($v)=>is_string($v)&&preg_match('/^[=+@\t\r\n-]/',$v)?"'".$v:$v,$values);fputcsv($h,$values,',','"','');}fclose($h);
        },'NGRS_'.$filters['year'].'_'.strtoupper($filters['metric']).'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
