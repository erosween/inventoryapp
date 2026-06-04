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
use App\Http\Controllers\MonitaDumaiController;
use App\Http\Controllers\Api\SalesChartController;
use App\Http\Controllers\DenomController;
use App\Http\Controllers\SfController;

//login
Route::middleware([RedirectIfAuthenticated::class])->group(function () {
	Route::get('/', [AuthController::class, 'showLoginForm'])->name('/');
	Route::post('/logins', [AuthController::class, 'login'])->name('login.post');
});
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

//  nocan
Route::get('/nocan', [NocanController::class, 'index'])->name('nocan.index');
Route::get('/form/form-nocan', [NocanController::class, 'form'])->name('nocan.form');
Route::get('/getnumber', [NocanController::class, 'getnumber']);
Route::post('/nocanproses', [NocanController::class, 'nocanproses']);

Route::get('/booking', [NocanbookingController::class, 'index'])->name('nocan.booking');
Route::get('/jual', [NocanjualController::class, 'index'])->name('nocan.jual');

Route::get('/search', [searchController::class, 'search'])->name('search');

//monita dumai
Route::get('/monitadumai', [MonitaDumaiController::class, 'index']);
Route::get('/monitadumai/leader-login', [MonitaDumaiController::class, 'showLeaderLogin'])->name('monita.leader.login');
Route::post('/monitadumai/leader-login', [MonitaDumaiController::class, 'leaderLogin'])->name('monita.leader.login.post');
Route::get('/monitadumai/leader-logout', [MonitaDumaiController::class, 'leaderLogout'])->name('monita.leader.logout');
Route::get('/monitadumai/search', [MonitaDumaiController::class, 'search']);
Route::get('/monitadumai/suggest', [MonitaDumaiController::class, 'suggest']);
Route::get('/monitadumai/nearby', [MonitaDumaiController::class, 'nearby']);
Route::get('/monitadumai/performance', [MonitaDumaiController::class, 'performance']);


// group middleware
Route::middleware(['auth'])->group(function () {

	Route::get('/home', [HomeController::class, 'index'])->name('home');
	Route::get('/chart/sales', [SalesChartController::class, 'sales']);

    // Audit Trail
    Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('audit/data', [AuditController::class, 'data'])->name('audit.data');

    // sf master data
    Route::get('/sf', [SfController::class, 'index']);
    Route::post('/sf', [SfController::class, 'store']);
    Route::post('/sf/update/{idsf}', [SfController::class, 'update']);
    Route::post('/sf/{idsf}', [SfController::class, 'destroy']);
    Route::get('/ajax/check-sf/{id}', [SfController::class, 'checkId'])->name('ajax.check-sf');
    Route::get('/sf/sync-login', [SfController::class, 'syncLogin'])->name('sf.sync-login');

    // Denom Management
    Route::get('/denoms', [DenomController::class, 'index'])->name('denoms.index');
    Route::post('/denoms', [DenomController::class, 'store'])->name('denoms.store');
    Route::post('/denoms/{iddenom}', [DenomController::class, 'update'])->name('denoms.update');
    Route::post('/denoms/delete/{iddenom}', [DenomController::class, 'destroy'])->name('denoms.destroy');
    Route::get('/ajax/check-denom/{id}', [DenomController::class, 'checkId'])->name('ajax.check-denom');

	// MASUK
	Route::get('/masuk', [MasukController::class, 'index'])->name('masuk.index');
	Route::get('/masuk/data', [MasukController::class, 'data'])->name('masuk.data');
	Route::get('/masuk/summary', [MasukController::class, 'summary']);
	Route::get('/exportexcelmasuk', [MasukController::class, 'exportexcel'])->name('masuk.export');
	// approve / terima barang
	Route::post('/masuk/{idkeluar}', [MasukController::class, 'masuk'])->name('masuk.approve');

	//menu summary stok
	Route::get('/stock', [StockController::class, 'index']);
	Route::get('/stocktap', [StockTapController::class, 'index']);
	Route::get('/stocksf', [StockSfController::class, 'index']);

	//injectvf segel
	Route::get('/injectvf', [InjectController::class, 'index']);
	Route::get('/injectvf/data', [InjectController::class, 'data'])->name('inject.data');
	Route::get('/exportinject', [InjectController::class, 'export']);
	Route::post('/injectvf/bulk-delete', [InjectController::class, 'bulkDelete'])->name('inject.bulk-delete');
	Route::post('/injectvf/{idinject}', [InjectController::class, 'delete']);
	//form inject segel
	Route::get('/form/forminject', [FormInjectsegelController::class, 'index']);
	Route::post('/form/forminject', [FormInjectsegelController::class, 'injectProses']);
	Route::post('/ajax/get-stock-segel-tap', [FormInjectsegelController::class, 'getStockSegelTap'])->name('ajax.get-stock-segel-tap');
	Route::post('/ajax/get-all-stock-inject', [InjectController::class, 'getAllStockTap'])->name('ajax.get-all-stock-inject');
	//form inject byu
	Route::get('/form/forminjectbyu', [FormInjectbyuController::class, 'index']);
	Route::post('/form/forminjectbyu', [FormInjectbyuController::class, 'injectProses']);
	Route::post('/ajax/get-stock-byu-tap', [FormInjectbyuController::class, 'getStockSegelTap'])->name('ajax.get-stock-byu-tap');

	// KELUAR TAP
	Route::get('/keluar', [KeluarController::class, 'index'])->name('keluar.index');
	Route::get('/keluar/data', [KeluarController::class, 'data'])->name('keluar.data');
	Route::get('/keluar/summary', [KeluarController::class, 'summary'])->name('keluar.summary');
	Route::get('/exporttap', [KeluarController::class, 'exportexcel']);

	//form keluar tap
	Route::get('form/formkeluartap', [FormKeluartapController::class, 'index']);
	Route::post('form/formkeluartap', [FormKeluartapController::class, 'proseskeluartapform']);
	Route::post('/ajax/get-stock-tap-pengirim', [FormKeluartapController::class, 'getStockTapPengirim'])
		->name('ajax.get-stock-tap-pengirim');
	Route::post('/ajax/get-all-stock-tap-keluar', [FormKeluartapController::class, 'getAllStockTap'])->name('ajax.get-all-stock-tap-keluar');

	//input DO
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

	// bo retur
	Route::get('/bo', [BOController::class, 'index']);
	Route::get('/bo/data', [BOController::class, 'data'])->name('bo.data');
	Route::post('/bo/store', [BOController::class, 'proseskeluarboform'])->name('bo.store');
	Route::get('/exportexcelbo', [BOController::class, 'exportexcel']);
	Route::get('/form/formkeluarbo', [BOController::class, 'keluarboform']);
	Route::post('/form/formkeluarbo', [BOController::class, 'getTap']);
	Route::post('/bo/delete/{idkeluar}', [BOController::class, 'delete']);
	Route::get('/bo/edit/{id}', [BOController::class, 'edit'])->name('bo.edit');
	Route::post('/bo/update/{id}', [BOController::class, 'update'])->name('bo.update');

	// voucher rusak
	Route::get('vrusak', [VrusakController::class, 'index'])->name('vrusak.index');
	Route::get('vrusak/data', [VrusakController::class, 'data'])->name('vrusak.data');
	Route::get('form/form-vrusak', [VrusakController::class, 'vrusak']);
	Route::post('vrusak', [VrusakController::class, 'vrusakproses']);
	Route::post('vrusak/{idrusak}', [VrusakController::class, 'delete']);
	Route::get('vrusak/edit/{id}', [VrusakController::class, 'edit'])->name('vrusak.edit');
	Route::post('vrusak/update/{id}', [VrusakController::class, 'update'])->name('vrusak.update');
	Route::get('exportrusak', [VrusakController::class, 'exportexcel']);

	//stok masuk sf
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

	//stok keluar sf
	Route::get('/sf-keluar', [SfkeluarController::class, 'index']);
	Route::get('form/form-sfkeluar', [SfkeluarController::class, 'formkeluarsf']);
	Route::post('/ajax/get-sf-keluar', [SfkeluarController::class, 'getSf'])
		->name('ajax.get-sf-keluar');
	Route::post('/ajax/get-stock', [SfkeluarController::class, 'getStock'])
		->name('ajax.get-stock');
	Route::post('/ajax/get-all-stock', [SfkeluarController::class, 'getAllStock'])
		->name('ajax.get-all-stock');
	Route::get('/sf-keluar/data', [SfkeluarController::class, 'data'])
		->name('sf-keluar.data');
	Route::get('/exportsfkeluar', [SfkeluarController::class, 'exportexcel']);
	Route::get('sf-keluar/edit/{id}', [SfkeluarController::class, 'editSfKeluar']);
	Route::post('sf-keluar/update/{id}', [SfkeluarController::class, 'updateSfKeluar']);
	Route::post('sf-keluar/bulk-delete', [SfkeluarController::class, 'bulkDelete'])->name('sf-keluar.bulk-delete');
	Route::post('sf-keluar/{idkeluar}', [SfkeluarController::class, 'delete']);
	Route::post('sf-keluar', [SfkeluarController::class, 'keluarsfproses']);

	//retursf
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

	// stock movement ledger
	Route::post('/sf-masuk-ledger', [StockMovementController::class, 'transfer']);
	Route::post('/sf-keluar-ledger', [StockMovementController::class, 'out']);

	// inbox
	Route::get('inbox', [InboxController::class, 'index']);

	// sisa stock daily
	Route::get('/sisastock', [sisaStockController::class, 'index'])->name('sisastock.index');
	Route::match(['get', 'post'], '/sisastock/data', [sisaStockController::class, 'data'])->name('sisastock.data');

	//HOME NOCAN
	Route::get('/homenocan', [HomenocanController::class, 'index']);
	Route::get('/nocanadmin', [NocanadminController::class, 'index']);
	Route::post('/nocanadmin/{id}', [NocanadminController::class, 'edit']);
	Route::post('/reset/{id}', [NocanadminController::class, 'reset']);
	Route::get('/exportnocan', [HomenocanController::class, 'exportexcel']);

	// mobile approval
	Route::get('/mobile-approval', [\App\Http\Controllers\MobileApprovalController::class, 'index'])->name('admin.mobile-approval.index');
	Route::get('/mobile-approval/data', [\App\Http\Controllers\MobileApprovalController::class, 'data'])->name('admin.mobile-approval.data');
	Route::get('/mobile-approval/stats', [\App\Http\Controllers\MobileApprovalController::class, 'getStats'])->name('admin.mobile-approval.stats');
	Route::get('/mobile-approval/sf-details', [\App\Http\Controllers\MobileApprovalController::class, 'getSfDetails'])->name('admin.mobile-approval.sf-details');
	Route::post('/mobile-approval/approve', [\App\Http\Controllers\MobileApprovalController::class, 'approve'])->name('admin.mobile-approval.approve');
	Route::post('/mobile-approval/bulk-approve', [\App\Http\Controllers\MobileApprovalController::class, 'bulkApprove'])->name('admin.mobile-approval.bulk-approve');
	Route::post('/mobile-approval/bulk-reject', [\App\Http\Controllers\MobileApprovalController::class, 'bulkReject'])->name('admin.mobile-approval.bulk-reject');
	Route::post('/mobile-approval/reject', [\App\Http\Controllers\MobileApprovalController::class, 'reject'])->name('admin.mobile-approval.reject');

});

// EMPLOYEE PRESENCE ADMIN (Standalone Access)
Route::prefix('admin-presensi')->name('admin-presensi.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\EmployeePresenceAdminController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\EmployeePresenceAdminController::class, 'login'])->name('login.post');
    Route::get('/logout', [\App\Http\Controllers\EmployeePresenceAdminController::class, 'logout'])->name('logout');
    Route::get('/', [\App\Http\Controllers\EmployeePresenceAdminController::class, 'index'])->name('index');
    Route::get('/template', [\App\Http\Controllers\EmployeePresenceAdminController::class, 'template'])->name('template');
    Route::post('/import', [\App\Http\Controllers\EmployeePresenceAdminController::class, 'import'])->name('import');
    Route::post('/employees/{employee}', [\App\Http\Controllers\EmployeePresenceAdminController::class, 'update'])->name('update');
});

Route::get('/presensi-admin', fn () => redirect()->route(session()->has('presence_admin_id') ? 'admin-presensi.index' : 'admin-presensi.login'));

// MOBILE SALES FORCE (Independent Access)
Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\MobileAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\MobileAuthController::class, 'login'])->name('login.post');
    Route::get('/logout', [\App\Http\Controllers\MobileAuthController::class, 'logout'])->name('logout');

    Route::middleware([\App\Http\Middleware\MobileSFAccess::class])->group(function () {
        Route::get('/', [\App\Http\Controllers\MobileSalesController::class, 'index'])->name('index');
        Route::get('/attendance', [\App\Http\Controllers\MobileAttendanceController::class, 'index'])->name('attendance');
        Route::post('/attendance', [\App\Http\Controllers\MobileAttendanceController::class, 'store'])->name('attendance.store');
        Route::post('/attendance/checkout', [\App\Http\Controllers\MobileAttendanceController::class, 'checkout'])->name('attendance.checkout');
        Route::get('/input', [\App\Http\Controllers\MobileSalesController::class, 'form'])->name('form');
        Route::post('/input', [\App\Http\Controllers\MobileSalesController::class, 'store'])->name('store');
        Route::get('/history', [\App\Http\Controllers\MobileSalesController::class, 'history'])->name('history');
        Route::get('/history/details', [\App\Http\Controllers\MobileSalesController::class, 'getVisitDetails'])->name('history.details');
        Route::get('/search-outlet', [\App\Http\Controllers\MobileSalesController::class, 'searchOutlet'])->name('search-outlet');
        Route::get('/edit/{id_outlet}/{tgl}', [\App\Http\Controllers\MobileSalesController::class, 'edit'])->name('edit');
        Route::put('/update/{id_outlet}/{tgl}', [\App\Http\Controllers\MobileSalesController::class, 'update'])->name('update');
        Route::get('/stock', [\App\Http\Controllers\MobileSalesController::class, 'stock'])->name('stock');
        Route::get('/password', [\App\Http\Controllers\MobileAuthController::class, 'showChangePasswordForm'])->name('password');
        Route::post('/password', [\App\Http\Controllers\MobileAuthController::class, 'updatePassword'])->name('password.update');
    });
});

// EMPLOYEE PRESENCE (Independent Access)
Route::prefix('presensi')->name('presensi.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\EmployeeAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\EmployeeAuthController::class, 'login'])->name('login.post');
    Route::get('/logout', [\App\Http\Controllers\EmployeeAuthController::class, 'logout'])->name('logout');

    Route::middleware([\App\Http\Middleware\EmployeeAttendanceAccess::class])->group(function () {
        Route::get('/', [\App\Http\Controllers\EmployeePresenceController::class, 'index'])->name('index');
        Route::get('/riwayat', [\App\Http\Controllers\EmployeePresenceController::class, 'history'])->name('history');
        Route::post('/enroll', [\App\Http\Controllers\EmployeePresenceController::class, 'enroll'])->name('enroll');
        Route::post('/attendance', [\App\Http\Controllers\EmployeePresenceController::class, 'store'])->name('attendance.store');
        Route::post('/attendance/checkout', [\App\Http\Controllers\EmployeePresenceController::class, 'checkout'])->name('attendance.checkout');
        Route::get('/menu', [\App\Http\Controllers\EmployeePresenceMenuController::class, 'dashboard'])->name('menu');
        Route::get('/pengajuan/{type}', [\App\Http\Controllers\EmployeePresenceMenuController::class, 'create'])->name('request.create');
        Route::post('/pengajuan/{type}', [\App\Http\Controllers\EmployeePresenceMenuController::class, 'store'])->name('request.store');
        Route::get('/kotak-masuk', [\App\Http\Controllers\EmployeePresenceMenuController::class, 'inbox'])->name('inbox');
        Route::get('/slip-gaji', [\App\Http\Controllers\EmployeePresenceMenuController::class, 'payslip'])->name('payslip');
        Route::get('/akun', [\App\Http\Controllers\EmployeePresenceMenuController::class, 'account'])->name('account');
    });
});
