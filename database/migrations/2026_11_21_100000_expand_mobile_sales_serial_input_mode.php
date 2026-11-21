<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE mobile_sales_serials MODIFY input_mode VARCHAR(20) NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE mobile_sales_serials SET input_mode = 'single' WHERE input_mode = 'uploaded_stock'");
        DB::statement('ALTER TABLE mobile_sales_serials MODIFY input_mode VARCHAR(10) NOT NULL');
    }
};
