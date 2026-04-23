@extends('layout.layout')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card premium-card">

                    {{-- TAB --}}
                    <div class="card-header bg-white border-bottom py-3">
                        <ul class="nav nav-pills nav-secondary nav-pills-no-bd d-flex align-items-center gap-2">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('stock') ? 'active' : '' }} font-weight-bold" href="{{ url('stock') }}" style="border-radius: 20px;">
                                    <i class="fas fa-layer-group mr-1"></i> Stock All
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('stocktap') ? 'active' : '' }} font-weight-bold"
                                    href="{{ url('stocktap') }}" style="border-radius: 20px;">
                                    <i class="fas fa-warehouse mr-1"></i> Stock Gudang
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('stocksf') ? 'active' : '' }} font-weight-bold shadow-sm"
                                    href="{{ url('stocksf') }}" style="border-radius: 20px;">
                                    <i class="fas fa-user-tie mr-1"></i> Stock SF
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body px-0 py-0">

                        {{-- ⬇️ SCROLL CSS BIASA --}}
                        <div class="table-scroll">

                            <table id="stock" class="table table-indigo table-hover w-100 mb-0">
                                <thead>
                                    {{-- GROUP HEADER --}}
                                    <tr class="group-header">
                                        <th rowspan="2" class="th-main sticky-no">NO</th>
                                        <th rowspan="2" class="th-main sticky-tap">TAP</th>
                                        <th rowspan="2" class="th-main sticky-sf">SF</th>

                                        @foreach ($groups as $group => $items)
                                            @if (count($items))
                                                <th colspan="{{ count($items) }}"
                                                    class="th-group
                                                    {{ $group == 'SEGEL' ? 'th-segel' : '' }}
                                                    {{ $group == '1 HARI' ? 'th-1-hari' : '' }}
                                                    {{ $group == '2 HARI' ? 'th-2-hari' : '' }}
                                                    {{ $group == '3 HARI' ? 'th-3-hari' : '' }}
                                                    {{ $group == '5 HARI' ? 'th-5-hari' : '' }}
                                                    {{ $group == '7 HARI' ? 'th-7-hari' : '' }}
                                                    {{ $group == '14 HARI' ? 'th-14-hari' : '' }}
                                                    {{ $group == '28 HARI' ? 'th-28-hari' : '' }}
                                                    {{ $group == '30 HARI' ? 'th-30-hari' : '' }}
                                                    {{ $group == 'VOICE' ? 'th-voice' : '' }}
                                                    {{ $group == 'LAINNYA' ? 'th-lainnya' : '' }}
                                                    ">
                                                    {{ $group }}
                                                </th>
                                            @endif
                                        @endforeach
                                        {{-- TOTAL KANAN --}}
                                        <th rowspan="2" class="th-main sticky-total" style="vertical-align: middle !important;">GRAND<br>TOTAL</th>
                                    </tr>

                                    {{-- SUB HEADER --}}
                                    <tr class="sub-header">
                                        @foreach ($groups as $items)
                                            @foreach ($items as $d)
                                                <th class="th-sub text-center">{{ $d->denom }}</th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>


                                <tbody>
                                    @foreach ($data as $row)
                                        @php $rowTotal = 0; @endphp
                                        <tr>
                                            <td class="sticky-no">{{ $loop->iteration }}</td>
                                            <td class="sticky-tap">{{ $row->idtap }}</td>
                                            <td class="sticky-sf">{{ $row->namasf }}</td>


                                            @foreach ($groups as $items)
                                                @foreach ($items as $d)
                                                    @php
                                                        $val = $row->{$d->iddenom} ?? 0;
                                                        $rowTotal += $val;
                                                    @endphp
                                                    <td class="text-right">{{ number_format($val) }}</td>
                                                @endforeach
                                            @endforeach
                                            {{-- TOTAL PER SF --}}
                                            <td class="text-right sticky-total-col">
                                                {{ number_format($row->grand_total ?? 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <th class="sticky-no"></th>
                                        <th class="sticky-tap"></th>
                                        <th class="sticky-sf">TOTAL</th>

                                        @foreach ($groups as $items)
                                            @foreach ($items as $d)
                                                <th class="text-right">
                                                    {{ number_format($data->sum($d->iddenom) ?? 0) }}
                                                </th>
                                            @endforeach
                                        @endforeach

                                        {{-- TOTAL GRAND --}}
                                        <th class="text-right sticky-total-footer">
                                            {{ number_format($data->sum('grand_total')) }}
                                        </th>
                                    </tr>
                                </tfoot>

                            </table>

                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#stock').DataTable({
            ordering: false,
            pageLength: 10,
            autoWidth: false,
            dom: '<"top"Bf>rt<"bottom"lip><"clear">',
            buttons: [{
                    extend: 'excelHtml5',
                    title: 'Stock_SF',
                    footer: true
                },
                {
                    extend: 'csvHtml5',
                    title: 'Stock_SF',
                    footer: true
                },
                {
                    extend: 'print',
                    footer: true
                }
            ]
        });
    </script>

    <style>
        /* SCROLL HORIZONTAL MURNI */
        .table-scroll {
            overflow-x: auto;
        }

        /* TABLE */
        #stock {
            font-size: 9.5px;
            white-space: nowrap;
            border-collapse: separate;
            border-spacing: 0;
        }
        #stock td, #stock th {
            padding: 4px 6px !important;
            border: 0.1px solid #e2e8f0;
        }

        /* HEADER */
        .th-main {
            background: #4e73df;
            color: #fff;
            font-weight: 700;
        }

        .th-group {
            color: #fff;
            font-weight: 600;
            text-align: center
        }

        .th-segel {
            background: #1e293b;
        }

        .th-1-hari {
            background: #1e3a8a;
        }

        .th-2-hari {
            background: #1e40af;
        }

        .th-3-hari {
            background: #1d4ed8;
        }

        .th-5-hari {
            background: #2563eb;
        }

        .th-7-hari {
            background: #3b82f6;
        }

        .th-14-hari {
            background: #0f766e;
        }

        .th-28-hari {
            background: #0d9488;
        }

        .th-30-hari {
            background: #0891b2;
        }

        .th-voice {
            background: #3730a3;
        }

        .th-lainnya {
            background: #475569;
        }

        /* SUB HEADER */
        .th-sub {
            background: #f1f5f9;
            font-size: 10px;
            font-weight: 600;
        }

        /* BODY */
        #stock tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        /* FOOTER */
        #stock tfoot th {
            background: #0f172a;
            color: #fff;
            font-weight: 700;
        }

        /* ================= HEADER GROUP (FIX FINAL) ================= */

        /* Header group (1 HARI, 2 HARI, dll) */
        #stock thead tr.group-header th {
            text-align: center !important;
            vertical-align: middle !important;
            color: #ffffff !important;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        /* Pastikan colspan header benar-benar center */
        #stock thead tr.group-header th[colspan] {
            text-align: center !important;
        }

        /* Hilangkan efek bootstrap table */
        #stock thead tr.group-header th {
            background-image: none !important;
            box-shadow: none !important;
        }

        /* ================= SUB HEADER ================= */

        #stock thead tr.sub-header th {
            text-align: center !important;
            vertical-align: middle !important;
            color: #1f2937 !important;
            /* abu gelap profesional */
        }

        /* ================= WARNA GROUP (FORCE WHITE TEXT) ================= */
        .th-group,
        .th-segel,
        .th-1-hari,
        .th-2-hari,
        .th-3-hari,
        .th-5-hari,
        .th-7-hari,
        .th-14-hari,
        .th-28-hari,
        .th-30-hari,
        .th-voice,
        .th-lainnya {
            color: #ffffff !important;
        }


        /* ================= HEADER NO & TAP ================= */

        .th-main {
            text-align: center !important;
            color: #ffffff !important;
        }



        /* ================= FREEZE COLUMN ================= */

        /* NO */
        .sticky-no {
            position: sticky;
            left: 0;
            z-index: 6;
            background: #4e73df;
            color: #fff;
        }

        /* TAP */
        .sticky-tap {
            position: sticky;
            left: 40px;
            /* SESUAIKAN DENGAN LEBAR KOLOM NO */
            z-index: 5;
            background: #4e73df;
            color: #fff;
        }

        /* FIX WIDTH KOLOM */
        .sticky-no {
            width: 45px !important;
            min-width: 45px !important;
            max-width: 45px !important;
        }

        .sticky-tap {
            width: 120px !important;
            min-width: 120px !important;
            max-width: 120px !important;
        }

        /* HEADER STICKY (V-Freeze) */
        #stock thead th {
            position: sticky;
            z-index: 31;
            background: #4e73df;
            color: #fff;
            vertical-align: middle !important;
            border: 0.1px solid #ffffff33 !important;
        }

        #stock thead tr.group-header th {
            top: 0;
            z-index: 33; /* Higher than sub-header */
        }

        /* Cells in Row 2 that stay sticky below Row 1 */
        #stock thead tr.sub-header th {
            top: 35px; /* Manually tuned offset to account for GRAND TOTAL height */
            z-index: 32;
            background: #f1f5f9 !important;
            color: #1f2937 !important;
            border: 0.1px solid #cbd5e1 !important;
        }

        /* CORNER STICKY (Horizontal + Vertical Intersections) */
        #stock thead .sticky-no,
        #stock thead .sticky-tap,
        #stock thead .sticky-sf,
        #stock thead .sticky-total,
        #stock thead tr.group-header th[rowspan="2"] {
            z-index: 50 !important;
            top: 0;
            background: #4e73df !important;
            color: #fff !important;
            vertical-align: middle !important;
        }

        /* BODY */
        #stock tbody .sticky-no,
        #stock tbody .sticky-tap {
            position: sticky;
            left: 0;
            z-index: 10;
            background: #ffffff;
            color: #111827;
            font-weight: 600;
        }

        /* NO */
        #stock thead .sticky-no,
        #stock tbody .sticky-no,
        #stock tfoot .sticky-footer-left {
            left: 0;
        }

        /* TAP */
        #stock thead .sticky-tap,
        #stock tbody .sticky-tap {
            left: 45px;
            /* SAMA DENGAN WIDTH NO */
        }


        /* BORDER SHADOW BIAR KELIATAN TERPISAH */
        .sticky-tap {
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.08);
        }

        /* ================= FOOTER STICKY FINAL ================= */

        /* NO (TOTAL) */
        #stock tfoot .sticky-no {
            position: sticky;
            left: 0;
            z-index: 30;
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            width: 45px !important;
            min-width: 45px !important;
            max-width: 45px !important;
        }

        /* SF */
        .sticky-sf {
            position: sticky;
            left: 165px;
            /* 45 (NO) + 120 (TAP) */
            z-index: 20;
            background: #4e73df;
            color: #ffffff;
            font-weight: 600;
            width: 180px !important;
            min-width: 180px !important;
            max-width: 180px !important;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        #stock thead .sticky-sf {
            top: 0;
            z-index: 10;
            background: #4e73df;
            color: #ffffff;
        }

        #stock tbody .sticky-sf {
            background: #ffffff;
            color: #111827;
        }

        #stock tfoot .sticky-sf {
            position: sticky;
            left: 165px;
            z-index: 28;
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
        }

        /* STICKY RIGHT COLUMN FOR GRAND TOTAL */
        .sticky-total { position: sticky !important; right: 0; z-index: 10; background: #ca8a04 !important; color: #fff !important; box-shadow: -2px 0 5px rgba(0,0,0,0.05); }
        .sticky-total-col { position: sticky !important; right: 0; z-index: 9; background: #fff; font-weight: bold; box-shadow: -2px 0 5px rgba(0,0,0,0.05); }
        .sticky-total-footer { position: sticky !important; right: 0; z-index: 39; background: #0f172a; color: #ffffff; font-weight: 700; box-shadow: -2px 0 6px rgba(0, 0, 0, 0.25); }
        #stock tbody tr:hover td.sticky-total-col { background-color: #f1f5f9; }
    </style>
@endpush
