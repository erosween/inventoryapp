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

// group middleware
Route::middleware(['auth'])->group(function () {

	Route::get('/home', [HomeController::class, 'index'])->name('home');

	//masuk
	Route::get('/masuk', [MasukController::class, 'index']);
	Route::post('masuk/{idkeluar}', [MasukController::class, 'masuk']);
	Route::get('/exportexcelmasuk', [MasukController::class, 'exportexcel']);

	//menu summary stok
	Route::get('/stock', [StockController::class, 'index']);
	Route::get('/stocktap', [StockTapController::class, 'index']);
	Route::get('/stocksf', [StockSfController::class, 'index']);

	//export excel STOCK
	Route::get('/exportexcelall', [StockController::class, 'exportexcel']);
	Route::get('/exportexceltap', [StockTapController::class, 'exportexcel']);
	Route::get('/exportexcelsf', [StockSfController::class, 'exportexcel']);

	//injectvf segel
	Route::get('/injectvf', [InjectController::class, 'index']);
	Route::post('injectvf/{idinject}', [InjectController::class, 'delete']);
	Route::get('/exportinject', [InjectController::class, 'exportexcel']);

	//form inject segel
	Route::get('/form/forminject', [FormInjectsegelController::class, 'index']);
	Route::post('/form/forminject', [FormInjectsegelController::class, 'injectProses']);
	//form inject roamax
	Route::get('/form/forminjectroamax', [FormInjectroamaxController::class, 'index']);
	Route::post('/form/forminjectroamax', [FormInjectroamaxController::class, 'injectProses']);
	//form inject byu
	Route::get('/form/forminjectbyu', [FormInjectbyuController::class, 'index']);
	Route::post('/form/forminjectbyu', [FormInjectbyuController::class, 'injectProses']);

	//keluar
	Route::get('/keluar', [KeluarController::class, 'index']);
	Route::get('/exporttap', [KeluarController::class, 'exportexcel']);

	//form keluar tap
	Route::get('form/formkeluartap', [FormKeluartapController::class, 'index']);
	Route::post('form/formkeluartap', [FormKeluartapController::class, 'proseskeluartapform']);

	//input DO
	Route::get('/DO', [InputDOController::class, 'index']);
	// Route::post('/DO', [InputDOController::class, 'DOmasukform']);
	Route::post('/DO', [InputDOController::class, 'masukproses']);
	//untuk select bertingkat tap dan bo
	Route::get('form/formDO', [InputDOController::class, 'formDO']);
	Route::post('form/formDO', [InputDOController::class, 'getTap']);
	Route::post('/DO/{idmasuk}', [InputDOController::class, 'delete']);
	Route::get('/exportexceldo', [InputDOController::class, 'exportexcel']);

	//BO RETUR
	Route::get('BO', [BOController::class, 'index']);
	Route::post('/BO/{idkeluar}', [BOController::class, 'delete']);
	Route::get('/exportexcelbo', [BOController::class, 'exportexcel']);
	Route::get('/form/formkeluarbo', [BOController::class, 'keluarboform']);
	Route::post('/BO', [BOController::class, 'proseskeluarboform']);
	Route::post('/form/formkeluarbo', [BOController::class, 'getTap']);

	//voucher rusak
	Route::get('vrusak', [VrusakController::class, 'index']);
	Route::get('form/form-vrusak', [VrusakController::class, 'vrusak']);
	Route::post('vrusak', [VrusakController::class, 'vrusakproses']);
	Route::post('vrusak/{idrusak}', [VrusakController::class, 'delete']);
	Route::get('/exportrusak', [VrusakController::class, 'exportexcel']);

	//stok masuk sf
	Route::get('sf-masuk', [SfmasukController::class, 'index']);
	Route::get('/form/form-sfmasuk', [SfmasukController::class, 'formmasuksf']);
	Route::post('sf-masuk', [SfmasukController::class, 'masuksfproses']);
	Route::post('/form/form-sfmasuk', [SfmasukController::class, 'getSf']);
	Route::post('sf-masuk/{idmasuk}', [SfmasukController::class, 'delete']);
	Route::get('/exportsfmasuk', [sfmasukController::class, 'exportexcel']);

	//stok keluar sf
	Route::get('/sf-keluar', [SfkeluarController::class, 'index']);
	Route::get('form/form-sfkeluar', [SfkeluarController::class, 'formkeluarsf']);
	Route::post('/form/form-sfkeluar', [SfkeluarController::class, 'getSf']);
	Route::post('sf-keluar', [SfkeluarController::class, 'keluarsfproses']);
	Route::post('sf-keluar/{idkeluar}', [SfkeluarController::class, 'delete']);
	Route::get('/exportsfkeluar', [SfkeluarController::class, 'exportexcel']);

	//retursf
	Route::get('retursf', [RetursfController::class, 'index']);
	Route::post('/form/form-retursf', [RetursfController::class, 'getSf']);
	Route::get('/form/form-retursf', [RetursfController::class, 'formretursf']);
	Route::post('retursf', [RetursfController::class, 'retursfproses']);
	Route::get('/exportretursf', [RetursfController::class, 'exportexcel']);
	Route::post('exportretursf/{idrusak}', [RetursfController::class, 'delete']);

	//cek stok daily
	Route::get('detail', [DetailController::class, 'index']);

	// inbox
	Route::get('inbox', [InboxController::class, 'index']);

	// sisa stock 
	Route::get('sisastock', [sisaStockController::class, 'index']);


	//HOME NOCAN
	Route::get('/homenocan', [HomenocanController::class, 'index']);
	Route::get('/nocanadmin', [NocanadminController::class, 'index']);
	Route::post('/nocanadmin/{id}', [NocanadminController::class, 'edit']);
	Route::post('/reset/{id}', [NocanadminController::class, 'reset']);
	Route::get('/exportnocan', [HomenocanController::class, 'exportexcel']);
});
