<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class NgrsColumnFilters extends OutletColumnFilters
{
    public function keys(): array
    {
        return ['rs','id_outlet','nama_outlet','tap','cluster',...array_map(fn($m)=>'m'.$m,range(1,12)),'total','mom_compare',...array_map(fn($m)=>'gap_m'.$m,range(2,12)),...array_map(fn($m)=>'mom_pct_m'.$m,range(2,12))];
    }

    public function apply($query,array $options,?string $facet=null)
    {
        $query=$query->reorder();
        foreach(['columns','exclude'] as $mode)foreach($options[$mode]??[] as $key=>$values){
            if($key===$facet)continue;
            $expr=$this->expression($key);
            $query->whereRaw($expr.($mode==='columns'?' IN (':' NOT IN (').implode(',',array_fill(0,count($values),'?')).')',$values);
        }
        $sort=$options['sort']??'total';$this->check($sort);
        return $query->orderByRaw("CASE WHEN `$sort` IS NULL THEN 1 ELSE 0 END")->orderBy($sort,$options['direction']??'desc')->orderBy('rs')->orderBy('tap')->orderBy('cluster');
    }
}
