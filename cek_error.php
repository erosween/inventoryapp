<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/sisastock/data', 'GET', [
    'draw' => '1',
    'columns' => [
        ['data' => 'idtap', 'name' => 'idtap', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
        ['data' => 'V1', 'name' => 'V1', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
    ],
    'order' => [
        ['column' => '0', 'dir' => 'asc']
    ],
    'start' => '0',
    'length' => '25',
    'search' => ['value' => '', 'regex' => 'false']
]);
$request->setLaravelSession($app['session']->driver());
$request->session()->put('idtap', 'SBP_DUMAI');

$controller = app()->make(\App\Http\Controllers\sisaStockController::class);
try {
   $response = $controller->data($request);
   $content = $response->getContent();
   if (!$content) {
      throw new \Exception("Content is empty!");
   }
   echo "\n✅ BERHASIL! Content Length: " . strlen($content) . " bytes\n";
   $decoded = json_decode($content, true);
   if (isset($decoded['error'])) {
       echo "\n❌ DATATABLES ERROR MSG:\n" . $decoded['error'] . "\n";
   }
} catch (\Exception $e) {
   echo "\n❌ ERROR DITEMUKAN:\n" . $e->getMessage() . "\n";
}
