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
    #stock-adjustment-card .denom-table th { white-space: nowrap; }
    #stock-adjustment-card .denom-table .qty-input { min-width: 130px; }
    #stock-adjustment-card .denom-search-wrap { max-width: 420px; }
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
                        </div>
                        </div>

                        <div class="adjustment-section mb-0">
                            <div class="section-title"><i class="fas fa-edit mr-1"></i> Nilai Penyesuaian</div>
                            <div class="form-group denom-search-wrap">
                                <label for="denom_search">Cari Denom</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="search" id="denom_search" class="form-control" placeholder="Cari grup atau nama denom..." autocomplete="off">
                                </div>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered table-hover denom-table mb-0">
                                    <thead class="thead-light">
                                        <tr><th>Grup</th><th>Denom</th><th class="text-right">Qty Saat Ini</th><th>Qty Baru</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($denoms as $denom)
                                            <tr data-denom="{{ $denom->iddenom }}">
                                                <td>{{ $denom->group_name }}</td>
                                                <td>{{ $denom->denom }}</td>
                                                <td class="text-right font-weight-bold current-qty">-</td>
                                                <td>
                                                    <input type="number" name="stocks[{{ $denom->iddenom }}]" value="{{ old('stocks.' . $denom->iddenom) }}" class="form-control qty-input" min="0" max="2147483647" placeholder="Tidak diubah">
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr id="denom_empty_result" class="d-none">
                                            <td colspan="4" class="text-center text-muted py-4">Denom tidak ditemukan.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row align-items-end">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Alasan Penyesuaian</label>
                                    <textarea name="reason" class="form-control" rows="2" minlength="5" maxlength="500" required>{{ old('reason') }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-3">
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

    function loadCurrentStocks() {
        const targetId = $('#target_id').val();
        if (!targetId) {
            $('.current-qty').text('-');
            return;
        }

        $('.current-qty').text('Memuat...');
        $.get('{{ route('stock-adjustment.current') }}', {
            target_type: $('#target_type').val(),
            target_id: targetId
        }).done(response => {
            $('.denom-table tbody tr').each(function () {
                const stock = response.stocks[$(this).data('denom')] ?? 0;
                $(this).find('.current-qty').text(Number(stock).toLocaleString('id-ID'));
            });
        }).fail(() => {
            $('.current-qty').text('Gagal memuat');
        });
    }

    function filterDenoms() {
        const keyword = $('#denom_search').val().trim().toLocaleLowerCase('id-ID');
        let visibleCount = 0;

        $('.denom-table tbody tr[data-denom]').each(function () {
            const matches = $(this).text().toLocaleLowerCase('id-ID').includes(keyword);
            $(this).toggle(matches);
            if (matches) visibleCount++;
        });

        $('#denom_empty_result').toggleClass('d-none', visibleCount > 0);
    }

    $(function () {
        $('#target_id').select2({
            placeholder: 'Pilih / cari target',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#stock-adjustment-card')
        });
        $('#target_type').on('change', renderTargets);
        $('#target_id').on('change', loadCurrentStocks);
        $('#denom_search').on('input', filterDenoms);
        renderTargets();
    });
</script>
@endpush
