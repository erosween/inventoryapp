<?php
namespace Tests\Unit;

use App\Support\OutletReportPeriod;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class OutletReportPeriodTest extends TestCase
{
    public function test_closed_month_ends_and_mid_month_boundaries(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-05', 'Asia/Jakarta'));
        try {
            foreach ([
                ['2026-09-30','2026-08-31',true],
                ['2026-02-28','2026-01-31',true],
                ['2024-02-29','2024-01-31',true],
                ['2026-01-31','2025-12-31',true],
                ['2026-09-29','2026-08-29',false],
                ['2026-11-05','2026-10-05',false],
            ] as [$date,$expected,$closed]) {
                $cutoff=Carbon::parse($date);
                $this->assertSame($expected,OutletReportPeriod::previousEnd($cutoff)->toDateString());
                $this->assertSame($closed,OutletReportPeriod::isClosedMonthEnd($cutoff));
                $this->assertSame($date,$cutoff->toDateString());
            }
        } finally { Carbon::setTestNow(); }
    }
}
