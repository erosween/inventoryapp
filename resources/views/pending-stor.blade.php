@extends('layout.layout')

@section('content')
<div class="main-panel">
    <div class="content"><div class="page-inner">
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        <div class="card premium-card">
            <div class="card-header py-3 px-4 bg-white border-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div><h4 class="card-title mb-1 font-weight-bold"><i class="fas fa-hourglass-half mr-2"></i>Pending Stor (Konsinyasi)</h4><div class="text-muted small">Qty mengalokasikan stok tersedia agar tidak dipakai transaksi lain. Stok fisik SF dipotong setelah pembayaran dikonfirmasi.</div></div>
                    <input type="text" id="daterange" class="form-control" style="max-width:260px" autocomplete="off" placeholder="Pilih periode">
                </div>
            </div>
            <div class="card-body table-responsive">
                <table id="pending-stor-table" class="table table-hover w-100">
                    <thead><tr><th>Tanggal Jual</th><th>Denom</th><th>Qty</th><th>TAP</th><th>SF</th><th>ID Outlet</th><th>SN Awal</th><th>SN Akhir</th><th>Keterangan</th><th>Aksi</th></tr></thead>
                </table>
            </div>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const start = moment().startOf('month'), end = moment().endOf('month');
    const $range = $('#daterange');
    $range.val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
    const table = $('#pending-stor-table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        order: [[0, 'desc']],
        ajax: { url: @json(route('pending-stor.data')), data: d => { d.daterange = $range.val(); } },
        columns: [
            {data:'tgl',name:'f.tgl'}, {data:'denom',name:'d.denom'},
            {data:'qty',name:'f.qty',className:'text-right'}, {data:'idtap',name:'f.idtap'},
            {data:'namasf',name:'i.namasf'}, {data:'id_outlet',name:'f.id_outlet',defaultContent:'—'},
            {data:'sn_awal',name:'f.sn_awal',defaultContent:'—'}, {data:'sn_akhir',name:'f.sn_akhir',defaultContent:'—'},
            {data:'tambahanket',name:'f.tambahanket',defaultContent:'—'},
            {data:'action',orderable:false,searchable:false}
        ]
    });
    $range.daterangepicker({startDate:start,endDate:end,locale:{format:'YYYY-MM-DD',separator:' - ',applyLabel:'Pilih',cancelLabel:'Batal'}})
        .on('apply.daterangepicker', function (event, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            table.ajax.reload();
        });
});
</script>
@endpush
