<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (Schema::hasTable('appsdumais')) {
    $columns = Schema::getColumnListing('appsdumais');
    echo "Columns in appsdumais: " . implode(', ', $columns) . "\n";

    $first = DB::table('appsdumais')->first();
    print_r($first);
} else {
    echo "Table appsdumais not found!\n";
}
