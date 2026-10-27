<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>PUSH SO · SURYA</title>
<link rel="stylesheet" href="{{ asset('css/surya-outlet.css') }}?v={{ filemtime(public_path('css/surya-outlet.css')) }}">
@include('surya.partials.assets')
<link rel="stylesheet" href="{{ asset('css/surya-taskforce.css') }}?v={{ filemtime(public_path('css/surya-taskforce.css')) }}">
</head><body><div class="shell" id="app-shell" data-surya-workspace>
@include('surya.partials.sidebar',['activePage'=>'push-so'])
<div class="main surya-workspace-main">@include('surya.partials.header')
<main class="content surya-content taskforce-content">
@include('surya.partials.page-heading',['title'=>'PUSH SO','description'=>'Taskforce · Pantau transaksi outlet dan tindak lanjut kunjungan asmen.'])
<div class="outlet-summary"><span class="count">{{ number_format($outlets->total()) }} outlet</span><span>Cutoff SA/PV <strong>{{ $cutoff->translatedFormat('d M Y') }}</strong></span><span>FM-1: bulan lalu penuh · M-1: tanggal sebanding · M: MTD</span></div>
@if($errors->any())<div class="tf-alert" role="alert">{{ $errors->first() }}</div>@endif
<div class="tf-data-note">@if($upload)Daftar taskforce {{ $cutoff->translatedFormat('F Y') }} · {{ number_format($upload->outlet_count) }} outlet dalam file · Diupload {{ \Carbon\Carbon::parse($upload->created_at)->format('d/m/Y H:i') }}. Outlet ditampilkan sesuai wilayah akses.@else<strong>Belum ada daftar taskforce untuk {{ $cutoff->translatedFormat('F Y') }}.</strong> Admin perlu mengupload file khusus taskforce melalui menu Upload Transaksi.@endif</div>
<div class="tf-data-note">Tindak lanjut dicatat per outlet untuk {{ $cutoff->translatedFormat('F Y') }}.@unless($canAct) Pengisian dilakukan oleh akun asmen sesuai wilayahnya.@endunless</div>
<div class="card tf-table-card"><div class="table-wrap"><table class="tf-table">
<colgroup><col style="width:40px"><col style="width:135px"><col style="width:210px"><col span="4" style="width:150px">@foreach($metrics as $metric)<col span="3" style="width:90px">@endforeach<col style="width:130px"><col style="width:250px"><col style="width:150px"></colgroup>
<thead><tr><th rowspan="2" class="tf-no">No</th><th rowspan="2" class="tf-id">ID Outlet</th><th rowspan="2" class="tf-name">Nama Outlet</th><th rowspan="2">Kecamatan</th><th rowspan="2">SF</th><th rowspan="2">TAP</th><th rowspan="2">Hari PJP</th>@foreach($metrics as $metric)<th colspan="3" class="tf-metric-heading">{{ $metric['label'] }}</th>@endforeach<th rowspan="2">Visit Asmen</th><th rowspan="2">Catatan</th><th rowspan="2">Action</th></tr><tr>@foreach($metrics as $metric)<th>FM-1</th><th>M-1</th><th class="tf-current">M</th>@endforeach</tr></thead>
<tbody>@forelse($outlets as $outlet)<tr>
<td class="tf-no">{{ $outlets->firstItem()+$loop->index }}</td><td class="tf-id">{{ $outlet->id_outlet }}</td><td class="tf-name">{{ $outlet->nama_outlet?:'–' }}</td><td>{{ $outlet->kecamatan?:'–' }}</td><td>{{ $outlet->sf?:'–' }}</td><td>{{ $outlet->tap?:'–' }}</td><td>{{ $outlet->pjp?:'–' }}</td>
@foreach($metrics as $metric)@foreach(['fm1','m1','mtd'] as $part)<td class="tf-number {{ $part==='mtd'?'tf-current':'' }}">{{ number_format($outlet->{$metric['key'].'_'.$part}??0,0,',','.') }}</td>@endforeach @endforeach
<td><span class="tf-status {{ $outlet->visited?'done':'pending' }}">{{ $outlet->visited?'Sudah visit':'Belum visit' }}</span>@if($outlet->visited_at)<small class="tf-subtext">{{ \Carbon\Carbon::parse($outlet->visited_at)->format('d/m/Y') }}</small>@endif</td>
<td class="tf-notes">{{ $outlet->notes?:'–' }}@if($outlet->action_by)<small class="tf-subtext">{{ $outlet->action_by }} · {{ \Carbon\Carbon::parse($outlet->action_updated_at)->format('d/m H:i') }}</small>@endif</td>
<td>@if($canAct)<button type="button" class="btn tf-action" data-action-url="{{ route('surya.taskforce.push-so.store',['idOutlet'=>$outlet->id_outlet]) }}" data-outlet="{{ $outlet->nama_outlet }}" data-visited="{{ $outlet->visited?1:0 }}" data-date="{{ $outlet->visited_at }}" data-notes="{{ $outlet->notes }}">Isi tindak lanjut</button>@else<span class="tf-subtext">Diisi asmen</span>@endif</td>
</tr>@empty<tr><td colspan="31" class="table-empty">Tidak ada outlet yang sesuai filter.</td></tr>@endforelse</tbody></table></div>
<div class="table-footer"><span>Menampilkan {{ $outlets->firstItem()??0 }}–{{ $outlets->lastItem()??0 }} dari {{ number_format($outlets->total()) }} outlet</span><nav class="pagination" aria-label="Halaman outlet">@if($outlets->previousPageUrl())<a class="page-link" href="{{ $outlets->previousPageUrl() }}">‹ Sebelumnya</a>@endif<span class="page-link active">{{ $outlets->currentPage() }} / {{ $outlets->lastPage() }}</span>@if($outlets->nextPageUrl())<a class="page-link" href="{{ $outlets->nextPageUrl() }}">Berikutnya ›</a>@endif</nav></div></div>
</main></div></div>
@if($canAct)<dialog class="tf-dialog" id="push-so-dialog"><form id="push-so-action"><div class="tf-dialog-heading"><h2>Tindak lanjut outlet</h2><button type="button" data-close aria-label="Tutup">×</button></div><p id="action-outlet"></p><input type="hidden" name="sales_cutoff" value="{{ $cutoff->toDateString() }}"><label>Status visit<select class="input" name="visited" required><option value="0">Belum visit</option><option value="1">Sudah visit</option></select></label><label id="visit-date-label">Tanggal visit<input class="input" name="visited_at" type="date" max="{{ now()->toDateString() }}"></label><label>Catatan<textarea class="input" name="notes" rows="5" maxlength="3000" placeholder="Hasil kunjungan, kendala, atau rencana tindak lanjut…"></textarea></label><p class="tf-save-message" role="status" aria-live="polite"></p><div class="modal-actions"><button class="btn btn-secondary" type="button" data-close>Batal</button><button class="btn" type="submit">Simpan</button></div></form></dialog>@endif
<script src="{{ asset('js/surya-taskforce.js') }}?v={{ filemtime(public_path('js/surya-taskforce.js')) }}" defer></script>
</body></html>
