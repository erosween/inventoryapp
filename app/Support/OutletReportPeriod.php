<?php
namespace App\Support;

use Carbon\Carbon;

final class OutletReportPeriod
{
    public static function isClosedMonthEnd(Carbon $cutoff): bool
    {
        return $cutoff->day === $cutoff->daysInMonth
            && $cutoff->toDateString() < Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString();
    }

    public static function previousEnd(Carbon $cutoff): Carbon
    {
        $previous = $cutoff->copy()->startOfMonth()->subMonth();
        return self::isClosedMonthEnd($cutoff)
            ? $previous->endOfMonth()
            : $previous->day(min($cutoff->day, $previous->daysInMonth))->endOfDay();
    }
}
