<?php
namespace App\Services;

use App\Support\SuryaTapScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class NgrsReport
{
    public function scopes(): ?array
    {
        $scopes=SuryaTapScope::forUser(auth()->user(),session('idtap'));
        if($scopes===null)return null;
        if(!array_diff(['DUMAI','DURI','BENGKALIS','RUPAT','SEI PAKNING'],$scopes))$scopes[]='INFILTRASI DUMAI';
        if(!array_diff(['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'],$scopes))$scopes[]='INFILTRASI ROHIL';
        return $scopes;
    }

    private bool $mapReady=false;
    public function master()
    {
        if(!$this->mapReady){app(NgrsOutletMap::class)->refresh();$this->mapReady=true;}
        return DB::table('surya_ngrs_outlet_map')->select('rs','candidates','id_outlet','nama_outlet','master_tap');
    }

    public function active(int $year)
    {
        return DB::table('surya_ngrs_monthly as n')->join('surya_ngrs_months as a',fn($j)=>$j->on('a.month','=','n.month')->on('a.upload_id','=','n.upload_id'))
            ->whereBetween('n.month',["$year-01-01","$year-12-01"]);
    }

    private function search($q,string $search): void
    {
        if($search==='')return;
        $q->where(fn($q)=>$q->where('m.rs','like',"%$search%")
            ->orWhere(fn($q)=>$q->where('m.candidates',1)->where(fn($q)=>$q->where('m.nama_outlet','like',"%$search%")->orWhere('m.id_outlet','like',"%$search%"))));
    }

    public function base(array $filters)
    {
        $q=$this->active($filters['year']);
        if ($filters['q'] !== '') $q->leftJoinSub($this->master(),'m',fn($j)=>$j->on('m.rs','=','n.rs'));
        $scopes=$this->scopes();if($scopes!==null)$q->whereIn('n.tap',$scopes);
        if($filters['tap']!=='')$q->where('n.tap',$filters['tap']);
        // Search RS must also work for transactions not present in the master.
        if($filters['q']!=='')$q->where(fn($q)=>$q->where('n.rs','like','%'.$filters['q'].'%')->orWhere(fn($q)=>$q->where('m.candidates',1)->where(fn($q)=>$q->where('m.nama_outlet','like','%'.$filters['q'].'%')->orWhere('m.id_outlet','like','%'.$filters['q'].'%'))));
        return $q;
    }

    public function dayDivisors(int $year,array $months): array
    {
        $today=now('Asia/Jakarta');$days=[];
        foreach($months as $month){
            $start=\Carbon\Carbon::create($year,$month,1,0,0,0,'Asia/Jakarta');
            if($start->lt($today->copy()->startOfMonth()))$days[$month]=$start->daysInMonth;
            else{
                // Global file cutoff: the same divisor applies to every TAP and role.
                $latest=DB::table('surya_ngrs_transactions as t')->join('surya_ngrs_months as a',fn($j)=>$j->on('a.month','=','t.month')->on('a.upload_id','=','t.upload_id'))
                    ->where('t.month',$start->toDateString())->max('t.transaction_date');
                $days[$month]=$latest?(int)substr($latest,8,2):0;
            }
        }
        return $days;
    }

    public function summary(array $filters): array
    {
        if($filters['q']!=='')return $this->computeSummary($filters);
        $this->master();
        $months=DB::table('surya_ngrs_months')->whereBetween('month',[$filters['year'].'-01-01',$filters['year'].'-12-01'])->orderBy('month')->pluck('upload_id','month');
        $version=hash('sha256',$months->toJson().'|'.now('Asia/Jakarta')->format('Y-m').'|'.Cache::get('surya-ngrs-outlet-map-v1',''));
        $key='surya-ngrs-summary-v2-'.hash('sha256',json_encode([$filters['year'],$filters['metric'],$filters['tap'],$this->scopes()]));
        $cached=Cache::get($key);if(($cached['version']??null)===$version)return $cached['data'];
        $data=$this->computeSummary($filters);Cache::put($key,['version'=>$version,'data'=>$data],300);return $data;
    }

    private function computeSummary(array $filters): array
    {
        $data=$this->base($filters)->select('n.tap','n.cluster','n.month')->selectRaw('SUM(n.trx) AS trx,SUM(n.rech) AS rech')->groupBy('n.tap','n.cluster','n.month')->get();
        $rows=[];$clusters=[];$grand=['tap'=>'Grand Total','months'=>[],'total'=>0];$metric=$filters['metric'];$scopes=$this->scopes();
        foreach($data as$r){$key=$r->tap.'|'.$r->cluster;$rows[$key]??=['tap'=>$r->tap,'cluster'=>$r->cluster,'months'=>[],'total'=>0];$month=(int)substr($r->month,5,2);$v=(int)$r->$metric;$rows[$key]['months'][$month]=$v;$rows[$key]['total']+=$v;$rows[$key]['recharge'][$month]=(int)$r->rech;$rows[$key]['total_recharge']=($rows[$key]['total_recharge']??0)+(int)$r->rech;}
        foreach(array_merge(NgrsImport::TAPS,['INFILTRASI DUMAI','INFILTRASI ROHIL'])as$tap){
            if(($scopes!==null&&!in_array($tap,$scopes,true))||($filters['tap']!==''&&$filters['tap']!==$tap))continue;
            $cluster=$tap==='INFILTRASI DUMAI'?'INF DB':($tap==='INFILTRASI ROHIL'?'INF RH':(in_array($tap,['DUMAI','DURI','BENGKALIS','RUPAT','SEI PAKNING'])?'DUMAI BENGKALIS':'ROKAN HILIR'));
            $rows[$tap.'|'.$cluster]??=['tap'=>$tap,'cluster'=>$cluster,'months'=>[],'total'=>0];
        }
        uasort($rows,fn($a,$b)=>(str_starts_with($a['tap'],'INFILTRASI')<=>str_starts_with($b['tap'],'INFILTRASI'))?:strnatcasecmp($a['tap'],$b['tap']));
        $comparison=app(NgrsComparison::class)->plan($filters['year']);
        if($comparison&&$comparison['previous_available']){
            app(NgrsAnnual::class)->refresh($filters['year']);
            $compare=DB::table('surya_ngrs_annual as n')->where('n.year',$filters['year']);
            if($scopes!==null)$compare->whereIn('n.tap',$scopes);
            if($filters['tap']!=='')$compare->where('n.tap',$filters['tap']);
            if($filters['q']!==''){
                $compare->leftJoinSub($this->master(),'m',fn($j)=>$j->on('m.rs','=','n.rs'));
                $compare->where(fn($q)=>$q->where('n.rs','like','%'.$filters['q'].'%')->orWhere(fn($q)=>$q->where('m.candidates',1)->where(fn($q)=>$q->where('m.nama_outlet','like','%'.$filters['q'].'%')->orWhere('m.id_outlet','like','%'.$filters['q'].'%'))));
            }
            foreach($compare->select('n.tap','n.cluster')->selectRaw('SUM(n.compare_trx) AS trx,SUM(n.compare_rech) AS rech')->groupBy('n.tap','n.cluster')->get() as $entry){
                $key=$entry->tap.'|'.$entry->cluster;
                if(isset($rows[$key])){$rows[$key]['compare_trx']=(int)$entry->trx;$rows[$key]['compare_rech']=(int)$entry->rech;}
            }
        }
        foreach($rows as$r){$cluster=in_array($r['cluster'],['INF DB','DUMAI BENGKALIS'])?'Dumai Bengkalis':'Rokan Hilir';$clusters[$cluster]??=['tap'=>$cluster,'months'=>[],'total'=>0];foreach($r['months']as$m=>$v){$clusters[$cluster]['months'][$m]=($clusters[$cluster]['months'][$m]??0)+$v;$grand['months'][$m]=($grand['months'][$m]??0)+$v;}$clusters[$cluster]['compare_trx']=($clusters[$cluster]['compare_trx']??0)+($r['compare_trx']??0);$clusters[$cluster]['compare_rech']=($clusters[$cluster]['compare_rech']??0)+($r['compare_rech']??0);$grand['compare_trx']=($grand['compare_trx']??0)+($r['compare_trx']??0);$grand['compare_rech']=($grand['compare_rech']??0)+($r['compare_rech']??0);$clusters[$cluster]['total']+=$r['total'];$grand['total']+=$r['total'];foreach($r['recharge']??[] as $m=>$value){$clusters[$cluster]['recharge'][$m]=($clusters[$cluster]['recharge'][$m]??0)+$value;$grand['recharge'][$m]=($grand['recharge'][$m]??0)+$value;}$clusters[$cluster]['total_recharge']=($clusters[$cluster]['total_recharge']??0)+($r['total_recharge']??0);$grand['total_recharge']=($grand['total_recharge']??0)+($r['total_recharge']??0);}
        uksort($clusters,fn($a,$b)=>array_search($a,['Dumai Bengkalis','Rokan Hilir'])<=>array_search($b,['Dumai Bengkalis','Rokan Hilir']));
        $statsQuery = $this->base($filters);
        if ($filters['q'] === '') $statsQuery->leftJoinSub($this->master(), 'm', fn ($join) => $join->on('m.rs', '=', 'n.rs'));
        $stats=$statsQuery->selectRaw('COALESCE(SUM(n.trx),0) AS trx,COALESCE(SUM(n.rech),0) AS rech,COUNT(DISTINCT n.rs) AS rs,COUNT(DISTINCT CASE WHEN m.candidates IS NULL OR m.candidates<>1 THEN n.rs END) AS unmapped')->first();
        return compact('rows','clusters','grand','stats');
    }

    private function unionText(string $sql): string
    {
        return DB::getDriverName()==='sqlite'?$sql:"CONVERT($sql USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    }

    public function attachCategories($page): void
    {
        $ids = $page->getCollection()->where('candidates', 1)->pluck('id_outlet')->filter()->unique();
        $categories = DB::table('appsdumais')->whereIn('id_outlet', $ids)
            ->selectRaw("id_outlet, MAX(COALESCE(NULLIF(TRIM(kategori_outlet),''),'Reguler')) AS kategori_outlet")
            ->groupBy('id_outlet')->pluck('kategori_outlet', 'id_outlet');
        $page->through(function ($row) use ($categories) {
            $row->kategori_outlet = (int) $row->candidates === 1 ? ($categories[$row->id_outlet] ?? 'Reguler') : null;
            return $row;
        });
    }

    public function details(array $filters, bool $includeCategory = true)
    {
        $metric=$filters['metric'];$year=$filters['year'];
        app(NgrsAnnual::class)->refresh($year);
        $q=DB::table('surya_ngrs_annual as n')->where('n.year',$year)->leftJoinSub($this->master(),'m',fn($j)=>$j->on('m.rs','=','n.rs'));
        $scopes=$this->scopes();if($scopes!==null)$q->whereIn('n.tap',$scopes);
        if($filters['tap']!=='')$q->where('n.tap',$filters['tap']);
        if($filters['q']!=='')$q->where(fn($q)=>$q->where('n.rs','like','%'.$filters['q'].'%')->orWhere(fn($q)=>$q->where('m.candidates',1)->where(fn($q)=>$q->where('m.nama_outlet','like','%'.$filters['q'].'%')->orWhere('m.id_outlet','like','%'.$filters['q'].'%'))));
        $q->selectRaw($this->unionText('n.rs').' AS rs,'.$this->unionText('n.tap').' AS tap,'.$this->unionText('n.cluster').' AS cluster')->selectRaw("m.candidates,m.id_outlet,m.nama_outlet,'upload' AS tap_source");
        foreach(range(1,12)as$m)$q->selectRaw("n.{$metric}_m$m AS m$m");$q->selectRaw("n.{$metric}_total AS total,n.compare_{$metric} AS compare_qty");
        // Registered RS without any transaction remain visible with zero quantities.
        $zero=DB::query()->fromSub($this->master(),'m')->whereNotIn('m.rs',DB::table('surya_ngrs_annual')->where('year',$year)->select('rs'))
            ->selectRaw($this->unionText('m.rs').' AS rs,'.$this->unionText('m.master_tap').' AS tap,'.$this->unionText("CASE WHEN m.master_tap IN ('DUMAI','DURI','BENGKALIS','RUPAT','SEI PAKNING') THEN 'DUMAI BENGKALIS' ELSE 'ROKAN HILIR' END").' AS cluster')->selectRaw("m.candidates,m.id_outlet,m.nama_outlet,'master' AS tap_source");
        $scopes=$this->scopes();if($scopes!==null)$zero->whereIn('m.master_tap',$scopes);
        if($filters['tap']!=='')$zero->where('m.master_tap',$filters['tap']);$this->search($zero,$filters['q']);
        for($m=1;$m<=12;$m++)$zero->selectRaw("0 AS m$m");$zero->selectRaw('0 AS total,0 AS compare_qty');
        $query=DB::query()->fromSub($q->unionAll($zero),'annual_details')->select('annual_details.*');
        if ($includeCategory) {
            // Read categories directly from the master, so category uploads need no NGRS re-upload.
            // Group by outlet ID to preserve one report row even if the master contains duplicates.
            $categoryId = DB::getDriverName() === 'sqlite' ? 'CAST(id_outlet AS TEXT)' : 'CAST(id_outlet AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci';
            $categories=DB::table('appsdumais')->selectRaw("$categoryId AS id_outlet")
                ->selectRaw("MAX(COALESCE(NULLIF(TRIM(kategori_outlet),''),'Reguler')) AS kategori_outlet")->groupBy('id_outlet');
            $query->leftJoinSub($categories,'category',fn($j)=>$j->on('category.id_outlet','=',DB::raw($this->unionText('annual_details.id_outlet'))))
                ->selectRaw("CASE WHEN annual_details.candidates=1 THEN COALESCE(category.kategori_outlet,'Reguler') ELSE NULL END AS kategori_outlet");
        }
        $months=DB::table('surya_ngrs_months')->whereBetween('month',["$year-01-01","$year-12-01"])->pluck('month')->map(fn($date)=>(int)substr($date,5,2))->all();
        $comparison=app(NgrsComparison::class)->plan($year);
        $query->selectRaw($comparison&&$comparison['previous_available']?'compare_qty AS mom_compare':'NULL AS mom_compare');
        foreach(range(2,12)as$m){
            $available=in_array($m,$months,true)&&in_array($m-1,$months,true);
            $previous=$comparison&&$comparison['month']===$m?'compare_qty':'m'.($m-1);
            $query->selectRaw($available?"(CAST(m$m AS SIGNED)-CAST($previous AS SIGNED)) AS gap_m$m":"NULL AS gap_m$m");
            $query->selectRaw($available?"CASE WHEN $previous=0 THEN NULL ELSE ROUND((1.0*m$m/$previous-1)*100,1) END AS mom_pct_m$m":"NULL AS mom_pct_m$m");
        }
        return DB::query()->fromSub($query,'details')->orderByDesc('total')->orderBy('rs')->orderBy('tap');
    }
}
