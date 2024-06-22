<?php

namespace App\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Number extends Model
{
    use HasFactory;
}

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbers', function (Blueprint $table) {
            $table->id();
            $table->integer('number');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbers');
    }
};
