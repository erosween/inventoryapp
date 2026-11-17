<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Keep the first recorded active ownership row for each serial and outlet.
     * Older app flows could insert the same serial repeatedly on the same
     * receipt; retain those rows for audit, but move extras out of active state.
     */
    public function up(): void
    {
        $duplicateGroups = DB::table('surya_sa_consignment_numbers')
            ->whereIn('status', ['draft', 'consigned', 'approved'])
            ->select('serial_number', 'id_outlet')
            ->selectRaw('COUNT(*) AS active_count')
            ->groupBy('serial_number', 'id_outlet')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $rows = DB::table('surya_sa_consignment_numbers')
                ->where('serial_number', $group->serial_number)
                ->where('id_outlet', $group->id_outlet)
                ->whereIn('status', ['draft', 'consigned', 'approved'])
                ->orderBy('id')
                ->pluck('id');

            $duplicateIds = $rows->slice(1)->all();
            if ($duplicateIds !== []) {
                DB::table('surya_sa_consignment_numbers')
                    ->whereIn('id', $duplicateIds)
                    ->update([
                        'status' => 'migrated',
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Archived rows are retained as audit history; do not reactivate them.
    }
};
