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
                                <a class="nav-link {{ request()->is('stocktap') ? 'active' : '' }} font-weight-bold shadow-sm"
                                    href="{{ url('stocktap') }}" style="border-radius: 20px;">
                                    <i class="fas fa-warehouse mr-1"></i> Stock Gudang
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('stocksf') ? 'active' : '' }} font-weight-bold"
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
                                    <tr class="voucher-header">
                                        <th rowspan="3" class="th-main sticky-no">NO</th>
                                        <th rowspan="3" class="th-main sticky-tap">TAP</th>
                                        @foreach ($groups as $voucherType => $validityGroups)
                                            @php
                                                $voucherColspan = collect($validityGroups)->sum(fn ($items) => count($items) + 1);
                                            @endphp
                                            @if ($voucherColspan)
                                                <th colspan="{{ $voucherColspan }}"
                                                    class="th-voucher {{ $voucherType === 'VOUCHER by.U' ? 'th-voucher-byu' : 'th-voucher-fisik' }}">
                                                    {{ $voucherType }}
                                                </th>
                                            @endif
                                        @endforeach
                                        <th rowspan="3" class="th-main sticky-total">GRAND<br>TOTAL</th>
                                    </tr>

                                    <tr class="group-header">
                                        @foreach ($groups as $validityGroups)
                                            @foreach ($validityGroups as $groupName => $items)
                                                <th colspan="{{ count($items) + 1 }}"
                                                    class="th-group th-{{ Str::slug($groupName) }}">
                                                    {{ $groupName }}
                                                </th>
                                            @endforeach
                                        @endforeach
                                    </tr>

                                    <tr class="sub-header">
                                        @foreach ($groups as $validityGroups)
                                            @foreach ($validityGroups as $groupName => $items)
                                                @foreach ($items as $d)
                                                    <th class="th-sub">{{ $d->denom }}</th>
                                                @endforeach
                                                <th class="th-validity-total">TOTAL<br>{{ $groupName }}</th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($data as $row)
                                        <tr>
                                            <td class="text-center sticky-no">{{ $loop->iteration }}</td>
                                            <td class="sticky-tap">{{ $row->idtap }}</td>


                                            @foreach ($groups as $validityGroups)
                                                @foreach ($validityGroups as $items)
                                                    @foreach ($items as $d)
                                                        <td class="text-end">
                                                            {{ number_format($row->{$d->iddenom} ?? 0) }}
                                                        </td>
                                                    @endforeach
                                                    <td class="text-end validity-total">
                                                        {{ number_format(collect($items)->sum(fn ($d) => $row->{$d->iddenom} ?? 0)) }}
                                                    </td>
                                                @endforeach
                                            @endforeach
                                            <td class="text-end font-weight-bold sticky-total-col">
                                                {{ number_format($row->grand_total ?? 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <th class="sticky-footer-left sticky-no"></th>
                                        <th class="sticky-footer-left sticky-tap" style="text-align: center;">TOTAL</th>

                                        @foreach ($groups as $validityGroups)
                                            @foreach ($validityGroups as $items)
                                                @foreach ($items as $d)
                                                    <th class="text-end">
                                                        {{ number_format($data->sum($d->iddenom)) }}
                                                    </th>
                                                @endforeach
                                                <th class="text-end validity-total-footer">
                                                    {{ number_format(collect($items)->sum(fn ($d) => $data->sum($d->iddenom))) }}
                                                </th>
                                            @endforeach
                                        @endforeach
                                        <th class="text-end sticky-total-footer">
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
                    title: 'Stock_Gudang',
                    footer: true
                },
                {
                    extend: 'csvHtml5',
                    title: 'Stock_Gudang',
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
            border-collapse: separate;
            border-spacing: 0;
        }
        #stock td, #stock th {
            padding: 4px 6px !important;
            white-space: normal !important;
            word-wrap: break-word;
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
        .th-voucher {
            color: #fff !important;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .6px;
            text-align: center !important;
            border-left: 3px solid #fff !important;
        }
        .th-voucher-fisik { background: #334155 !important; }
        .th-voucher-byu { background: #047857 !important; }
        .th-validity-total {
            background: #fef3c7 !important;
            color: #92400e !important;
            font-weight: 800 !important;
            border-right: 3px solid #cbd5e1 !important;
        }
        .validity-total {
            background: #fffbeb;
            color: #92400e;
            font-weight: 800;
            border-right: 3px solid #cbd5e1 !important;
        }
        .validity-total-footer {
            background: #78350f !important;
            border-right: 3px solid #cbd5e1 !important;
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
            left: 20px;
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
            z-index: 30;
            background: #4e73df;
            color: #fff;
            vertical-align: middle !important;
        }

        #stock thead tr.voucher-header th {
            top: 0;
            z-index: 32;
        }

        #stock thead tr.group-header th {
            top: 28px; /* Fixed height for sticky sub-header */
            z-index: 31;
        }

        #stock thead tr.sub-header th {
            top: 56px;
            z-index: 30;
            background: #f1f5f9 !important;
            color: #1f2937 !important;
        }

        /* CORNER STICKY (Horizontal + Vertical) */
        #stock thead .sticky-no,
        #stock thead .sticky-tap,
        #stock thead .sticky-total {
            z-index: 50 !important;
        }

        #stock thead tr.group-header .sticky-no,
        #stock thead tr.group-header .sticky-tap,
        #stock thead tr.group-header .sticky-total {
            top: 0;
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

        /* TAP */
        #stock tfoot .sticky-tap {
            position: sticky;
            left: 45px;
            /* HARUS sama dengan width NO */
            z-index: 29;
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            width: 120px !important;
            min-width: 120px !important;
            max-width: 120px !important;
        }

        /* Shadow pemisah footer */
        #stock tfoot .sticky-tap {
            box-shadow: 2px 0 6px rgba(0, 0, 0, 0.25);
        }

        /* Footer tetap di bawah */
        #stock tfoot th {
            position: sticky;
            bottom: 0;
        }

        /* STICKY RIGHT COLUMN FOR GRAND TOTAL */
        .sticky-total { position: sticky !important; right: 0; z-index: 10; background: #ca8a04 !important; color: #fff !important; box-shadow: -2px 0 5px rgba(0,0,0,0.05); }
        .sticky-total-col { position: sticky !important; right: 0; z-index: 9; background: #fff; font-weight: bold; box-shadow: -2px 0 5px rgba(0,0,0,0.05); }
        .sticky-total-footer { position: sticky !important; right: 0; z-index: 39; background: #0f172a; color: #ffffff; font-weight: 700; box-shadow: -2px 0 6px rgba(0, 0, 0, 0.25); }
        #stock tbody tr:hover td.sticky-total-col { background-color: #f1f5f9; }
    </style>
@endpush
