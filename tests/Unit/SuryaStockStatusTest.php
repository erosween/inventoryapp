<?php

namespace Tests\Unit;

use App\Support\SuryaStockStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SuryaStockStatusTest extends TestCase
{
    public static function coverageCases(): array
    {
        return [
            'empty stock' => [0, 100, 1000, 'Stock Kurang', 'low', 'KURANG 100 PCS'],
            'below one day' => [80, 100, 1000, 'Stock Kurang', 'low', 'KURANG 20 PCS'],
            'exactly one day' => [100, 100, 1000, 'Stock Aman', 'safe', 'CUKUP 1 HARI'],
            'partial day' => [150, 100, 1000, 'Stock Aman', 'safe', 'CUKUP 1,5 HARI'],
            'two days' => [200, 100, 1000, 'Stock Aman', 'safe', 'CUKUP 2 HARI'],
            'exactly buffer' => [300, 100, 1000, 'Stock Aman', 'safe', 'CUKUP 3 HARI'],
            'over buffer' => [400, 100, 1000, 'Stock Over', 'over', 'CUKUP 4 HARI · TAHAN PENAMBAHAN'],
            'barely over buffer' => [3001, 1000, 10000, 'Stock Over', 'over', 'CUKUP >3 HARI · TAHAN PENAMBAHAN'],
            'achieved with stock' => [200, 0, 1000, 'Stock Aman', 'safe', 'TARGET TERCAPAI · SISA STOK 200 PCS'],
            'achieved empty' => [0, 0, 1000, 'Stock Aman', 'safe', 'TARGET TERCAPAI · SISA STOK 0 PCS'],
            'unset target' => [200, 0, 0, 'Stock Aman', 'safe', 'TARGET BELUM DIATUR · SISA STOK 200 PCS'],
        ];
    }

    #[DataProvider('coverageCases')]
    public function test_status_explains_daily_gap_buffer_and_zero_daily_without_division_by_zero(
        int $stock, int $daily, int $target, string $status, string $class, string $detail
    ): void {
        $result = SuryaStockStatus::describe($stock, $daily, $target);
        $this->assertSame($status, $result['status']);
        $this->assertSame($class, $result['status_class']);
        $this->assertSame($detail, $result['status_detail']);
    }
}
