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
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link " href="{{ url('stock') }}" role="tab">Stock All</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link " href="{{ url('stocktap') }}" role="tab">Stock Gudang</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link active" href="{{ url('stocksf') }}" role="tab">Stock SF</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="col-auto mb-3">
                                    <a href="{{ url('exportexcelsf') }}" class="btn btn-success btn-sm btn-rounded"><i
                                            class='fas fa-file-export'></i>Export</a>
                                </div>
                                <div class="table-responsive">
                                    <table id="stock" class="display table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>NO</th>
                                                <th>TAP</th>
                                                <th>SF</th>
                                                <th>SEGEL</th>
                                                <th>5GB/1hari</th>
                                                <th>5GB/2hari</th>
                                                <th>5GB/3hari</th>
                                                {{-- <th>RoaMAX SEGEL</th> --}}
                                                {{-- <th>1GB/1hari</th> --}}
                                                <th>2GB/1hari</th>
                                                <th>4GB/1hari</th>
                                                <th>8GB/2hari</th>
                                                <th>2GB/3hari ZONA 1</th>
                                                <th>2GB/3hari ZONA 2</th>
                                                {{-- <th>2.5GB/3hari</th> --}}
                                                <th>3GB/3hari</th>
                                                <th>4GB/3hari</th>
                                                <th>4GB/5hari</th>
                                                <th>4.5GB/3hari</th>
                                                <th>5GB/5hari</th>
                                                {{-- <th>2.5GB/5hari ZONA 1</th> --}}
                                                <th>3GB/5hari</th>
                                                <th>7GB/7hari</th>
                                                <th>10GB/7hari</th>
                                                <th>10GB/30hari</th>
                                                <th>11GB/30hari</th>
                                                <th>12GB/30hari</th>
                                                <th>18GB/30hari</th>
                                                <th>20GB/30hari</th>
                                                <th>30GB/30hari</th>
                                                <th>VOICE 30 HARI</th>
                                                <th>BYU SEGEL</th>
                                                <th>BYU 1GB/1hari</th>
                                                <th>BYU 2GB/1hari</th>
                                                <th>BYU 2GB/3hari</th>
                                                <th>BYU 3GB/3hari</th>
                                                <th>BYU 4GB/3hari</th>
                                                <th>BYU 2.5GB/5hari</th>
                                                <th>BYU 7.5GB/5hari</th>
                                                <th>BYU 3GB/7hari</th>
                                                <th>BYU 4GB/7hari</th>
                                                <th>BYU 5GB/7hari</th>
                                                <th>BYU 6.5GB/7hari</th>
                                                <th>BYU 7.5GB/7hari</th>
                                                <th>BYU 7GB/14hari</th>
                                                <th>BYU 10GB/14hari</th>
                                                <th>BYU KAGET 3GB/30hari</th>
                                                <th>BYU KAGET 7GB/30hari</th>
                                                <th>BYU KAGET 9GB/30hari</th>
                                                <th>BYU KAGET 14GB/30hari</th>
                                                <th>BYU KAGET 20GB/30hari</th>
                                                <th>GRAND TOTAL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data as $row)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td> {{ $row->idtap }}</td>
                                                    <td> {{ $row->namasf }}</td>
                                                    <td> {{ number_format($row->SEGEL) }}</td>
                                                    <td> {{ number_format($row->V48) }}</td>
                                                    <td> {{ number_format($row->V49) }}</td>
                                                    <td> {{ number_format($row->V50) }}</td>
                                                    {{-- <td> {{ number_format($row -> V16 )}}</td> --}}
                                                    {{-- <td> {{ number_format($row->V32) }}</td> --}}
                                                    <td> {{ number_format($row->V42) }}</td>
                                                    <td> {{ number_format($row->V59) }}</td>
                                                    <td> {{ number_format($row->V63) }}</td>
                                                    <td> {{ number_format($row->V2) }}</td>
                                                    <td> {{ number_format($row->V24) }}</td>
                                                    {{-- <td> {{ number_format($row->V41) }}</td> --}}
                                                    <td> {{ number_format($row->V30) }}</td>
                                                    <td> {{ number_format($row->V64) }}</td>
                                                    <td> {{ number_format($row->V6) }}</td>
                                                    <td> {{ number_format($row->V3) }}</td>
                                                    <td> {{ number_format($row->V7) }}</td>
                                                    {{-- <td> {{ number_format($row -> V5 )}}</td> --}}
                                                    <td> {{ number_format($row->V31) }}</td>
                                                    <td> {{ number_format($row->V28) }}</td>
                                                    <td> {{ number_format($row->V29) }}</td>
                                                    <td> {{ number_format($row->V65) }}</td>
                                                    <td> {{ number_format($row->V47) }}</td>
                                                    <td> {{ number_format($row->V54) }}</td>
                                                    <td> {{ number_format($row->V53) }}</td>
                                                    <td> {{ number_format($row->V66) }}</td>
                                                    <td> {{ number_format($row->V55) }}</td>
                                                    <td> {{ number_format($row->V15) }}</td>
                                                    <td> {{ number_format($row->V33) }}</td>
                                                    <td> {{ number_format($row->V44) }}</td>
                                                    <td> {{ number_format($row->V45) }}</td>
                                                    <td> {{ number_format($row->V34) }}</td>
                                                    <td> {{ number_format($row->V51) }}</td>
                                                    <td> {{ number_format($row->V58) }}</td>
                                                    <td> {{ number_format($row->V35) }}</td>
                                                    <td> {{ number_format($row->V60) }}</td>
                                                    <td> {{ number_format($row->V61) }}</td>
                                                    <td> {{ number_format($row->V46) }}</td>
                                                    <td> {{ number_format($row->V36) }}</td>
                                                    <td> {{ number_format($row->V62) }}</td>
                                                    <td> {{ number_format($row->V56) }}</td>
                                                    <td> {{ number_format($row->V52) }}</td>
                                                    <td> {{ number_format($row->V57) }}</td>
                                                    <td> {{ number_format($row->V43) }}</td>
                                                    <td> {{ number_format($row->V37) }}</td>
                                                    <td> {{ number_format($row->V38) }}</td>
                                                    <td> {{ number_format($row->V39) }}</td>
                                                    <td> {{ number_format($row->V40) }}</td>
                                                    <td> {{ number_format($row->totalbaris) }} </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan='3'><strong>TOTAL</strong></td>
                                                <td><strong>{{ number_format($gTotal['SEGEL']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V48']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V49']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V50']) }}</strong></td>
                                                {{-- <td><strong>{{ number_format($gTotal['V16']) }}</strong></td> --}}
                                                {{-- <td><strong>{{ number_format($gTotal['V32']) }}</strong></td> --}}
                                                <td><strong>{{ number_format($gTotal['V42']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V59']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V63']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V2']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V24']) }}</strong></td>
                                                {{-- <td><strong>{{ number_format($gTotal['V41']) }}</strong></td> --}}
                                                <td><strong>{{ number_format($gTotal['V30']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V64']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V6']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V3']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V7']) }}</strong></td>
                                                {{-- <td><strong>{{ number_format($gTotal['V5'])}}</strong></td> --}}
                                                <td><strong>{{ number_format($gTotal['V31']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V28']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V29']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V65']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V47']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V54']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V53']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V55']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V66']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V15']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V33']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V44']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V51']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V58']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V45']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V34']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V35']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V60']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V61']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V46']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V36']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V62']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V56']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V52']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V57']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V43']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V37']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V38']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V39']) }}</strong></td>
                                                <td><strong>{{ number_format($gTotal['V40']) }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
        // Add Row untuk total disetiap menu
        $("#add-row1").DataTable({
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
