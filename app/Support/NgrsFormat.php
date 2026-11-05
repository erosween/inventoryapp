<?php
namespace App\Support;
class NgrsFormat
{
    public static function value(int $trx,int $recharge,int $days,string $period,string $metric): string
    {
        $value=self::numericValue($trx,$recharge,$days,$period,$metric);
        if($value===null)return '—';
        return $metric==='rech'?number_format($value,3,'.',','):number_format($value,0,',','.');
    }
    public static function numericValue(int $trx,int $recharge,int $days,string $period,string $metric):int|float|null
    {
        if($days<1)return null;
        if($metric==='rech')return round(($recharge/($period==='average'?$days:1))/1_000_000_000,3);
        return $period==='average'?intdiv($trx+$days-1,$days):$trx;
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
    public static function percent(int $trx,int $recharge,int $days,int $previousTrx,int $previousRecharge,int $previousDays,string $period,string $metric):array
    {
        if($days<1||$previousDays<1)return ['direction'=>'missing','text'=>'—','value'=>null];
        $current=$metric==='rech'?$recharge/($period==='average'?$days:1):($period==='average'?intdiv($trx+$days-1,$days):$trx);
        $previous=$metric==='rech'?$previousRecharge/($period==='average'?$previousDays:1):($period==='average'?intdiv($previousTrx+$previousDays-1,$previousDays):$previousTrx);
        if($previous==0)return ['direction'=>'missing','text'=>'—','value'=>null];
        $value=round(($current/$previous-1)*100,1);$direction=$value>0?'positive':($value<0?'negative':'flat');
        return ['direction'=>$direction,'value'=>$value,'text'=>($value>0?'▲ +':($value<0?'▼ -':'')).number_format(abs($value),1,',','.').'%'];
    }
}
