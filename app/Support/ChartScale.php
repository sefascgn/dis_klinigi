<?php

namespace App\Support;

/**
 * Grafik eksenleri için "yuvarlak" ölçek: 0 / 25.000 / 50.000 gibi okunaklı adımlar.
 */
class ChartScale
{
    /**
     * @return array{min: float, max: float, ticks: list<float>}
     */
    public static function make(float $min, float $max, int $tickCount = 4): array
    {
        $min = min(0.0, $min);
        $max = max(0.0, $max);

        if ($max - $min < 0.01) {
            return ['min' => 0.0, 'max' => 1.0, 'ticks' => [0.0]];
        }

        $step = self::niceStep(($max - $min) / $tickCount);
        $niceMin = floor($min / $step) * $step;
        $niceMax = ceil($max / $step) * $step;

        $ticks = [];
        for ($v = $niceMin; $v <= $niceMax + $step / 2; $v += $step) {
            $ticks[] = round($v, 2);
        }

        return ['min' => $niceMin, 'max' => $niceMax, 'ticks' => $ticks];
    }

    /**
     * Değerin ölçek içindeki yüzdesi (0 = alt, 100 = üst).
     */
    public static function percent(float $value, array $scale): float
    {
        return ($value - $scale['min']) / ($scale['max'] - $scale['min']) * 100;
    }

    private static function niceStep(float $raw): float
    {
        $magnitude = 10 ** floor(log10($raw));
        $fraction = $raw / $magnitude;

        $nice = match (true) {
            $fraction <= 1 => 1,
            $fraction <= 2 => 2,
            $fraction <= 2.5 => 2.5,
            $fraction <= 5 => 5,
            default => 10,
        };

        return $nice * $magnitude;
    }
}
