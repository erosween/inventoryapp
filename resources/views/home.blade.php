@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="page-header">
                    <h4 class="page-title">Dashboard</h4>
                </div>

                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body ">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <strong>
                                                    <p class="card-category">STOCK SEGEL</p>
                                                </strong>
                                                <h4 class="card-title">{{ number_format($segel) }} Pcs</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="far fa-newspaper"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <strong>
                                                    <p class="card-category">STOCK INJECT</p>
                                                </strong>
                                                <h4 class="card-title">{{ number_format($inject) }} Pcs</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="far fa-chart-bar"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <strong>
                                                    <p class="card-category">SALES {{ strtoupper($newmonth) }}</p>
                                                </strong>
                                                <h4 class="card-title">{{ number_format($sales) }} Pcs</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- row --}}

                    {{-- penjualan bulanan --}}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">PENJUALAN BULANAN</h4>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <form action="#" method="GET">
                                            <label for="tahun" class="mt-2">Filter Tahun:</label>
                                            <select name="tahun" id="tahun" class="form-control">
                                                <option value="">--Pilih Tahun--</option>
                                                <?php
                                                $selectedYear = request('tahun'); // Mendapatkan tahun yang dipilih
                                                $years = [2023, 2024, 2025, 2026]; // Daftar tahun yang tersedia
                                                ?>
                                                @foreach ($years as $year)
                                                    <option value="{{ $year }}"
                                                        {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary mt-2">Filter</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="add-row" class="display table table-striped table-hover header-blue">
                                            <thead>
                                                <tr>
                                                    <th>TAP</th>
                                                    @foreach ($months as $month)
                                                        <th>{{ $month }}</th>
                                                    @endforeach
                                                    <th>Total</th> <!-- Total Penjualan untuk semua bulan -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {{-- TAP rows --}}
                                                @foreach ($result as $tap => $monthsData)
                                                    <tr>
                                                        <td>{{ $tap }}</td>
                                                        @foreach ($monthsData as $value)
                                                            <td>{{ number_format($value) }}</td>
                                                        @endforeach
                                                        <td>{{ number_format(array_sum($monthsData)) }}</td>
                                                    </tr>
                                                @endforeach

                                            </tbody>

                                            <tfoot>
                                                @if (session('idtap') != 'SBP_DUMAI')
                                                @else
                                                    {{-- Cluster rows (masih di tbody, bukan tfoot) --}}
                                                    @foreach ($clusterFooter as $cluster => $monthsData)
                                                        <tr class="table-secondary font-weight">
                                                            <td>{{ $cluster }}</td>
                                                            @foreach ($monthsData as $value)
                                                                <td>{{ number_format($value) }}</td>
                                                            @endforeach
                                                            <td>{{ number_format(array_sum($monthsData)) }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                {{-- Total semua cluster (tfoot asli) --}}
                                                <tr>
                                                    <th>Total</th>
                                                    @foreach ($totalFooter as $total)
                                                        <th>{{ number_format($total) }}</th>
                                                    @endforeach
                                                    <th>{{ number_format(array_sum($totalFooter)) }}</th>
                                                </tr>
                                            </tfoot>

                                        </table>
                                        {{-- table responsive --}}
                                    </div>
                                </div>
                                {{-- card body --}}
                            </div>
                            {{-- card --}}
                        </div>

                    </div>

                    {{-- end bulanan --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">MONTH on MONTH SALES</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="stock3" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>NO</th>
                                                    <th>TAP</th>
                                                    <th>{{ strtoupper($month1) }} ({{ $tanggal }})</th>
                                                    <th>{{ strtoupper($newmonth) }} ({{ $tanggal }})</th>
                                                    <th>MOM</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($penjualan as $tapId => $data)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $tapId }}</td>
                                                        <td>{{ number_format($data['sales1']) }}</td>
                                                        <td>{{ number_format($data['salesnow']) }}</td>
                                                        <td>
                                                            @if ($data['sales1'] != 0)
                                                                {{ number_format(($data['salesnow'] / $data['sales1'] - 1) * 100, 2) }}%
                                                            @else
                                                                0
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                {{-- Tambahkan cluster summary --}}
                                                @if (session('idtap') != 'SBP_DUMAI')
                                                @else
                                                    @php
                                                        $clusters = [
                                                            'DUMAI BENGKALIS' => [
                                                                'DUMAI',
                                                                'DURI',
                                                                'BENGKALIS',
                                                                'SEI PAKNING',
                                                                'RUPAT',
                                                            ],
                                                            'ROKAN HILIR' => [
                                                                'UJUNG TANJUNG',
                                                                'BAGAN SIAPI-API',
                                                                'BAGAN BATU',
                                                            ],
                                                        ];
                                                    @endphp

                                                    @foreach ($clusters as $clusterName => $taps)
                                                        @php
                                                            $sales1Cluster = 0;
                                                            $salesNowCluster = 0;
                                                            foreach ($taps as $tap) {
                                                                if (isset($penjualan[$tap])) {
                                                                    $sales1Cluster += $penjualan[$tap]['sales1'];
                                                                    $salesNowCluster += $penjualan[$tap]['salesnow'];
                                                                }
                                                            }
                                                            $momCluster =
                                                                $sales1Cluster != 0
                                                                    ? ($salesNowCluster / $sales1Cluster - 1) * 100
                                                                    : 0;
                                                        @endphp
                                                        <tr class="table-secondary font-weight">
                                                            <td colspan="2">{{ $clusterName }}</td>
                                                            <td>{{ number_format($sales1Cluster) }}</td>
                                                            <td>{{ number_format($salesNowCluster) }}</td>
                                                            <td>{{ number_format($momCluster, 2) }}%</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>

                                            <tfoot>
                                                <tr>
                                                    <td colspan='2'><strong>TOTAL</strong></td>
                                                    <td><strong>{{ number_format(array_sum(array_column($penjualan, 'sales1'))) }}</strong>
                                                    </td>
                                                    <td><strong>{{ number_format(array_sum(array_column($penjualan, 'salesnow'))) }}</strong>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $sales1Total = array_sum(
                                                                array_column($penjualan, 'sales1'),
                                                            );
                                                            $salesNowTotal = array_sum(
                                                                array_column($penjualan, 'salesnow'),
                                                            );

                                                            // Memastikan $sales1Total tidak sama dengan nol sebelum melakukan pembagian
                                                            $percentage =
                                                                $sales1Total != 0
                                                                    ? ($salesNowTotal / $sales1Total - 1) * 100
                                                                    : 0;
                                                        @endphp

                                                        <strong>{{ number_format($percentage, 2) }}%</strong>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        {{-- table responsive --}}
                                    </div>
                                </div>
                                {{-- card body --}}
                            </div>
                            {{-- card --}}
                        </div>
                        {{-- col md 12 --}}


                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">LAST UPDATE</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="stock" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>NO</th>
                                                    <th>TAP</th>
                                                    <th>Tanggal Masuk SF</th>
                                                    <th>Tanggal Penjualan SF</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($tglUpload as $tapId => $data)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $tapId }}</td>
                                                        <td>{{ $data['masuk'] }}</td>
                                                        <td>{{ $data['keluar'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        {{-- table responsive --}}
                                    </div>
                                </div>
                                {{-- card body --}}
                            </div>
                            {{-- card --}}
                        </div>

                        {{-- login sbp dumai --}}
                        @if (session('idtap') == 'SBP_DUMAI')
                            {{-- sales per cluster --}}
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center tex-center">
                                            <h4 class="card-title">SALES PRODUCT DUMAI BENGKALIS</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="stock1" class="display table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>DENOM</th>
                                                        <th>QUANTITY</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($denomdumai as $data)
                                                        <tr>
                                                            <td>{{ $data->denom }}</td>
                                                            <td>{{ number_format($data->qty) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td><strong>GRAND TOTAL</strong></td>
                                                        <td><strong>{{ number_format($grandTotaldb) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            {{-- table responsive --}}
                                        </div>
                                    </div>
                                    {{-- card body --}}
                                </div>
                                {{-- card --}}
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center tex-center">
                                            <h4 class="card-title">SALES PRODUCT ROKAN HILIR</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="stock2" class="display table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>DENOM</th>
                                                        <th>QUANTITY</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($denomrohil as $data)
                                                        <tr>
                                                            <td>{{ $data->denom }}</td>
                                                            <td>{{ number_format($data->qty) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td><strong>GRAND TOTAL</strong></td>
                                                        <td><strong>{{ number_format($grandTotalrh) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            {{-- table responsive --}}
                                        </div>
                                    </div>
                                    {{-- card body --}}
                                </div>
                                {{-- card --}}
                            </div>
                        @else
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center tex-center">
                                            <h4 class="card-title">SALES PRODUCT {{ $idtap }}</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="stock5" class="display table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>DENOM</th>
                                                        <th>QTY</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($salesdenom as $data)
                                                        <tr>
                                                            <td>{{ $data->denom }}</td>
                                                            <td>{{ number_format($data->qty) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td><strong>GRAND TOTAL</strong></td>
                                                        <td><strong>{{ number_format($grandTotal) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            {{-- table responsive --}}
                                        </div>
                                    </div>
                                    {{-- card body --}}
                                </div>
                                {{-- card --}}
                            </div>
                        @endif

                        {{-- col md 12 --}}
                    </div>
                </div>
                {{-- container fluid --}}
            </div>
            {{-- page inner --}}
        </div>
        {{-- content --}}
    </div>
    {{-- main panel --}}
@endsection
@push('scripts')
    <script>
        // Add Row
        $("#add-row").DataTable({
            pageLength: 10,
            order: [
                [0, "asc"]
            ]
        });

        // Add Row untuk total disetiap menu
        $("#add-row1").DataTable({
            searching: false,
            paging: false,
            info: false,
            order: [
                [1, "desc"]
            ]
        });


        // {{-- table penjualan perdenom di home --}}
        // Add Row
        $("#stock1").DataTable({
            searching: false,
            paging: false,
            info: false,
            order: [
                [1, "desc"]
            ]
        });

        // Add Row
        $("#stock2").DataTable({
            searching: false,
            paging: false,
            info: false,
            order: [
                [1, "desc"]
            ]
        });

        $("#stock3").DataTable({
            searching: false,
            paging: false,
            info: false,
            order: [
                [1, "desc"]
            ]
        });
    </script>
@endpush
