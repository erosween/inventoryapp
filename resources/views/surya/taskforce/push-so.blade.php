<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>TASKFORCE · SURYA</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="{{ asset('css/surya-outlet.css') }}?v={{ filemtime(public_path('css/surya-outlet.css')) }}">
@include('surya.partials.assets')
<link rel="stylesheet" href="{{ asset('css/surya-taskforce.css') }}?v={{ filemtime(public_path('css/surya-taskforce.css')) }}">
</head><body><div class="shell" id="app-shell" data-surya-workspace>
@include('surya.partials.sidebar',['activePage'=>'push-so'])
<div class="main surya-workspace-main">@include('surya.partials.header')
<main class="content surya-content taskforce-content">
@include('surya.partials.page-heading',['title'=>'TASKFORCE'])
<div class="outlet-summary"><span class="count">{{ number_format($outlets->total()) }} outlet</span><strong>{{ $cutoff->translatedFormat('F Y') }}</strong></div>
@if($errors->any())<div class="tf-alert" role="alert">{{ $errors->first() }}</div>@endif
@unless($historyReady)<div class="tf-data-note" role="status">Riwayat kunjungan sedang disiapkan. Coba kembali beberapa saat lagi.</div>@endunless
@unless($upload)<div class="tf-data-note">Belum ada daftar taskforce untuk {{ $cutoff->translatedFormat('F Y') }}.</div>@endunless
<form class="tf-toolbar card" method="get" id="taskforce-toolbar">
<input type="hidden" name="sales_cutoff" value="{{ $cutoff->toDateString() }}">
@if(isset($options['sort']))<input type="hidden" name="sort" value="{{ $options['sort'] }}"><input type="hidden" name="direction" value="{{ $options['direction']??'asc' }}">@endif
@foreach(['columns','exclude'] as $mode)@foreach($options[$mode]??[] as $key=>$values)@foreach($values as $value)<input type="hidden" name="{{ $mode }}[{{ $key }}][]" value="{{ $value }}">@endforeach @endforeach @endforeach
<label>Periode taskforce<input class="input" type="month" name="period_month" value="{{ $cutoff->format('Y-m') }}" required></label>
<label>TAP<select class="input tf-select" name="tap" data-placeholder="Semua TAP"><option value=""></option>@foreach($taps as $tap)<option value="{{ $tap }}" @selected(($options['tap']??'')===$tap)>{{ $tap }}</option>@endforeach</select></label>
<label>Sales Force<select class="input tf-select" name="sf" data-placeholder="Semua SF"><option value=""></option>@foreach($sfs as $sf)<option value="{{ $sf }}" @selected(($options['sf']??'')===$sf)>{{ $sf }}</option>@endforeach</select></label>
<label>Visit dari<input class="input" type="date" name="visit_from" value="{{ $options['visit_from']??'' }}" max="{{ now('Asia/Jakarta')->toDateString() }}"></label><label>Visit sampai<input class="input" type="date" name="visit_to" value="{{ $options['visit_to']??'' }}" max="{{ now('Asia/Jakarta')->toDateString() }}"></label>
<div class="tf-toolbar-actions"><button class="btn" type="submit"><i class="fas fa-filter"></i> Tampilkan</button><a class="btn btn-secondary" href="{{ route('surya.taskforce.push-so',['sales_cutoff'=>$cutoff->toDateString()]) }}">Reset</a>@if($historyReady)<a class="btn" href="{{ route('surya.taskforce.visits.download',array_merge($options,['sales_cutoff'=>$cutoff->toDateString()])) }}"><i class="fas fa-download"></i> Download Excel</a>@else<span class="tf-subtext">Download riwayat sedang disiapkan</span>@endif</div>
</form>
@if($upload && !$upload->headers)<div class="tf-alert">Upload lama hanya menyimpan ID outlet. Upload ulang file taskforce agar seluruh header dan isinya dapat ditampilkan. Status visit dan catatan tetap tersimpan.</div>@endif
@php
    $nameColumn=null;
    $widths=[];
    foreach($headers as $index=>$header) {
        $normalized=preg_replace('/[^a-z0-9]/','',strtolower($header));
        if(in_array($normalized,['namaoutlet','outletname','namaoutletdigipos'],true)) $nameColumn=$index;
        $widths[]=in_array($normalized,['no','nomor'],true) ? 42 : (in_array($normalized,['idoutlet','iddigipos'],true) ? 135 : (in_array($normalized,['namaoutlet','outletname','namaoutletdigipos'],true) ? 230 : 150));
    }
    $freezeEnd=$nameColumn??0;
    $lefts=[];$offset=0;
    foreach($widths as $width) { $lefts[]=$offset;$offset+=$width; }
@endphp
<div class="card tf-table-card"><div class="table-wrap"><table class="tf-table tf-upload-table tf-fixed-table" style="--tf-table-width:{{ array_sum($widths)+1110 }}px">
<colgroup>@foreach($widths as $width)<col style="width:{{ $width }}px">@endforeach<col style="width:145px"><col style="width:110px"><col style="width:145px"><col style="width:270px"><col style="width:250px"><col style="width:190px"></colgroup>
<thead><tr>@foreach($headers as $column=>$header)<th scope="col" class="{{ $column<=$freezeEnd?'tf-frozen':'' }} {{ $column===$freezeEnd?'tf-frozen-edge':'' }}" style="--tf-left:{{ $lefts[$column] }}px"><span class="tf-column-heading"><span>{{ $header }}@if(($options['sort']??null)===(string)$column)<i class="fas {{ ($options['direction']??'asc')==='asc'?'fa-arrow-up':'fa-arrow-down' }} tf-sort-indicator" aria-label="Urutan aktif"></i>@endif</span><button type="button" class="tf-column-filter {{ isset($options['columns'][$column]) || isset($options['exclude'][$column]) ? 'is-active' : '' }}" data-column="{{ $column }}" data-label="{{ $header }}" aria-label="Filter {{ $header }}" aria-haspopup="dialog" aria-expanded="false"><i class="fas fa-filter" aria-hidden="true"></i></button></span></th>@endforeach
@foreach(['visit'=>'Status Visit','count'=>'Jumlah Visit','date'=>'Visit Terakhir','notes'=>'Temuan Terakhir','followup'=>'Tindak Lanjut'] as $key=>$label)<th scope="col"><span class="tf-column-heading"><span>{{ $label }}@if(($options['sort']??null)===$key)<i class="fas {{ ($options['direction']??'asc')==='asc'?'fa-arrow-up':'fa-arrow-down' }} tf-sort-indicator" aria-label="Urutan aktif"></i>@endif</span><button type="button" class="tf-column-filter {{ isset($options['columns'][$key]) || isset($options['exclude'][$key]) ? 'is-active' : '' }}" data-column="{{ $key }}" data-label="{{ $label }}" aria-label="Filter {{ $label }}" aria-haspopup="dialog" aria-expanded="false"><i class="fas fa-filter" aria-hidden="true"></i></button></span></th>@endforeach<th scope="col">Action</th></tr></thead>
<tbody>@forelse($outlets as $outlet)<tr data-visit-row data-outlet-name="{{ $outlet->nama_outlet }}" data-outlet-id="{{ $outlet->id_outlet }}" data-cutoff="{{ $cutoff->toDateString() }}" data-action-url="{{ route('surya.taskforce.visits.store',['idOutlet'=>$outlet->id_outlet]) }}" data-history-url="{{ route('surya.taskforce.visits.history',['idOutlet'=>$outlet->id_outlet]) }}">
@foreach($headers as $column=>$header)<td class="tf-upload-cell {{ $column<=$freezeEnd?'tf-frozen':'' }} {{ $column===$freezeEnd?'tf-frozen-edge':'' }}" style="--tf-left:{{ $lefts[$column] }}px">{{ \App\Support\TaskforceCellFormat::display($header,$outlet->uploaded_cells[$column]??'') }}</td>@endforeach
<td><span class="tf-status {{ $outlet->visited?'done':'pending' }}">{{ $outlet->visited?'Sudah visit':'Belum visit' }}</span></td>
<td class="tf-visit-count">{{ number_format($outlet->visit_count,0,',','.') }} kali</td>
<td>{{ $outlet->visited_at?\Carbon\Carbon::parse($outlet->visited_at)->format('d/m/Y'):'—' }}</td>
<td class="tf-notes">{{ $outlet->notes??'—' }}@if($outlet->action_by)<small class="tf-subtext">{{ $outlet->action_by }} · {{ \Carbon\Carbon::parse($outlet->action_updated_at)->format('d/m H:i') }}</small>@endif</td>
<td class="tf-notes">{{ $outlet->follow_up??'—' }}</td>
<td><div class="tf-row-actions">@if($canAct)<button type="button" class="btn tf-action" data-add-visit>Tambah Visit</button>@endif<button type="button" class="btn btn-secondary tf-action" data-visit-history @disabled(!$historyReady)>Riwayat</button></div></td>
</tr>@empty<tr><td colspan="{{ count($headers)+6 }}" class="table-empty">Belum ada outlet taskforce untuk wilayah Anda pada bulan ini.</td></tr>@endforelse</tbody></table></div>
<div class="table-footer"><span>Menampilkan {{ $outlets->firstItem()??0 }}–{{ $outlets->lastItem()??0 }} dari {{ number_format($outlets->total()) }} outlet</span><nav class="pagination" aria-label="Halaman outlet">@if($outlets->previousPageUrl())<a class="page-link" href="{{ $outlets->previousPageUrl() }}">‹ Sebelumnya</a>@endif<span class="page-link active">{{ $outlets->currentPage() }} / {{ $outlets->lastPage() }}</span>@if($outlets->nextPageUrl())<a class="page-link" href="{{ $outlets->nextPageUrl() }}">Berikutnya ›</a>@endif</nav></div></div>
</main></div></div>
<dialog id="tf-visit-dialog" class="tf-dialog tf-history-dialog" aria-labelledby="tf-visit-title">
<header class="tf-dialog-heading"><div><h2 id="tf-visit-title">Kunjungan Outlet</h2><p data-visit-outlet></p></div><button type="button" data-visit-close aria-label="Tutup riwayat">×</button></header>
<div class="tf-visit-tabs"><button type="button" data-visit-tab="form">Tambah Visit</button><button type="button" data-visit-tab="history">Riwayat</button></div>
<form id="tf-visit-form"><label>Tanggal visit<input class="input" name="visited_at" type="date" max="{{ now('Asia/Jakarta')->toDateString() }}" required></label>
<label>Hasil temuan<textarea class="input" name="notes" rows="4" maxlength="3000" placeholder="Temuan saat kunjungan" required></textarea></label>
<label>Tindak lanjut<textarea class="input" name="follow_up" rows="3" maxlength="3000" placeholder="Rencana tindak lanjut"></textarea></label>
<button type="submit" class="btn" data-submit-visit>Simpan Kunjungan</button></form>
<section data-visit-history-panel hidden><p data-history-status role="status" aria-live="polite"></p><div data-history-items></div><button class="btn btn-secondary" type="button" data-history-more hidden>Muat lainnya</button></section>
<p class="tf-save-message" data-visit-message role="status" aria-live="polite"></p>
</dialog>
<div id="tf-column-menu" class="tf-column-menu" role="dialog" aria-labelledby="tf-column-title" hidden data-values-url="{{ route('surya.taskforce.filter-values') }}" data-selection="{{ json_encode($options['columns']??new \stdClass()) }}" data-excluded="{{ json_encode($options['exclude']??new \stdClass()) }}">
<div class="tf-column-menu-heading"><strong id="tf-column-title">Filter kolom</strong><button type="button" data-filter-close aria-label="Tutup filter">×</button></div>
<div class="tf-sort-menu"><button type="button" data-sort-direction="asc"><i class="fas fa-arrow-up"></i> Urutkan naik</button><button type="button" data-sort-direction="desc"><i class="fas fa-arrow-down"></i> Urutkan turun</button><button type="button" data-sort-clear>Urutan file asli</button></div>
<input class="input" type="search" data-filter-search placeholder="Cari nilai…" aria-label="Cari nilai filter">
<div class="tf-column-menu-tools"><button type="button" data-filter-all>Pilih semua</button><button type="button" data-filter-none>Kosongkan pilihan</button></div>
<div data-filter-values class="tf-column-values"></div><p data-filter-message role="status" aria-live="polite"></p><button type="button" data-filter-more hidden>Muat lainnya</button>
<div class="tf-column-menu-actions"><button type="button" class="btn btn-secondary" data-filter-reset>Hapus filter</button><button type="button" class="btn" data-filter-apply>Terapkan</button></div></div>
<script src="{{ asset('js/surya-taskforce-columns.js') }}?v={{ filemtime(public_path('js/surya-taskforce-columns.js')) }}" defer></script>
<script src="{{ asset('js/surya-taskforce-visits.js') }}?v={{ filemtime(public_path('js/surya-taskforce-visits.js')) }}" defer></script>
<script src="{{ asset('js/surya-taskforce.js') }}?v={{ filemtime(public_path('js/surya-taskforce.js')) }}" defer></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</body></html>
