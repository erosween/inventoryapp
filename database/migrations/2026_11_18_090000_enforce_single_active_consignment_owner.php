<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateSerials = DB::table('surya_sa_consignment_numbers')
            ->whereIn('status', ['draft', 'consigned', 'approved'])
            ->select('serial_number')
            ->selectRaw('COUNT(*) AS active_count')
            ->groupBy('serial_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateSerials as $duplicate) {
            $activeIds = DB::table('surya_sa_consignment_numbers')
                ->where('serial_number', $duplicate->serial_number)
                ->whereIn('status', ['draft', 'consigned', 'approved'])
                ->orderByDesc('id')
                ->pluck('id');

            $supersededIds = $activeIds->slice(1)->all();
            if ($supersededIds !== []) {
                DB::table('surya_sa_consignment_numbers')
                    ->whereIn('id', $supersededIds)
                    ->update([
                        'status' => 'migrated',
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Archived duplicates remain as audit history and are not reactivated.
    }
};
