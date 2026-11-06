<?php
namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Shared, data-driven default for outlet nota reports, independent of the viewer's TAP. */
final class OutletSalesCutoff
{
    public static function latest(): Carbon
    {
        $today=Carbon::now('Asia/Jakarta')->startOfDay();
        $taps=['DUMAI','DURI','BENGKALIS','RUPAT','SEI PAKNING','BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'];
        $last=[];$latest=null;
        foreach ([SalesSource::legacy('s'),SalesSource::mobile('s')] as $source) {
            $source->join('denom as d','d.iddenom','=','s.iddenom')->whereIn('s.idtap',$taps)
                ->whereRaw("UPPER(COALESCE(d.product_group,'PV'))='PV'")
                ->where('s.tgl','<=',$today->copy()->endOfDay()->toDateTimeString());
            $dates=(clone $source)->where('s.tgl','>=',$today->copy()->startOfMonth()->toDateString())
                ->selectRaw('s.idtap, MAX(s.tgl) AS last_input')->groupBy('s.idtap')->get();
            foreach($dates as $row) $last[$row->idtap]=max($last[$row->idtap]??'',substr($row->last_input,0,10));
            if($dates->isEmpty()) $latest=max($latest??'',(string)$source->max('s.tgl'));
        }
        if($last) return Carbon::parse(min($last),'Asia/Jakarta')->startOfDay();
        // Before this month's first input, show the last complete month when available.
        return $latest ? Carbon::parse($latest,'Asia/Jakarta')->endOfMonth()->startOfDay() : $today->copy()->startOfMonth();
    }
}
