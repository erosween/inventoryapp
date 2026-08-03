@extends('layout.layout')

@php
    use Illuminate\Support\Str;
    $isCurrentStockPage = $isCurrentStockPage ?? false;
    $initialMode = $initialMode ?? 'all';
    $dailyGroupMap = collect($groups)->flatMap(function ($validityGroups, $category) {
        return collect($validityGroups)->map(function ($items, $validity) use ($category) {
            return [
                'key' => md5($category . '|' . $validity),
                'denoms' => collect($items)->pluck('iddenom')->values()->all(),
            ];
        })->values();
    })->values()->all();
    $dailySummaryMap = collect($groups)
        ->filter(fn ($validityGroups, $category) => in_array(strtoupper((string) $category), ['REGULER', 'BYU'], true))
        ->flatMap(function ($validityGroups, $category) {
            return collect($validityGroups)->map(function ($items, $validity) use ($category) {
                return [
                    'key' => md5($category . '|' . $validity),
                    'category' => $category,
                    'validity' => $validity,
                    'denoms' => collect($items)->pluck('iddenom')->values()->all(),
                ];
            })->values();
        })->values()->all();
@endphp

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card premium-card stock-card daily-stock-card">
                    <div class="card-header bg-white border-bottom">
                        <form id="filter_form" class="row align-items-center">
                            <div class="col-md-4 col-sm-12">
                                <h4 class="card-title mb-0 font-weight-bold text-indigo">
                                    <i class="fas fa-warehouse mr-2"></i>Stock Gudang
                                </h4>
                                <div class="text-muted small">Monitoring stok aktif dan histori harian dalam satu tampilan</div>
                            </div>
                            <div class="col-md-8 col-sm-12 d-flex flex-wrap align-items-center justify-content-md-end justify-content-start mt-3 mt-md-0">
                                <div class="daily-mode-toggle mr-2" role="group" aria-label="Perspektif stok">
                                    <button type="button" class="{{ $initialMode === 'all' ? 'active' : '' }}" data-mode="all">All</button>
                                    <button type="button" class="{{ $initialMode === 'tap' ? 'active' : '' }}" data-mode="tap">TAP</button>
                                    <button type="button" class="{{ $initialMode === 'sf' ? 'active' : '' }}" data-mode="sf">SF</button>
                                </div>
                                @unless($isCurrentStockPage)
                                <div class="input-group daily-date-filter">
                                    <input type="date" id="target_date" class="form-control border-0 bg-transparent font-weight-bold shadow-none" value="{{ $date }}">
                                </div>
                                @endunless
                            </div>
                        </form>
                    </div>

                    <div class="card-body px-0 py-0">
                        <div class="stock-table-toolbar" aria-label="Aksi dan pencarian tabel"></div>
                        <div class="table-scroll stock-freeze stock-freeze--daily">
                            <table id="sisastock" class="table table-indigo table-hover w-100 mb-0">
                                <thead>
                                    <tr class="stock-leaf-header">
                                        <th class="th-main sticky-no">NO</th>
                                        <th class="th-main sticky-tap">TAP</th>
                                        <th class="th-main sticky-sf daily-sf-column">SALES FORCE</th>
                                        @foreach ($groups as $category => $validityGroups)
                                            @foreach ($validityGroups as $groupName => $items)
                                                @foreach ($items as $d)
                                                    <th class="th-stock-leaf th-voucher-{{ Str::slug($category) }}"
                                                        data-export-title="{{ $category }} {{ $groupName }} {{ $d->denom }}">
                                                        <span class="stock-leaf-category">{{ $category }}</span>
                                                        <span class="stock-leaf-validity">{{ $groupName }}</span>
                                                        <span class="stock-leaf-denom">{{ $d->denom }}</span>
                                                    </th>
                                                @endforeach
                                                <th class="th-stock-leaf th-validity-total"
                                                    data-export-title="TOTAL {{ $category }} {{ $groupName }}">
                                                    <span class="stock-leaf-category">{{ $category }}</span>
                                                    <span class="stock-leaf-validity">{{ $groupName }}</span>
                                                    <span class="stock-leaf-denom">TOTAL</span>
                                                </th>
                                            @endforeach
                                        @endforeach
                                        <th class="th-main grand-total-header" data-export-title="GRAND TOTAL">GRAND<br>TOTAL</th>
                                        @foreach ($dailySummaryMap as $summaryIndex => $summary)
                                            @php
                                                $previousSummary = $summaryIndex > 0 ? $dailySummaryMap[$summaryIndex - 1] : null;
                                                $startsSummaryCategory = ! $previousSummary || $previousSummary['category'] !== $summary['category'];
                                            @endphp
                                            <th class="th-stock-leaf daily-validity-summary summary-{{ Str::slug($summary['category']) }} {{ $startsSummaryCategory ? 'summary-category-start' : '' }}"
                                                data-export-title="TOTAL {{ $summary['category'] }} {{ $summary['validity'] }}">
                                                <span class="stock-leaf-category">{{ $summary['category'] }}</span>
                                                <span class="stock-leaf-validity">{{ $summary['validity'] }}</span>
                                                <span class="stock-leaf-denom">TOTAL</span>
                                            </th>
                                        @endforeach
                                        <th class="th-main sticky-total" data-export-title="GRAND TOTAL">GRAND<br>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- DataTables populated via AJAX --}}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="sticky-footer-left sticky-no"></th>
                                        <th class="sticky-footer-left sticky-tap" style="text-align: center;">TOTAL</th>
                                        <th class="sticky-footer-left sticky-sf daily-sf-column"></th>

                                        @foreach ($groups as $validityGroups)
                                            @foreach ($validityGroups as $items)
                                                @foreach ($items as $d)
                                                    <th class="text-end">0</th>
                                                @endforeach
                                                <th class="text-end validity-total-footer">0</th>
                                            @endforeach
                                        @endforeach
                                        <th class="text-end grand-total-footer">0</th>
                                        @foreach ($dailySummaryMap as $summaryIndex => $summary)
                                            @php
                                                $previousSummary = $summaryIndex > 0 ? $dailySummaryMap[$summaryIndex - 1] : null;
                                                $startsSummaryCategory = ! $previousSummary || $previousSummary['category'] !== $summary['category'];
                                            @endphp
                                            <th class="text-end daily-validity-summary-footer summary-{{ Str::slug($summary['category']) }} {{ $startsSummaryCategory ? 'summary-category-start' : '' }}">0</th>
                                        @endforeach
                                        <th class="text-end sticky-total-footer">0</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="stock-table-footer" aria-label="Informasi dan navigasi tabel"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let stockMode = @json($initialMode);
            let dateReloadTimer = null;
            const dailyGroups = @json($dailyGroupMap);
            const dailySummaries = @json($dailySummaryMap);
            const dailyExportOptions = {
                columns: ':visible',
                format: {
                    header: function(data, column, node) {
                        return node?.dataset.exportTitle || $('<div>').html(data).text().replace(/\s+/g, ' ').trim();
                    }
                }
            };

            function downloadStock(format) {
                const baseUrl = format === 'csv'
                    ? @json(route('sisastock.export', ['format' => 'csv']))
                    : @json(route('sisastock.export', ['format' => 'xlsx']));
                const url = new URL(baseUrl, window.location.origin);
                url.searchParams.set('mode', stockMode);
                url.searchParams.set('date', @json($isCurrentStockPage ? date('Y-m-d') : null) || $('#target_date').val());
                window.location.assign(url.toString());
            }

            function applyActiveColumns(api, activeDenoms, mode) {
                const active = new Set(activeDenoms || []);
                $('.stock-freeze--daily').toggleClass('has-sf-freeze', mode === 'sf');
                api.column(2).visible(mode === 'sf', false);
                let columnIndex = 3;

                dailyGroups.forEach(function(group) {
                    let visibleDenoms = 0;
                    group.denoms.forEach(function(denom) {
                        const visible = active.has(denom);
                        api.column(columnIndex).visible(visible, false);
                        visibleDenoms += visible ? 1 : 0;
                        columnIndex++;
                    });

                    api.column(columnIndex).visible(visibleDenoms > 0, false);
                    columnIndex++;
                });

                columnIndex++; // Grand Total
                dailySummaries.forEach(function(summary) {
                    api.column(columnIndex).visible(summary.denoms.some(denom => active.has(denom)), false);
                    columnIndex++;
                });

                api.columns.adjust();
            }

            let table = $('#sisastock').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                deferRender: true,
                searchDelay: 250,
                ajax: {
                    url: "{{ route('sisastock.data') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: function(d) {
                        d.date = @json($isCurrentStockPage ? date('Y-m-d') : null) || $('#target_date').val();
                        d.mode = stockMode;
                    }
                },
                columns: [
                    { 
                        data: null, 
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1,
                        className: 'text-center sticky-no'
                    },
                    { data: 'idtap', className: 'sticky-tap font-weight-bold' },
                    { data: 'sf_name', name: 'sf_name', className: 'sticky-sf font-weight-bold daily-sf-column', defaultContent: '' },
                    @foreach ($groups as $category => $validityGroups)
                        @foreach ($validityGroups as $groupName => $items)
                            @foreach ($items as $d)
                                {
                                    data: '{{ $d->iddenom }}',
                                    name: '{{ $d->iddenom }}',
                                    render: function(data, type) {
                                        const value = parseInt(data, 10) || 0;
                                        return $.fn.dataTable.render.number(',', '.', 0).display(value);
                                    },
                                    className: 'text-end'
                                },
                            @endforeach
                            {
                                data: 'validity_{{ md5($category . '|' . $groupName) }}',
                                name: 'validity_{{ md5($category . '|' . $groupName) }}',
                                render: function(data, type) {
                                    const value = parseInt(data, 10) || 0;
                                    return $.fn.dataTable.render.number(',', '.', 0).display(value);
                                },
                                className: 'text-end font-weight-bold validity-total'
                            },
                        @endforeach
                    @endforeach
                    {
                        data: 'grand_total',
                        name: 'grand_total',
                        render: function(data, type) {
                            const value = parseInt(data, 10) || 0;
                            return $.fn.dataTable.render.number(',', '.', 0).display(value);
                        },
                        className: 'text-end font-weight-bold grand-total-cell'
                    },
                    @foreach ($dailySummaryMap as $summaryIndex => $summary)
                    @php
                        $previousSummary = $summaryIndex > 0 ? $dailySummaryMap[$summaryIndex - 1] : null;
                        $startsSummaryCategory = ! $previousSummary || $previousSummary['category'] !== $summary['category'];
                    @endphp
                    {
                        data: 'summary_{{ $summary['key'] }}',
                        name: 'summary_{{ $summary['key'] }}',
                        render: function(data) {
                            const value = parseInt(data, 10) || 0;
                            return $.fn.dataTable.render.number(',', '.', 0).display(value);
                        },
                        className: 'text-end font-weight-bold daily-validity-summary-cell summary-{{ Str::slug($summary['category']) }} {{ $startsSummaryCategory ? 'summary-category-start' : '' }}'
                    },
                    @endforeach
                    {
                        data: 'grand_total_end',
                        name: 'grand_total_end',
                        render: function(data) {
                            const value = parseInt(data, 10) || 0;
                            return $.fn.dataTable.render.number(',', '.', 0).display(value);
                        },
                        className: 'text-end font-weight-bold sticky-total-col'
                    }
                ],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
                    
                    @foreach ($groups as $category => $validityGroups)
                        @foreach ($validityGroups as $groupName => $items)
                            @foreach ($items as $d)
                                @php $jsVarId = 'v_' . str_replace('-', '_', Str::slug($d->iddenom)); @endphp
                                var total_{{ $jsVarId }} = api
                                    .column("{{ $d->iddenom }}:name", { page: 'current' })
                                    .data()
                                    .reduce(function (a, b) {
                                        return (parseInt(a, 10) || 0) + (parseInt(b, 10) || 0);
                                    }, 0);

                                $(api.column("{{ $d->iddenom }}:name").footer()).html(
                                    $.fn.dataTable.render.number(',', '.', 0).display(total_{{ $jsVarId }})
                                );
                            @endforeach
                            @php $validityColumn = 'validity_' . md5($category . '|' . $groupName); @endphp
                            var total_{{ $validityColumn }} = api
                                .column("{{ $validityColumn }}:name", { page: 'current' })
                                .data()
                                .reduce(function(a, b) {
                                    return (parseInt(a, 10) || 0) + (parseInt(b, 10) || 0);
                                }, 0);
                            $(api.column("{{ $validityColumn }}:name").footer()).html(
                                $.fn.dataTable.render.number(',', '.', 0).display(total_{{ $validityColumn }})
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

                    @foreach ($dailySummaryMap as $summary)
                        @php $summaryColumn = 'summary_' . $summary['key']; @endphp
                        var total_{{ $summaryColumn }} = api
                            .column("{{ $summaryColumn }}:name", { page: 'current' })
                            .data()
                            .reduce(function(a, b) {
                                return (parseInt(a, 10) || 0) + (parseInt(b, 10) || 0);
                            }, 0);
                        $(api.column("{{ $summaryColumn }}:name").footer()).html(
                            $.fn.dataTable.render.number(',', '.', 0).display(total_{{ $summaryColumn }})
                        );
                    @endforeach

                    var grand_total_end_all = api
                        .column("grand_total_end:name", { page: 'current' })
                        .data()
                        .reduce(function(a, b) {
                            return (parseInt(a, 10) || 0) + (parseInt(b, 10) || 0);
                        }, 0);
                    $(api.column("grand_total_end:name").footer()).html(
                        $.fn.dataTable.render.number(',', '.', 0).display(grand_total_end_all)
                    );

                },
                drawCallback: function(settings) {
                    applyActiveColumns(this.api(), settings.json?.active_denoms || [], settings.json?.mode || stockMode);
                },
                pageLength: 25,
                autoWidth: false,
                dom: '<"top"Bf>rt<"bottom"lip><"clear">',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        action: function() { downloadStock('xlsx'); },
                        title: function() { return (@json($isCurrentStockPage) ? 'Ringkasan_Stok_' : 'Sisa_Stok_Daily_') + (@json($isCurrentStockPage ? date('Y-m-d') : null) || $('#target_date').val()); },
                        footer: true,
                        exportOptions: dailyExportOptions
                    },
                    {
                        extend: 'csvHtml5',
                        action: function() { downloadStock('csv'); },
                        title: function() { return (@json($isCurrentStockPage) ? 'Ringkasan_Stok_' : 'Sisa_Stok_Daily_') + (@json($isCurrentStockPage ? date('Y-m-d') : null) || $('#target_date').val()); },
                        footer: true,
                        exportOptions: dailyExportOptions
                    },
                    {
                        extend: 'print',
                        footer: true,
                        exportOptions: dailyExportOptions
                    }
                ]
            });

            const $stockWrapper = $('#sisastock_wrapper');
            $('.stock-table-toolbar').append($stockWrapper.children('.top'));
            $('.stock-table-footer').append($stockWrapper.children('.bottom'));

            $('#filter_form').on('submit', function(event) {
                event.preventDefault();
                table.ajax.reload();
            });

            function syncStockUrl() {
                const url = new URL(window.location.href);
                url.searchParams.set('mode', stockMode);
                const selectedDate = $('#target_date').val();
                if (selectedDate) url.searchParams.set('date', selectedDate);
                window.history.replaceState({}, '', url.toString());
            }

            $('#target_date').on('change', function() {
                clearTimeout(dateReloadTimer);
                syncStockUrl();
                dateReloadTimer = setTimeout(function() {
                    table.ajax.reload(null, true);
                }, 120);
            });

            $('.daily-mode-toggle button').on('click', function() {
                const nextMode = $(this).data('mode');
                if (nextMode === stockMode) return;

                stockMode = nextMode;
                $('.daily-mode-toggle button').removeClass('active');
                $(this).addClass('active');
                syncStockUrl();
                table.ajax.reload();
            });
        });
    </script>

    <style>
        /* SCROLL HORIZONTAL */
        .table-scroll {
            overflow-x: auto;
        }

        .daily-mode-toggle { display:flex; gap:4px; padding:4px; border:1px solid #e3e6ef; border-radius:10px; background:#f7f8fc; }
        .daily-mode-toggle button { min-width:48px; padding:8px 11px; border:0; border-radius:7px; background:transparent; color:#697386; font-size:11px; font-weight:700; cursor:pointer; }
        .daily-mode-toggle button.active { background:#4f46e5; color:#fff; box-shadow:0 5px 12px rgba(79,70,229,.22); }

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
        .th-main { background: #4e73df !important; color: #fff !important; font-weight: 700; text-align: center; }
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
        #sisastock thead .th-validity-total {
            background:#eee6c7 !important;
            color:#292a35 !important;
            border-color:#dfd3a5 !important;
            font-weight:800;
        }
        #sisastock tbody .validity-total {
            background:#fbf8ec !important;
            color:#292a35 !important;
            font-weight:800;
        }
        #sisastock tfoot .validity-total-footer {
            background:#e9e1c2 !important;
            color:#292a35 !important;
            font-weight:800;
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
            left: 0; /* Default layout fallback */
            z-index: 5;
            background: #4e73df;
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
            background: #4e73df !important;
            color: #fff !important;
        }

        /* HEADER STICKY (V-Freeze) */
        #sisastock thead th {
            position: sticky;
            z-index: 30;
            background: #4e73df;
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
    @include('layout.stock-freeze-styles')
@endpush
