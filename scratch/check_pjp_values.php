<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$pjp_values = DB::table('appsdumais')->select('pjp')->distinct()->get();
echo "Distinct PJP values:\n";
foreach($pjp_values as $v) echo "- " . $v->pjp . "\n";

$sf_values = DB::table('appsdumais')->select('sf')->distinct()->limit(10)->get();
echo "\nSome SF values in appsdumais:\n";
foreach($sf_values as $v) echo "- " . $v->sf . "\n";
