<?php

namespace App\Support;

final class IntertapTransferKey
{
    public static function make($date, $sender, $recipient, $denom, $qty, $serial): string
    {
        return hash('sha256', implode('|', [
            substr((string) $date, 0, 10),
            self::text($sender),
            self::text($recipient),
            self::text($denom),
            (int) $qty,
            self::serial($serial),
        ]));
    }

    public static function text($value): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $value)));
    }

    public static function serial($value): string
    {
        return mb_strtoupper(preg_replace('/[^\pL\pN]+/u', '', (string) $value));
    }
}
