{{--
    Tampilan bersama Transaksi Outlet SA dan PV. Dataset dan ekspor mengikuti
    OutletTransactionController.
--}}<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Transaksi {{ strtoupper($family) }} · SURYA</title>
@include('surya.partials.assets')
@foreach(['surya-ngrs','surya-outlet-columns'] as $css)<link rel="stylesheet" href="{{ asset('css/'.$css.'.css') }}?v={{ filemtime(public_path('css/'.$css.'.css')) }}">@endforeach
<link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}"><script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}" defer></script><script src="{{ asset('vendor/flatpickr/id.js') }}" defer></script>
<link rel="stylesheet" href="{{ asset('css/surya-outlet-transactions.css') }}?v={{ filemtime(public_path('css/surya-outlet-transactions.css')) }}">
<link rel="stylesheet" href="{{ asset('css/surya-transaction-tables.css') }}?v={{ filemtime(public_path('css/surya-transaction-tables.css')) }}"></head><body><div class="shell" data-surya-workspace>@include('surya.partials.sidebar',['activePage'=>'transactions-'.$family])<div class="main surya-workspace-main">@include('surya.partials.header')<main class="content surya-content ngrs-page transaction-report outlet-transactions-page">
@include('surya.partials.page-heading',['title'=>'Transaksi '.strtoupper($family),'description'=>'Rekap penjualan per TAP dan outlet.'])
@php
$monthNames=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
$identity=['id_outlet'=>'ID Outlet','nama_outlet'=>'Nama Outlet','tap'=>'TAP','cluster'=>'Cluster','nomor_rs'=>'Nomor RS','kecamatan'=>'Kecamatan','sf'=>'SF','pjp'=>'Hari PJP','kategori_outlet'=>'Kategori Outlet'];
$numeric=[];foreach($monthNames as $i=>$name){$numeric['m'.($i+1)]=$name;if($comparison && $i+1===$cutoff->month-1)$numeric['compare']=$name.'-MoM';}
$numeric['total']='Total '.$cutoff->year;foreach(range(2,12) as $m){$numeric['gap_m'.$m]='GAP '.$monthNames[$m-1];$numeric['mom_pct_m'.$m]='MoM % '.$monthNames[$m-1];}
@endphp
<form class="ngrs-toolbar outlet-transactions-toolbar" method="get">
<label>Cutoff transaksi<input name="cutoff" type="date" value="{{ $filters['cutoff'] }}" min="2026-01-01" max="{{ now('Asia/Jakarta')->toDateString() }}" data-transaction-date></label>
<label>TAP<select name="tap"><option value="">Semua TAP</option>@foreach($taps as $tap)<option @selected($filters['tap']===$tap)>{{ $tap }}</option>@endforeach</select></label>
<label class="ngrs-search">Cari outlet<input name="q" value="{{ $filters['q'] }}" placeholder="ID outlet, nama, RS, SF, kecamatan"></label>
<label>Per halaman<select name="per_page">@foreach([25,50,100] as $n)<option @selected($filters['per_page']===$n)>{{ $n }}</option>@endforeach</select></label>
<span data-column-options>@foreach(['columns','exclude'] as $mode)@foreach(request($mode,[]) as $key=>$values)@foreach($values  as $value)<input type="hidden" name="{{ $mode }}[{{ $key }}][]" value="{{ $value }}">@endforeach @endforeach @endforeach @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="direction" value="{{ request('direction','asc') }}">@endif</span>
<button class="ngrs-button">Tampilkan</button><a class="ngrs-button secondary" data-ngrs-download href="{{ route('surya.outlet-transactions.export',array_merge(request()->query(),['family'=>$family,'cutoff'=>$filters['cutoff']])) }}">Download Outlet</a>
</form>
<div class="ngrs-stats"><div><span>Total {{ strtoupper($family) }} · {{ $cutoff->year }}</span><strong>{{ number_format($grand['total'],0) }}</strong><small>Januari sampai cutoff</small></div><div><span>MTD · {{ $cutoff->translatedFormat('F') }}</span><strong>{{ number_format($grand['m'.$cutoff->month],0) }}</strong><small>1–{{ $cutoff->day }} {{ $cutoff->translatedFormat('M Y') }}</small></div><div><span>Outlet</span><strong>{{ number_format($rows->count(),0) }}</strong><small>Sesuai akses dan filter</small></div></div>
<div class="ngrs-report-heading"><div><h2>Rekap per TAP · {{ strtoupper($family) }}</h2><p>Cutoff {{ $cutoff->translatedFormat('d M Y') }} · penjumlahan transaksi per ID outlet.</p></div><a class="ngrs-button secondary" href="{{ route('surya.outlet-transactions.export',array_merge($filters,['family'=>$family,'summary'=>1])) }}">Download Rekap</a></div>
<section class="ngrs-card"><div class="ngrs-scroll"><table class="ngrs-summary" data-transaction-table="summary" data-report-end-month="{{ $cutoff->month }}"><thead><tr><th>TAP</th>@foreach($numeric  as $key=>$label)<th data-report-key="{{ $key }}">{{ $label }}</th>@endforeach</tr></thead><tbody>@foreach($summary  as $row)@include('surya.partials.outlet-transaction-summary-row',['kind'=>''])@endforeach</tbody><tfoot>@foreach($clusters  as $row)@include('surya.partials.outlet-transaction-summary-row',['kind'=>'ngrs-cluster'])@endforeach @include('surya.partials.outlet-transaction-summary-row',['row'=>$grand,'kind'=>'ngrs-grand'])</tfoot></table></div></section>
<div class="ngrs-report-heading"><div><h2>Detail per outlet · {{ strtoupper($family) }}</h2><p>Filter dan urutkan melalui header kolom.</p></div></div>
@if($comparison)<p class="ngrs-note ngrs-comparison-note">{{ $monthNames[$cutoff->month-2] }}-MoM: 1–{{ $previousEnd->day }} {{ $previousEnd->translatedFormat('M Y') }} · pembanding MTD {{ $cutoff->translatedFormat('F') }}.</p>@endif
<section class="ngrs-card"><div class="ngrs-scroll ngrs-detail-scroll"><table class="ngrs-detail" data-transaction-table="detail" data-report-end-month="{{ $cutoff->month }}"><thead><tr><th>No</th>@foreach($identity+$numeric  as $column=>$label)<th class="{{ ['nama_outlet'=>'ngrs-frozen-name','tap'=>'ngrs-frozen-tap','cluster'=>'ngrs-frozen-cluster'][$column]??'' }}">@include('surya.partials.outlet-column-heading',compact('column','label'))</th>@endforeach</tr></thead><tbody>
@forelse($outlets  as $outlet)<tr><td>{{ $outlets->firstItem()+$loop->index }}</td>@foreach($identity  as $key=>$label)<td class="{{ ['nama_outlet'=>'ngrs-outlet-name ngrs-frozen-name','tap'=>'ngrs-frozen-tap','cluster'=>'ngrs-frozen-cluster'][$key]??'' }}">{{ $outlet[$key]?:'—' }}</td>@endforeach @foreach($numeric  as $key=>$label)@php $value=$outlet[$key]; @endphp@php $pct=str_starts_with($key,'mom_pct_');$gap=str_starts_with($key,'gap_')||$pct;$direction=$value===null?'missing':($value>0?'positive':($value<0?'negative':'flat')); @endphp
<td class="{{ $gap?'ngrs-gap is-'.$direction:($key==='compare'?'ngrs-mom-helper':($key==='total'?'ngrs-total':'')) }}">@if($value===null)—@elseif($pct){{ ($value>0?'▲ +':($value<0?'▼ -':'')).number_format(abs($value),1).'%' }}@else{{ number_format($value,0) }}@endif</td>@endforeach</tr>@empty<tr><td colspan="{{ 1+count($identity)+count($numeric) }}" class="ngrs-empty">Tidak ada outlet sesuai filter.</td></tr>@endforelse
</tbody></table></div><footer class="ngrs-footer"><span>Menampilkan {{ $outlets->firstItem()??0 }}–{{ $outlets->lastItem()??0 }} dari {{ number_format($outlets->total(),0) }} outlet</span><div>@if($outlets->previousPageUrl())<a href="{{ $outlets->previousPageUrl() }}">‹ Sebelumnya</a>@endif<span>{{ $outlets->currentPage() }} / {{ $outlets->lastPage() }}</span>@if($outlets->nextPageUrl())<a href="{{ $outlets->nextPageUrl() }}">Berikutnya ›</a>@endif</div></footer></section>
<p class="ngrs-note">Sumber: Detail Outlet · bulan selesai dibandingkan penuh, MTD sampai tanggal setara.</p>
</main></div></div>
@include('surya.partials.outlet-column-menu', [
    'columnSearch' => $filters['q'],
    'columnValuesUrl' => route('surya.outlet-transactions.values', ['family' => $family]),
    'columnResetLabel' => 'Urutan total transaksi',
])
<script src="{{ asset('js/surya-ngrs-columns.js') }}?v={{ filemtime(public_path('js/surya-ngrs-columns.js')) }}" defer></script>
<script src="{{ asset('js/surya-taskforce-columns.js') }}?v={{ filemtime(public_path('js/surya-taskforce-columns.js')) }}" defer></script>
<script src="{{ asset('js/surya-outlet-transactions.js') }}?v={{ filemtime(public_path('js/surya-outlet-transactions.js')) }}" defer></script><script src="{{ asset('js/surya-transaction-tables.js') }}?v={{ filemtime(public_path('js/surya-transaction-tables.js')) }}" defer></script></body></html>
