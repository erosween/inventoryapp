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
<div class="outlet-summary"><span class="count">{{ number_format($outlets->total()) }} outlet</span><span>Taskforce <strong>{{ $cutoff->translatedFormat('F Y') }}</strong></span><span>Kolom dan data mengikuti file upload.</span></div>
@if($errors->any())<div class="tf-alert" role="alert">{{ $errors->first() }}</div>@endif
<div class="tf-data-note">@if($upload)Daftar taskforce {{ $cutoff->translatedFormat('F Y') }} · {{ number_format($upload->outlet_count) }} outlet dalam file · Diupload {{ \Carbon\Carbon::parse($upload->created_at)->format('d/m/Y H:i') }}. Outlet ditampilkan sesuai wilayah akses.@else<strong>Belum ada daftar taskforce untuk {{ $cutoff->translatedFormat('F Y') }}.</strong> Admin perlu mengupload file khusus taskforce melalui menu Upload Transaksi.@endif</div>
<div class="tf-data-note">Tindak lanjut dicatat per outlet untuk {{ $cutoff->translatedFormat('F Y') }}.@unless($canAct) Pengisian dilakukan oleh akun asmen sesuai wilayahnya.@endunless</div>
@if($upload && !$upload->headers)<div class="tf-alert">Upload lama hanya menyimpan ID outlet. Upload ulang file taskforce agar seluruh header dan isinya dapat ditampilkan. Status visit dan catatan tetap tersimpan.</div>@endif
<div class="card tf-table-card"><div class="table-wrap"><table class="tf-table tf-upload-table">
<thead><tr>@foreach($headers as $header)<th scope="col">{{ $header }}</th>@endforeach<th scope="col">Visit Asmen</th><th scope="col">Tanggal Visit</th><th scope="col">Catatan Asmen</th><th scope="col">Action</th></tr></thead>
<tbody>@forelse($outlets as $outlet)<tr>
@foreach($headers as $column=>$header)<td class="tf-upload-cell">{{ $outlet->uploaded_cells[$column]??'' }}</td>@endforeach
<td><span class="tf-status {{ $outlet->visited?'done':'pending' }}">{{ $outlet->visited?'Sudah visit':'Belum visit' }}</span></td>
<td>{{ $outlet->visited_at ? \Carbon\Carbon::parse($outlet->visited_at)->format('d/m/Y') : '–' }}</td>
<td class="tf-notes">{{ $outlet->notes?:'–' }}@if($outlet->action_by)<small class="tf-subtext">{{ $outlet->action_by }} · {{ \Carbon\Carbon::parse($outlet->action_updated_at)->format('d/m H:i') }}</small>@endif</td>
<td>@if($canAct)<button type="button" class="btn tf-action" data-action-url="{{ route('surya.taskforce.push-so.store',['idOutlet'=>$outlet->id_outlet]) }}" data-outlet="{{ $outlet->nama_outlet }}" data-visited="{{ $outlet->visited?1:0 }}" data-date="{{ $outlet->visited_at }}" data-notes="{{ $outlet->notes }}">Isi tindak lanjut</button>@else<span class="tf-subtext">Diisi asmen</span>@endif</td>
</tr>@empty<tr><td colspan="{{ count($headers)+4 }}" class="table-empty">Belum ada outlet taskforce untuk wilayah Anda pada bulan ini.</td></tr>@endforelse</tbody></table></div>
<div class="table-footer"><span>Menampilkan {{ $outlets->firstItem()??0 }}–{{ $outlets->lastItem()??0 }} dari {{ number_format($outlets->total()) }} outlet</span><nav class="pagination" aria-label="Halaman outlet">@if($outlets->previousPageUrl())<a class="page-link" href="{{ $outlets->previousPageUrl() }}">‹ Sebelumnya</a>@endif<span class="page-link active">{{ $outlets->currentPage() }} / {{ $outlets->lastPage() }}</span>@if($outlets->nextPageUrl())<a class="page-link" href="{{ $outlets->nextPageUrl() }}">Berikutnya ›</a>@endif</nav></div></div>
</main></div></div>
@if($canAct)<dialog class="tf-dialog" id="push-so-dialog"><form id="push-so-action"><div class="tf-dialog-heading"><h2>Tindak lanjut outlet</h2><button type="button" data-close aria-label="Tutup">×</button></div><p id="action-outlet"></p><input type="hidden" name="sales_cutoff" value="{{ $cutoff->toDateString() }}"><label>Status visit<select class="input" name="visited" required><option value="0">Belum visit</option><option value="1">Sudah visit</option></select></label><label id="visit-date-label">Tanggal visit<input class="input" name="visited_at" type="date" max="{{ now()->toDateString() }}"></label><label>Catatan<textarea class="input" name="notes" rows="5" maxlength="3000" placeholder="Hasil kunjungan, kendala, atau rencana tindak lanjut…"></textarea></label><p class="tf-save-message" role="status" aria-live="polite"></p><div class="modal-actions"><button class="btn btn-secondary" type="button" data-close>Batal</button><button class="btn" type="submit">Simpan</button></div></form></dialog>@endif
<script src="{{ asset('js/surya-taskforce.js') }}?v={{ filemtime(public_path('js/surya-taskforce.js')) }}" defer></script>
</body></html>
