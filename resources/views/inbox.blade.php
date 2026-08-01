@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->has('error'))
                            <div class="alert alert-danger">
                                {{ $errors->first('error') }}
                            </div>
                        @endif

                        <div class="card premium-card">
                            <div class="card-header bg-white border-bottom py-3">
                                <div class="d-flex align-items-center">
                                    <h4 class="card-title text-indigo font-weight-bold">
                                        <i class="fas fa-inbox mr-2"></i>Terima Stock Dari TAP lain
                                    </h4>
                                </div>
                            </div>
                            {{-- card header --}}
                            <div class="card-body px-0 py-0">
                                <div class="table-responsive">
                                    <table id="add-row" class="table table-indigo table-hover w-100 mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Denom</th>
                                                <th>Quantity</th>
                                                <th>Pengirim</th>
                                                <th>Penerima</th>
                                                <th>Sn</th>
                                                <th style="width: 10%">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data as $row)
                                                <tr>
                                                    <td> {{ $row->tgl }}</td>
                                                    <td> {{ $row->denom }}</td>
                                                    <td> {{ number_format($row->qty) }}</td>
                                                    <td> {{ $row->idtap }}</td>
                                                    <td> {{ $row->penerima }}</td>
                                                    <td> {{ $row->sn }}</td>
                                                    <td>
                                                        @if ($row->status == 0)
                                                            <span class="badge badge-success">Approved</span>
                                                        @else
                                                            <button class="btn btn-warning btn-round ml-auto btn-sm"
                                                                data-toggle="modal"
                                                                data-target="#addRowModal{{ $row->idkeluar }}">
                                                                {{-- <i class="fa fa-trash"></i> --}}
                                                                Wait for Approval
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Area (Outside Table) -->
                        @foreach ($data as $row)
                            <div class="modal fade inbox-approval-modal" id="addRowModal{{ $row->idkeluar }}" role="dialog"
                                aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header no-bd">
                                            <h5 class="modal-title">
                                                Terima Stok Masuk dari <strong>TAP
                                                    {{ $row->idtap }}</strong>
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="{{ route('masuk.approve', $row->idkeluar) }}" method="post" class="form-approve">
                                                @csrf
                                                <h5>Pastikan Barang yang diterima sudah sesuai, stok
                                                    <strong>{{ $row->denom }}</strong> dengan Quantity
                                                    <strong>{{ number_format($row->qty) }}</strong>
                                                </h5>
                                                <input type="hidden" name="iddenom"
                                                    value="{{ $row->iddenom }}" class="form-control mb-1">
                                                <input type="hidden" name="pengirim"
                                                    value="{{ $row->idtap }}" class="form-control mb-1">
                                                <input type="hidden" name="penerima"
                                                    value="{{ $row->penerima }}" class="form-control mb-1">
                                                <input type="hidden" name="status"
                                                    value="{{ $row->status }}" class="form-control mb-1">
                                                <input type="hidden" name="qty"
                                                    value="{{ $row->qty }}" class="form-control mb-1">
                                                <div class="modal-footer no-bd">
                                                    <button type="submit"
                                                        class="btn btn-primary">Terima</button>
                                                    <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{-- col-md 12 --}}
                </div>
                {{-- row --}}
            </div>
            {{-- page inner --}}
        </div>
        {{-- content --}}
    </div>
    {{-- main panel --}}
    <style>
        .inbox-approval-modal { z-index: 2000; }
        .inbox-approval-modal + .modal-backdrop,
        body.modal-open .modal-backdrop { z-index: 1990; }
        .inbox-approval-modal .modal-dialog {
            margin-top: 100px;
            margin-bottom: 24px;
        }
        .inbox-approval-modal .modal-content {
            max-height: calc(100vh - 124px);
            overflow: hidden;
            border: 0;
            border-radius: 12px;
        }
        .inbox-approval-modal .modal-header { flex: 0 0 auto; }
        .inbox-approval-modal .modal-body { overflow-y: auto; }
        @media (max-width: 575.98px) {
            .inbox-approval-modal .modal-dialog { margin: 86px 12px 16px; }
            .inbox-approval-modal .modal-content { max-height: calc(100vh - 102px); }
        }
    </style>
@endsection
@push('scripts')
    <script>
        // Add Row
        $("#add-row").DataTable({
            pageLength: 5,
            order: [
                [0, "desc"]
            ]
        });
    </script>

    <script>
        // Add Row
        $("#stock").DataTable({
            pageLength: 10,
        });
    </script>

    {{-- table penjualan perdenom di home --}}
    <script>
        // Add Row
        $("#stock1").DataTable({
            searching: false,
            paging: false,
            info: false,
            order: [
                [1, "desc"]
            ]
        });
    </script>

    <script>
        // Add Row
        $("#stock2").DataTable({
            searching: false,
            paging: false,
            info: false,
            order: [
                [1, "desc"]
            ]
        });
    </script>
@endpush
