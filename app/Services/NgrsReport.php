<?php
namespace App\Services;

use App\Support\SuryaTapScope;
use Illuminate\Support\Facades\DB;

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

    public function master()
    {
        $raw="REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(CAST(nomor_rs AS ".(DB::getDriverName()==='sqlite'?'TEXT':'CHAR').")),' ',''),'+',''),'-',''),'(',''),')','')";
        $zero=DB::getDriverName()==='sqlite'?"('62' || SUBSTR($raw,2))":"CONCAT('62',SUBSTR($raw,2))";
        $eight=DB::getDriverName()==='sqlite'?"('62' || $raw)":"CONCAT('62',$raw)";
        $rs="CASE WHEN $raw LIKE '0%' THEN $zero WHEN $raw LIKE '8%' THEN $eight ELSE $raw END";
        return DB::table('appsdumais')->whereNotNull('nomor_rs')->whereRaw("$rs LIKE '62%' AND LENGTH($rs)>=9")
            ->selectRaw("$rs AS rs,COUNT(*) AS candidates,MIN(id_outlet) AS id_outlet,MIN(nama_outlet) AS nama_outlet,MIN(tap) AS master_tap")
            ->groupByRaw($rs);
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
        $q=$this->active($filters['year'])->leftJoinSub($this->master(),'m',fn($j)=>$j->on('m.rs','=','n.rs'));
        $scopes=$this->scopes();if($scopes!==null)$q->whereIn('n.tap',$scopes);
        if($filters['tap']!=='')$q->where('n.tap',$filters['tap']);
        // Search RS must also work for transactions not present in the master.
        if($filters['q']!=='')$q->where(fn($q)=>$q->where('n.rs','like','%'.$filters['q'].'%')->orWhere(fn($q)=>$q->where('m.candidates',1)->where(fn($q)=>$q->where('m.nama_outlet','like','%'.$filters['q'].'%')->orWhere('m.id_outlet','like','%'.$filters['q'].'%'))));
        return $q;
    }

    public function summary(array $filters): array
    {
        $data=$this->base($filters)->select('n.tap','n.cluster','n.month')->selectRaw('SUM(n.trx) AS trx,SUM(n.rech) AS rech')->groupBy('n.tap','n.cluster','n.month')->get();
        $rows=[];$clusters=[];$grand=['tap'=>'Grand Total','months'=>[],'total'=>0];$metric=$filters['metric'];$scopes=$this->scopes();
        foreach($data as$r){$key=$r->tap.'|'.$r->cluster;$rows[$key]??=['tap'=>$r->tap,'cluster'=>$r->cluster,'months'=>[],'total'=>0];$month=(int)substr($r->month,5,2);$v=(int)$r->$metric;$rows[$key]['months'][$month]=$v;$rows[$key]['total']+=$v;}
        foreach(array_merge(NgrsImport::TAPS,['INFILTRASI DUMAI','INFILTRASI ROHIL'])as$tap){
            if(($scopes!==null&&!in_array($tap,$scopes,true))||($filters['tap']!==''&&$filters['tap']!==$tap))continue;
            $cluster=$tap==='INFILTRASI DUMAI'?'INF DB':($tap==='INFILTRASI ROHIL'?'INF RH':(in_array($tap,['DUMAI','DURI','BENGKALIS','RUPAT','SEI PAKNING'])?'DUMAI BENGKALIS':'ROKAN HILIR'));
            $rows[$tap.'|'.$cluster]??=['tap'=>$tap,'cluster'=>$cluster,'months'=>[],'total'=>0];
        }
        uasort($rows,fn($a,$b)=>(str_starts_with($a['tap'],'INFILTRASI')<=>str_starts_with($b['tap'],'INFILTRASI'))?:strnatcasecmp($a['tap'],$b['tap']));
        foreach($rows as$r){$cluster=in_array($r['cluster'],['INF DB','DUMAI BENGKALIS'])?'Dumai Bengkalis':'Rokan Hilir';$clusters[$cluster]??=['tap'=>$cluster,'months'=>[],'total'=>0];foreach($r['months']as$m=>$v){$clusters[$cluster]['months'][$m]=($clusters[$cluster]['months'][$m]??0)+$v;$grand['months'][$m]=($grand['months'][$m]??0)+$v;}$clusters[$cluster]['total']+=$r['total'];$grand['total']+=$r['total'];}
        uksort($clusters,fn($a,$b)=>array_search($a,['Dumai Bengkalis','Rokan Hilir'])<=>array_search($b,['Dumai Bengkalis','Rokan Hilir']));
        $stats=$this->base($filters)->selectRaw('COALESCE(SUM(n.trx),0) AS trx,COALESCE(SUM(n.rech),0) AS rech,COUNT(DISTINCT n.rs) AS rs,COUNT(DISTINCT CASE WHEN m.candidates IS NULL OR m.candidates<>1 THEN n.rs END) AS unmapped')->first();
        return compact('rows','clusters','grand','stats');
    }

    private function unionText(string $sql): string
    {
        return DB::getDriverName()==='sqlite'?$sql:"CONVERT($sql USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    }

    public function details(array $filters)
    {
        $metric=$filters['metric'];$year=$filters['year'];
        $q=$this->base($filters)->selectRaw($this->unionText('n.rs').' AS rs,'.$this->unionText('n.tap').' AS tap,'.$this->unionText('n.cluster').' AS cluster')->selectRaw("MAX(m.candidates) AS candidates,MAX(m.id_outlet) AS id_outlet,MAX(m.nama_outlet) AS nama_outlet,'upload' AS tap_source");
        for($m=1;$m<=12;$m++)$q->selectRaw("SUM(CASE WHEN n.month=? THEN n.$metric ELSE 0 END) AS m$m",[sprintf('%04d-%02d-01',$year,$m)]);
        $q->selectRaw("SUM(n.$metric) AS total")->groupBy('n.rs','n.tap','n.cluster');
        // Registered RS without any transaction remain visible with zero quantities.
        $zero=DB::query()->fromSub($this->master(),'m')->whereNotIn('m.rs',$this->active($year)->select('n.rs'))
            ->selectRaw($this->unionText('m.rs').' AS rs,'.$this->unionText('m.master_tap').' AS tap,'.$this->unionText("CASE WHEN m.master_tap IN ('DUMAI','DURI','BENGKALIS','RUPAT','SEI PAKNING') THEN 'DUMAI BENGKALIS' ELSE 'ROKAN HILIR' END").' AS cluster')->selectRaw("m.candidates,m.id_outlet,m.nama_outlet,'master' AS tap_source");
        $scopes=$this->scopes();if($scopes!==null)$zero->whereIn('m.master_tap',$scopes);
        if($filters['tap']!=='')$zero->where('m.master_tap',$filters['tap']);$this->search($zero,$filters['q']);
        for($m=1;$m<=12;$m++)$zero->selectRaw("0 AS m$m");$zero->selectRaw('0 AS total');
        return DB::query()->fromSub($q->unionAll($zero),'details')->orderByDesc('total')->orderBy('rs')->orderBy('tap');
    }
}
