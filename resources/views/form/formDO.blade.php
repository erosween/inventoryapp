@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="page-header">
                    <h4 class="page-title">Input DO Masuk TAP</h4>
                </div>

                <div class="row justify-content-center">
                    <div class="col-xl-8 col-lg-9 col-md-11">
                        <div class="card shadow-sm">

                            <div class="card-header">
                                <strong>Form Input</strong>
                                <div class="text-muted small">
                                    Input DO masuk ke TAP
                                </div>
                            </div>

                            <form action="{{ url('DO') }}" method="POST" id="formDO">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" id="date" class="form-control"
                                                    required>
                                            </div>
                                        </div>

                                        {{-- Kategori Segel --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Kategori Segel</label>
                                                <select name="kategorisegel" class="form-control select2" required>
                                                    <option></option>
                                                    <option value="SEGEL">SEGEL</option>
                                                    <option value="V33">BYU</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- BO / Penerima --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>BO / Penerima</label>
                                                <select name="penerima" id="bo" class="form-control select2"
                                                    required>
                                                    <option></option>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->namabo }}">
                                                            {{ $row->namabo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>TAP Penerima</label>
                                                <select name="idtap" id="tappenerima" class="form-control select2"
                                                    disabled required>
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- No DO --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>No DO</label>
                                                <input type="text" name="nomordo" class="form-control" required>
                                            </div>
                                        </div>

                                        {{-- Week --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Minggu</label>
                                                <select name="week" class="form-control" required>
                                                    <option value="W1">W1</option>
                                                    <option value="W2">W2</option>
                                                    <option value="W3">W3</option>
                                                    <option value="W4">W4</option>
                                                    <option value="W5">W5</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Qty --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" class="form-control" min="1"
                                                    required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>SN Awal - SN Akhir</label>
                                                <input type="text" name="sn" class="form-control" required>
                                            </div>
                                        </div>

                                        <input type="hidden" name="pengirim" value="DO">

                                    </div>
                                </div>

                                <div class="card-footer text-end">
                                    <a href="{{ url('DO') }}" class="btn btn-light">Back</a>
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

            $('.select2').select2({
                placeholder: 'Pilih / Cari...',
                width: '100%'
            });

            /* BO → TAP */
            $('#bo').on('change', function() {

                let bo = $(this).val();
                let $tap = $('#tappenerima');

                $tap.prop('disabled', true).empty().trigger('change');

                if (!bo) return;

                $.post('{{ route('do.get-tap') }}', {
                    bo: bo,
                    _token: '{{ csrf_token() }}'
                }).done(function(res) {
                    $tap.html(res)
                        .prop('disabled', false)
                        .trigger('change');
                });

            });

        });

        /* limit tanggal */
        document.addEventListener("DOMContentLoaded", function() {
            const inputDate = document.getElementById('date');
            const today = new Date();
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);

            inputDate.max = today.toISOString().split('T')[0];
            inputDate.min = monthAgo.toISOString().split('T')[0];
        });
    </script>
@endpush
