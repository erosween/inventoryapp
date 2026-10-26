<?php

namespace App\Support;

class SuryaStockStatus
{
    public const BUFFER_DAYS = 3;

    /** Describe coverage against the remaining daily target, per product/validity. */
    public static function describe(int $stock, int $daily, int $target): array
    {
        if ($daily <= 0) {
            return [
                'status' => 'Stock Aman',
                'status_class' => 'safe',
                'status_detail' => ($target > 0 ? 'TARGET TERCAPAI' : 'TARGET BELUM DIATUR')
                    .' · SISA STOK '.number_format($stock).' PCS',
            ];
        }

        if ($stock < $daily) {
            return [
                'status' => 'Stock Kurang',
                'status_class' => 'low',
                'status_detail' => 'KURANG '.number_format($daily - $stock).' PCS',
            ];
        }

        $over = $stock > $daily * self::BUFFER_DAYS;
        $days = rtrim(rtrim(number_format($stock / $daily, 2, ',', '.'), '0'), ',');
        // Rounding must not make a quantity just above the buffer look within it.
        if ($over && $days === (string) self::BUFFER_DAYS) {
            $days = '>'.self::BUFFER_DAYS;
        }

        return [
            'status' => $over ? 'Stock Over' : 'Stock Aman',
            'status_class' => $over ? 'over' : 'safe',
            'status_detail' => 'CUKUP '.$days.' HARI'.($over ? ' · TAHAN PENAMBAHAN' : ''),
        ];
    }
}
