<?php
namespace App\Services;

use Illuminate\Support\Collection;

class OutletTransactionColumns extends OutletColumnFilters
{
    public function keys(): array
    {
        return ['id_outlet','nama_outlet','nomor_rs','tap','cluster','kecamatan','sf','pjp','kategori_outlet',
            ...array_map(fn($m)=>'m'.$m,range(1,12)), 'compare','total',
            ...array_map(fn($m)=>'gap_m'.$m,range(2,12)),...array_map(fn($m)=>'mom_pct_m'.$m,range(2,12))];
    }
    public function apply(Collection $rows, array $options, ?string $facet=null): Collection
    {
        foreach (['columns','exclude'] as $mode) foreach ($options[$mode]??[] as $key=>$values) {
            if ($key===$facet) continue;
            $rows=$rows->filter(fn($r)=>in_array((string)($r[$key]??''),$values,true) === ($mode==='columns'));
        }
        $sort=$options['sort']??'total'; $this->check($sort); $direction=($options['direction']??'desc')==='asc'?1:-1;
        return $rows->sort(function($a,$b) use($sort,$direction) {
            $x=$a[$sort]??null; $y=$b[$sort]??null;
            if ($x===null || $y===null) return ($x===null)<=>($y===null);
            $numeric=$sort==='total'||$sort==='compare'||preg_match('/^(m\d+|gap_m\d+|mom_pct_m\d+)$/',$sort);
            return (($numeric?($x<=>$y):strnatcasecmp((string)$x,(string)$y))*$direction) ?: strcmp((string)$a['id_outlet'],(string)$b['id_outlet']);
        })->values();
    }
}
