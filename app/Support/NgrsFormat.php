<?php
namespace App\Support;
class NgrsFormat
{
    public static function value(int $trx,int $recharge,int $days,string $period,string $metric): string
    {
        if($days<1)return '—';
        if($metric==='rech')return number_format(($recharge/($period==='average'?$days:1))/1_000_000_000,3,'.',',');
        $value=$period==='average'?intdiv($trx+$days-1,$days):$trx;
        return number_format($value,0,',','.');
    }
    public static function gap(int $trx,int $recharge,int $days,int $previousTrx,int $previousRecharge,int $previousDays,string $period,string $metric): array
    {
        if($days<1||$previousDays<1)return ['direction'=>'missing','text'=>'—','value'=>null];
        if($metric==='rech'){
            $current=$recharge/($period==='average'?$days:1);$previous=$previousRecharge/($period==='average'?$previousDays:1);
            $value=round(($current-$previous)/1_000_000_000,3);$text=number_format(abs($value),3,'.',',');
        }else{
            $current=$period==='average'?intdiv($trx+$days-1,$days):$trx;$previous=$period==='average'?intdiv($previousTrx+$previousDays-1,$previousDays):$previousTrx;
            $value=$current-$previous;$text=number_format(abs($value),0,',','.');
        }
        $direction=$value>0?'positive':($value<0?'negative':'flat');
        return ['direction'=>$direction,'text'=>($value>0?'▲ +':($value<0?'▼ -':'')).$text,'value'=>$value];
    }
}
