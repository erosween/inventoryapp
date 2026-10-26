@extends('surya.layouts.workspace')
@section('title', 'Stock Gudang')
@section('active-page', 'stock-gudang')
@section('description', 'Pantau stok TAP dan sales force beserta histori hariannya.')
@section('content-class', 'surya-warehouse-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
@include('layout.premium-theme')
<link rel="stylesheet" href="{{ asset('css/surya-warehouse.css') }}?v={{ filemtime(public_path('css/surya-warehouse.css')) }}">
@endpush
@prepend('scripts')
<script src="{{ asset('assets/js/core/jquery.3.2.1.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
@endprepend
