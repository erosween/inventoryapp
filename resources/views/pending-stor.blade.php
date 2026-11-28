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
                    <div><h4 class="card-title mb-1 font-weight-bold"><i class="fas fa-hourglass-half mr-2"></i>Pending Stor (Konsinyasi)</h4><div class="text-muted small">Qty mengalokasikan stok tersedia agar tidak dipakai transaksi lain. Stok fisik SF dipotong sesuai qty yang dibayar; sisa qty tetap pending.</div></div>
                    <input type="text" id="daterange" class="form-control" style="max-width:260px" autocomplete="off" placeholder="Pilih periode">
                </div>
            </div>
            <div class="card-body table-responsive">
                <table id="pending-stor-table" class="table table-hover w-100">
                    <thead><tr><th>Tanggal Jual</th><th>Denom</th><th>Qty</th><th>TAP</th><th>SF</th><th>ID Outlet</th><th>SN Awal</th><th>SN Akhir</th><th>Keterangan</th><th>Aksi</th></tr></thead>
                </table>
            </div>
        </div>
        <div class="modal fade" id="pending-payment-modal" tabindex="-1" role="dialog" aria-labelledby="pending-payment-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow">
                    <form method="POST" id="pending-payment-form">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title font-weight-bold" id="pending-payment-title">Konfirmasi Pembayaran Konsinyasi</h5>
                                <div class="small text-muted" id="pending-payment-summary"></div>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="pending-paid-qty" class="font-weight-bold">Qty dibayar sekarang</label>
                                <input type="number" class="form-control" id="pending-paid-qty" name="paid_qty" min="1" required>
                                <small class="form-text text-muted" id="pending-paid-qty-help"></small>
                            </div>
                            <div id="pending-payment-serial-fields" class="border rounded p-3 bg-light">
                                <div class="small text-muted mb-2">Isi rentang SN yang dibayar. Sisa rentang otomatis tetap Pending Stor.</div>
                                <div class="form-row">
                                    <div class="form-group col-6 mb-0">
                                        <label for="pending-paid-sn-start">SN awal dibayar</label>
                                        <input type="text" inputmode="numeric" class="form-control" id="pending-paid-sn-start" name="paid_sn_start" autocomplete="off">
                                    </div>
                                    <div class="form-group col-6 mb-0">
                                        <label for="pending-paid-sn-end">SN akhir dibayar</label>
                                        <input type="text" inputmode="numeric" class="form-control" id="pending-paid-sn-end" name="paid_sn_end" autocomplete="off">
                                    </div>
                                </div>
                                <small class="form-text text-muted mt-2" id="pending-payment-serial-help"></small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i>Konfirmasi Sudah Bayar</button>
                        </div>
                    </form>
                </div>
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
    const $paymentModal = $('#pending-payment-modal');
    const $paymentForm = $('#pending-payment-form');
    const $paidQty = $('#pending-paid-qty');
    const $snStart = $('#pending-paid-sn-start');
    const $snEnd = $('#pending-paid-sn-end');
    const $serialFields = $('#pending-payment-serial-fields');
    let serialBounds = null;

    function syncPaidEnd() {
        if (!serialBounds || !$snStart.val() || !$paidQty.val()) return;
        try {
            const startNumber = BigInt($snStart.val());
            const endNumber = startNumber + BigInt($paidQty.val()) - 1n;
            $snEnd.val(endNumber.toString().padStart(serialBounds.length, '0'));
        } catch (_) {}
    }

    $(document).on('click', '.js-open-pending-payment', function () {
        const button = this;
        const maxQty = Number(button.dataset.qty || 0);
        const startSn = button.dataset.snStart || '';
        const endSn = button.dataset.snEnd || '';
        const hasRange = startSn !== '' && endSn !== '' && button.dataset.serialMode !== 'broken';

        $paymentForm.attr('action', button.dataset.action);
        $paidQty.attr('max', maxQty).val(maxQty);
        $('#pending-payment-summary').text('Sisa konsinyasi: ' + maxQty.toLocaleString('id-ID') + ' qty');
        $('#pending-paid-qty-help').text('Maksimal ' + maxQty.toLocaleString('id-ID') + ' qty.');
        serialBounds = hasRange ? {start: startSn, end: endSn, length: startSn.length} : null;
        $serialFields.toggle(hasRange);
        $snStart.prop('required', hasRange).val(hasRange ? startSn : '');
        $snEnd.prop('required', hasRange).val(hasRange ? endSn : '');
        $('#pending-payment-serial-help').text(hasRange ? 'Rentang SN tersedia: ' + startSn + ' – ' + endSn : '');
        $paymentModal.modal('show');
    });

    $paidQty.on('input change', syncPaidEnd);
    $snStart.on('input change', syncPaidEnd);

    $range.daterangepicker({startDate:start,endDate:end,locale:{format:'YYYY-MM-DD',separator:' - ',applyLabel:'Pilih',cancelLabel:'Batal'}})
        .on('apply.daterangepicker', function (event, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            table.ajax.reload();
        });
});
</script>
@endpush
