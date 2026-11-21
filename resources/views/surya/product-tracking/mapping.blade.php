@extends('surya.layouts.workspace')

@section('title', 'Petakan Kolom '.(str_starts_with($upload->kind,'so-')?'SO '.strtoupper(substr($upload->kind,3)):'Digipos'))
@section('active-page', $upload->kind==='warehouse'?'product-tracking-warehouse':'product-tracking-upload')
@section('description', 'Tinjau contoh data dan tentukan kolom yang menjadi identitas transaksi.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">@endpush

@section('content')
    @php($isSerialDateUpload = $upload->kind !== 'warehouse')
    <section class="pt-card"><div class="pt-result-head"><div><h2>Petakan kolom file</h2><p class="pt-subtitle mb-0">{{ $upload->filename }} · {{ $upload->kind==='scan'?'Tanggal scan akan dibaca dari file':(str_starts_with($upload->kind,'so-')?'Tanggal redeem akan dibaca dari file':'Warehouse Digipos') }}</p></div><form id="pt-cancel-upload" method="post" action="{{ route('surya.product-tracking.imports.cancel',$upload->id) }}" onsubmit="return confirm('Batalkan pemetaan dan hapus file upload ini?')">@csrf</form><button class="pt-button" type="submit" form="pt-cancel-upload"><i class="fas fa-times"></i> Batal</button></div>
        <form method="post" action="{{ route('surya.product-tracking.imports.complete',$upload->id) }}" id="pt-mapping-form" data-loading-message="Mengimpor {{ $upload->kind==='scan'?'data ST Digipos':(str_starts_with($upload->kind,'so-')?'data redeem SO':'data Warehouse') }}. File besar mungkin memerlukan waktu beberapa saat…" data-loading-label="Mengimpor…">
            @csrf
            <div class="pt-mapping-grid">
                @foreach([
                    'serial_column'=>'SN / Serial Number',
                    'outlet_column'=>'ID Outlet (wajib)',
                    'sf_column'=>'Sales Force',
                    'date_column'=>$upload->kind==='scan'?'Tanggal scan / ST (wajib)':(str_starts_with($upload->kind,'so-')?'Tanggal redeem / SO (wajib)':'Tanggal ST'),
                    'product_column'=>'Produk / Denom',
                ] as $key=>$label)
                    <div class="pt-field"><label for="{{ $key }}">{{ $label }}</label><select id="{{ $key }}" name="{{ $key }}" {{ $key==='outlet_column'?'required':'' }}><option value="">— Tidak dipetakan —</option>@foreach($headers as $column=>$header)<option value="{{ $column }}">{{ $header }}</option>@endforeach</select></div>
                @endforeach
            </div>
            <p class="pt-muted mt-3">Kolom SN wajib dan harus unik di dalam file ini. @if($isSerialDateUpload) Isi kolom tanggal sebagai teks dengan format <strong>dd-mmm-yyyy</strong>, contoh <strong>12-oct-2026</strong>; semua baris harus memakai tanggal yang sama. @endif Sistem menyimpan serial secara berurutan dan menggabungkan SN berdekatan bila tanggal, outlet, SF, serta denom sama.</p>
            @error('file')<div class="pt-alert error">{{ $message }}</div>@enderror
            @if($duplicateReportAvailable)<div class="pt-actions mt-2"><a class="pt-button" href="{{ route('surya.product-tracking.imports.duplicates.download',$upload->id) }}"><i class="fas fa-download"></i> Unduh daftar SN duplikat CSV</a></div>@endif
            @error('serial_column')<div class="pt-alert error">{{ $message }}</div>@enderror
            @error('date_column')<div class="pt-alert error">{{ $message }}</div>@enderror
            <div class="pt-actions pt-import-actions"><button class="pt-button primary" type="submit"><i class="fas fa-check"></i><span>Impor {{ $upload->kind==='scan'?'Hasil Scan':(str_starts_with($upload->kind,'so-')?'SO '.strtoupper(substr($upload->kind,3)):'Warehouse') }}</span></button><span class="pt-loading-status" role="status" aria-live="polite" hidden></span></div>
        </form>
    </section>
    <section class="pt-card"><h2>Pratinjau file</h2><div class="pt-table-wrap"><table class="pt-table"><thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>@forelse($preview as $row)<tr>@foreach($headers as $column=>$header)<td>{{ $row[$column]??'' }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headers) }}" class="pt-empty">Tidak ada baris data pada pratinjau.</td></tr>@endforelse</tbody></table></div></section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{const suggestions={serial_column:['sn','serial','serialno','serialnumber','nomorseri','sndigipos','serialnumberdigipos','serialnumbervoucher','nomorserial','iccid'],outlet_column:['idoutlet','iddigipos','kodeoutlet','outletid','outletcode','customerid','idcustomer','clientid','kodepelanggan'],sf_column:['sf','salesforce','namasf','salesforcename','namasalesforce','salesforcename','kodesf','userloginsf'],date_column:['tglst','tanggalst','tanggaljual','tgljual','tanggal','date','soldat','transactiondate','tanggaltransaksi'],product_column:['produk','product','denom','namaproduk','productname','productdescription','namaproduk']};for(const [id,names]of Object.entries(suggestions)){const select=document.getElementById(id);if(!select)continue;const found=[...select.options].find(option=>names.includes(option.text.trim().toLowerCase().replace(/[^a-z0-9]/g,'')));if(found)select.value=found.value;}
const form=document.getElementById('pt-mapping-form');form?.addEventListener('submit',()=>{if(form.dataset.submitting==='true')return;form.dataset.submitting='true';form.setAttribute('aria-busy','true');const button=form.querySelector('button[type="submit"]');const status=form.querySelector('.pt-loading-status');if(button){button.disabled=true;button.innerHTML='<span class="pt-spinner" aria-hidden="true"></span><span>Mengimpor…</span>';}if(status){status.textContent=form.dataset.loadingMessage;status.hidden=false;}});
});
</script>
@endpush
