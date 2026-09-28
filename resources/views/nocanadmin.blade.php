@extends('layout.layout')

@section('content')
<div class="main-panel"><div class="content"><div class="page-inner">
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
    <div class="card premium-card">
        <div class="card-header"><h4 class="card-title mb-0">REKAPAN NOCAN MSP</h4></div>
        <div class="card-body pt-3"><div class="table-responsive">
            <table id="nocan-table" class="display table table-striped table-hover w-100">
                <thead><tr><th>Tanggal</th><th>Tap</th><th>Nomor</th><th>Booked By</th><th>Status</th><th>ID Outlet</th><th>Aksi</th></tr></thead>
                <tbody></tbody>
            </table>
        </div></div>
    </div>
</div></div></div>

<div class="modal fade" id="nocanEditModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header no-bd"><h5 class="modal-title">EDIT DATA</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <form method="post" id="nocan-edit-form" class="nocan-edit-form">
                @csrf
                <label for="nocan-number">NOMOR:</label><input type="text" id="nocan-number" class="form-control mb-1" disabled>
                <label for="nocan-price">HARGA:</label><input type="number" id="nocan-price" name="harga" class="form-control mb-1" min="0" required>
                <label for="nocan-tap">TAP:</label><input type="text" id="nocan-tap" name="tap" class="form-control mb-1" required>
                <label for="nocan-seller">PENJUAL:</label><input type="text" id="nocan-seller" name="penjual" class="form-control mb-1" required>
                <label for="nocan-division">DIVISI:</label>
                <select name="divisi" id="nocan-division" class="form-control mb-1" required><option value="outlet">Outlet</option><option value="karyawan">Karyawan</option><option value="ds">DS</option></select>
                <label for="nocan-outlet">ID OUTLET:</label><input type="number" id="nocan-outlet" name="outlet" class="form-control mb-1" min="1" required>
                <small class="form-text text-muted mb-2 outlet-help">Karyawan otomatis 1, DS otomatis 123.</small>
                <label for="nocan-status">STATUS:</label>
                <select id="nocan-status" class="form-control mb-2" name="status" required><option value="">--PILIH--</option><option value="PAID">PAID</option><option value="SOLD">SOLD</option><option value="BOOKING">BOOKING</option></select>
                <label for="nocan-date">UPDATE TANGGAL (JIKA BAYAR):</label><input type="date" id="nocan-date" name="tanggal" class="form-control mb-3" required>
                <button type="submit" class="btn btn-danger">Submit</button><button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </form>
        </div>
    </div></div>
</div>

<div class="modal fade" id="nocanResetModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header no-bd"><h5 class="modal-title">Reset Nomor</h5></div>
        <div class="modal-body"><p>Status nomor <strong id="reset-nocan-number"></strong> akan diubah menjadi READY.</p>
            <form method="post" id="nocan-reset-form">@csrf <button type="submit" class="btn btn-danger">Reset</button> <button type="button" class="btn btn-primary" data-dismiss="modal">Batal</button></form>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const editUrl = @json(route('nocanadmin.edit', ['id' => '__ID__']));
    const resetUrl = @json(route('nocanadmin.reset', ['id' => '__ID__']));
    const editForm = document.getElementById('nocan-edit-form');

    function syncDivision(resetOutlet) {
        const division = $('#nocan-division').val();
        const outlet = $('#nocan-outlet');
        const help = editForm.querySelector('.outlet-help');
        if (division === 'karyawan') { outlet.val('1').prop('readonly', true); help.textContent = 'ID otomatis 1 untuk Karyawan.'; }
        else if (division === 'ds') { outlet.val('123').prop('readonly', true); help.textContent = 'ID otomatis 123 untuk DS.'; }
        else { if (resetOutlet && ['1', '123'].includes(outlet.val())) outlet.val(''); outlet.prop('readonly', false); help.textContent = 'Masukkan ID outlet. ID 1 dan 123 tidak dapat digunakan.'; }
    }
    function warnReserved(value) {
        const message = value === '1' ? 'ID 1 khusus untuk divisi Karyawan.' : 'ID 123 khusus untuk divisi DS.';
        if (typeof swal === 'function') swal('ID Outlet tidak valid', message, 'warning'); else alert(message);
    }

    const table = $('#nocan-table').DataTable({
        processing: true, serverSide: true, searchDelay: 350, pageLength: 25, order: [[0, 'desc']], ajax: @json(route('nocanadmin.data')),
        columns: [
            {data:'tanggal', name:'tanggal', defaultContent:''}, {data:'tap', name:'tap', defaultContent:''},
            {data:'nomor', name:'nomor', defaultContent:''}, {data:'booked', name:'booked', defaultContent:''},
            {data:'status', name:'status', defaultContent:''}, {data:'outlet', name:'outlet', defaultContent:''},
            {data:null, orderable:false, searchable:false, render:function() { return '<div class="d-flex"><button type="button" class="btn btn-warning btn-round btn-sm mr-1 nocan-edit">Edit</button><button type="button" class="btn btn-danger btn-round btn-sm nocan-reset">Reset</button></div>'; }}
        ]
    });

    $('#nocan-table').on('click', '.nocan-edit', function() {
        const row = table.row($(this).closest('tr')).data();
        const outlet = row.outlet == null ? '' : String(row.outlet);
        editForm.action = editUrl.replace('__ID__', row.id);
        $('#nocan-number').val(row.nomor || ''); $('#nocan-price').val(row.harga || 0); $('#nocan-tap').val(row.tap || ''); $('#nocan-seller').val(row.booked || '');
        $('#nocan-division').val(outlet === '1' ? 'karyawan' : (outlet === '123' ? 'ds' : 'outlet'));
        $('#nocan-outlet').val(outlet); $('#nocan-status').val(String(row.status || '').toUpperCase()); $('#nocan-date').val(row.tanggal || '');
        syncDivision(false); $('#nocanEditModal').modal('show');
    });
    $('#nocan-table').on('click', '.nocan-reset', function() {
        const row = table.row($(this).closest('tr')).data();
        document.getElementById('nocan-reset-form').action = resetUrl.replace('__ID__', row.id);
        $('#reset-nocan-number').text(row.nomor || ''); $('#nocanResetModal').modal('show');
    });
    $('#nocan-division').on('change', function() { syncDivision(true); });
    $('#nocan-outlet').on('change', function() { if ($('#nocan-division').val() === 'outlet' && ['1','123'].includes(this.value.trim())) { const value=this.value.trim(); this.value=''; warnReserved(value); this.focus(); } });
    editForm.addEventListener('submit', function(event) { const outlet=$('#nocan-outlet').val().trim(); if ($('#nocan-division').val()==='outlet' && ['1','123'].includes(outlet)) { event.preventDefault(); warnReserved(outlet); } });
});
</script>
@endpush
