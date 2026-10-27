<?php

namespace Tests\Unit;

use App\Support\StockProduct;
use PHPUnit\Framework\TestCase;

class StockProductTest extends TestCase
{
    public function test_duplicate_sp7_does_not_double_count_or_enter_pv(): void
    {
        $denoms = collect([
            (object) ['iddenom'=>'SP7', 'denom'=>'PERDANA SEGEL BYU', 'group_name'=>'SEGEL', 'kategori_inject'=>'BYU'],
            (object) ['iddenom'=>'SP7', 'denom'=>'PERDANA SEGEL BYU', 'group_name'=>'SEGEL', 'kategori_inject'=>'SA'],
            (object) ['iddenom'=>'V33', 'denom'=>'BYU SEGEL', 'group_name'=>'SEGEL', 'kategori_inject'=>'BYU'],
        ]);
        $canonical = StockProduct::canonical($denoms);
        self::assertCount(2, $canonical);
        self::assertTrue(StockProduct::isSa($canonical->firstWhere('iddenom', 'SP7')));
        self::assertFalse(StockProduct::isSa($canonical->firstWhere('iddenom', 'V33')));
        self::assertTrue(StockProduct::isSealed($canonical->firstWhere('iddenom', 'SP7')));
        self::assertSame('SA', $canonical->firstWhere('iddenom', 'SP7')->kategori_inject);
        self::assertSame('BYU', $denoms->first()->kategori_inject);
    }
}
