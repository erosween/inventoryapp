<?php
namespace App\Support;
class NgrsFormat
{
    public static function value(int $trx,int $recharge,int $days,string $period,string $metric): string
    {
        if($days<1)return '—';
        if($metric==='rech')return number_format(($recharge/($period==='average'?$days:1))/1_000_000_000,3,'.',',').' mio';
        $value=$period==='average'?intdiv($trx+$days-1,$days):$trx;
        return number_format($value,0,',','.');
    }
}
