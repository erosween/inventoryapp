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
                'destination_name_prefix' => null,
                'destination_name_excludes' => [],
                'excluded_source_ids' => ['V33', 'SEGEL', 'SP6', 'SP7'],
                'destination_name_exclude' => null,
            ],
            'SP6' => [
                'label' => 'PERDANA SEGEL SIMPATI',
                'source_id' => 'SP6',
                'destination_category' => 'SA',
                'product_group' => null,
                'destination_name_prefix' => null,
                'destination_name_excludes' => ['SA BYU%', 'PERDANA SEGEL BYU%'],
                'excluded_source_ids' => ['SP6', 'SP7'],
            ],
            'SP7' => [
                'label' => 'PERDANA SEGEL BYU',
                'source_id' => 'SP7',
                'destination_category' => 'SA',
                'product_group' => null,
                'destination_name_prefix' => 'SA BYU%',
                'destination_name_excludes' => [],
                'excluded_source_ids' => ['SP6', 'SP7'],
            ],
            default => [
                'label' => 'PV SEGEL',
                'source_id' => 'SEGEL',
                'destination_category' => 'SEGEL',
                'product_group' => null,
                'destination_name_prefix' => null,
                'destination_name_excludes' => [],
                'excluded_source_ids' => ['SEGEL', 'V33', 'SP6', 'SP7'],
            ],
        };
    }
}
