<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Detail Outlet · SURYA</title>

<link rel="stylesheet" href="{{ asset('css/surya-outlet.css') }}?v={{ filemtime(public_path('css/surya-outlet.css')) }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    @include('surya.partials.assets')
<link rel="stylesheet" href="{{ asset('css/surya-outlet-columns.css') }}?v={{ filemtime(public_path('css/surya-outlet-columns.css')) }}">
</head>
<body><div class="shell" id="app-shell" data-surya-workspace>
@include('surya.partials.sidebar', ['activePage' => 'outlet-detail'])

<div class="main surya-workspace-main">
    @include('surya.partials.header')
    <main class="content surya-content">
        @include('surya.partials.page-heading', ['title' => 'Detail Outlet', 'description' => $activeTab === 'digipos' ? 'Ringkasan sell-through dan transaksi outlet Digipos.' : 'Ringkasan visit dan belanja outlet non-Digipos dari Surya Mobile.'])
        <div class="outlet-summary"><span class="count">{{ number_format($activeTab === 'digipos' ? $outletCount : $nonDigiposCount) }} outlet</span>@if($activeTab === 'digipos')<span>Cutoff ST SA/PV: <strong>{{ $salesCutoffDate }}</strong></span>@endif</div>
        <nav class="outlet-tabs" aria-label="Jenis outlet"><a class="outlet-tab {{ $activeTab === 'digipos' ? 'active' : '' }}" href="{{ route('surya.outlet-detail', ['tab' => 'digipos']) }}"><i class="fas fa-store"></i> Outlet Digipos <span>{{ number_format($outletCount) }}</span></a><a class="outlet-tab {{ $activeTab === 'non-digipos' ? 'active' : '' }}" href="{{ route('surya.outlet-detail', ['tab' => 'non-digipos']) }}"><i class="fas fa-shop"></i> Outlet Non-Digipos <span>{{ number_format($nonDigiposCount) }}</span></a></nav>
        <form class="filters {{ $activeTab === 'non-digipos' ? 'non-digipos' : '' }}" method="get" action="{{ route('surya.outlet-detail') }}"><input type="hidden" name="tab" value="{{ $activeTab }}"><input class="input" name="q" value="{{ $search }}" placeholder="{{ $activeTab === 'digipos' ? 'Cari ID outlet, nama, kecamatan, SF, TAP, atau hari PJP...' : 'Cari ID, nama outlet, owner, nomor HP, alamat, SF, atau TAP...' }}"><select class="input select2-filter" name="tap" data-placeholder="Semua TAP"><option value=""></option>@foreach($taps as $tap)<option value="{{ $tap }}" @selected($selectedTap === $tap)>{{ $tap }}</option>@endforeach</select>@if($activeTab === 'digipos')<select class="input select2-filter" name="sf" data-placeholder="Semua SF"><option value=""></option>@foreach($sfs as $sf)<option value="{{ $sf }}" @selected($selectedSf === $sf)>{{ $sf }}</option>@endforeach</select><select class="input select2-filter" name="pjp" data-placeholder="Semua Hari PJP"><option value=""></option>@foreach($pjpDays as $day)<option value="{{ $day }}" @selected($selectedPjp === $day)>{{ $day }}</option>@endforeach</select><input class="input" id="sales-cutoff" name="sales_cutoff" type="date" value="{{ $salesCutoffValue }}" max="{{ $salesCutoffMax }}">@endif<span data-column-options>@foreach(['columns','exclude'] as $mode)@foreach(request($mode,[]) as $key=>$values)@foreach($values as $value)<input type="hidden" name="{{ $mode }}[{{ $key }}][]" value="{{ $value }}">@endforeach @endforeach @endforeach @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="direction" value="{{ request('direction','asc') }}">@endif</span><input type="hidden" name="per_page" value="{{ $perPage }}"><button class="btn" type="submit"><i class="fas fa-magnifying-glass"></i>&nbsp; Tampilkan</button><a class="btn" href="{{ route('surya.outlet-detail.export', ['q' => $search, 'tap' => $selectedTap, 'sf' => $selectedSf, 'pjp' => $selectedPjp, 'sales_cutoff' => $salesCutoffValue, 'columns'=>request('columns',[]), 'exclude'=>request('exclude',[]), 'sort'=>request('sort'), 'direction'=>request('direction')]) }}"><i class="fas fa-file-excel"></i>&nbsp; Export Excel</a></form>
        @php
            $groups = ['st_sa' => ['label' => 'ST SA', 'class' => 'sales'], 'st_pv' => ['label' => 'ST PV', 'class' => 'sales'], 'cvm' => ['label' => 'CVM', 'class' => 'upload'], 'super_seru' => ['label' => 'Super Seru', 'class' => 'upload'], 'comsak' => ['label' => 'Comsak', 'class' => 'upload'], 'hot_promo' => ['label' => 'Hot Promo', 'class' => 'upload'], 'biometrik' => ['label' => 'Biometrik', 'class' => 'upload']];
            $periods = ['fm1' => 'FM1', 'm1' => 'M1', 'mtd' => 'MTD', 'gap' => 'Gap MoM', 'mom_pct' => '%MoM'];
            $paginator = $activeTab === 'digipos' ? $outlets : $nonDigiposOutlets;
            $pageStart = max(1, $paginator->currentPage() - 2); $pageEnd = min($paginator->lastPage(), $paginator->currentPage() + 2);
        @endphp
        @if($activeTab === 'digipos')
        <div class="card"><div class="table-wrap"><table class="detail-table outlet-table-v2">
        <colgroup><col style="width:34px"><col style="width:130px"><col style="width:230px"><col span="4" style="width:160px"><col span="35" style="width:100px"></colgroup>
        <thead><tr><th class="freeze-number" rowspan="2">No</th><th class="freeze-id" rowspan="2">@include('surya.partials.outlet-column-heading',['column'=>'id_outlet','label'=>'ID Outlet'])</th><th class="freeze-name" rowspan="2">@include('surya.partials.outlet-column-heading',['column'=>'nama_outlet','label'=>'Nama Outlet'])</th><th rowspan="2">@include('surya.partials.outlet-column-heading',['column'=>'kecamatan','label'=>'Kecamatan'])</th><th rowspan="2">@include('surya.partials.outlet-column-heading',['column'=>'sf','label'=>'SF'])</th><th rowspan="2">@include('surya.partials.outlet-column-heading',['column'=>'tap','label'=>'TAP'])</th><th rowspan="2">@include('surya.partials.outlet-column-heading',['column'=>'pjp','label'=>'Hari PJP'])</th>@foreach($groups as $group)<th class="group" colspan="5">{{ $group['label'] }}</th>@endforeach</tr><tr>@foreach($groups as $key=>$group)@foreach($periods as $period=>$label)<th>@include('surya.partials.outlet-column-heading',['column'=>$key.'_'.$period,'label'=>$label,'filterLabel'=>$group['label'].' '.$label])</th>@endforeach @endforeach</tr></thead><tbody>
        @forelse($outlets as $outlet)
        <tr>
            <td class="freeze-number">{{ $outlets->firstItem() + $loop->index }}</td>
            <td class="freeze-id">@if($canEditOutlet)<button class="outlet-profile-link" type="button" data-outlet-id="{{ $outlet->id_outlet }}">{{ $outlet->id_outlet }}</button>@else{{ $outlet->id_outlet }}@endif</td>
            <td class="freeze-name">{{ $outlet->nama_outlet ?: '–' }}</td>
            <td>{{ $outlet->kecamatan ?: '–' }}</td><td>{{ $outlet->sf ?: '–' }}</td><td>{{ $outlet->tap ?: '–' }}</td><td>{{ $outlet->pjp ?: '–' }}</td>
            @foreach($groups as $key => $group)@foreach($periods as $period => $label)@php $value=$outlet->{$key.'_'.$period};$isPercent=$period==='mom_pct'; @endphp<td class="metric {{ $loop->first ? 'group-start' : '' }} {{ $group['class'] }} {{ $isPercent && $value !== null ? ($value >= 0 ? 'positive' : 'negative') : '' }} {{ $value === null ? 'blank' : '' }}">{{ $value === null ? '–' : ($isPercent ? ($value > 0 ? '+' : '').number_format($value,1,',','.').'%' : number_format($value,0,',','.')) }}</td>@endforeach @endforeach
        </tr>
        @empty<tr><td class="table-empty" colspan="42">Outlet PJP tidak ditemukan.</td></tr>@endforelse
        </tbody></table></div>
        @else
        <div class="card"><div class="table-wrap"><table class="non-digipos-table"><thead><tr><th>Outlet</th><th>Owner</th><th>Contact</th><th>Alamat</th><th>SF</th><th>TAP</th><th>SA</th><th>PV</th></tr></thead><tbody>@forelse($nonDigiposOutlets as $outlet)<tr><td><strong>{{ $outlet->outlet_name ?: '–' }}</strong></td><td>{{ $outlet->owner_name ?: '–' }}</td><td>{{ $outlet->phone_number ?: '–' }}</td><td class="product-summary">{{ $outlet->outlet_address ?: '–' }}</td><td><strong>{{ $outlet->namasf ?: $outlet->idsf }}</strong></td><td>{{ $outlet->idtap ?: '–' }}</td><td class="number sales">{{ number_format($outlet->total_sa) }}</td><td class="number sales">{{ number_format($outlet->total_pv) }}</td></tr>@empty<tr><td class="table-empty" colspan="8">Outlet non-Digipos tidak ditemukan.</td></tr>@endforelse</tbody></table></div>
        @endif
        <div class="table-footer"><form class="page-size" method="get"><span>Tampilkan</span><select name="per_page" onchange="this.form.submit()">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach</select><input type="hidden" name="tab" value="{{ $activeTab }}"><input type="hidden" name="q" value="{{ $search }}"><input type="hidden" name="tap" value="{{ $selectedTap }}"><input type="hidden" name="sales_cutoff" value="{{ $salesCutoffValue }}"></form><div class="table-info">Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ number_format($paginator->total()) }} outlet</div>@if($paginator->lastPage()>1)<nav class="pagination"><a class="page-link {{ $paginator->onFirstPage()?'disabled':'' }}" href="{{ $paginator->previousPageUrl()?:'#' }}">‹</a>@if($pageStart>1)<a class="page-link" href="{{ $paginator->url(1) }}">1</a>@if($pageStart>2)<span class="page-gap">…</span>@endif @endif @for($page=$pageStart;$page<=$pageEnd;$page++)<a class="page-link {{ $page===$paginator->currentPage()?'active':'' }}" href="{{ $paginator->url($page) }}">{{ $page }}</a>@endfor @if($pageEnd<$paginator->lastPage())@if($pageEnd<$paginator->lastPage()-1)<span class="page-gap">…</span>@endif<a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a>@endif<a class="page-link {{ $paginator->hasMorePages()?'':'disabled' }}" href="{{ $paginator->nextPageUrl()?:'#' }}">›</a></nav>@endif</div></div>
    </main>
</div></div>
@if($canEditOutlet)<div class="modal-backdrop" id="profile-modal" aria-hidden="true"><div class="profile-modal"><div class="modal-head"><div><h3>Profil Outlet</h3><p id="profile-subtitle">Memuat data outlet...</p></div><button class="modal-close" type="button" data-profile-close><i class="fas fa-xmark"></i></button></div><div class="profile-summary"><div><small>ID Outlet</small><strong id="profile-id">–</strong></div><div><small>TAP</small><strong id="profile-tap">–</strong></div><div><small>Nama Outlet</small><strong id="profile-name">–</strong></div><div><small>Kecamatan</small><strong id="profile-kecamatan">–</strong></div></div><form id="profile-form"><div class="form-grid"><div class="field"><label for="profile-sf">Sales Force</label><select class="input" id="profile-sf" required></select></div><div class="field"><label for="profile-pjp">Hari PJP</label><select class="input" id="profile-pjp" required></select></div></div><div class="modal-message" id="profile-message"></div><div class="modal-actions"><button class="btn btn-secondary" type="button" data-profile-close>Batal</button><button class="btn" id="profile-save" type="submit">Simpan Perubahan</button></div></form></div></div>@endif
@if($activeTab === 'digipos')
<div id="tf-column-menu" class="tf-column-menu" role="dialog" aria-labelledby="tf-column-title" hidden data-outlet-search="{{ $search }}" data-values-url="{{ route('surya.outlet-detail.filter-values') }}" data-selection="{{ json_encode(request('columns',new \stdClass())) }}" data-excluded="{{ json_encode(request('exclude',new \stdClass())) }}">
<div class="tf-column-menu-heading"><strong id="tf-column-title">Filter kolom</strong><button type="button" data-filter-close aria-label="Tutup filter">×</button></div>
<div class="tf-sort-menu"><button type="button" data-sort-direction="asc"><i class="fas fa-arrow-up"></i> Urutkan naik</button><button type="button" data-sort-direction="desc"><i class="fas fa-arrow-down"></i> Urutkan turun</button><button type="button" data-sort-clear>Urutan nama outlet</button></div>
<input class="input" type="search" data-filter-search placeholder="Cari nilai…" aria-label="Cari nilai filter">
<div class="tf-column-menu-tools"><button type="button" data-filter-all>Pilih semua</button><button type="button" data-filter-none>Kosongkan pilihan</button></div>
<div data-filter-values class="tf-column-values"></div><p data-filter-message role="status" aria-live="polite"></p><button type="button" data-filter-more hidden>Muat lainnya</button>
<div class="tf-column-menu-actions"><button type="button" class="btn btn-secondary" data-filter-reset>Hapus filter</button><button type="button" class="btn" data-filter-apply>Terapkan</button></div></div>
<script src="{{ asset('js/surya-outlet-columns.js') }}?v={{ filemtime(public_path('js/surya-outlet-columns.js')) }}" defer></script>
<script src="{{ asset('js/surya-taskforce-columns.js') }}?v={{ filemtime(public_path('js/surya-taskforce-columns.js')) }}" defer></script>
@endif
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
if (window.jQuery && $.fn.select2) $('.select2-filter').select2({
    width: '100%',
    allowClear: true,
    minimumResultsForSearch: 0,
    language: { noResults: () => 'Tidak ditemukan' }
});
function changeOutletTap() {
    const sf = this.form.querySelector('[name="sf"]');
    if (sf) sf.value = '';
    this.form.submit();
}
if (window.jQuery && $.fn.select2) {
    $('.filters select[name="tap"]').on('change', changeOutletTap);
} else {
    document.querySelector('.filters select[name="tap"]')?.addEventListener('change', changeOutletTap);
}
const pageSizeForm = document.querySelector('.page-size');
if (pageSizeForm) {
    for (const name of ['sf', 'pjp']) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = document.querySelector('.filters [name="' + name + '"]')?.value || '';
        pageSizeForm.appendChild(input);
    }
}

const canEditOutlet=@json($canEditOutlet);if(canEditOutlet){const modal=document.getElementById('profile-modal'),form=document.getElementById('profile-form'),message=document.getElementById('profile-message'),save=document.getElementById('profile-save'),profileUrl=@json(route('surya.outlet-detail.profile',['idOutlet'=>'__ID__'])),updateUrl=@json(route('surya.outlet-detail.profile.update',['idOutlet'=>'__ID__'])),detailTable=document.querySelector('.detail-table');let activeId='';const closeModal=()=>{modal.classList.remove('open');modal.setAttribute('aria-hidden','true')};document.querySelectorAll('[data-profile-close]').forEach(button=>button.onclick=closeModal);modal.addEventListener('click',event=>{if(event.target===modal)closeModal()});detailTable?.addEventListener('click',async event=>{const button=event.target.closest('[data-outlet-id]');if(!button)return;activeId=button.dataset.outletId;message.textContent='';modal.classList.add('open');modal.setAttribute('aria-hidden','false');document.getElementById('profile-subtitle').textContent='Memuat data outlet...';try{const response=await fetch(profileUrl.replace('__ID__',encodeURIComponent(activeId)),{headers:{Accept:'application/json'}});if(!response.ok)throw new Error('Profil outlet gagal dimuat.');const data=await response.json(),outlet=data.outlet;document.getElementById('profile-id').textContent=outlet.id_outlet;document.getElementById('profile-tap').textContent=outlet.tap||'–';document.getElementById('profile-name').textContent=outlet.nama_outlet||'–';document.getElementById('profile-kecamatan').textContent=outlet.kecamatan||'–';document.getElementById('profile-subtitle').textContent='Ubah mapping Sales Force dan hari PJP.';const sf=document.getElementById('profile-sf');sf.innerHTML='<option value="UNMAPPING">UNMAPPING</option>';data.sales.forEach(item=>sf.add(new Option(`${item.namasf} (${item.idsf})`,item.namasf)));sf.value=outlet.sf||'UNMAPPING';const pjp=document.getElementById('profile-pjp');pjp.innerHTML='';data.days.forEach(day=>pjp.add(new Option(day,day)));pjp.value=outlet.pjp||'UNMAPPING'}catch(error){message.textContent=error.message}});form.addEventListener('submit',async event=>{event.preventDefault();message.textContent='';save.disabled=true;try{const response=await fetch(updateUrl.replace('__ID__',encodeURIComponent(activeId)),{method:'PUT',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({sf:document.getElementById('profile-sf').value,pjp:document.getElementById('profile-pjp').value})});const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat()[0]||data.message||'Perubahan gagal disimpan.');window.location.reload()}catch(error){message.textContent=error.message;save.disabled=false}})}
</script></body></html>
