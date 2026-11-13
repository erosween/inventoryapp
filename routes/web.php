<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BOController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MasukController;
use App\Http\Controllers\NocanController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\InjectController;
use App\Http\Controllers\KeluarController;
use App\Http\Controllers\VrusakController;
use App\Http\Controllers\InputDOController;
use App\Http\Controllers\ReturSfController;
use App\Http\Controllers\SfmasukController;
use App\Http\Controllers\StockSfController;
use App\Http\Controllers\SfkeluarController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\StockTapController;
use App\Http\Controllers\HomenocanController;
use App\Http\Controllers\NocanjualController;
use App\Http\Controllers\NocanbookingController;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Controllers\FormInjectbyuController;
use App\Http\Controllers\FormKeluartapController;
use App\Http\Controllers\FormInjectsegelController;
use App\Http\Controllers\FormInjectroamaxController;
use App\Http\Controllers\sisaStockController;
use App\Http\Controllers\NocanadminController;
use App\Http\Controllers\searchController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockJourneyController;
use App\Http\Controllers\SuryaStockController;
use App\Http\Controllers\MonitaDumaiController;
use App\Http\Controllers\Api\SalesChartController;
use App\Http\Controllers\DenomController;
use App\Http\Controllers\SfController;

// Login web Inventory. Sesi sales force mobile dimulai dari /mobile/login.
Route::middleware([RedirectIfAuthenticated::class])->group(function () {
	Route::get('/', [AuthController::class, 'showLoginForm'])->name('/');
	Route::post('/logins', [AuthController::class, 'login'])->name('login.post');
});
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Nomor cantik. Jalur pencarian, booking, dan penjualan nomor.
Route::get('/nocan', [NocanController::class, 'index'])->name('nocan.index');
Route::get('/form/form-nocan', [NocanController::class, 'form'])->name('nocan.form');
Route::get('/getnumber', [NocanController::class, 'getnumber']);
Route::post('/nocanproses', [NocanController::class, 'nocanproses']);

Route::get('/booking', [NocanbookingController::class, 'index'])->name('nocan.booking');
Route::get('/jual', [NocanjualController::class, 'index'])->name('nocan.jual');

Route::get('/search', [searchController::class, 'search'])->name('search');

// SURYA Web. Akses halaman diperiksa oleh middleware surya.web.
Route::middleware('surya.web')->group(function () {
Route::get('/surya', [MonitaDumaiController::class, 'index'])->name('surya.dashboard');
Route::get('/surya/dashboard', [HomeController::class, 'index'])->middleware(['auth', 'asmen.access'])->name('surya.inventory-dashboard');
// KPI SVP. Blade dan surya-kpi-svp.js menangani tampilan, KpiSvpController membaca dan menyimpan entri.
Route::view('/surya/kpi-svp', 'surya.kpi-svp')->name('surya.kpi-svp');
Route::get('/surya/kpi-svp/entries', [\App\Http\Controllers\KpiSvpController::class, 'entries'])->name('surya.kpi-svp.entries');
Route::put('/surya/kpi-svp/entries', [\App\Http\Controllers\KpiSvpController::class, 'save'])->name('surya.kpi-svp.save');
// Upload Outletlist. Profil outlet, kategori, dan histori nota punya jalur import masing-masing.
Route::post('/surya/outletlist/upload', [\App\Http\Controllers\OutletlistUploadController::class, 'store'])->name('surya.outletlist.upload');
Route::get('/surya/outletlist/template', [\App\Http\Controllers\OutletlistUploadController::class, 'template'])->name('surya.outletlist.template');
Route::post('/surya/outletlist/category', [\App\Http\Controllers\OutletlistUploadController::class, 'categoryStore'])->name('surya.outletlist.category');
Route::post('/surya/outletlist/nota', [\App\Http\Controllers\OutletlistUploadController::class, 'notaStore'])->name('surya.outletlist.nota');
Route::get('/surya/outletlist/nota/template', [\App\Http\Controllers\OutletlistUploadController::class, 'notaTemplate'])->name('surya.outletlist.nota-template');
// Transaksi Outlet. SA/PV dan CVM memakai URL /surya/transaksi-outlet, NGRS tetap memakai /surya/ngrs.
Route::get('/surya/transaksi-outlet/{family}/filter-values', [\App\Http\Controllers\OutletTransactionController::class, 'values'])->where('family','sa|pv')->name('surya.outlet-transactions.values');
Route::get('/surya/transaksi-outlet/{family}/export', [\App\Http\Controllers\OutletTransactionController::class, 'export'])->where('family','sa|pv')->name('surya.outlet-transactions.export');
Route::get('/surya/transaksi-outlet/cvm/filter-values', [\App\Http\Controllers\OutletCvmController::class, 'values'])->name('surya.outlet-cvm.values');
Route::get('/surya/transaksi-outlet/cvm/export', [\App\Http\Controllers\OutletCvmController::class, 'export'])->name('surya.outlet-cvm.export');
Route::get('/surya/transaksi-outlet/cvm', [\App\Http\Controllers\OutletCvmController::class, 'index'])->name('surya.outlet-cvm');
Route::post('/surya/transaksi-outlet/cvm/upload/{kind}', [\App\Http\Controllers\OutletCvmController::class, 'store'])->where('kind', 'cvm|sub-cvm')->name('surya.outlet-cvm.upload');
Route::get('/surya/transaksi-outlet/{family}', [\App\Http\Controllers\OutletTransactionController::class, 'index'])->where('family','sa|pv')->name('surya.outlet-transactions');
Route::get('/surya/ngrs/filter-values', [\App\Http\Controllers\NgrsController::class, 'values'])->name('surya.ngrs.filter-values');
Route::get('/surya/ngrs', [\App\Http\Controllers\NgrsController::class, 'index'])->name('surya.ngrs');
Route::get('/surya/ngrs/summary-export', [\App\Http\Controllers\NgrsController::class, 'summaryExport'])->name('surya.ngrs.summary-export');
Route::get('/surya/ngrs/export', [\App\Http\Controllers\NgrsController::class, 'export'])->name('surya.ngrs.export');
Route::post('/surya/ngrs/upload', [\App\Http\Controllers\NgrsController::class, 'store'])->name('surya.ngrs.upload');
Route::get('/surya/ngrs/template', [\App\Http\Controllers\NgrsController::class, 'template'])->name('surya.ngrs.template');
Route::get('/surya/outletlist/category/template', [\App\Http\Controllers\OutletlistUploadController::class, 'categoryTemplate'])->name('surya.outletlist.category-template');
// Upload Transaksi. File metrik bisa diproses bertahap melalui endpoint process.
Route::get('/surya/transaction-upload', [MonitaDumaiController::class, 'metricsUploadForm'])->name('surya.transaction-upload');
Route::post('/surya/transaction-upload', [MonitaDumaiController::class, 'metricsUpload'])->name('surya.transaction-upload.store');
Route::post('/surya/transaction-upload/{upload}/process', [MonitaDumaiController::class, 'metricsUploadProcess'])->whereUuid('upload')->name('surya.transaction-upload.process');
// Stok dan target. Stock On Hand, Stock Gudang per tanggal, dan pengelolaan target.
Route::get('/surya/stock-on-hand', [SuryaStockController::class, 'stock'])->name('surya.stock-on-hand');
Route::get('/surya/stock-gudang', [sisaStockController::class, 'index'])->middleware('auth')->name('surya.stock-gudang');
Route::get('/surya/input-target', [SuryaStockController::class, 'targets'])->name('surya.input-target');
Route::post('/surya/input-target/upload', [SuryaStockController::class, 'upload'])->name('surya.input-target.upload');
Route::patch('/surya/input-target/{target}', [SuryaStockController::class, 'update'])->name('surya.input-target.update');
// Detail Outlet. Halaman utama dan endpoint data, ringkasan, filter, ekspor, serta profil ada di
// MonitaDumaiController.
Route::get('/surya/detail-outlet', [MonitaDumaiController::class, 'outletDetail'])->name('surya.outlet-detail');
// Taskforce Push SO. Daftar penugasan dari upload dan riwayat kunjungan per outlet.
Route::get('/surya/taskforce/push-so', [\App\Http\Controllers\TaskforceController::class, 'index'])->name('surya.taskforce.push-so');
Route::get('/surya/taskforce/push-so/visits/download', [\App\Http\Controllers\TaskforceController::class, 'visitWorkbook'])->name('surya.taskforce.visits.download');
Route::get('/surya/taskforce/push-so/{idOutlet}/visits', [\App\Http\Controllers\TaskforceController::class, 'visitHistory'])->name('surya.taskforce.visits.history');
Route::post('/surya/taskforce/push-so/{idOutlet}/visits', [\App\Http\Controllers\TaskforceController::class, 'addVisit'])->name('surya.taskforce.visits.store');
Route::get('/surya/taskforce/push-so/download', [\App\Http\Controllers\TaskforceController::class, 'download'])->name('surya.taskforce.download');
Route::get('/surya/taskforce/push-so/filter-values', [\App\Http\Controllers\TaskforceController::class, 'filterValues'])->name('surya.taskforce.filter-values');
Route::post('/surya/taskforce/push-so/{idOutlet}/action', [\App\Http\Controllers\TaskforceController::class, 'store'])->name('surya.taskforce.push-so.store');
Route::post('/surya/taskforce/upload', [\App\Http\Controllers\TaskforceController::class, 'upload'])->name('surya.taskforce.upload');
Route::get('/surya/taskforce/template', [\App\Http\Controllers\TaskforceController::class, 'template'])->name('surya.taskforce.template');
// Endpoint Detail Outlet. Ringkasan dan daftar outlet dapat dimuat terpisah oleh browser.
Route::get('/surya/detail-outlet/sales-summary/download', [MonitaDumaiController::class, 'outletSalesSummary'])->name('surya.outlet-detail.sales-summary.download');
Route::get('/surya/detail-outlet/sales-summary', [MonitaDumaiController::class, 'outletSalesSummary'])->name('surya.outlet-detail.sales-summary');
Route::get('/surya/detail-outlet/filter-values', [MonitaDumaiController::class, 'outletColumnValues'])->name('surya.outlet-detail.filter-values');
Route::get('/surya/detail-outlet/data', [MonitaDumaiController::class, 'outletDetailData'])->name('surya.outlet-detail.data');
Route::get('/surya/detail-outlet/export', [MonitaDumaiController::class, 'outletDetailExport'])->name('surya.outlet-detail.export');
Route::get('/surya/detail-outlet/{idOutlet}/profile', [MonitaDumaiController::class, 'outletProfile'])->name('surya.outlet-detail.profile');
Route::put('/surya/detail-outlet/{idOutlet}/profile', [MonitaDumaiController::class, 'updateOutletProfile'])->name('surya.outlet-detail.profile.update');
Route::get('/surya/export-visits', [MonitaDumaiController::class, 'exportVisits'])->name('surya.visits.export');
Route::post('/surya', [MonitaDumaiController::class, 'leaderLogin'])->name('surya.leader.login.post');
Route::get('/surya/leader-login', [MonitaDumaiController::class, 'showLeaderLogin']);
Route::get('/surya/leader-logout', [MonitaDumaiController::class, 'leaderLogout'])->name('surya.leader.logout');
// Peta Outlet. Pencarian, data peta, foto, lokasi sekitar, dan performa outlet.
Route::get('/surya/search', [MonitaDumaiController::class, 'search']);
Route::get('/surya/suggest', [MonitaDumaiController::class, 'suggest']);
Route::get('/surya/outlets', [MonitaDumaiController::class, 'outlets']);
Route::get('/surya/outlet-insights', [MonitaDumaiController::class, 'outletInsights']);
Route::get('/surya/outlet-branding-photos', [MonitaDumaiController::class, 'outletBrandingPhotos'])->name('surya.outlet-branding-photos');
Route::get('/surya/nearby', [MonitaDumaiController::class, 'nearby']);
Route::get('/surya/performance', [MonitaDumaiController::class, 'performance']);
});

// Redirect untuk tautan lama yang masih memakai alamat Monita Dumai.
Route::redirect('/monitadumai', '/surya', 301);
Route::redirect('/monitadumai/leader-login', '/surya/leader-login', 301);
Route::redirect('/monitadumai/leader-logout', '/surya/leader-logout', 301);
Route::redirect('/monitadumai/search', '/surya/search', 301);
Route::redirect('/monitadumai/suggest', '/surya/suggest', 301);
Route::redirect('/monitadumai/outlets', '/surya/outlets', 301);
Route::redirect('/monitadumai/nearby', '/surya/nearby', 301);
Route::redirect('/monitadumai/performance', '/surya/performance', 301);


// Web Inventory. Grup ini memakai sesi web dan pembatasan akses asmen.
Route::middleware(['auth', 'asmen.access'])->group(function () {

	// Dashboard Inventory berbagi HomeController dengan Dashboard SURYA. Layout mengikuti route aktif.
	Route::get('/home', [HomeController::class, 'index'])->name('home');
	Route::get('/chart/sales', [SalesChartController::class, 'sales']);

    // Audit Trail
    Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('audit/data', [AuditController::class, 'data'])->name('audit.data');

    // Penyesuaian saldo stok (khusus admin_super)
    Route::get('/stock-adjustment', [StockAdjustmentController::class, 'index'])->name('stock-adjustment.index');
    Route::get('/stock-adjustment/current', [StockAdjustmentController::class, 'current'])->name('stock-adjustment.current');
    Route::post('/stock-adjustment', [StockAdjustmentController::class, 'update'])->name('stock-adjustment.update');

    // Master Sales Force. Data petugas, akun mobile, dan ekspor daftar SF.
    Route::get('/sf', [SfController::class, 'index']);
    Route::post('/sf', [SfController::class, 'store']);
    Route::post('/sf/update/{idsf}', [SfController::class, 'update']);
    Route::post('/sf/{idsf}', [SfController::class, 'destroy']);
    Route::get('/ajax/check-sf/{id}', [SfController::class, 'checkId'])->name('ajax.check-sf');
    Route::get('/sf/sync-login', [SfController::class, 'syncLogin'])->name('sf.sync-login');
    Route::get('/sf/export', [SfController::class, 'export'])->name('sf.export');

    // Master denom. Klasifikasi produk dan varian dipakai oleh stok, inject, dan laporan.
    Route::get('/denoms', [DenomController::class, 'index'])->name('denoms.index');
    Route::post('/denoms', [DenomController::class, 'store'])->name('denoms.store');
    Route::post('/denoms/{iddenom}', [DenomController::class, 'update'])->name('denoms.update');
    Route::post('/denoms/delete/{iddenom}', [DenomController::class, 'destroy'])->name('denoms.destroy');
    Route::get('/ajax/check-denom/{id}', [DenomController::class, 'checkId'])->name('ajax.check-denom');

	// Penerimaan barang. Daftar dan ekspor transaksi masuk tersedia sebelum konfirmasi penerimaan.
	Route::get('/masuk', [MasukController::class, 'index'])->name('masuk.index');
	Route::get('/masuk/data', [MasukController::class, 'data'])->name('masuk.data');
	Route::get('/masuk/summary', [MasukController::class, 'summary']);
	Route::get('/exportexcelmasuk', [MasukController::class, 'exportexcel'])->name('masuk.export');
	// approve / terima barang
	Route::post('/masuk/{idkeluar}', [MasukController::class, 'masuk'])->name('masuk.approve');

	// Ringkasan stok. Saldo menurut TAP atau SF dan riwayat perpindahan barang.
	Route::get('/stock', [StockController::class, 'index']);
	Route::get('/stocktap', [StockTapController::class, 'index']);
	Route::get('/stocksf', [StockSfController::class, 'index']);
	Route::get('/stock-journey', [StockJourneyController::class, 'index'])->name('stock-journey.index');

	// Riwayat inject. Daftar, ekspor, dan pembatalan transaksi inject.
	Route::get('/injectvf', [InjectController::class, 'index']);
	Route::get('/injectvf/data', [InjectController::class, 'data'])->name('inject.data');
	Route::get('/exportinject', [InjectController::class, 'export']);
	Route::post('/injectvf/bulk-delete', [InjectController::class, 'bulkDelete'])->name('inject.bulk-delete');
	Route::post('/injectvf/{idinject}', [InjectController::class, 'delete']);
	// Input inject. Form segel dan pengecekan stok sumber di TAP.
	Route::get('/form/forminject', [FormInjectsegelController::class, 'index']);
	Route::post('/form/forminject', [FormInjectsegelController::class, 'injectProses']);
	Route::post('/ajax/get-stock-segel-tap', [FormInjectsegelController::class, 'getStockSegelTap'])->name('ajax.get-stock-segel-tap');
	Route::post('/ajax/get-all-stock-inject', [InjectController::class, 'getAllStockTap'])->name('ajax.get-all-stock-inject');
	// Jalur form khusus inject by.U.
	Route::get('/form/forminjectbyu', [FormInjectbyuController::class, 'index']);
	Route::post('/form/forminjectbyu', [FormInjectbyuController::class, 'injectProses']);
	Route::post('/ajax/get-stock-byu-tap', [FormInjectbyuController::class, 'getStockSegelTap'])->name('ajax.get-stock-byu-tap');

	// Pengiriman antar-TAP. Riwayat, ringkasan, pembatalan, dan ekspor.
	Route::get('/keluar', [KeluarController::class, 'index'])->name('keluar.index');
	Route::get('/keluar/data', [KeluarController::class, 'data'])->name('keluar.data');
	Route::get('/keluar/summary', [KeluarController::class, 'summary'])->name('keluar.summary');
	Route::post('/keluar/{idkeluar}/cancel', [KeluarController::class, 'cancel'])->name('keluar.cancel');
	Route::get('/exporttap', [KeluarController::class, 'exportexcel']);

	// Form pengiriman antar-TAP dan pengecekan saldo pengirim.
	Route::get('form/formkeluartap', [FormKeluartapController::class, 'index']);
	Route::post('form/formkeluartap', [FormKeluartapController::class, 'proseskeluartapform']);
	Route::post('/ajax/get-stock-tap-pengirim', [FormKeluartapController::class, 'getStockTapPengirim'])
		->name('ajax.get-stock-tap-pengirim');
	Route::post('/ajax/get-all-stock-tap-keluar', [FormKeluartapController::class, 'getAllStockTap'])->name('ajax.get-all-stock-tap-keluar');

	// Input DO. Penerimaan barang dari DO, koreksi transaksi, dan ekspor.
	Route::get('/DO', [InputDOController::class, 'index'])->name('do.index');
	Route::get('/DO/DATA', [InputDOController::class, 'data'])->name('do.data');
	Route::post('/do/get-tap', [InputDOController::class, 'getTap'])
		->name('do.get-tap');
	// Route::post('/DO', [InputDOController::class, 'DOmasukform']);
	Route::post('/DO', [InputDOController::class, 'masukproses']);
	//untuk select bertingkat tap dan bo
	Route::get('form/formDO', [InputDOController::class, 'formDO']);
	Route::post('form/formDO', [InputDOController::class, 'getTap']);
	Route::post('/DO/{idmasuk}', [InputDOController::class, 'delete']);
	Route::get('/DO/edit/{id}', [InputDOController::class, 'edit'])->name('do.edit');
	Route::post('/DO/update/{id}', [InputDOController::class, 'update'])->name('do.update');
	Route::get('/exportexceldo', [InputDOController::class, 'exportexcel']);

	// Retur BO. Input, koreksi, pembatalan, dan ekspor pengembalian barang.
	Route::get('/bo', [BOController::class, 'index']);
	Route::get('/bo/data', [BOController::class, 'data'])->name('bo.data');
	Route::post('/bo/store', [BOController::class, 'proseskeluarboform'])->name('bo.store');
	Route::get('/exportexcelbo', [BOController::class, 'exportexcel']);
	Route::get('/form/formkeluarbo', [BOController::class, 'keluarboform']);
	Route::post('/form/formkeluarbo', [BOController::class, 'getTap']);
	Route::post('/bo/delete/{idkeluar}', [BOController::class, 'delete']);
	Route::get('/bo/edit/{id}', [BOController::class, 'edit'])->name('bo.edit');
	Route::post('/bo/update/{id}', [BOController::class, 'update'])->name('bo.update');

	// Voucher rusak. Pencatatan, koreksi, dan pembatalan stok rusak.
	Route::get('vrusak', [VrusakController::class, 'index'])->name('vrusak.index');
	Route::get('vrusak/data', [VrusakController::class, 'data'])->name('vrusak.data');
	Route::get('form/form-vrusak', [VrusakController::class, 'vrusak']);
	Route::post('vrusak/stock-tap', [VrusakController::class, 'stockTap'])->name('vrusak.stock-tap');
	Route::post('vrusak', [VrusakController::class, 'vrusakproses']);
	Route::post('vrusak/{idrusak}', [VrusakController::class, 'delete']);
	Route::get('vrusak/edit/{id}', [VrusakController::class, 'edit'])->name('vrusak.edit');
	Route::post('vrusak/update/{id}', [VrusakController::class, 'update'])->name('vrusak.update');
	Route::get('exportrusak', [VrusakController::class, 'exportexcel']);

	// SF Masuk. Penyaluran barang dari TAP ke stok petugas.
	Route::get('sf-masuk', [SfmasukController::class, 'index']);
	Route::get('/form/form-sfmasuk', [SfmasukController::class, 'formmasuksf']);
	Route::post('sf-masuk', [SfmasukController::class, 'masuksfproses']);
	Route::post('/ajax/get-sf-masuk', [SfmasukController::class, 'getSf'])
		->name('ajax.get-sf-masuk');
	Route::post('/ajax/get-stock-tap', [SfmasukController::class, 'getStockTap'])
		->name('ajax.get-stock-tap');
	Route::post('/ajax/get-all-stock-tap-sfmasuk', [SfmasukController::class, 'getAllStockTap'])->name('ajax.get-all-stock-tap-sfmasuk');
	Route::get('/sf-masuk/data', [SfmasukController::class, 'data'])
		->name('sf-masuk.data');
	Route::get('sf-masuk/edit/{id}', [SfmasukController::class, 'editSfMasuk'])->name('sf-masuk.edit');
	Route::post('sf-masuk/update/{id}', [SfmasukController::class, 'updateSfMasuk'])->name('sf-masuk.update');
	Route::post('sf-masuk/bulk-delete', [SfmasukController::class, 'bulkDelete'])->name('sf-masuk.bulk-delete');
	Route::post('sf-masuk/{idmasuk}', [SfmasukController::class, 'delete']);
	Route::get('/exportsfmasuk', [SfmasukController::class, 'exportexcel']);

	// SF Keluar. Input penjualan, daftar dan ekspor gabungan jurnal SF serta approval mobile.
	Route::get('/sf-keluar', [SfkeluarController::class, 'index']);
	Route::get('form/form-sfkeluar', [SfkeluarController::class, 'formkeluarsf']);
	Route::post('/ajax/get-sf-keluar', [SfkeluarController::class, 'getSf'])
		->name('ajax.get-sf-keluar');
	Route::post('/ajax/get-outlets-keluar', [SfkeluarController::class, 'getOutlets'])
		->name('ajax.get-outlets-keluar');
	Route::post('/ajax/get-stock', [SfkeluarController::class, 'getStock'])
		->name('ajax.get-stock');
	Route::post('/ajax/get-all-stock', [SfkeluarController::class, 'getAllStock'])
		->name('ajax.get-all-stock');
	Route::get('/sf-keluar/data', [SfkeluarController::class, 'data'])
		->name('sf-keluar.data');
	Route::get('/exportsfkeluar', [SfkeluarController::class, 'exportexcel']);
	Route::get('sf-keluar/edit/{id}', [SfkeluarController::class, 'editSfKeluar'])->name('sf-keluar.edit');
	Route::post('sf-keluar/update/{id}', [SfkeluarController::class, 'updateSfKeluar'])->name('sf-keluar.update');
	Route::post('sf-keluar/bulk-delete', [SfkeluarController::class, 'bulkDelete'])->name('sf-keluar.bulk-delete');
	Route::post('sf-keluar/{idkeluar}', [SfkeluarController::class, 'delete']);
	Route::post('sf-keluar', [SfkeluarController::class, 'keluarsfproses']);

	// Retur SF. Pengembalian barang dari SF ke TAP dan pembatalan retur.
	Route::get('/retursf', [ReturSfController::class, 'index'])->name('retursf.index');
	Route::get('/retursf/data', [ReturSfController::class, 'data'])->name('retursf.data');
	Route::get('/exportretursf', [ReturSfController::class, 'exportexcel']);
	Route::post('/retursf/bulk-delete', [ReturSfController::class, 'bulkDelete'])->name('retursf.bulk-delete');
	Route::post('/retursf/{idretur}', [ReturSfController::class, 'delete']);
	Route::get('/retursf/edit/{id}', [ReturSfController::class, 'edit'])->name('retursf.edit');
	Route::post('/retursf/update/{id}', [ReturSfController::class, 'update'])->name('retursf.update');
	Route::get('/form/form-retursf', [ReturSfController::class, 'form']);
	Route::post('/retursf', [ReturSfController::class, 'store']);
	Route::post('/ajax/get-all-stock-sf-retur', [ReturSfController::class, 'getAllStockSf'])->name('ajax.get-all-stock-sf-retur');

	// Jalur API internal untuk transfer dan pengeluaran stok lewat ledger.
	Route::post('/sf-masuk-ledger', [StockMovementController::class, 'transfer']);
	Route::post('/sf-keluar-ledger', [StockMovementController::class, 'out']);

	// inbox
	Route::get('inbox', [InboxController::class, 'index']);

	// Stock Gudang. Saldo per tanggal dan ekspor Excel atau CSV.
	Route::get('/sisastock', [sisaStockController::class, 'index'])->name('sisastock.index');
	Route::match(['get', 'post'], '/sisastock/data', [sisaStockController::class, 'data'])->name('sisastock.data');
	Route::get('/sisastock/export/{format}', [sisaStockController::class, 'export'])
		->whereIn('format', ['xlsx', 'csv'])
		->name('sisastock.export');

	// Administrasi nomor cantik. Daftar, koreksi status, reset, dan ekspor.
	Route::get('/homenocan', [HomenocanController::class, 'index']);
	Route::get('/nocanadmin', [NocanadminController::class, 'index'])->name('nocanadmin.index');
	Route::get('/nocanadmin/data', [NocanadminController::class, 'data'])->name('nocanadmin.data');
	Route::post('/nocanadmin/{id}', [NocanadminController::class, 'edit'])->name('nocanadmin.edit');
	Route::post('/reset/{id}', [NocanadminController::class, 'reset'])->name('nocanadmin.reset');
	Route::get('/exportnocan', [HomenocanController::class, 'exportexcel']);

	// Approval dan monitoring lapangan. Modul web untuk transaksi sales mobile, visit, dan field report.
	Route::get('/mobile-approval', [\App\Http\Controllers\MobileApprovalController::class, 'index'])->name('admin.mobile-approval.index');
	Route::get('/surya/visits', [\App\Http\Controllers\SuryaVisitReportController::class, 'index'])->name('surya.visits.report');
	Route::match(['GET', 'POST'], '/surya/visits/auto-approve-pending', [\App\Http\Controllers\SuryaVisitReportController::class, 'autoApprovePending'])->name('surya.visits.auto-approve-pending');
	Route::post('/surya/visits/{visit}/approve', [\App\Http\Controllers\SuryaVisitReportController::class, 'approve'])->name('surya.visits.approve');
	Route::post('/surya/visits/{visit}/reject', [\App\Http\Controllers\SuryaVisitReportController::class, 'reject'])->name('surya.visits.reject');
	Route::get('/surya/field-reports', [\App\Http\Controllers\SuryaFieldReportController::class, 'index'])->name('surya.field-reports');
	Route::get('/mobile-approval/data', [\App\Http\Controllers\MobileApprovalController::class, 'data'])->name('admin.mobile-approval.data');
	Route::get('/mobile-approval/stats', [\App\Http\Controllers\MobileApprovalController::class, 'getStats'])->name('admin.mobile-approval.stats');
	Route::get('/mobile-approval/sf-details', [\App\Http\Controllers\MobileApprovalController::class, 'getSfDetails'])->name('admin.mobile-approval.sf-details');
	Route::post('/mobile-approval/approve', [\App\Http\Controllers\MobileApprovalController::class, 'approve'])->name('admin.mobile-approval.approve');
	Route::post('/mobile-approval/bulk-approve', [\App\Http\Controllers\MobileApprovalController::class, 'bulkApprove'])->name('admin.mobile-approval.bulk-approve');
	Route::post('/mobile-approval/bulk-reject', [\App\Http\Controllers\MobileApprovalController::class, 'bulkReject'])->name('admin.mobile-approval.bulk-reject');
	Route::post('/mobile-approval/reject', [\App\Http\Controllers\MobileApprovalController::class, 'reject'])->name('admin.mobile-approval.reject');

});

// SURYA Mobile. Login SF terpisah, lalu MobileSFAccess menjaga jalur operasional.
Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\MobileAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\MobileAuthController::class, 'login'])->name('login.post');
    Route::get('/logout', [\App\Http\Controllers\MobileAuthController::class, 'logout'])->name('logout');

    Route::middleware([\App\Http\Middleware\MobileSFAccess::class])->group(function () {
        Route::get('/', [\App\Http\Controllers\MobileSalesController::class, 'index'])->name('index');
        Route::get('/input', [\App\Http\Controllers\MobileSalesController::class, 'form'])->name('form');
        // Visit SF. Status kunjungan, check-in, pemilik stok, dan checkout.
        Route::get('/visit', [\App\Http\Controllers\SuryaVisitController::class, 'create'])->name('visit');
        Route::get('/visit-menu', [\App\Http\Controllers\SuryaVisitController::class, 'menu'])->name('visit.menu');
        Route::get('/visit-status', [\App\Http\Controllers\SuryaVisitController::class, 'status'])->name('visit.status');
        Route::post('/visit', [\App\Http\Controllers\SuryaVisitController::class, 'store'])->name('visit.store');
        Route::post('/visit-stock-owner', [\App\Http\Controllers\SuryaVisitController::class, 'assignStockOwner'])->name('visit.stock-owner');
        Route::post('/visit-checkout', [\App\Http\Controllers\SuryaVisitController::class, 'checkout'])->name('visit.checkout');
        // Fitur lapangan tambahan. Titip jual, survei, dan laporan jaringan.
        Route::get('/consignment', [\App\Http\Controllers\MobileSuryaExtrasController::class, 'consignment'])->name('surya.consignment');
        Route::get('/survey', [\App\Http\Controllers\MobileSuryaExtrasController::class, 'survey'])->name('surya.survey');
        Route::get('/network', [\App\Http\Controllers\MobileSuryaExtrasController::class, 'network'])->name('surya.network');
        Route::post('/surya/{type}', [\App\Http\Controllers\MobileSuryaExtrasController::class, 'store'])->name('surya.store');
        // Submit penjualan, validasi nomor titip jual, dan alur bukti transaksi.
        Route::post('/input', [\App\Http\Controllers\MobileSalesController::class, 'store'])->name('store');
        Route::post('/consignment/check', [\App\Http\Controllers\MobileSalesController::class, 'checkConsignmentNumbers'])->name('consignment.check');
        Route::get('/receipt/{receipt}', [\App\Http\Controllers\MobileSalesController::class, 'showReceipt'])->name('receipt.show');
        Route::get('/receipt/{receipt}/image.jpg', [\App\Http\Controllers\MobileSalesController::class, 'receiptImage'])->name('receipt.image');
        Route::post('/receipt/{receipt}/whatsapp', [\App\Http\Controllers\MobileSalesController::class, 'prepareReceiptWhatsapp'])->name('receipt.whatsapp');
        Route::post('/receipt/{receipt}/confirm', [\App\Http\Controllers\MobileSalesController::class, 'confirmReceiptWhatsapp'])->name('receipt.confirm');
        // Riwayat transaksi, pencarian outlet, koreksi penjualan, dan stok petugas.
        Route::get('/history', [\App\Http\Controllers\MobileSalesController::class, 'history'])->name('history');
        Route::get('/history/details', [\App\Http\Controllers\MobileSalesController::class, 'getVisitDetails'])->name('history.details');
        Route::get('/search-outlet', [\App\Http\Controllers\MobileSalesController::class, 'searchOutlet'])->name('search-outlet');
        Route::get('/outlet-performance/{id_outlet}', [\App\Http\Controllers\MobileSalesController::class, 'outletPerformance'])->name('outlet-performance');
        Route::get('/edit/{id_outlet}/{tgl}', [\App\Http\Controllers\MobileSalesController::class, 'edit'])->name('edit');
        Route::put('/update/{id_outlet}/{tgl}', [\App\Http\Controllers\MobileSalesController::class, 'update'])->name('update');
        Route::get('/stock', [\App\Http\Controllers\MobileSalesController::class, 'stock'])->name('stock');
        Route::get('/password', [\App\Http\Controllers\MobileAuthController::class, 'showChangePasswordForm'])->name('password');
        Route::post('/password', [\App\Http\Controllers\MobileAuthController::class, 'updatePassword'])->name('password.update');
    });
});
