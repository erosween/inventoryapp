@extends('surya.layouts.workspace')

@section('title', 'Cek Duplikat')
@section('active-page', 'product-tracking-upload')
@section('description', 'Daftar rentang SN yang bertumpuk pada upload yang sudah tersimpan.')
@section('content-class', 'pt-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">
@endpush

@section('content')
<section class="pt-card">
    <div class="pt-result-head">
        <div>
            <h2>Cek Duplikat · {{ $kind === 'scan' ? 'ST Digipos' : 'SO '.strtoupper(substr($kind, 3)) }}</h2>
            <p class="pt-subtitle mb-0">Pemeriksaan manual untuk upload yang sudah tersimpan. Hasil ini hanya menampilkan data dan tidak mengubah atau menghapus stok.</p>
        </div>
        <a class="pt-button" href="{{ route('surya.product-tracking.imports', ['kind' => $kind]) }}"><i class="fas fa-arrow-left"></i> Kembali ke Upload</a>
    </div>

    @if(session('success'))
        <div class="pt-alert success">{{ session('success') }}</div>
    @endif

    @if($duplicates !== [])
        <div class="pt-alert info">Pilih catatan yang ingin dibersihkan. Hanya bagian SN yang bertumpuk dihapus; sisa rentang unik tetap tersimpan.</div>
    @endif

    @if($truncated)
        <div class="pt-alert info">Ditampilkan 500 rentang duplikat pertama. Pemeriksaan dihentikan setelah mencapai batas tampilan.</div>
    @endif

    @if($duplicates === [])
        <div class="pt-alert success">Tidak ditemukan rentang SN yang bertumpuk pada upload {{ $kind === 'scan' ? 'ST Digipos' : 'SO '.strtoupper(substr($kind, 3)) }} yang selesai.</div>
    @else
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <p class="pt-subtitle mb-0">{{ number_format(count($duplicates)) }} rentang SN bertumpuk ditemukan{{ $truncated ? ' (daftar dibatasi 500; tombol bersihkan memproses semuanya)' : '' }}.</p>
        <form method="POST" action="{{ route('surya.product-tracking.imports.duplicates.remove-all') }}" onsubmit="return confirm('Bersihkan SEMUA SN duplikat untuk data ini? Catatan pertama untuk setiap SN dipertahankan dan bagian rentang unik tetap disimpan. Proses dapat memakan waktu.');">
            @csrf
            <input type="hidden" name="kind" value="{{ $kind }}">
            <button class="pt-button" type="submit"><i class="fas fa-broom"></i> Hapus semua duplikat</button>
        </form>
    </div>
        <div class="pt-table-wrap">
            <table class="pt-table">
                <thead><tr><th>SN Duplikat</th><th>Jumlah SN</th><th>File Pertama</th><th>Tanggal</th><th>ID Outlet</th><th>File Duplikat</th><th>Tanggal</th><th>ID Outlet</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($duplicates as $duplicate)
                        <tr>
                            <td>{{ $duplicate['serial_start'] }}@if($duplicate['serial_end'] !== $duplicate['serial_start']) – {{ $duplicate['serial_end'] }}@endif</td>
                            <td>{{ number_format((int) $duplicate['quantity']) }}</td>
                            <td>{{ $duplicate['first_file'] }}</td><td>{{ $duplicate['first_date'] ?: '—' }}</td><td>{{ $duplicate['first_outlet'] ?: '—' }}</td>
                            <td>{{ $duplicate['duplicate_file'] }}</td><td>{{ $duplicate['duplicate_date'] ?: '—' }}</td><td>{{ $duplicate['duplicate_outlet'] ?: '—' }}</td>
                            <td class="text-nowrap">
                                @foreach(['first' => 'file pertama', 'duplicate' => 'file duplikat'] as $source => $label)
                                    <form method="POST" action="{{ route('surya.product-tracking.imports.duplicates.remove') }}" class="d-inline" onsubmit="return confirm('Hapus SN yang bertumpuk dari {{ $label }}? Rentang SN unik akan tetap disimpan.');">
                                        @csrf
                                        <input type="hidden" name="kind" value="{{ $kind }}">
                                        <input type="hidden" name="first_id" value="{{ $duplicate['first_id'] }}">
                                        <input type="hidden" name="duplicate_id" value="{{ $duplicate['duplicate_id'] }}">
                                        <input type="hidden" name="remove_from" value="{{ $source }}">
                                        <button class="pt-button pt-button-sm" type="submit">Hapus dari {{ $label }}</button>
                                    </form>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
