<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ENTERPRISE SECURITY HARDENING
     * 
     * 1. Fix negative stock values in stockawalsf (data corruption from legacy)
     * 2. Add UNSIGNED constraint to stock columns (database-level anti-minus)
     * 3. Add missing indexes for performance on high-volume tables
     * 4. Add missing primary key to kodetap
     */
    public function up(): void
    {
        // ============================================
        // 1. FIX EXISTING NEGATIVE STOCK → RESET TO 0
        // ============================================
        DB::table('stockawalsf')->where('stock', '<', 0)->update(['stock' => 0]);
        DB::table('stockawaltap')->where('stock', '<', 0)->update(['stock' => 0]);

        // ============================================
        // 2. CHANGE stock COLUMNS TO UNSIGNED (ANTI MINUS DI LEVEL DATABASE)
        // ============================================
        DB::statement('ALTER TABLE stockawaltap MODIFY COLUMN stock INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE stockawalsf MODIFY COLUMN stock INT UNSIGNED NOT NULL DEFAULT 0');

        // ============================================
        // 3. ADD MISSING INDEXES FOR PERFORMANCE
        // ============================================

        // injectvf — sering di-query by tgl, idtap, iddenom
        Schema::table('injectvf', function (Blueprint $table) {
            $table->index('tgl');
            $table->index('idtap');
            $table->index('iddenom');
        });

        // retursf — sering di-query by tgl
        Schema::table('retursf', function (Blueprint $table) {
            $table->index('tgl');
            $table->index('iddenom');
        });

        // returvfrusak — sering di-query by tgl
        Schema::table('returvfrusak', function (Blueprint $table) {
            $table->index('tgl');
            $table->index('iddenom');
        });

        // ============================================
        // 4. ADD PRIMARY KEY TO kodetap (IF MISSING)
        // ============================================
        $hasPK = DB::select("SHOW KEYS FROM kodetap WHERE Key_name = 'PRIMARY'");
        if (empty($hasPK)) {
            // Add auto-increment id as primary key
            if (!Schema::hasColumn('kodetap', 'id')) {
                Schema::table('kodetap', function (Blueprint $table) {
                    $table->id()->first();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert stock columns to signed int
        DB::statement('ALTER TABLE stockawaltap MODIFY COLUMN stock INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE stockawalsf MODIFY COLUMN stock INT NOT NULL DEFAULT 0');

        // Drop indexes
        Schema::table('injectvf', function (Blueprint $table) {
            $table->dropIndex(['tgl']);
            $table->dropIndex(['idtap']);
            $table->dropIndex(['iddenom']);
        });

        Schema::table('retursf', function (Blueprint $table) {
            $table->dropIndex(['tgl']);
            $table->dropIndex(['iddenom']);
        });

        Schema::table('returvfrusak', function (Blueprint $table) {
            $table->dropIndex(['tgl']);
            $table->dropIndex(['iddenom']);
        });
    }
};
