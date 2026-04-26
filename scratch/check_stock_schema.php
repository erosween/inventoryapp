<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('stockawalsf');
echo "Columns in stockawalsf: " . implode(', ', $columns) . "\n";

$first = DB::table('stockawalsf')->first();
print_r($first);
