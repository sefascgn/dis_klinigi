<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2, ',', '.').' ₺';
    }

    /**
     * Grafik ekseni ve kartlar için kısa gösterim: 950 ₺, 12,5 B ₺, 1,2 Mn ₺.
     */
    public static function compact(float|int|string|null $amount): string
    {
        $value = (float) $amount;
        $abs = abs($value);

        return match (true) {
            $abs >= 1_000_000 => self::trim($value / 1_000_000).' Mn ₺',
            $abs >= 1_000 => self::trim($value / 1_000).' B ₺',
            default => number_format($value, 0, ',', '.').' ₺',
        };
    }

    private static function trim(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, ',', '.'), '0'), ',');
    }
}
