@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- Page Header --}}
                <div class="page-header">
                    <h4 class="page-title">Input Stok Keluar SF</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Input</strong>
                                <div class="text-muted small">
                                    Input data stok keluar untuk Sales Force
                                </div>
                            </div>

                            <form action="{{ url('sf-keluar') }}" method="POST" id="mainForm">
                                @csrf

                                @php use Illuminate\Support\Str; @endphp
                                <input type="hidden" name="trx_id" value="{{ Str::uuid() }}">


                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" id="date" name="tgl" class="form-control"
                                                    value="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>TAP</label>
                                                <select name="idtap" id="kategoritap" class="form-control select2"
                                                    required>
                                                    <option></option>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}">{{ $row->idtap }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- SF --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Sales Force</label>
                                                <select name="idsf" id="idsf" class="form-control select2" disabled
                                                    required>
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Denom --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Denom</label>
                                                <select name="iddenom" id="iddenom" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($denom as $row)
                                                        <option value="{{ $row->iddenom }}">
                                                            {{ $row->denom }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Quantity --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" id="qty" class="form-control"
                                                    min="1" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN Awal - SN Akhir</label>
                                                <input type="text" name="tambahanket" id="tambahanket"
                                                    class="form-control" placeholder="SN Awal - SN Akhir">
                                            </div>
                                        </div>

                                        {{-- Stok --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Stok Tersedia</label>
                                                <input type="text" id="stok_info" class="form-control mb-1" readonly>
                                                <div class="text-danger small d-none" id="stok_warning" style="font-weight: 600;">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> Quantity melebihi stok tersedia
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                {{-- FOOTER --}}
                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('sf-keluar') }}" class="btn btn-light">Kembali</a>

                                    {{-- tombol preview --}}
                                    <button type="submit" id="submitBtn" class="btn btn-primary">
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            let currentStock = 0;
            let sfStockData = {}; // Cache data stok SF

            /* ================= SELECT2 ================= */
            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            $(document).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-search__field')?.focus();
                }, 50);
            });

            /* ================= TAP → SF ================= */
            $('#kategoritap').on('change', function() {
                const idtap = $(this).val();
                const $sf = $('#idsf');

                $sf.prop('disabled', true).empty().trigger('change');

                if (!idtap) return;

                $.post('{{ route('ajax.get-sf-keluar') }}', {
                        idtap,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        let options = '<option value="">-- Pilih SF --</option>';
                        res.forEach(item => {
                            options += `<option value="${item.idsf}">${item.namasf}</option>`;
                        });
                        $sf.html(options)
                            .prop('disabled', false)
                            .trigger('change');
                    });
            });

            /* ================= RESET SAAT SF GANTI ================= */
            $('#idsf').on('change', function() {
                const idsf = $(this).val();

                $('#iddenom').val(null).trigger('change');
                $('#qty').val('');
                $('#tambahanket').val('');
                $('#stok_info').val('');
                $('#stok_warning').addClass('d-none');
                $('#submitBtn').prop('disabled', true);
                
                sfStockData = {}; // Clear cache

                if (!idsf) return;

                // Load all stock for this SF once
                $.post('{{ route('ajax.get-all-stock') }}', {
                        idsf: idsf,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        sfStockData = res;
                        // Trigger change standar agar iddenom load stoknya ke kotak stok_info
                        $('#iddenom').trigger('change'); 
                    });
            });

            /* ================= DENOM → LOAD STOK ================= */
            $('#iddenom').on('change', function() {
                const iddenom = $(this).val();
                
                if (!iddenom) {
                    currentStock = 0;
                    return;
                }

                // Ambil dari cache lokal (cepat)
                currentStock = parseInt(sfStockData[iddenom]) || 0;

                if (currentStock <= 0) {
                    $('#stok_info').val('Stok habis');
                    $('#stok_warning').removeClass('d-none');
                    $('#submitBtn').prop('disabled', true);
                } else {
                    $('#stok_info').val(currentStock + ' pcs');
                    $('#stok_warning').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                }
            });

            /* ================= VALIDASI QTY ================= */
            $('#qty').on('input', function() {
                const qty = parseInt($(this).val()) || 0;

                if (qty > currentStock) {
                    $(this).addClass('is-invalid');
                    $('#stok_warning').removeClass('d-none');
                    $('#submitBtn').prop('disabled', true);
                } else {
                    $(this).removeClass('is-invalid');
                    $('#stok_warning').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                }
            });

            /* ================= ANTI DOUBLE SUBMIT ================= */
            let submitting = false;
            $('#mainForm').on('submit', function() {
                if (submitting) return false;
                submitting = true;

                $('#submitBtn')
                    .prop('disabled', true)
                    .text('Menyimpan...');
            });

        });

        // Tanggal maksimal hari ini dan minimal sebulan yang lalu
        document.addEventListener("DOMContentLoaded", function() {
            const inputDate = document.getElementById('date');
            const today = new Date();
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);

            // Mengatur tanggal maksimal hingga hari ini
            inputDate.max = today.toISOString().split('T')[0];
            // Mengatur tanggal minimal ke satu bulan yang lalu
            inputDate.min = monthAgo.toISOString().split('T')[0];
        });
    </script>
@endpush
