<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Covered by migration 000002 in bounded batches. Kept as a separate
        // migration for installations that already received 000002 before
        // the numeric-prefix matching was corrected.
    }

    public function down(): void {}
};
