@extends('surya.layouts.workspace')
@section('title', 'Dashboard Inventory')
@section('active-page', 'inventory-dashboard')
@section('description', 'Ringkasan stok, penjualan, inject, dan pertumbuhan sesuai wilayah akses Anda.')
@section('content-class', 'surya-inventory-dashboard-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
@include('layout.premium-theme')
<link rel="stylesheet" href="{{ asset('css/surya-inventory-dashboard.css') }}?v={{ filemtime(public_path('css/surya-inventory-dashboard.css')) }}">
@endpush
@prepend('scripts')
<script src="{{ asset('assets/js/core/jquery.3.2.1.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.29.2/dist/feather.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(() => {
    // Cards are refreshed independently. Their hidden controls must reflect
    // the latest filters before the shared Inventory handlers build a URL.
    const syncHiddenFilters = () => {
        const params = new URLSearchParams(window.location.search);
        document.querySelectorAll('.surya-inventory-dashboard-page input[type="hidden"][name]').forEach(input => {
            if (params.has(input.name)) input.value = params.get(input.name);
        });
    };
    document.addEventListener('change', syncHiddenFilters, true);
    document.addEventListener('submit', syncHiddenFilters, true);
    document.addEventListener('click', event => {
        if (event.target.closest('form, #sales-card-reload-btn, #monthly-sales-table-btn, #monthly-sales-chart-btn')) syncHiddenFilters();
    }, true);
})();
</script>
@endprepend
