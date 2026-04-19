<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/sisastock/data', 'GET');
$request->setLaravelSession($app['session']->driver());
$request->session()->put('idtap', 'SBP_DUMAI');

$controller = app()->make(\App\Http\Controllers\sisaStockController::class);
try {
   $response = $controller->data($request);
   echo "Success! JSON Output:\n" . substr(json_encode($response->getData()), 0, 100);
} catch (\Exception $e) {
   echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
