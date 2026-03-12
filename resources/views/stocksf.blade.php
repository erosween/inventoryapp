@extends('layout.layout')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card">

                    {{-- TAB --}}
                    <div class="card-header">
                        <ul class="nav nav-pills nav-secondary">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('stock') ? 'active' : '' }}" href="{{ url('stock') }}">
                                    Stock All
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('stocktap') ? 'active' : '' }}"
                                    href="{{ url('stocktap') }}">
                                    Stock Gudang
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('stocksf') ? 'active' : '' }}"
                                    href="{{ url('stocksf') }}">
                                    Stock SF
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body">

                        {{-- ⬇️ SCROLL CSS BIASA --}}
                        <div class="table-scroll">

                            <table id="stock" class="display table table-striped table-hover">
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
                                        <th rowspan="2" class="th-main sticky-total">TOTAL</th>
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
                                            <td class="text-right sticky-total font-weight-bold">
                                                {{ number_format($rowTotal) }}
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
                                        <th class="text-right sticky-total">
                                            {{ number_format(
                                                $data->sum(function ($row) use ($groups) {
                                                    $t = 0;
                                                    foreach ($groups as $items) {
                                                        foreach ($items as $d) {
                                                            $t += $row->{$d->iddenom} ?? 0;
                                                        }
                                                    }
                                                    return $t;
                                                }),
                                            ) }}
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
            font-size: 11px;
            min-width: 2200px;
        }

        /* HEADER */
        .th-main {
            background: #4f46e5;
            color: #fff;
            font-weight: 700;
        }

        .th-group {
            color: #fff;
            font-weight: 600;
            text-align: center
        }

        .th-segel {
            background: #334155;
        }

        .th-1-hari {
            background: #2563eb;
        }

        .th-2-hari {
            background: #0891b2;
        }

        .th-3-hari {
            background: #059669;
        }

        .th-5-hari {
            background: #16a34a;
        }

        .th-7-hari {
            background: #2173e6;
        }

        .th-14-hari {
            background: #682799;
        }

        .th-28-hari {
            background: #4022ea;
        }

        .th-30-hari {
            background: #7082e8;
        }

        .th-voice {
            background: #7082e8;
        }

        .th-lainnya {
            background: #7082e8;
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
            background: #4f46e5;
            color: #fff;
        }

        /* TAP */
        .sticky-tap {
            position: sticky;
            left: 40px;
            /* SESUAIKAN DENGAN LEBAR KOLOM NO */
            z-index: 5;
            background: #4f46e5;
            color: #fff;
        }

        /* FIX WIDTH KOLOM */
        .sticky-no {
            width: 40px;
            min-width: 40px;
            max-width: 40px;
        }

        .sticky-tap {
            width: 140px;
            min-width: 140px;
            max-width: 140px;
        }

        /* BODY BACKGROUND FIX */
        /* HEADER */
        #stock thead .sticky-no,
        #stock thead .sticky-tap,
        #stock thead .sticky-sf {
            position: sticky;
            top: 0;
            z-index: 20;
            /* LEBIH TINGGI DARI GROUP */
            background: #4f46e5;
            color: #fff;
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
            left: 40px;
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
            width: 40px;
            min-width: 40px;
            max-width: 40px;
        }

        /* SF */
        .sticky-sf {
            position: sticky;
            left: 180px;
            /* 40 (NO) + 140 (TAP) */
            z-index: 20;
            background: #4f46e5;
            color: #ffffff;
            font-weight: 600;
            width: 160px;
            min-width: 160px;
            max-width: 160px;
        }


        #stock thead .sticky-sf {
            top: 0;
            z-index: 10;
            background: #4f46e5;
            color: #ffffff;
        }

        #stock tbody .sticky-sf {
            background: #ffffff;
            color: #111827;
        }

        #stock tfoot .sticky-sf {
            position: sticky;
            left: 180px;
            z-index: 28;
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
        }

        .sticky-sf {
            box-shadow: 2px 0 6px rgba(0, 0, 0, 0.18);
        }
    </style>
@endpush
