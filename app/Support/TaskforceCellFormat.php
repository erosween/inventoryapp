<?php
namespace App\Support;

final class TaskforceCellFormat
{
    public static function display(string $header, mixed $value): string
    {
        $text = (string) $value;
        $name = self::normalizedHeader($header);
        if (self::isPercentageHeader($header, $name)) {
            return preg_match('/^[+-]?(?:\d+|\d{1,3}(?:\.\d{3})+),\d+\s*%?$/D', trim($text))
                ? strtr($text, '.,', ',.')
                : $text;
        }
        if (!self::isQuantityHeader($header)) return $text;
        // Uploaded reports can contain already formatted Indonesian quantities.
        if (preg_match('/^[+-]?\d{1,3}(?:\.\d{3})+(?:,\d+)?$/D', trim($text))) {
            return strtr($text, '.,', ',.');
        }
        if (preg_match('/^[+-]?\d+,\d+$/D', trim($text))
            && !preg_match('/^[+-]?\d{1,3},\d{3}$/D', trim($text))) {
            return str_replace(',', '.', $text);
        }
        if (!preg_match('/^(-?)(\d+)$/D', trim($text), $match)) return $text;
        $digits = ltrim($match[2], '0');
        $digits = $digits === '' ? '0' : $digits;
        return $match[1].preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $digits);
    }

    public static function forExport(string $header, mixed $value): mixed
    {
        $text=(string)$value;
        return self::isQuantityHeader($header)&&preg_match('/^-?\d{1,15}$/D',$text) ? (int)$text : $value;
    }

    public static function isQuantityHeader(string $header): bool
    {
        $name = self::normalizedHeader($header);
        // Identity, dates, and percentages are excluded from integer export formatting.
        if (preg_match('/^(id|no|nomor|kode|code|tanggal|tgl|date|phone|telepon|hp)/', $name)
            || self::isPercentageHeader($header, $name)) return false;
        return (bool) preg_match('/qty|quantity|jumlah|total|target|realisasi|capaian|trx|^(?:st)?(?:sa|pv)(?:fm1|m1|m|mtd|gapmom)?$|fm1|m1|mtd|gap|^sa$|^pv$|^bio|cvm|superseru|comsak|hotpromo|digital|^ss/', $name);
    }

    private static function normalizedHeader(string $header): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $header));
    }

    private static function isPercentageHeader(string $header, string $name): bool
    {
        return str_contains($header, '%') || str_contains($name, 'persen')
            || (str_contains($name, 'mom') && !str_contains($name, 'gap'));
    }
}
