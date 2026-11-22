{{-- Filter interaktif untuk grafik analisis penjualan. --}}
<div class="distribution-controls" data-distribution-controls>
    <label for="distribution-{{ $kind }}">Produk</label>
    <select id="distribution-{{ $kind }}" class="distribution-product" data-kind="{{ $kind }}" aria-label="Produk distribusi {{ $kind }}">
        @foreach(['pv_all'=>'PV ALL','pv_reg'=>'PV REGULER','pv_byu'=>'PV BYU','sa_all'=>'SA ALL','sa_simpati'=>'SA SIMPATI','sa_byu'=>'SA BYU'] as $value => $label)
            <option value="{{ $value }}" @selected($selectedProduct === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <label for="distribution-cluster">Cluster</label>
    <select id="distribution-cluster" class="distribution-filter-select" aria-label="Filter cluster penjualan">
        <option value="all" @selected($selectedCluster === 'all')>Semua Cluster</option>
        @foreach($clusters as $key => $cluster)
            <option value="{{ $key }}" @selected($selectedCluster === $key)>{{ $cluster['label'] }}</option>
        @endforeach
    </select>
    <label for="distribution-tap">TAP</label>
    <select id="distribution-tap" class="distribution-filter-select" aria-label="Filter TAP penjualan">
        <option value="all" @selected($selectedTap === 'all')>Semua TAP</option>
        @foreach($taps as $tap)
            <option value="{{ $tap }}" data-clusters="{{ collect($clusters)->filter(fn ($cluster) => in_array($tap, $cluster['taps'], true))->keys()->implode(',') }}" @selected($selectedTap === $tap)>{{ $tap }}</option>
        @endforeach
    </select>
    <span class="distribution-status" role="status" aria-live="polite"></span>
</div>
