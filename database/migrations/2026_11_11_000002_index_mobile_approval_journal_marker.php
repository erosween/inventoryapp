<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // SalesSource narrows the journal by the "Mobile Approval ID: " prefix.
            DB::statement('ALTER TABLE keluarsf ADD INDEX keluarsf_approval_marker_idx (tambahanket(40)), ALGORITHM=INPLACE, LOCK=NONE');
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE keluarsf DROP INDEX keluarsf_approval_marker_idx, ALGORITHM=INPLACE, LOCK=NONE');
        }
    }
};
