<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class DenomGrouping
{
    public const DO_CODES = ['SEGEL', 'V33', 'SP6', 'SP7', 'SP1'];
    public const SOURCES = ['SEGEL', 'V33', 'SP6', 'SP7', 'V16'];
    public const FILTERS = ['pv_all', 'pv_reg', 'pv_byu', 'sa_all', 'sa_simpati', 'sa_byu'];

    public static function infer(object $row): array
    {
        $id = strtoupper(trim((string) $row->iddenom));
        $category = strtoupper(trim((string) ($row->kategori_inject ?? '')));
        $group = strtoupper(trim((string) ($row->group_name ?? '')));
        $legacy = strtoupper(trim((string) ($row->product_group ?? '')));
        $name = strtoupper(trim((string) ($row->denom ?? '')));
        $sa = str_starts_with($id, 'SP') || $category === 'SA' || $group === 'SA' || in_array($legacy, ['SA', 'SP'], true);
        if (in_array($id, ['SEGEL', 'V16', 'V33'], true)) $sa = false;
        $byu = in_array($id, ['SP7', 'V33'], true) || str_starts_with($name, 'SA BYU') || $category === 'BYU';
        $variant = $category === 'ROAMAX' ? 'ROAMAX' : ($byu ? 'BYU' : ($sa ? 'SIMPATI' : 'REGULER'));
        $variant = match ($id) {
            'SEGEL'=>'REGULER', 'V16'=>'ROAMAX', 'V33', 'SP7'=>'BYU', 'SP1', 'SP6'=>'SIMPATI', default=>$variant,
        };
        $sealed = $id !== 'SP1' && (in_array($id, self::SOURCES, true) || $group === 'SEGEL');
        $source = $sealed ? null : match ($variant) {
            'SIMPATI' => 'SP6', 'BYU' => $sa ? 'SP7' : 'V33',
            'ROAMAX' => 'V16', default => 'SEGEL',
        };
        return ['product' => $sa ? 'SA' : 'PV', 'product_variant' => $variant,
            'stock_form' => $sealed ? 'SEGEL' : 'INJECT', 'inject_source_id' => $source,
            'allow_do' => in_array($id, self::DO_CODES, true)];
    }

    public static function sourceFor(string $product, string $variant): string
    {
        return match ($product.'|'.$variant) {
            'PV|REGULER' => 'SEGEL', 'PV|BYU' => 'V33', 'PV|ROAMAX' => 'V16',
            'SA|SIMPATI' => 'SP6', 'SA|BYU' => 'SP7',
            default => throw ValidationException::withMessages(['product_variant' => 'Varian tidak sesuai dengan produk.']),
        };
    }

    public static function forMaster(string $id, array $input): array
    {
        if (in_array(strtoupper($id), [...self::SOURCES, 'SP1'], true)) {
            return self::infer((object) array_merge($input, ['iddenom' => strtoupper($id)]));
        }
        $product = $input['product'];
        $variant = $input['product_variant'];
        $source = self::sourceFor($product, $variant);
        $form = $input['stock_form'];
        if ($form === 'SEGEL') {
            throw ValidationException::withMessages(['stock_form' => 'Segel menggunakan kode sumber yang sudah tersedia.']);
        }
        return ['product'=>$product, 'product_variant'=>$variant, 'stock_form'=>$form,
            'inject_source_id'=>$source, 'allow_do'=>false];
    }

    public static function filterDenoms($query, string $filter, string $alias = 'denom')
    {
        $product = str_starts_with($filter, 'sa_') ? 'SA' : 'PV';
        $query->where($alias.'.product', $product);
        $variant = match ($filter) {
            'pv_reg'=>'REGULER', 'pv_byu', 'sa_byu'=>'BYU', 'sa_simpati'=>'SIMPATI', default=>null,
        };
        if ($variant) $query->where($alias.'.product_variant', $variant);
        return $query;
    }

    public static function filterTransactions($query, string $table, string $filter): void
    {
        $query->whereIn($table.'.iddenom', function ($q) use ($filter) {
            self::filterDenoms($q->select('grouping.iddenom')->from('denom as grouping'), $filter, 'grouping');
        });
    }

    public static function destinations($query, string $source)
    {
        if (!in_array($source, self::SOURCES, true)) {
            throw ValidationException::withMessages(['category'=>'Sumber segel tidak valid.']);
        }
        return $query->where('stock_form', 'INJECT')->where('inject_source_id', $source);
    }
}
