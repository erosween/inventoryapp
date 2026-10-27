@php
    $metadata = isset($groupingDenom) ? (array) $groupingDenom : [];
@endphp
<div class="col-sm-12"><div class="form-group">
    <label>Produk</label>
    <select name="product" class="form-control" required>
        @foreach(['PV'=>'PV', 'SA'=>'SA'] as $key=>$label)
        <option value="{{ $key }}" @selected(($metadata['product'] ?? 'PV') === $key)>{{ $label }}</option>
        @endforeach
    </select>
</div></div>
<div class="col-sm-12"><div class="form-group">
    <label>Varian Produk</label>
    <select name="product_variant" class="form-control" required>
        @foreach(['REGULER'=>'Reguler (PV)', 'SIMPATI'=>'Simpati (SA)', 'BYU'=>'By.U', 'ROAMAX'=>'RoaMAX (PV)'] as $key=>$label)
        <option value="{{ $key }}" @selected(($metadata['product_variant'] ?? 'REGULER') === $key)>{{ $label }}</option>
        @endforeach
    </select>
</div></div>
<div class="col-sm-12"><div class="form-group">
    <label>Bentuk Stok</label>
    <select name="stock_form" class="form-control" required>
        @foreach(['INJECT'=>'Inject / siap jual', 'SEGEL'=>'Segel (kode sumber tetap)'] as $key=>$label)
        <option value="{{ $key }}" @selected(($metadata['stock_form'] ?? 'INJECT') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <small class="form-text text-muted">Sumber segel mengikuti Produk + Varian. Input DO hanya SEGEL, V33, SP6, SP7, SP1. SP1 juga bisa di-inject dari SP6.</small>
    @if(isset($groupingDenom))
    <small class="form-text text-muted">Sumber saat ini: {{ $groupingDenom->inject_source_id ?: '–' }} · DO: {{ $groupingDenom->allow_do ? 'Diizinkan' : 'Tidak' }}.</small>
    @endif
</div></div>
