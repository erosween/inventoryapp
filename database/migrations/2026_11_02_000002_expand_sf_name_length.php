<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE idsf MODIFY COLUMN namasf VARCHAR(100) NOT NULL');
        }
    }
    public function down(): void
    {
        // Do not truncate names already saved with the expanded limit.
    }
};
