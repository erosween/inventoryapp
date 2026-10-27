            @php
                $momCurrentLabel = strtoupper($momSelectedDate->copy()->locale('id')->translatedFormat('d M'));
                $momPreviousLabel = strtoupper($momEndPrevMonthPartial->copy()->locale('id')->translatedFormat('d M'));
            @endphp
            <div class="card enterprise-shadow mb-4" id="mom-cluster-card">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap:12px;">
                        <div>
                            <h6 class="font-weight-bold text-dark mb-0">📊 MoM Sales Tap</h6>
                            <small class="text-muted">
                                1–{{ $momCurrentLabel }} vs 1–{{ $momPreviousLabel }} · Stok Gudang + SF per {{ $momSelectedDate->copy()->locale('id')->translatedFormat('d M Y') }}
                            </small>
                        </div>
                        <div class="export-actions mom-toolbar">
                            <form method="GET" action="{{ request()->routeIs('surya.inventory-dashboard') ? route('surya.inventory-dashboard') : url('home') }}#mom-cluster-card"
                                class="mom-filter-form">
                                @if(request()->routeIs('surya.inventory-dashboard'))
                                <input type="hidden" name="sales_product" value="{{ $salesProductFilter }}">
                                <input type="hidden" name="inject_product" value="{{ $injectProductFilter }}">
                                @endif
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <input type="hidden" name="sales_year" value="{{ $salesYear }}">
                                <input type="hidden" name="sales_view" value="{{ $salesView }}">
                                <input type="hidden" name="sales_period" value="{{ $salesPeriod }}">
                                <input type="hidden" name="sales_annual_cutoff" value="{{ $annualCutoff }}">
                                <input type="hidden" name="mode" value="{{ $mode }}">
                                <div class="mom-filter-field">
                                <label for="mom-product">Produk</label>
                                <select name="mom_product" id="mom-product" class="form-control" aria-label="Produk MoM">
@foreach(['pv_all'=>'PV All','pv_reg'=>'PV Reguler','pv_byu'=>'PV By.U','sa_all'=>'SA All','sa_simpati'=>'SA Simpati','sa_byu'=>'SA By.U'] as $value => $label)
<option value="{{ $value }}" @selected($momProductFilter === $value)>{{ $label }}</option>
@endforeach
</select>
                                </div>
                                <div class="mom-filter-field">
                                <label for="mom_date">Tanggal MoM</label>
                                <input type="date" name="mom_date" id="mom_date"
                                    class="form-control form-control-sm"
                                    value="{{ $momSelectedDate->toDateString() }}"
                                    max="{{ $fairCutoffThisMonth->toDateString() }}">
                                </div>
                            </form>
                            <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="mom-copy-btn" title="Copy sebagai gambar" style="gap:6px;">
                                <i data-feather="copy" style="width:14px;height:14px"></i> Copy
                            </button>
                            <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="mom-download-btn" title="Download PNG" style="gap:6px;">
                                <i data-feather="download" style="width:14px;height:14px"></i> PNG
                            </button>
                        </div>
                        <small id="mom-export-status" class="text-muted w-100 export-status"></small>
                    </div>
                </div>
                <div class="table-responsive" id="mom-cluster-table-wrap">
                    <table class="table table-hover mb-0">
                        <colgroup>
                            <col class="mom-location-col">
                            <col class="mom-number-col">
                            <col class="mom-number-col">
                            <col class="mom-number-col">
                            <col class="mom-growth-col">
                            <col class="mom-number-col">
                            <col class="mom-number-col">
                            <col class="mom-number-col">
                        </colgroup>
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="border-0">LOKASI (TAP / SF)</th>
                                <th class="text-right border-0">MTD ({{ $momCurrentLabel }})</th>
                                <th class="text-right border-0">M-1 ({{ $momPreviousLabel }})</th>
                                <!-- <th class="text-right border-0">M-2 PARTIAL</th> -->
                                <th class="text-right border-0">M-1 FULL</th>
                                <th class="text-right border-0">GROWTH (M-1)</th>
                                <th class="text-right border-0">DAILY ST</th>
                                <th class="text-right border-0">STOCK GUDANG</th>
                                <th class="text-right border-0">STOCK DAYS</th>
                                <!-- <th class="text-right border-0">GROWTH (M-2)</th> -->
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($momProductRows as $r)
                                @php
                                    $dailySt = $momSelectedDate->day > 0 ? $r->curr_qty / $momSelectedDate->day : 0;
                                    $stockGudang = (float) ($stockGudangByTap[$r->idtap] ?? 0);
                                    $stockDays = $dailySt > 0 ? $stockGudang / $dailySt : 0;
                                @endphp
                                <tr class="bg-white">
                                    <td class="font-weight-bold text-dark">
                                        <button class="btn btn-sm btn-light border toggle-sf rounded py-0 px-2 mr-2"
                                            data-target="sf-{{ Str::slug($r->idtap) }}">
                                            +
                                        </button>
                                        {{ $r->idtap }}
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">{{ number_format($r->curr_qty) }}</td>
                                    <td class="text-right text-muted">{{ number_format($r->prev_partial_qty) }}</td>
                                    <td class="text-right text-muted">{{ number_format($r->prev_full_qty) }}</td>
                                    <td class="text-right font-weight-bold {{ $r->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                        {!! $r->mom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($r->mom), 2) }} %
                                    </td>
                                    <td class="text-right metric-daily-st">{{ number_format($dailySt, 0) }}</td>
                                    <td class="text-right metric-stock">{{ number_format($stockGudang) }}</td>
                                    <td class="text-right metric-stock-days">{{ number_format($stockDays, 0) }} hari</td>
                                </tr>

                                <tr id="sf-{{ Str::slug($r->idtap) }}" class="sf-row d-none bg-light">
                                    <td colspan="8" class="p-0 border-0">
                                        <div class="p-3 bg-light border-left border-right">
                                            <table class="table table-sm mb-0 bg-white shadow-sm rounded">
                                                <thead class="bg-white">
                                                    <tr>
                                                        <th class="border-top-0">Nama Petugas</th>
                                                        <th class="text-right border-top-0">MTD ({{ $momCurrentLabel }})</th>
                                                        <th class="text-right border-top-0">M-1 ({{ $momPreviousLabel }})</th>
                                                        <th class="text-right border-top-0">M-1 Full</th>
                                                        <th class="text-right border-top-0">Growth (M-1)</th>
                                                        <th class="text-right border-top-0">Daily ST</th>
                                                        <th class="text-right border-top-0">Stok SF</th>
                                                        <th class="text-right border-top-0">Stock Days</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($momProductSfByTap[$r->idtap] ?? [] as $sf)
                                                        <tr>
                                                            <td class="text-muted border-bottom-0"><i data-feather="user" style="width:14px;height:14px"></i> {{ $sf->namasf ?? $sf->idsf }}</td>
                                                            <td class="text-right font-weight-bold border-bottom-0">{{ number_format($sf->curr_qty) }}</td>
                                                            <td class="text-right text-muted border-bottom-0">{{ number_format($sf->prev_qty) }}</td>
                                                            <td class="text-right text-muted border-bottom-0">{{ number_format($sf->prev_full_qty) }}</td>
                                                            <td class="text-right border-bottom-0 {{ $sf->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                                                <span class="badge badge-{{ $sf->mom >= 0 ? 'success' : 'danger' }} badge-pill bg-{{ $sf->mom >= 0 ? 'success' : 'danger' }} text-white font-weight-bold px-2 py-1">
                                                                    {!! $sf->mom >= 0 ? '&#43;' : '' !!} {{ number_format($sf->mom, 2) }} %
                                                                </span>
                                                            </td>
                                                            <td class="text-right metric-daily-st">{{ number_format($sf->daily_st, 0) }}</td>
                                                            <td class="text-right metric-stock">{{ number_format($sf->stock_qty, 0) }}</td>
                                                            <td class="text-right metric-stock-days">{{ $sf->stock_days === null ? '–' : number_format($sf->stock_days, 0).' hari' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="bg-light text-dark font-weight-bold" style="border-top: 2px solid #ddd">
                            @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP', 'CLUSTER_DUMAI']))
                            <tr>
                                <td class="border-0"><span class="badge badge-secondary mr-2">CLUSTER</span> Dumai Bengkalis</td>
                                <td class="text-right border-0">{{ number_format($momProductClusters['dumai_bengkalis']->curr_qty) }}</td>
                                <td class="text-right opacity-75 border-0">{{ number_format($momProductClusters['dumai_bengkalis']->prev_partial_qty) }}</td>
                                <td class="text-right opacity-75 border-0">{{ number_format($momProductClusters['dumai_bengkalis']->prev_full_qty) }}</td>
                                <td class="text-right border-0 {{ $momProductClusters['dumai_bengkalis']->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $momProductClusters['dumai_bengkalis']->mom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($momProductClusters['dumai_bengkalis']->mom), 2) }} %
                                </td>
                                @php
                                    $dumaiDailySt = $momSelectedDate->day > 0 ? $momProductClusters['dumai_bengkalis']->curr_qty / $momSelectedDate->day : 0;
                                    $dumaiStock = collect(['DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING'])->sum(fn($tap) => (float) ($stockGudangByTap[$tap] ?? 0));
                                @endphp
                                <td class="text-right border-0">{{ number_format($dumaiDailySt, 0) }}</td>
                                <td class="text-right border-0">{{ number_format($dumaiStock) }}</td>
                                <td class="text-right border-0">{{ number_format($dumaiDailySt > 0 ? $dumaiStock / $dumaiDailySt : 0, 0) }} hari</td>
                            </tr>
                            @endif
                            @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP', 'CLUSTER_ROHIL']))
                            <tr>
                                <td class="border-0"><span class="badge badge-light text-dark mr-2">CLUSTER</span> Rokan Hilir</td>
                                <td class="text-right border-0">{{ number_format($momProductClusters['rokan_hilir']->curr_qty) }}</td>
                                <td class="text-right opacity-75 border-0">{{ number_format($momProductClusters['rokan_hilir']->prev_partial_qty) }}</td>
                                <td class="text-right opacity-75 border-0">{{ number_format($momProductClusters['rokan_hilir']->prev_full_qty) }}</td>
                                <td class="text-right border-0 {{ $momProductClusters['rokan_hilir']->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $momProductClusters['rokan_hilir']->mom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($momProductClusters['rokan_hilir']->mom), 2) }} %
                                </td>
                                @php
                                    $rohilDailySt = $momSelectedDate->day > 0 ? $momProductClusters['rokan_hilir']->curr_qty / $momSelectedDate->day : 0;
                                    $rohilStock = collect(['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'])->sum(fn($tap) => (float) ($stockGudangByTap[$tap] ?? 0));
                                @endphp
                                <td class="text-right border-0">{{ number_format($rohilDailySt, 0) }}</td>
                                <td class="text-right border-0">{{ number_format($rohilStock) }}</td>
                                <td class="text-right border-0">{{ number_format($rohilDailySt > 0 ? $rohilStock / $rohilDailySt : 0, 0) }} hari</td>
                            </tr>
                            @endif
                            @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP']))
                            @php
                                $grandCurr = $momProductClusters['dumai_bengkalis']->curr_qty + $momProductClusters['rokan_hilir']->curr_qty;
                                $grandPrevPart = $momProductClusters['dumai_bengkalis']->prev_partial_qty + $momProductClusters['rokan_hilir']->prev_partial_qty;
                                $grandPrevFull = $momProductClusters['dumai_bengkalis']->prev_full_qty + $momProductClusters['rokan_hilir']->prev_full_qty;
                                $grandMom = $grandPrevPart > 0 ? (($grandCurr - $grandPrevPart) / $grandPrevPart) * 100 : 0;
                            @endphp
                            <tr style="background-color: #e3f2fd; color: #0d47a1;">
                                <td class="border-0 font-weight-bold">✨ GRAND TOTAL MOM</td>
                                <td class="text-right border-0 font-weight-bold">{{ number_format($grandCurr) }}</td>
                                <td class="text-right opacity-75 border-0 font-weight-bold">{{ number_format($grandPrevPart) }}</td>
                                <td class="text-right opacity-75 border-0 font-weight-bold">{{ number_format($grandPrevFull) }}</td>
                                <td class="text-right border-0 font-weight-bold {{ $grandMom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $grandMom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($grandMom), 2) }} %
                                </td>
                                @php
                                    $grandDailySt = $momSelectedDate->day > 0 ? $grandCurr / $momSelectedDate->day : 0;
                                    $grandStock = $dumaiStock + $rohilStock;
                                @endphp
                                <td class="text-right border-0 font-weight-bold">{{ number_format($grandDailySt, 0) }}</td>
                                <td class="text-right border-0 font-weight-bold">{{ number_format($grandStock) }}</td>
                                <td class="text-right border-0 font-weight-bold">{{ number_format($grandDailySt > 0 ? $grandStock / $grandDailySt : 0, 0) }} hari</td>
                                {{-- M-2 comparison is intentionally disabled for the unified PV dataset. --}}
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>
