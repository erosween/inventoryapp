<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NgrsOutletMap
{
    // The source fingerprint includes every matching field, so renamed/reassigned
    // outlets are reflected immediately, including changes outside the upload screen.
    public function refresh(): void
    {
        $source=DB::table('appsdumais')->select('id_outlet','nama_outlet','nomor_rs','tap')->whereNotNull('nomor_rs')->orderBy('id_outlet')->get();
        $fingerprint=hash('sha256',$source->toJson());$key='surya-ngrs-outlet-map-v1';
        if(Cache::get($key)===$fingerprint)return;
        Cache::lock($key.'-lock',60)->block(20,function()use($source,$fingerprint,$key){
            if(Cache::get($key)===$fingerprint)return;
            $rows=[];
            foreach($source as $outlet){
                $rs=NgrsImport::normalizeRs((string)$outlet->nomor_rs);if(!$rs)continue;
                if(isset($rows[$rs])){$rows[$rs]['candidates']++;continue;}
                $rows[$rs]=['rs'=>$rs,'candidates'=>1,'id_outlet'=>$outlet->id_outlet,'nama_outlet'=>$outlet->nama_outlet,'master_tap'=>$outlet->tap];
            }
            DB::transaction(function()use($rows){DB::table('surya_ngrs_outlet_map')->delete();foreach(array_chunk(array_values($rows),500)as $batch)DB::table('surya_ngrs_outlet_map')->insert($batch);});
            Cache::put($key,$fingerprint,86400);
        });
    }
}
