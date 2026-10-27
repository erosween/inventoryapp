<div class="distribution-controls">
    <label for="distribution-{{ $kind }}">Produk</label>
    <select id="distribution-{{ $kind }}" class="distribution-product" data-kind="{{ $kind }}" aria-label="Produk distribusi {{ $kind }}">
        @foreach(['pv_all'=>'PV ALL','pv_reg'=>'PV REGULER','pv_byu'=>'PV BYU','sa_all'=>'SA ALL','sa_simpati'=>'SA SIMPATI','sa_byu'=>'SA BYU'] as $value => $label)
            <option value="{{ $value }}" @selected($selectedProduct === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <span class="distribution-status" role="status" aria-live="polite"></span>
</div>
<small class="text-muted">Cutoff {{ $momSelectedDate->copy()->locale('id')->translatedFormat('d M Y') }}</small>
