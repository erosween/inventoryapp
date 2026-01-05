@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- ERROR --}}
                @if ($errors->has('error'))
                    <div class="alert alert-danger">
                        {{ $errors->first('error') }}
                    </div>
                @endif

                {{-- HEADER --}}
                <div class="page-header">
                    <h4 class="page-title">Input Stok Keluar BO</h4>
                </div>

                <div class="row justify-content-center">
                    <div class="col-xl-8 col-lg-9 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Keluar BO</strong>
                                <div class="text-muted small">
                                    Pastikan qty sesuai stok BO
                                </div>
                            </div>

                            <form action="{{ route('bo.store') }}" method="POST" id="formKeluarBO">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- TANGGAL --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-2">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" id="date" class="form-control"
                                                    required>
                                            </div>
                                        </div>

                                        {{-- KATEGORI --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-2">
                                                <label>Kategori Segel</label>
                                                <select name="iddenom" class="form-control select2" required>
                                                    <option></option>
                                                    <option value="SEGEL">SEGEL</option>
                                                    <option value="V16">SEGEL ROAMAX</option>
                                                    <option value="V33">BYU</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- BO --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-2">
                                                <label>BO (Pengirim)</label>
                                                <select name="pengirim" id="kategorisegelmasuk" class="form-control select2"
                                                    required>
                                                    <option></option>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idsf }}">
                                                            {{ $row->namabo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-2">
                                                <label>TAP (Penerima)</label>
                                                <select name="penerima" id="tappenerima" class="form-control select2"
                                                    required>
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- QTY --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-2">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" class="form-control" min="1"
                                                    required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-2">
                                                <label>SN (Opsional)</label>
                                                <input type="text" name="sn" class="form-control"
                                                    placeholder="SN Awal - SN Akhir">
                                            </div>
                                        </div>

                                        {{-- KETERANGAN --}}
                                        <div class="col-md-12">
                                            <div class="form-group mb-2">
                                                <label>Keterangan Tambahan (Opsional)</label>
                                                <input type="text" name="tambahanket" class="form-control">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('bo') }}" class="btn btn-light">
                                        Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
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

            /* =====================
               SELECT2
            ===================== */
            $('.select2').select2({
                placeholder: 'Pilih / Cari…',
                width: '100%',
                allowClear: true
            });

            $(document).on('select2:open', () => {
                document.querySelector('.select2-search__field')?.focus();
            });

            /* =====================
               DATE LIMIT (H-30)
            ===================== */
            const inputDate = document.getElementById('date');
            const today = new Date();
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);

            inputDate.max = today.toISOString().split('T')[0];
            inputDate.min = monthAgo.toISOString().split('T')[0];

            /* =====================
               AJAX TAP
            ===================== */
            $('#kategorisegelmasuk').on('change', function() {
                let idtap = $(this).val();
                if (!idtap) return;

                $('#tappenerima').html('<option>Loading...</option>');

                $.post('{{ url('form/formkeluarbo') }}', {
                    idtap: idtap,
                    _token: '{{ csrf_token() }}'
                }).done(function(res) {
                    $('#tappenerima').html(res).trigger('change');
                });
            });

            /* =====================
               PREVENT DOUBLE SUBMIT
            ===================== */
            $('#formKeluarBO').on('submit', function() {
                $('#submitBtn').prop('disabled', true)
                    .text('Processing...');
            });

        });
    </script>
@endpush
