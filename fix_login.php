<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;

Schema::dropIfExists('login_test2');
Schema::create('login_test2', function (Blueprint $table) {
    $table->id();
    $table->string('idtap');
    $table->string('username')->nullable();
    $table->string('password')->unique();
    $table->enum('level', ['1','2']);
});

echo "Success\n";
