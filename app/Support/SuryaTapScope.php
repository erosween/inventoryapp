<?php

namespace App\Support;

use App\Models\User;

class SuryaTapScope
{
    /** Null means unrestricted; an empty list means no permitted TAP. */
    public static function forUser(?User $user, ?string $sessionTap): ?array
    {
        $username = strtolower(trim((string) $user?->username));
        if (in_array($username, ['admin_super', 'gm_cluster', 'branch_dumai'], true)) {
            return null;
        }

        $clusters = [
            'CLUSTER_DUMAI' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            'CLUSTER_ROHIL' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ];
        if (isset($clusters[strtoupper($username)])) {
            return $clusters[strtoupper($username)];
        }

        if (str_starts_with($username, 'asmen_')) {
            $suffix = strtoupper(substr($username, 6));
            $tap = match ($suffix) {
                'BAGAN_BATU', 'BAGANBATU' => 'BAGAN BATU',
                'BAGAN_SIAPI_API', 'BAGANSIAPI_API', 'BAGANSIAPIAPI' => 'BAGAN SIAPI-API',
                'UJUNG_TANJUNG', 'UJUNGTANJUNG' => 'UJUNG TANJUNG',
                'ROHIL', 'ROKAN_HILIR' => 'ROHIL',
                default => str_replace('_', ' ', $suffix),
            };

            return $tap !== '' ? [$tap] : [];
        }

        $level = strtoupper(trim((string) $user?->level));
        $sessionTap = strtoupper(trim((string) $sessionTap));
        if ($username === 'gm' || $level === 'GM') {
            return $clusters[$sessionTap] ?? null;
        }
        if (in_array($level, ['TAP', 'ADMIN TAP', 'ADMIN_TAP'], true) && $sessionTap !== '') {
            return [$sessionTap];
        }

        return null;
    }
}
