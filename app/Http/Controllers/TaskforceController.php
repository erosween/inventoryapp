<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaskforceController extends MonitaDumaiController
{
    public const METRICS = [
        'sa' => ['label'=>'SA', 'key'=>'st_sa', 'column'=>null],
        'pv' => ['label'=>'PV', 'key'=>'st_pv', 'column'=>null],
        'bio' => ['label'=>'Biometrik', 'key'=>'biometrik', 'column'=>'bio'],
        'cvm' => ['label'=>'CVM', 'key'=>'cvm', 'column'=>'cvm'],
        'ss' => ['label'=>'Super Seru', 'key'=>'super_seru', 'column'=>'ss'],
        'comsak' => ['label'=>'Comsak', 'key'=>'comsak', 'column'=>'comsak'],
        'hot_promo' => ['label'=>'Hot Promo', 'key'=>'hot_promo', 'column'=>'hot_promo'],
    ];

    public function index(Request $request)
    {
        $rules = ['period'=>'nullable|in:fm1,m1,mtd', 'visit'=>'nullable|in:all,visited,pending',
            'q'=>'nullable|string|max:200', 'tap'=>'nullable|string|max:150',
            'sf'=>'nullable|string|max:150', 'pjp'=>'nullable|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'per_page'=>'nullable|in:10,25,50,100', 'sales_cutoff'=>'nullable|date_format:Y-m-d|before_or_equal:today'];
        foreach (self::METRICS as $key=>$metric) {
            $rules[$key.'_min']='nullable|integer|min:0';
            $rules[$key.'_max']='nullable|integer|min:0|gte:'.$key.'_min';
            // A maximum may be supplied without a minimum.
            if (!$request->filled($key.'_min')) $rules[$key.'_max']='nullable|integer|min:0';
        }
        $filters=$request->validate($rules);
        $cutoff=$this->outletSalesCutoff($request);
        $scopes=$this->suryaTapScopes();
        $upload=DB::table('taskforce_uploads')->where('period_month',$cutoff->copy()->startOfMonth()->toDateString())->orderByDesc('id')->first();
        $base=$this->taskforceOutlets($scopes, $upload?->id, $filters['tap']??'', $filters['q']??'', $filters['pjp']??'', $filters['sf']??'');
        $taps=$this->taskforceOutlets($scopes,$upload?->id)->distinct()->orderBy('outlet.tap')->pluck('outlet.tap');
        $sfs=$this->taskforceOutlets($scopes,$upload?->id, $filters['tap']??'')->whereNotNull('outlet.sf')
            ->whereRaw("TRIM(outlet.sf) <> ''")->distinct()->orderBy('outlet.sf')->pluck('outlet.sf');
        $period=$filters['period']??'mtd';
        $hasNumericFilter=collect(array_keys(self::METRICS))->contains(fn($key)=>$request->filled($key.'_min') || $request->filled($key.'_max'));
        // Materialize only scoped IDs and grouped totals. Joining several aggregate
        // derived tables makes MySQL re-evaluate large sales sets during COUNT.
        $eligibleOutletIds=$hasNumericFilter ? (clone $base)->pluck('outlet.id_outlet') : collect();
        foreach (self::METRICS as $key=>$metric) {
            if (!$request->filled($key.'_min') && !$request->filled($key.'_max')) continue;
            if ($eligibleOutletIds->isEmpty()) break;
            $totals=$this->metricTotals($key, $period, $cutoff, $eligibleOutletIds->all());
            $eligibleOutletIds=$eligibleOutletIds->filter(function($id) use($totals,$filters,$key,$request) {
                $value=(int)($totals->get((string)$id)??0);
                return (!$request->filled($key.'_min') || $value >= (int)$filters[$key.'_min'])
                    && (!$request->filled($key.'_max') || $value <= (int)$filters[$key.'_max']);
            })->values();
        }
        if ($hasNumericFilter) $base->whereIn('outlet.id_outlet',$eligibleOutletIds->all());
        $latest=DB::table('taskforce_push_so_actions')->where('period_month',$cutoff->copy()->startOfMonth()->toDateString())
            ->selectRaw('id_outlet, MAX(id) as action_id')->groupBy('id_outlet');
        $base->leftJoinSub($latest,'latest_action','latest_action.id_outlet','=','outlet.id_outlet')
            ->leftJoin('taskforce_push_so_actions as action','action.id','=','latest_action.action_id');
        if (($filters['visit']??'all') === 'visited') $base->where('action.visited',true);
        if (($filters['visit']??'all') === 'pending') $base->where(fn($q)=>$q->whereNull('action.id')->orWhere('action.visited',false));
        $outlets=$base->select('outlet.id_outlet','outlet.nama_outlet','outlet.kecamatan','outlet.sf','outlet.tap','outlet.pjp',
            'action.visited','action.visited_at','action.notes','action.username as action_by','action.updated_at as action_updated_at')
            ->orderBy('outlet.nama_outlet')->orderBy('outlet.id_outlet')->paginate((int)($filters['per_page']??25))->withQueryString();
        $outlets->setCollection($this->hydrateOutletDetailPage($outlets->getCollection(),$cutoff));
        $metrics=self::METRICS;
        $canAct=$this->canAct();
        return response()->view('surya.taskforce.push-so',compact('outlets','taps','sfs','filters','period','cutoff','metrics','canAct','upload'))
            ->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');
    }

    private function metricTotals(string $key, string $period, Carbon $cutoff, array $eligibleOutletIds)
    {
        $month=$cutoff->copy()->startOfMonth();
        $previous=$month->copy()->subMonth();
        $end=$period==='fm1' ? $previous->copy()->endOfMonth() : $previous->copy()->day(min($cutoff->day,$previous->daysInMonth));
        if (in_array($key,['sa','pv'],true)) {
            if ($period==='mtd') {
                $parts=[];
                foreach (['keluarsf','mobile_penjualan'] as $table) {
                    $q=DB::table($table.' as sale')->join('denom as d','d.iddenom','=','sale.iddenom')
                        ->whereBetween('sale.tgl',[$month->toDateString(),$cutoff->toDateString()])
                        ->whereIn('sale.id_outlet',$eligibleOutletIds)
                        ->whereRaw($key==='sa' ? "UPPER(COALESCE(d.product_group,'PV')) IN ('SA','SP')" : "UPPER(COALESCE(d.product_group,'PV')) = 'PV'")
                        ->selectRaw('sale.id_outlet, SUM(sale.qty) as amount')->groupBy('sale.id_outlet');
                    if ($table==='mobile_penjualan') $q->where('sale.status','approved');
                    $parts[]=$q;
                }
                $q=DB::query()->fromSub($parts[0]->unionAll($parts[1]),'sales')->selectRaw('id_outlet, SUM(amount) as amount')->groupBy('id_outlet');
            } elseif (Schema::hasTable('surya_outlet_sales_history')) {
                $q=DB::table('surya_outlet_sales_history')->whereIn('id_outlet',$eligibleOutletIds)->whereBetween('transaction_date',[$previous->toDateString(),$end->toDateString()])
                    ->selectRaw("id_outlet, SUM({$key}) as amount")->groupBy('id_outlet');
            } else {
                // Same zero fallback as Detail Outlet while no history is uploaded.
                $q=DB::table('appsdumais')->whereRaw('1=0')->selectRaw('id_outlet, 0 as amount');
            }
        } else {
            $column=self::METRICS[$key]['column'];
            $suffix=$period==='mtd'?'m':$period;
            $ranked=DB::table('surya_daily_metrics as metric')->select('metric.id_outlet')->selectRaw("metric.{$column}_{$suffix} as amount")
                ->selectRaw('ROW_NUMBER() OVER (PARTITION BY metric.id_outlet ORDER BY metric.metric_date DESC, metric.id DESC) as metric_rank')
                ->whereIn('metric.id_outlet',$eligibleOutletIds)
                ->where(fn($q)=>$q->whereNull('metric.uploaded_metrics')->orWhere('metric.uploaded_metrics','')->orWhereRaw('FIND_IN_SET(?, metric.uploaded_metrics) > 0',[$column]));
            $q=DB::query()->fromSub($ranked,'ranked_metrics')->where('metric_rank',1)->select('id_outlet','amount');
        }
        return $q->pluck('amount','id_outlet');
    }

    public function store(Request $request, string $idOutlet)
    {
        abort_unless($this->canAct(),403,'Hanya asmen yang dapat mengisi tindak lanjut.');
        $data=$request->validate(['sales_cutoff'=>'required|date_format:Y-m-d|before_or_equal:today',
            'visited'=>'required|boolean','visited_at'=>'nullable|required_if:visited,1|date_format:Y-m-d|before_or_equal:today',
            'notes'=>'nullable|string|max:3000']);
        if ($data['visited'] && !Carbon::parse($data['visited_at'])->isSameMonth(Carbon::parse($data['sales_cutoff']))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['visited_at'=>'Tanggal visit harus berada dalam bulan tindak lanjut.']);
        }
        $uploadId=DB::table('taskforce_uploads')->where('period_month',Carbon::parse($data['sales_cutoff'])->startOfMonth()->toDateString())->max('id');
        $outlet=$this->taskforceOutlets($this->suryaTapScopes(),$uploadId)->where('outlet.id_outlet',$idOutlet)->first();
        abort_unless($outlet,404);
        $month=Carbon::parse($data['sales_cutoff'])->startOfMonth()->toDateString();
        // Append a new event so previous notes and the responsible user remain auditable.
        DB::table('taskforce_push_so_actions')->insert([
            'id_outlet'=>$idOutlet,'period_month'=>$month,'visited'=>(bool)$data['visited'],
            'visited_at'=>$data['visited'] ? $data['visited_at'] : null,'notes'=>$data['notes']??null,
            'user_id'=>(string)auth()->id(),'username'=>auth()->user()->username,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        return response()->json(['message'=>'Tindak lanjut outlet berhasil disimpan.']);
    }

    private function canAct(): bool
    {
        return auth()->check() && str_starts_with(strtolower(auth()->user()->username),'asmen_');
    }

    private function taskforceOutlets(?array $scopes, ?int $uploadId, string $tap='', string $search='', string $pjp='', string $sf='')
    {
        return DB::table('appsdumais as outlet')
            ->whereExists(fn($q)=>$q->selectRaw('1')->from('taskforce_upload_outlets as assignment')
                ->where('assignment.upload_id',$uploadId??0)->whereColumn('assignment.id_outlet','outlet.id_outlet'))
            ->when($scopes!==null,fn($q)=>$q->whereIn('outlet.tap',$scopes))
            ->when($tap!=='',fn($q)=>$q->where('outlet.tap',$tap))
            ->when($sf!=='',fn($q)=>$q->where('outlet.sf',$sf))
            ->when($pjp!=='',fn($q)=>$q->where('outlet.pjp',$pjp))
            ->when($search!=='',fn($q)=>$q->where(function($q) use($search) {
                foreach(['id_outlet','nama_outlet','kecamatan','sf','tap','pjp'] as $column) $q->orWhere('outlet.'.$column,'like','%'.$search.'%');
            }));
    }

    public function upload(Request $request, \App\Services\TaskforceUpload $importer)
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(),403);
        $data=$request->validate(['taskforce_month'=>'required|date_format:Y-m',
            'taskforce_file'=>'required|file|mimes:xlsx,xls,csv,txt|extensions:xlsx,xls,csv|max:5120']);
        try {
            $result=$importer->import($request->file('taskforce_file'),$data['taskforce_month'].'-01',auth()->user()->username);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw \Illuminate\Validation\ValidationException::withMessages(['taskforce_file'=>'File gagal dibaca. Gunakan template Excel/CSV yang benar. Daftar sebelumnya tidak berubah.']);
        }
        return redirect()->route('surya.transaction-upload')->with('success',"Taskforce {$data['taskforce_month']}: {$result['count']} outlet disimpan, {$result['duplicates']} duplikat diabaikan. Status visit dan catatan tetap tersimpan.");
    }

    public function template()
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(),403);
        return response()->streamDownload(function() { echo "\xEF\xBB\xBFID Outlet\r\n"; },'template-taskforce.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
