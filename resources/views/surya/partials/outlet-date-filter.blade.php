{{--
    Kontrol cutoff laporan Detail Outlet. Nilai dipakai oleh tabel, ringkasan, dan ekspor.
--}}<div class="outlet-period">
    <div class="outlet-period-copy"><label for="sales-cutoff">Periode laporan</label><span id="outlet-period-caption" data-period-caption>@if(\App\Support\OutletReportPeriod::isClosedMonthEnd(\Carbon\Carbon::parse($salesCutoffValue)))Bulan penuh · {{ \Carbon\Carbon::parse($salesCutoffValue)->locale('id')->translatedFormat('F Y') }}@else MTD 1–{{ \Carbon\Carbon::parse($salesCutoffValue)->locale('id')->translatedFormat('d F Y') }}@endif</span></div>
    <div class="outlet-period-controls">
        <div class="outlet-date-control">
            <input class="input" id="sales-cutoff" name="sales_cutoff" type="date" value="{{ $salesCutoffValue }}" max="{{ $salesCutoffMax }}" data-latest="{{ now('Asia/Jakarta')->subDays(2)->toDateString() }}" aria-describedby="outlet-period-caption">
            <button type="button" data-open-outlet-date aria-label="Buka kalender periode"><i class="fas fa-calendar-days" aria-hidden="true"></i></button>
        </div>
        <div class="outlet-period-presets" aria-label="Pilihan periode cepat"><button type="button" data-period-preset="latest">Periode terbaru</button><button type="button" data-period-preset="previous-month">Akhir bulan lalu</button></div>
    </div>
</div>
