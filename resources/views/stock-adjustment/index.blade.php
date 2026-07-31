@extends('layout.layout')

@section('content')
<style>
    #stock-adjustment-card .adjustment-section {
        background: #f8fafc;
        border: 1px solid #e8edf3;
        border-radius: 10px;
        padding: 18px 18px 6px;
        margin-bottom: 18px;
    }
    #stock-adjustment-card .section-title {
        color: #334155;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .25px;
        margin-bottom: 12px;
        text-transform: uppercase;
    }
    #stock-adjustment-card .select2-container { width: 100% !important; }
    #stock-adjustment-card .stock-current {
        background: #eef2f7;
        color: #1e293b;
        font-size: 18px;
    }
</style>
<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="card" id="stock-adjustment-card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Penyesuaian Stok</h4>
                    <p class="text-muted mb-0">Ubah saldo stok gudang TAP atau stok petugas. Seluruh perubahan dicatat pada Audit Trail.</p>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('stock-adjustment.update') }}" id="stock-adjustment-form">
                        @csrf
                        <div class="adjustment-section">
                            <div class="section-title"><i class="fas fa-crosshairs mr-1"></i> Pilih Target Stok</div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Jenis Stok</label>
                                    <select name="target_type" id="target_type" class="form-control" required>
                                        <option value="tap">Gudang TAP</option>
                                        <option value="sales">Petugas / Sales</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Target</label>
                                    <select name="target_id" id="target_id" class="form-control searchable-select" required></select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Denom</label>
                                    <select name="iddenom" id="iddenom" class="form-control searchable-select" required>
                                        <option value="">Pilih denom</option>
                                        @foreach ($denoms as $denom)
                                            <option value="{{ $denom->iddenom }}">{{ $denom->group_name }} — {{ $denom->denom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="adjustment-section mb-0">
                            <div class="section-title"><i class="fas fa-edit mr-1"></i> Nilai Penyesuaian</div>
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Stok Saat Ini</label>
                                    <input type="text" id="current_stock" class="form-control font-weight-bold stock-current" value="-" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Stok Baru</label>
                                    <input type="number" name="stock" class="form-control" min="0" max="2147483647" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Alasan Penyesuaian</label>
                                    <textarea name="reason" class="form-control" rows="2" minlength="5" maxlength="500" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Yakin ingin mengubah saldo stok ini?')">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const tapTargets = @json($taps->map(fn ($tap) => ['id' => $tap, 'label' => $tap])->values());
    const salesTargets = @json($sales->map(fn ($sf) => [
        'id' => $sf->idsf,
        'label' => $sf->idtap . ' — ' . $sf->namasf . ' (' . $sf->idsf . ')',
    ])->values());

    function renderTargets() {
        const targets = $('#target_type').val() === 'tap' ? tapTargets : salesTargets;
        const targetSelect = $('#target_id').empty();
        targets.forEach(item => {
            $('<option>').val(item.id).text(item.label).appendTo(targetSelect);
        });
        targetSelect.val(null).trigger('change');
    }

    function loadCurrentStock() {
        const targetId = $('#target_id').val();
        const denom = $('#iddenom').val();
        if (!targetId || !denom) {
            $('#current_stock').val('-');
            return;
        }

        $('#current_stock').val('Memuat...');
        $.get('{{ route('stock-adjustment.current') }}', {
            target_type: $('#target_type').val(),
            target_id: targetId,
            iddenom: denom
        }).done(response => {
            $('#current_stock').val(Number(response.stock).toLocaleString('id-ID'));
        }).fail(() => {
            $('#current_stock').val('Gagal memuat');
        });
    }

    $(function () {
        $('#target_id').select2({
            placeholder: 'Pilih / cari target',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#stock-adjustment-card')
        });
        $('#iddenom').select2({
            placeholder: 'Pilih / cari denom',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#stock-adjustment-card')
        });

        $('#target_type').on('change', renderTargets);
        $('#target_id, #iddenom').on('change', loadCurrentStock);
        renderTargets();
    });
</script>
@endpush
