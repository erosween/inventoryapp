@extends('layout.layout')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="page-header">
                    <h4 class="page-title">Cek Sisa Stok Daily</h4>
                </div>

                <div class="card">
                    <div class="card-header">
                        <form id="filter_form" class="row align-items-center">
                            <div class="col-md-4 col-sm-12">
                                <h4 class="card-title mb-0" style="font-weight: 700; color: #1e293b;">
                                    <i class="fas fa-history text-primary mr-2"></i>Histori Sisa Stok
                                </h4>
                                <small class="text-muted">Pilih tanggal untuk melihat sisa stok pada periode tersebut</small>
                            </div>
                            <div class="col-md-8 col-sm-12 d-flex justify-content-md-end justify-content-start mt-3 mt-md-0">
                                <div class="input-group" style="max-width: 320px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-primary text-white border-0"><i class="fas fa-calendar-day"></i></span>
                                    </div>
                                    <input type="date" id="target_date" class="form-control border-0 bg-light" value="{{ $date }}" style="height: 42px; font-weight: 600; color: #334155;">
                                    <div class="input-group-append">
                                        <button id="btn_apply" type="button" class="btn btn-primary" style="height: 42px; border: none; font-weight: 600;">
                                            Cari <i class="fas fa-arrow-right ml-1"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">
                        <div class="table-scroll">
                            <table id="sisastock" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="th-main sticky-no">NO</th>
                                        <th rowspan="2" class="th-main sticky-tap">TAP</th>

                                        @foreach ($groups as $groupName => $items)
                                            @if (count($items) > 0)
                                                <th colspan="{{ count($items) }}"
                                                    class="th-group th-{{ Str::slug($groupName) }}">
                                                    {{ $groupName }}
                                                </th>
                                            @endif
                                        @endforeach
                                        <th rowspan="2" class="th-main sticky-total" style="vertical-align: middle !important;">GRAND<br>TOTAL</th>
                                    </tr>

                                    <tr>
                                        @foreach ($groups as $groupName => $items)
                                            @foreach ($items as $d)
                                                <th class="th-sub th-{{ Str::slug($groupName) }}-sub">
                                                    {{ $d->denom }}
                                                </th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- DataTables populated via AJAX --}}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="sticky-footer-left sticky-no"></th>
                                        <th class="sticky-footer-left sticky-tap" style="text-align: center;">TOTAL</th>

                                        @foreach ($groups as $items)
                                            @foreach ($items as $d)
                                                <th class="text-end">0</th>
                                            @endforeach
                                        @endforeach
                                        <th class="text-end sticky-total-footer">0</th>
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
        $(document).ready(function() {
            let table = $('#sisastock').DataTable({
                processing: true,
                serverSide: true,
                ordering: false, // Disable sorting to prevent header arrows from messing up layout
                ajax: {
                    url: "{{ route('sisastock.data') }}",
                    data: function(d) {
                        return {
                            date: $('#target_date').val(),
                            draw: d.draw
                        };
                    }
                },
                columns: [
                    { 
                        data: null, 
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1,
                        className: 'text-center sticky-no'
                    },
                    { data: 'idtap', className: 'sticky-tap font-weight-bold' },
                    @foreach ($groups as $items)
                        @foreach ($items as $d)
                            { 
                                data: '{{ $d->iddenom }}', 
                                name: '{{ $d->iddenom }}',
                                render: $.fn.dataTable.render.number(',', '.', 0),
                                className: 'text-end'
                            },
                        @endforeach
                    @endforeach
                    {
                        data: 'grand_total',
                        name: 'grand_total',
                        render: $.fn.dataTable.render.number(',', '.', 0),
                        className: 'text-end font-weight-bold sticky-total-col'
                    }
                ],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
                    
                    @foreach ($groups as $items)
                        @foreach ($items as $d)
                            var total_{{ Str::slug($d->iddenom) }} = api
                                .column("{{ $d->iddenom }}:name", { page: 'current' })
                                .data()
                                .reduce(function (a, b) {
                                    return (parseInt(a, 10) || 0) + (parseInt(b, 10) || 0);
                                }, 0);
                            
                            $(api.column("{{ $d->iddenom }}:name").footer()).html(
                                $.fn.dataTable.render.number(',', '.', 0).display(total_{{ Str::slug($d->iddenom) }})
                            );
                        @endforeach
                    @endforeach

                    var grand_total_all = api
                        .column("grand_total:name", { page: 'current' })
                        .data()
                        .reduce(function (a, b) {
                            return (parseInt(a, 10) || 0) + (parseInt(b, 10) || 0);
                        }, 0);
                    
                    $(api.column("grand_total:name").footer()).html(
                        $.fn.dataTable.render.number(',', '.', 0).display(grand_total_all)
                    );
                },
                ordering: false,
                pageLength: 25,
                autoWidth: false,
                dom: '<"top"Bf>rt<"bottom"lip><"clear">',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        title: 'Sisa_Stok_Daily_{{ $date }}',
                        footer: true
                    },
                    {
                        extend: 'csvHtml5',
                        title: 'Sisa_Stok_Daily_{{ $date }}',
                        footer: true
                    },
                    {
                        extend: 'print',
                        footer: true
                    }
                ]
            });

            $('#btn_apply').click(function() {
                table.draw();
            });
        });
    </script>

    <style>
        /* SCROLL HORIZONTAL */
        .table-scroll {
            overflow-x: auto;
        }

        /* TABLE */
        #sisastock {
            font-size: 9.5px;
            border-collapse: separate;
            border-spacing: 0;
        }
        #sisastock td, #sisastock th {
            padding: 4px 6px !important;
            white-space: normal !important;
            word-wrap: break-word;
        }

        /* HEADER STYLING */
        #sisastock thead th { vertical-align: middle !important; }
        .th-main { background: #4f46e5 !important; color: #fff !important; font-weight: 700; text-align: center; }
        .th-group { color: #fff !important; font-weight: 700; text-align: center; letter-spacing: 0.3px; }
        
        .th-segel { background: #1e293b !important; }
        .th-1-hari { background: #1e3a8a !important; }
        .th-2-hari { background: #1e40af !important; }
        .th-3-hari { background: #1d4ed8 !important; }
        .th-5-hari { background: #2563eb !important; }
        .th-7-hari { background: #3b82f6 !important; }
        .th-14-hari { background: #0f766e !important; }
        .th-28-hari { background: #0d9488 !important; }
        .th-30-hari { background: #0891b2 !important; }
        .th-voice { background: #3730a3 !important; }
        .th-lainnya { background: #475569 !important; }

        .th-sub { background: #f1f5f9; font-size: 10px; font-weight: 600; text-align: center; color: #1f2937 !important; }

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
            left: 0; /* Default layout fallback */
            z-index: 5;
            background: #4f46e5;
            color: #fff;
        }

        /* FIX WIDTH KOLOM */
        .sticky-no {
            width: 45px !important;
            min-width: 45px !important;
            max-width: 45px !important;
            text-align: center;
        }

        .sticky-tap {
            width: 120px !important;
            min-width: 120px !important;
            max-width: 120px !important;
        }

        /* BODY BACKGROUND FIX */
        /* HEADER */
        #sisastock thead .sticky-no,
        #sisastock thead .sticky-tap {
            position: sticky;
            top: 0;
            z-index: 20;
            /* LEBIH TINGGI DARI GROUP */
            background: #4f46e5 !important;
            color: #fff !important;
        }

        /* HEADER STICKY (V-Freeze) */
        #sisastock thead th {
            position: sticky;
            z-index: 30;
            background: #4f46e5;
            color: #fff;
            vertical-align: middle !important;
        }

        #sisastock thead tr:first-child th {
            top: 0;
            z-index: 31;
        }

        #sisastock thead tr:nth-child(2) th {
            top: 28px; /* Offset for sub-header */
            z-index: 30;
            background: #f1f5f9 !important;
            color: #1f2937 !important;
        }

        /* CORNER STICKY (Horizontal + Vertical) */
        #sisastock thead .sticky-no,
        #sisastock thead .sticky-tap,
        #sisastock thead .sticky-total {
            z-index: 50 !important;
        }

        #sisastock thead tr:first-child .sticky-no,
        #sisastock thead tr:first-child .sticky-tap,
        #sisastock thead tr:first-child .sticky-total {
            top: 0;
        }

        /* BODY */
        #sisastock tbody .sticky-no,
        #sisastock tbody .sticky-tap {
            position: sticky;
            z-index: 10;
            background: #ffffff;
            color: #111827;
            font-weight: 600;
        }

        #sisastock tbody tr:nth-child(even) .sticky-no,
        #sisastock tbody tr:nth-child(even) .sticky-tap {
            background: #f8fafc;
        }

        /* BODY HOVER */
        #sisastock tbody tr:hover td.sticky-tap,
        #sisastock tbody tr:hover td.sticky-no { 
            background-color: #f1f5f9 !important; 
        }

        /* NO */
        #sisastock thead .sticky-no,
        #sisastock tbody .sticky-no,
        #sisastock tfoot .sticky-no {
            left: 0;
        }

        /* TAP */
        #sisastock thead .sticky-tap,
        #sisastock tbody .sticky-tap,
        #sisastock tfoot .sticky-tap {
            left: 45px;
            /* SAMA DENGAN WIDTH NO */
        }

        /* BORDER SHADOW BIAR KELIATAN TERPISAH */
        .sticky-tap {
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.08);
        }
        
        /* DATA TABLES BUTTONS */
        .dt-buttons { margin-bottom: 15px; }

        /* STICKY FOOTER */
        #sisastock tfoot th { background: #0f172a; color: #fff; font-weight: 700; position: sticky; bottom: 0; z-index: 20; }
        #sisastock tfoot .sticky-no { background: #0f172a; color: #ffffff; z-index: 30; left: 0; width: 45px !important; min-width: 45px !important; }
        #sisastock tfoot .sticky-tap { background: #0f172a; color: #ffffff; z-index: 29; left: 45px; width: 120px !important; min-width: 120px !important; box-shadow: 2px 0 6px rgba(0, 0, 0, 0.25); }
        
        /* OPTIONAL: STICKY RIGHT COLUMN FOR GRAND TOTAL */
        .sticky-total { position: sticky; right: 0; z-index: 9; background: #ca8a04 !important; color: #fff !important; box-shadow: -2px 0 5px rgba(0,0,0,0.05); }
        .sticky-total-col { position: sticky; right: 0; z-index: 8; background: #fff; font-weight: bold; box-shadow: -2px 0 5px rgba(0,0,0,0.05); }
        .sticky-total-footer { position: sticky; right: 0; z-index: 29; background: #0f172a; color: #ffffff; font-weight: 700; box-shadow: -2px 0 6px rgba(0, 0, 0, 0.25); }
        #sisastock tbody tr:hover td.sticky-total-col { background-color: #f1f5f9; }

    </style>
@endpush
