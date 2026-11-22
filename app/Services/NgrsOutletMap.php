<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NgrsOutletMap
{
    private const FINGERPRINT_KEY = 'surya-ngrs-outlet-map-v1';
    private const CHECKED_KEY = 'surya-ngrs-outlet-map-checked-v1';

    // Reuse the materialized map between requests. OutletlistImport invalidates this
    // marker after a successful import; the short TTL also catches out-of-band edits.
    public function refresh(bool $force = true): void
    {
        if (! $force && Cache::has(self::CHECKED_KEY)) return;

        $source=DB::table('appsdumais')->select('id_outlet','nama_outlet','nomor_rs','tap')->whereNotNull('nomor_rs')->orderBy('id_outlet')->get();
        $fingerprint=hash('sha256',$source->toJson());$key=self::FINGERPRINT_KEY;
        if(Cache::get($key)===$fingerprint){
            Cache::put(self::CHECKED_KEY, true, 30);
            return;
        }
        Cache::lock($key.'-lock',60)->block(20,function()use($source,$fingerprint,$key){
            if(Cache::get($key)===$fingerprint){Cache::put(self::CHECKED_KEY, true, 30);return;}
            $rows=[];
            foreach($source as $outlet){
                $rs=NgrsImport::normalizeRs((string)$outlet->nomor_rs);if(!$rs)continue;
                if(isset($rows[$rs])){$rows[$rs]['candidates']++;continue;}
                $rows[$rs]=['rs'=>$rs,'candidates'=>1,'id_outlet'=>$outlet->id_outlet,'nama_outlet'=>$outlet->nama_outlet,'master_tap'=>$outlet->tap];
            }
            DB::transaction(function()use($rows){DB::table('surya_ngrs_outlet_map')->delete();foreach(array_chunk(array_values($rows),500)as $batch)DB::table('surya_ngrs_outlet_map')->insert($batch);});
            Cache::put($key,$fingerprint,86400);
            Cache::put(self::CHECKED_KEY, true, 30);
        });
    }

    public function invalidate(): void
    {
        Cache::forget(self::CHECKED_KEY);
    }
}
