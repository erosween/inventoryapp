<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NGRS · SURYA</title>
@include('surya.partials.assets')
<link rel="stylesheet" href="{{ asset('css/surya-ngrs.css') }}?v={{ filemtime(public_path('css/surya-ngrs.css')) }}">
</head><body><div class="shell" data-surya-workspace>
@include('surya.partials.sidebar',['activePage'=>'ngrs'])
<div class="main surya-workspace-main">@include('surya.partials.header')<main class="content surya-content ngrs-page">
@include('surya.partials.page-heading',['title'=>'NGRS','description'=>'Rekap transaksi bulanan dan recharge per nomor RS.'])
@php $monthNames=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];$metricLabel=strtoupper($filters['metric']); @endphp
<form class="ngrs-toolbar" method="get" action="{{ route('surya.ngrs') }}">
<label>Tahun<select name="year">@foreach($years as $year)<option value="{{ $year }}" @selected($filters['year']===$year)>{{ $year }}</option>@endforeach</select></label>
<label>TAP<select name="tap"><option value="">Semua TAP</option>@foreach($taps as $tap)<option value="{{ $tap }}" @selected($filters['tap']===$tap)>{{ $tap }}</option>@endforeach</select></label>
<label class="ngrs-search">Cari outlet<input name="q" value="{{ $filters['q'] }}" placeholder="Nomor RS, ID atau nama outlet"></label>
<input type="hidden" name="metric" value="{{ $filters['metric'] }}"><input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
<button class="ngrs-button" type="submit">Tampilkan</button><a class="ngrs-button secondary" href="{{ route('surya.ngrs.export',request()->query()) }}">Download CSV</a>
</form>
<div class="ngrs-stats">
<div><span>Total TRX</span><strong>{{ number_format($summary['stats']->trx,0,',','.') }}</strong><small>{{ $filters['year'] }} · sesuai filter</small></div>
<div><span>Total RECH</span><strong>{{ number_format($summary['stats']->rech,0,',','.') }}</strong><small>Nilai recharge</small></div>
<div><span>RS bertransaksi</span><strong>{{ number_format($summary['stats']->rs,0,',','.') }}</strong><small>Nomor RS unik</small></div>
<div class="{{ $summary['stats']->unmapped?'ngrs-warning':'' }}"><span>Belum terpetakan</span><strong>{{ number_format($summary['stats']->unmapped,0,',','.') }}</strong><small>Termasuk RS ganda di master</small></div>
</div>
<div class="ngrs-report-heading"><div><h2>Rekap per TAP</h2><p>TAP dan cluster mengikuti file upload. Infiltrasi sudah masuk dalam total cluster.</p></div><nav class="ngrs-metrics" aria-label="Jenis nilai">@foreach(['trx'=>'TRX','rech'=>'RECH'] as $key=>$label)<a class="{{ $filters['metric']===$key?'active':'' }}" href="{{ route('surya.ngrs',array_merge(request()->except('page'),['metric'=>$key])) }}" @if($filters['metric']===$key) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav></div>
@if(!$months)<div class="ngrs-empty">Belum ada upload NGRS untuk tahun {{ $filters['year'] }}.</div>@endif
<section class="ngrs-card" aria-label="Rekap {{ $metricLabel }} per TAP"><div class="ngrs-scroll"><table class="ngrs-summary"><thead><tr><th>TAP · {{ $metricLabel }}</th>@foreach($monthNames as $name)<th>{{ $name }}</th>@endforeach<th>Total</th></tr></thead><tbody>
@foreach($summary['rows'] as $row)<tr><th>{{ $row['tap'] }}</th>@foreach(range(1,12) as $m)<td>{{ in_array($m,$months,true)?number_format($row['months'][$m]??0,0,',','.'):'—' }}</td>@endforeach<td class="ngrs-total">{{ number_format($row['total'],0,',','.') }}</td></tr>@endforeach
</tbody><tfoot>@foreach($summary['clusters'] as $row)<tr class="ngrs-cluster"><th><small>CLUSTER</small>{{ $row['tap'] }}</th>@foreach(range(1,12) as $m)<td>{{ in_array($m,$months,true)?number_format($row['months'][$m]??0,0,',','.'):'—' }}</td>@endforeach<td>{{ number_format($row['total'],0,',','.') }}</td></tr>@endforeach
<tr class="ngrs-grand"><th>GRAND TOTAL</th>@foreach(range(1,12) as $m)<td>{{ in_array($m,$months,true)?number_format($summary['grand']['months'][$m]??0,0,',','.'):'—' }}</td>@endforeach<td>{{ number_format($summary['grand']['total'],0,',','.') }}</td></tr></tfoot></table></div></section>
<div class="ngrs-report-heading"><div><h2>Detail outlet · {{ $metricLabel }}</h2><p>Referensi outlet dari appsdumais, dicocokkan lewat nomor RS. RS tanpa transaksi tetap ditampilkan; satu RS pada beberapa TAP dipisah sesuai transaksi.</p></div></div>
<section class="ngrs-card" aria-label="Detail NGRS per outlet"><div class="ngrs-scroll ngrs-detail-scroll"><table class="ngrs-detail"><thead><tr><th>No</th><th class="ngrs-rs">Nomor RS</th><th>ID Outlet</th><th>Nama Outlet</th><th>TAP transaksi</th><th>Cluster</th>@foreach($monthNames  as $name)<th>{{ $name }}</th>@endforeach<th>Total</th></tr></thead><tbody>
@forelse($outlets  as $outlet)<tr><td>{{ $outlets->firstItem()+$loop->index }}</td><th class="ngrs-rs">{{ $outlet->rs }}</th><td>{{ (int)$outlet->candidates===1?$outlet->id_outlet:'—' }}</td><td class="ngrs-outlet-name">@if((int)$outlet->candidates===1){{ $outlet->nama_outlet }}@else<span class="ngrs-unmapped">Belum terpetakan</span>@if((int)$outlet->candidates>1)<small>RS ganda di master</small>@endif @endif</td><td>{{ $outlet->tap }}@if($outlet->tap_source==='master')<small class="ngrs-master-tap">TAP master · belum transaksi</small>@endif</td><td>{{ $outlet->cluster }}</td>@foreach(range(1,12) as $m)<td>{{ in_array($m,$months,true)?number_format($outlet->{'m'.$m},0,',','.'):'—' }}</td>@endforeach<td class="ngrs-total">{{ number_format($outlet->total,0,',','.') }}</td></tr>
@empty<tr><td colspan="19" class="ngrs-empty">Tidak ada outlet sesuai filter.</td></tr>@endforelse
</tbody></table></div><footer class="ngrs-footer"><span>Menampilkan {{ $outlets->firstItem()??0 }}–{{ $outlets->lastItem()??0 }} dari {{ number_format($outlets->total(),0,',','.') }} baris outlet / TAP</span><div>@if($outlets->previousPageUrl())<a href="{{ $outlets->previousPageUrl() }}">‹ Sebelumnya</a>@endif<span>{{ $outlets->currentPage() }} / {{ $outlets->lastPage() }}</span>@if($outlets->nextPageUrl())<a href="{{ $outlets->nextPageUrl() }}">Berikutnya ›</a>@endif</div></footer></section>
<p class="ngrs-note">—: bulan belum diunggah. Nilai 0: tidak ada transaksi pada bulan yang sudah diunggah. Pemetaan RS ganda tidak dibagikan ke beberapa outlet agar transaksi tidak terhitung dua kali.</p>
</main></div></div></body></html>
