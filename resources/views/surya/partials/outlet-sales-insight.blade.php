{{-- Ringkasan outlet aktif PV per TAP dan kecamatan, dibagi berdasarkan jadwal PJP. --}}
<div class="outlet-insight-meta"><strong>RS Aktif PV</strong><span>FM-1: {{ $insight['cutoff']->copy()->startOfMonth()->subMonth()->translatedFormat('1 M Y') }}–{{ $insight['cutoff']->copy()->startOfMonth()->subDay()->translatedFormat('d M Y') }} · M-1 s.d. {{ $insight['previousEnd']->translatedFormat('d M Y') }} · MTD s.d. {{ $insight['cutoff']->translatedFormat('d M Y') }}</span><span>{{ number_format($insight['outletCount']) }} outlet sesuai filter</span></div>
@foreach(['tap'=>'Summary per TAP','district'=>'Summary per Kecamatan'] as $type=>$title)
@php
    $rows = $type === 'tap' ? array_merge($insight['tapRows'], $insight['clusters']) : $insight['districtRows'];
    $rows[] = $insight['grand'] + ['grand' => true];
    $areaLabel = $type === 'tap' ? 'TAP' : 'Kecamatan';
@endphp
<details class="outlet-insight-panel" data-summary-section="{{ $type }}">
    <summary>{{ $title }}<span>{{ count($type === 'tap' ? $insight['tapRows'] : $insight['districtRows']) }} kombinasi wilayah–PJP</span></summary>
    <div class="outlet-insight-scroll {{ $type === 'district' ? 'district' : '' }}" tabindex="0" aria-label="{{ $title }}">
        <table>
            <thead>
                <tr><th class="insight-area" rowspan="2">{{ $areaLabel }}</th><th rowspan="2">PJP</th><th colspan="6" class="insight-block">RS AKTIF PV</th></tr>
                <tr>@foreach(['FM-1','M-1','MTD','MoM','%MoM','Rate'] as $label)<th>{{ $label }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php
                        $fm1 = $row['values']['fm1'];
                        $m1 = $row['values']['m1'];
                        $mtd = $row['values']['mtd'];
                        $mom = $mtd - $m1;
                        $momPct = $m1 > 0 ? (($mtd / $m1) - 1) * 100 : null;
                        $rate = $row['outlet_total'] > 0 ? ($mtd / $row['outlet_total']) * 100 : null;
                        $rowClass = !empty($row['grand']) ? 'insight-grand' : (!empty($row['cluster']) ? 'insight-cluster' : '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <th class="insight-area">@if(!empty($row['cluster']))<small>CLUSTER</small>@endif{{ $row['area'] }}</th>
                        <td>{{ $row['pjp'] ?: '–' }}</td>
                        <td>{{ number_format($fm1) }}</td><td>{{ number_format($m1) }}</td><td>{{ number_format($mtd) }}</td>
                        <td class="{{ $mom > 0 ? 'positive' : ($mom < 0 ? 'negative' : '') }}">{{ $mom > 0 ? '+' : '' }}{{ number_format($mom) }}</td>
                        <td class="{{ $momPct !== null ? ($momPct > 0 ? 'positive' : ($momPct < 0 ? 'negative' : '')) : '' }}">{{ $momPct === null ? '–' : (($momPct > 0 ? '+' : '').number_format($momPct, 1).'%') }}</td>
                        <td>{{ $rate === null ? '–' : number_format($rate, 1).'%' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</details>
@endforeach
