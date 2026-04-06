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
use App\Http\Controllers\RetursfController;
use App\Http\Controllers\SfmasukController;
use App\Http\Controllers\StockSfController;
use App\Http\Controllers\SfkeluarController;
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

//login
Route::middleware([RedirectIfAuthenticated::class])->group(function () {
	Route::get('/', [AuthController::class, 'showLoginForm'])->name('/');
	Route::post('/logins', [AuthController::class, 'login'])->name('login.post');
});
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

//  nocan
Route::get('/nocan', [NocanController::class, 'index'])->name('nocan');
Route::get('/form/form-nocan', [NocanController::class, 'form'])->name('nocan');
Route::get('/getnumber', [NocanController::class, 'getnumber']);
Route::post('/nocanproses', [NocanController::class, 'nocanproses']);

Route::get('/booking', [NocanbookingController::class, 'index'])->name('nocan');
Route::get('/jual', [NocanjualController::class, 'index'])->name('nocan');

Route::get('/search', [searchController::class, 'search'])->name('search');

//monita dumai
use App\Http\Controllers\MonitaDumaiController;

Route::get('/monitadumai', [MonitaDumaiController::class, 'index']);
Route::get('/monitadumai/search', [MonitaDumaiController::class, 'search']);
Route::get('/monitadumai/suggest', [MonitaDumaiController::class, 'suggest']);
Route::get('/monitadumai/nearby', [MonitaDumaiController::class, 'nearby']);


// group middleware
Route::middleware(['auth'])->group(function () {

	Route::get('/home', [HomeController::class, 'index'])->name('home');
	Route::get('/chart/sales', [SalesChartController::class, 'sales']);

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
	Route::post('/injectvf/{idinject}', [InjectController::class, 'delete']);
	//form inject segel
	Route::get('/form/forminject', [FormInjectsegelController::class, 'index']);
	Route::post('/form/forminject', [FormInjectsegelController::class, 'injectProses']);
	Route::post('/ajax/get-stock-segel-tap', [FormInjectsegelController::class, 'getStockSegelTap'])->name('ajax.get-stock-segel-tap');
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
	Route::get('/exportexceldo', [InputDOController::class, 'exportexcel']);

	// bo retur
	Route::get('/bo', [BOController::class, 'index']);
	Route::get('/bo/data', [BOController::class, 'data'])->name('bo.data');
	Route::post('/bo/store', [BOController::class, 'proseskeluarboform'])->name('bo.store');
	Route::get('/exportexcelbo', [BOController::class, 'exportexcel']);
	Route::get('/form/formkeluarbo', [BOController::class, 'keluarboform']);
	Route::post('/form/formkeluarbo', [BOController::class, 'getTap']);
	Route::post('/bo/delete/{idkeluar}', [BOController::class, 'delete']);

	// voucher rusak
	Route::get('vrusak', [VrusakController::class, 'index'])->name('vrusak.index');
	Route::get('vrusak/data', [VrusakController::class, 'data'])->name('vrusak.data');
	Route::get('form/form-vrusak', [VrusakController::class, 'vrusak']);
	Route::post('vrusak', [VrusakController::class, 'vrusakproses']);
	Route::post('vrusak/{idrusak}', [VrusakController::class, 'delete']);
	Route::get('exportrusak', [VrusakController::class, 'exportexcel']);

	//stok masuk sf
	Route::get('sf-masuk', [SfmasukController::class, 'index']);
	Route::get('/form/form-sfmasuk', [SfmasukController::class, 'formmasuksf']);
	Route::post('sf-masuk', [SfmasukController::class, 'masuksfproses']);
	Route::post('/ajax/get-sf', [SfmasukController::class, 'getSf'])
		->name('ajax.get-sf');
	Route::post('/ajax/get-stock-tap', [SfmasukController::class, 'getStockTap'])
		->name('ajax.get-stock-tap');
	Route::get('/sf-masuk/data', [SfmasukController::class, 'data'])
		->name('sf-masuk.data');
	Route::post('sf-masuk/{idmasuk}', [SfmasukController::class, 'delete']);
	Route::get('/exportsfmasuk', [sfmasukController::class, 'exportexcel']);

	//stok keluar sf
	Route::get('/sf-keluar', [SfkeluarController::class, 'index']);
	Route::get('form/form-sfkeluar', [SfkeluarController::class, 'formkeluarsf']);
	Route::post('/ajax/get-sf', [SfkeluarController::class, 'getSf'])
		->name('ajax.get-sf');
	Route::post('/ajax/get-stock', [SfkeluarController::class, 'getStock'])
		->name('ajax.get-stock');
	Route::get('/sf-keluar/data', [SfkeluarController::class, 'data'])
		->name('sf-keluar.data');
	Route::get('/exportsfkeluar', [SfkeluarController::class, 'exportexcel']);
	Route::post('sf-keluar/{idkeluar}', [SfkeluarController::class, 'delete']);
	Route::post('sf-keluar', [SfkeluarController::class, 'keluarsfproses']);

	//retursf
	Route::get('/retursf', [ReturSfController::class, 'index'])->name('retursf.index');
	Route::get('/retursf/data', [ReturSfController::class, 'data'])->name('retursf.data');
	Route::get('/exportretursf', [ReturSfController::class, 'exportexcel']);
	Route::post('/retursf/{idretur}', [ReturSfController::class, 'delete']);
	Route::get('/form/form-retursf', [ReturSfController::class, 'form']);
	Route::post('/retursf', [ReturSfController::class, 'store']);

	// stock movement ledger
	Route::post('/sf-masuk-ledger', [StockMovementController::class, 'transfer']);
	Route::post('/sf-keluar-ledger', [StockMovementController::class, 'out']);

	// inbox
	Route::get('inbox', [InboxController::class, 'index']);

	//HOME NOCAN
	Route::get('/homenocan', [HomenocanController::class, 'index']);
	Route::get('/nocanadmin', [NocanadminController::class, 'index']);
	Route::post('/nocanadmin/{id}', [NocanadminController::class, 'edit']);
	Route::post('/reset/{id}', [NocanadminController::class, 'reset']);
	Route::get('/exportnocan', [HomenocanController::class, 'exportexcel']);
});
