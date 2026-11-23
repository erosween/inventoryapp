{{-- Outlet aktif per wilayah. Rate memakai jumlah outlet terjadwal PJP sebagai penyebut. --}}
<div class="outlet-insight-meta">
    <strong>Outlet Aktif</strong>
    <span>FM-1 bulan penuh · M-1 dan MTD sampai tanggal {{ $insight['cutoff']->format('d') }}</span>
    <span>Ambang aktif: PV 40 · SA 5 · CVM 11 · Sub-CVM 5 · Biometrik 1</span>
    <span>{{ number_format($insight['outletCount']) }} outlet sesuai filter</span>
</div>
@foreach(['tap'=>'Summary per TAP','district'=>'Summary per Kecamatan'] as $type=>$title)
    @php
        $rows = $type === 'tap' ? array_merge($insight['tapRows'], $insight['clusters']) : $insight['districtRows'];
        $rows[] = $insight['grand'] + ['grand' => true];
        $areaLabel = $type === 'tap' ? 'TAP' : 'Kecamatan';
    @endphp
    <details class="outlet-insight-panel" data-summary-section="{{ $type }}">
        <summary>{{ $title }}<span>{{ count($type === 'tap' ? $insight['tapRows'] : $insight['districtRows']) }} wilayah</span></summary>
        <div class="outlet-insight-scroll {{ $type === 'district' ? 'district' : '' }}" tabindex="0" aria-label="{{ $title }}">
            <table>
                <thead>
                    <tr>
                        <th class="insight-area" rowspan="2">{{ $areaLabel }}</th>
                        <th rowspan="2">PJP</th>
                        @foreach($insight['groups'] as $group)<th colspan="6" class="insight-block">{{ $group['label'] }}</th>@endforeach
                    </tr>
                    <tr>@foreach($insight['groups'] as $_)<th>FM-1</th><th>M-1</th><th>MTD</th><th>MoM</th><th>%MoM</th><th>Rate</th>@endforeach</tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php $rowClass = !empty($row['grand']) ? 'insight-grand' : (!empty($row['cluster']) ? 'insight-cluster' : ''); @endphp
                        <tr class="{{ $rowClass }}">
                            <th class="insight-area">@if(!empty($row['cluster']))<small>CLUSTER</small>@endif{{ $row['area'] }}</th>
                            <td>{{ number_format($row['pjp']) }}</td>
                            @foreach($insight['groups'] as $metric=>$group)
                                @php
                                    $fm1 = $row['values'][$metric]['fm1'];
                                    $m1 = $row['values'][$metric]['m1'];
                                    $mtd = $row['values'][$metric]['mtd'];
                                    $mom = $mtd - $m1;
                                    $momPct = $m1 > 0 ? (($mtd / $m1) - 1) * 100 : null;
                                    $rate = $row['pjp'] > 0 ? ($mtd / $row['pjp']) * 100 : null;
                                @endphp
                                <td>{{ number_format($fm1) }}</td><td>{{ number_format($m1) }}</td><td>{{ number_format($mtd) }}</td>
                                <td class="{{ $mom > 0 ? 'positive' : ($mom < 0 ? 'negative' : '') }}">{{ $mom > 0 ? '+' : '' }}{{ number_format($mom) }}</td>
                                <td class="{{ $momPct !== null ? ($momPct > 0 ? 'positive' : ($momPct < 0 ? 'negative' : '')) : '' }}">{{ $momPct === null ? '–' : (($momPct > 0 ? '+' : '').number_format($momPct, 1).'%') }}</td>
                                <td>{{ $rate === null ? '–' : number_format($rate, 1).'%' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endforeach
