<?php

namespace App\Support;

final class InjectProductConfig
{
    public static function for(string $category): array
    {
        return match (strtoupper(trim($category))) {
            'V33' => [
                'label' => 'PV BYU SEGEL',
                'source_id' => 'V33',
                'destination_category' => 'BYU',
                'product_group' => 'PV',
            ],
            'SP6' => [
                'label' => 'PERDANA SEGEL SIMPATI',
                'source_id' => 'SP6',
                'destination_category' => 'SA',
                'product_group' => null,
            ],
            'SP7' => [
                'label' => 'PERDANA SEGEL BYU',
                'source_id' => 'SP7',
                'destination_category' => 'SA',
                'product_group' => null,
            ],
            default => [
                'label' => 'PV SEGEL',
                'source_id' => 'SEGEL',
                'destination_category' => 'SEGEL',
                'product_group' => null,
            ],
        };
    }
}
