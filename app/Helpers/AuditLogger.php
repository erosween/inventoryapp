<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an action to the audit trail.
     *
     * @param string $action (INSERT, UPDATE, DELETE, APPROVE)
     * @param string $module (e.g. 'Stok Masuk SF')
     * @param string|int|null $record_id
     * @param array|null $old_values
     * @param array|null $new_values
     */
    public static function log($action, $module, $record_id = null, $old_values = null, $new_values = null)
    {
        try {
            DB::table('logs')->insert([
                'username'   => auth()->user() ? auth()->user()->username : 'SYSTEM',
                'action'     => strtoupper($action),
                'module'     => $module,
                'record_id'  => $record_id,
                'old_values' => $old_values ? json_encode($old_values) : null,
                'new_values' => $new_values ? json_encode($new_values) : null,
                'ip_address' => Request::ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Kita log ke file kalau database-nya error biar nggak nge-block proses utama
            \Log::error("Audit Log Error: " . $e->getMessage());
        }
    }
}
