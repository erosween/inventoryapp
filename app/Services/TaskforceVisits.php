<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class TaskforceVisits
{
    public function query($outletIds=null,?string $from=null,?string $to=null)
    {
        return DB::table('taskforce_visits as v')->when($outletIds!==null,fn($q)=>$q->whereIn('v.id_outlet',$outletIds))
            ->when($from,fn($q)=>$q->where('v.visited_at','>=',$from))->when($to,fn($q)=>$q->where('v.visited_at','<=',$to));
    }
    public function stats($outletIds,array $options)
    {
        return $this->query($outletIds,$options['visit_from']??null,$options['visit_to']??null)
            ->select('v.id_outlet')->selectRaw('COUNT(*) as visit_count, MAX(v.visited_at) as last_date')->groupBy('v.id_outlet');
    }
    public function latestIds($outletIds,array $options)
    {
        return $this->query($outletIds,$options['visit_from']??null,$options['visit_to']??null)
            ->joinSub($this->stats($outletIds,$options),'last_day',fn($j)=>$j->on('last_day.id_outlet','=','v.id_outlet')->on('last_day.last_date','=','v.visited_at'))
            ->select('v.id_outlet')->selectRaw('MAX(v.id) as visit_id')->groupBy('v.id_outlet');
    }
    public function record(string $outletId,string $month,array $data,$user,?int $legacyId=null): object
    {
        return DB::transaction(function()use($outletId,$month,$data,$user,$legacyId){
            $attributes=['id_outlet'=>$outletId,'period_month'=>$month,'visited_at'=>$data['visited_at'],
                'notes'=>$data['notes']??null,'follow_up'=>$data['follow_up']??null,
                'user_id'=>(string)$user->id,'username'=>$user->username,'request_id'=>$data['request_id'],
                'legacy_action_id'=>$legacyId,'created_at'=>now(),'updated_at'=>now()];
            DB::table('taskforce_visits')->insertOrIgnore($attributes);
            $visit=DB::table('taskforce_visits')->where('request_id',$data['request_id'])->first();
            if(!$visit)throw new \RuntimeException('Kunjungan gagal disimpan.');
            foreach(['id_outlet','period_month','visited_at','notes','follow_up','user_id']as$key){
                abort_unless((string)$visit->$key===(string)$attributes[$key],409,'Permintaan ini sudah dipakai. Muat ulang riwayat sebelum menambah visit lain.');
            }
            return $visit;
        });
    }
}
