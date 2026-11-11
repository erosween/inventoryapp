<?php
namespace App\Http\Controllers;

use App\Services\NgrsImport;
use App\Services\NgrsReport;
use App\Services\NgrsColumnFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NgrsController extends Controller
{
    private function admin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
    private function filters(Request $r): array
    {
        $v=$r->validate(['year'=>'nullable|integer|min:2026|max:2100','metric'=>'nullable|in:trx,rech','tap'=>'nullable|string|max:40','q'=>'nullable|string|max:150','per_page'=>'nullable|in:25,50,100','summary_view'=>'nullable|in:monthly,average','summary_metric'=>'nullable|in:trx,rech']);
        return ['year'=>(int)($v['year']??now('Asia/Jakarta')->year),'metric'=>$v['metric']??'trx','tap'=>trim($v['tap']??''),'q'=>trim($v['q']??''),'per_page'=>(int)($v['per_page']??25)];
    }
    // Rekap TAP dan detail RS tahunan memakai NgrsReport. Detail tetap dipaginasi setelah filter dan
    // urutan diterapkan.
    public function index(Request $request,NgrsReport $report)
    {
        $filters=$this->filters($request);$filters['metric']='trx';$scopes=$report->scopes();$taps=array_merge(NgrsImport::TAPS,['INFILTRASI DUMAI','INFILTRASI ROHIL']);if($scopes!==null)$taps=array_values(array_intersect($taps,$scopes));
        $months=DB::table('surya_ngrs_months')->whereBetween('month',[$filters['year'].'-01-01',$filters['year'].'-12-01'])->pluck('month')->map(fn($m)=>(int)substr($m,5,2))->all();
        $years=DB::table('surya_ngrs_months')->selectRaw('SUBSTR(month,1,4) AS year')->distinct()->pluck('year')->map(fn($y)=>(int)$y)->push(now('Asia/Jakarta')->year)->push($filters['year'])->unique()->sortDesc()->values();
        $columns=app(NgrsColumnFilters::class);$options=$columns->options($request);
        $includeCategory = ($options['sort'] ?? null) === 'kategori_outlet'
            || isset($options['columns']['kategori_outlet']) || isset($options['exclude']['kategori_outlet']);
        $summary = $report->summary($filters);
        $outlets = $columns->apply($report->details($filters, $includeCategory), $options)
            ->paginate($filters['per_page'])->withQueryString();
        if (! $includeCategory) $report->attachCategories($outlets);
        $days=$report->dayDivisors($filters['year'],$months);$summaryView=$request->input('summary_view','monthly');$summaryMetric=$request->input('summary_metric','trx');
        $comparison=app(\App\Services\NgrsComparison::class)->plan($filters['year']);
        return view('surya.ngrs',compact('filters','taps','months','years','summary','outlets','days','summaryView','summaryMetric','comparison'));
    }
    public function summaryExport(Request $request,NgrsReport $report)
    {
        $filters=$this->filters($request);$filters['metric']='trx';
        $summary=$report->summary($filters);
        $months=DB::table('surya_ngrs_months')->whereBetween('month',[$filters['year'].'-01-01',$filters['year'].'-12-01'])->pluck('month')->map(fn($m)=>(int)substr($m,5,2))->all();
        $days=$report->dayDivisors($filters['year'],$months);
        $comparison=app(\App\Services\NgrsComparison::class)->plan($filters['year']);
        $period=$request->input('summary_view','monthly');$metric=$request->input('summary_metric','trx');
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\NgrsSummaryExport($summary,$days,$comparison,$filters['year'],$period,$metric),
            'Rekap_TAP_NGRS_'.$filters['year'].'_'.strtoupper($metric).'_'.$period.'.xlsx');
    }
    public function values(Request $request,NgrsReport $report)
    {
        $request->validate(['column'=>'required|string','q'=>'nullable|string|max:150','outlet_q'=>'nullable|string|max:150','value_page'=>'nullable|integer|min:1|max:10000']);
        $columns=app(NgrsColumnFilters::class);$key=$request->input('column');$columns->check($key);$options=$columns->options($request);
        $filters=$this->filters($request);$filters['metric']='trx';$filters['q']=trim((string)$request->input('outlet_q'));
        $query=$columns->apply($report->details($filters),$options,$key)->reorder();$expr=$columns->expression($key);
        if($request->filled('q'))$query->whereRaw($expr.' LIKE ?',['%'.$request->input('q').'%']);
        $values=$query->selectRaw($expr.' AS value')->distinct()->orderBy('value')->offset(((int)$request->input('value_page',1)-1)*100)->limit(101)->pluck('value');
        return response()->json(['values'=>$values->take(100)->values(),'has_more'=>$values->count()>100])->header('Cache-Control','no-store');
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
        $filters=$this->filters($request);$columns=app(NgrsColumnFilters::class);$options=$columns->options($request);
        $months=DB::table('surya_ngrs_months')->whereBetween('month',[$filters['year'].'-01-01',$filters['year'].'-12-01'])->pluck('month')->map(fn($m)=>(int)substr($m,5,2))->all();
        $comparison=app(\App\Services\NgrsComparison::class)->plan($filters['year']);
        return response()->streamDownload(function()use($report,$filters,$months,$columns,$options,$comparison){$h=fopen('php://output','wb');fwrite($h,"\xEF\xBB\xBF");$headers=['RS','ID Outlet','Nama Outlet','Status Mapping','TAP','Cluster','Kategori Outlet'];foreach(range(1,12)as$m){$headers[]=sprintf('%04d-%02d',$filters['year'],$m);if($comparison&&$m===$comparison['previous'])$headers[]=sprintf('%04d-%02d MoM 1-%02d',$filters['year'],$m,(int)substr($comparison['previous_end'],8,2));}$headers[]='Total '.strtoupper($filters['metric']);foreach(range(2,12)as$m){$headers[]=sprintf('GAP MoM %04d-%02d',$filters['year'],$m);$headers[]=sprintf('MoM %% %04d-%02d',$filters['year'],$m);}fputcsv($h,$headers,',','"','');
            foreach($columns->apply($report->details($filters),$options)->cursor()as$r){$matched=(int)$r->candidates===1;$values=[$r->rs,$matched?$r->id_outlet:'',$matched?$r->nama_outlet:'Belum terpetakan',$matched?'Terpetakan':((int)$r->candidates>1?'RS ganda di master':'Belum terpetakan'),$r->tap,$r->cluster,$r->kategori_outlet??''];foreach(range(1,12)as$m){$values[]=in_array($m,$months,true)?(int)$r->{'m'.$m}:'';if($comparison&&$m===$comparison['previous'])$values[]=$r->mom_compare===null?'':(int)$r->mom_compare;}$values[]=(int)$r->total;foreach(range(2,12)as$m){$values[]=$r->{'gap_m'.$m}===null?'':(int)$r->{'gap_m'.$m};$values[]=$r->{'mom_pct_m'.$m}===null?'':(float)$r->{'mom_pct_m'.$m};}$values=array_map(fn($v)=>is_string($v)&&preg_match('/^[=+@\t\r\n-]/',$v)?"'".$v:$v,$values);fputcsv($h,$values,',','"','');}fclose($h);
        },'NGRS_'.$filters['year'].'_'.strtoupper($filters['metric']).'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
