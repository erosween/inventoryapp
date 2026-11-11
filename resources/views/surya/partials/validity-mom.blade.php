@php
    $isSuryaDashboard = request()->routeIs('surya.inventory-dashboard');
    $formatValidityNumber = fn ($value, int $decimals) => $isSuryaDashboard
        ? number_format($value, $decimals)
        : number_format($value, $decimals, ',', '.');
$labels=['pv_all'=>'PV All','pv_reg'=>'PV Reguler','pv_byu'=>'PV Byu','sa_all'=>'SA All','sa_simpati'=>'SA Simpati','sa_byu'=>'SA Byu'];
    $grouping = $validityMom['grouping'] ?? 'validity';
    $groupLabel = $grouping === 'denom' ? 'Denom' : 'Validity';
$cols=$validityMom['columns'];$displayRows=array_merge($validityMom['rows'],$validityMom['clusters']??[]);$displayRows[]=['tap'=>'GRAND TOTAL','values'=>$validityMom['total']];
@endphp
<section class="card enterprise-shadow h-100 validity-mom" id="validity-mom-card" data-current-product="{{ $validityMom['product'] }}" data-current-tab="{{ $validityMom['tab'] }}" data-current-group="{{ $grouping }}" aria-label="MoM per {{ $groupLabel }}">
    <header class="validity-head">
        <div class="validity-heading">
            <h3>MoM per {{ $groupLabel }}</h3>
            <p>1–{{ $validityMom['cutoff']->translatedFormat('d M Y') }} vs 1–{{ $validityMom['previousEnd']->translatedFormat('d M Y') }}</p>
        </div>
        <div class="validity-controls">
            <div class="validity-tabs" role="tablist" aria-label="Jenis transaksi">
                @foreach(['sales' => 'Penjualan', 'inject' => 'Inject'] as $key => $label)
                    <button type="button" role="tab" aria-selected="{{ $validityMom['tab'] === $key ? 'true' : 'false' }}"
                        data-validity-tab="{{ $key }}" @class(['active' => $validityMom['tab'] === $key])>{{ $label }}</button>
                @endforeach
            </div>
            <div class="validity-filters">
                <select data-validity-group aria-label="Pengelompokan MoM">
                    <option value="validity" @selected($grouping === 'validity')>Validity</option>
                    <option value="denom" @selected($grouping === 'denom')>Denom</option>
                </select>
                <input type="month" data-validity-month aria-label="Bulan dan tahun"
                    value="{{ $validityMom['cutoff']->format('Y-m') }}"
                    max="{{ $validityMom['latestMonth'] ?? $validityMom['cutoff']->format('Y-m') }}" required>
                <select data-validity-product aria-label="Produk">
                    @foreach($labels as $key => $label)
                        <option value="{{ $key }}" @selected($validityMom['product'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </header>
 <div class="validity-status" role="status" aria-live="polite" hidden></div>
 <div class="validity-scroll" tabindex="0" aria-label="Tabel MoM per {{ $groupLabel }}, geser untuk kolom lainnya"><table><thead><tr><th class="validity-tap" rowspan="2" scope="col">TAP</th>@foreach(['previous'=>'M-1','current'=>'MTD','growth'=>'MoM'] as $key=>$label)<th class="validity-block {{ $key }}" colspan="{{ count($cols)+1 }}" scope="colgroup">{{ $label }}</th>@endforeach</tr><tr>@foreach(['previous','current','growth'] as $period)@foreach($cols  as $column)<th class="{{ $period }}" scope="col">{{ $validityMom['columnLabels'][$column] ?? ($grouping === 'denom' ? Str::beforeLast($column, ' · ') : $column) }}</th>@endforeach<th class="{{ $period }} validity-total" scope="col">Total</th>@endforeach</tr></thead><tbody>
 @foreach($displayRows  as $row)<tr class="{{ $loop->last?'validity-grand':(!empty($row['cluster'])?'validity-cluster':'') }}"><th class="validity-tap" scope="row">@if(!empty($row['cluster']))<small class="validity-cluster-label">CLUSTER</small>@endif{{ $row['tap'] }}</th>@foreach(['previous','current','growth'] as $period)@php $cells=$row['values'];$cells['Total']=['current'=>array_sum(array_column($cells,'current')),'previous'=>array_sum(array_column($cells,'previous'))]; @endphp @foreach(array_merge($cols,['Total']) as $column)@php $cell=$cells[$column]??['current'=>0,'previous'=>0];$pct=$cell['previous']>0?($cell['current']/$cell['previous']-1)*100:null; @endphp<td class="{{ $column==='Total'?'validity-total':'' }} {{ $period==='growth'?($pct===null?'flat':($pct>0?'up':($pct<0?'down':'flat'))):'' }}">@if($period==='growth'){{ $pct===null?'—':($pct>0?'+':'').$formatValidityNumber($pct, 1).'%' }}@else{{ $formatValidityNumber($cell[$period], 0) }}@endif</td>@endforeach @endforeach</tr>@endforeach
 @if(count($validityMom['rows'])===0)<tr><td colspan="{{ 1+3*(count($cols)+1) }}">Belum ada transaksi untuk pilihan ini.</td></tr>@endif
 </tbody></table></div><footer class="validity-note">M-1 memakai rentang tanggal pembanding yang sama. MoM = (MTD ÷ M-1 − 1) × 100%. Tanda — berarti belum ada pembanding.</footer>
</section>
