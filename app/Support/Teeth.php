<?php

namespace App\Support;

/**
 * FDI (iki haneli) diş numaralandırma sistemi.
 */
class Teeth
{
    /**
     * Kalıcı dişler, ağız şemasındaki görünüm sırasıyla (hasta karşıdan bakılıyormuş gibi).
     *
     * @var array<string, list<int>>
     */
    public const PERMANENT = [
        'upper' => [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28],
        'lower' => [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38],
    ];

    /**
     * @var array<string, list<int>>
     */
    public const DECIDUOUS = [
        'upper' => [55, 54, 53, 52, 51, 61, 62, 63, 64, 65],
        'lower' => [85, 84, 83, 82, 81, 71, 72, 73, 74, 75],
    ];

    /**
     * @return list<int>
     */
    public static function all(): array
    {
        return array_merge(...array_values(self::PERMANENT), ...array_values(self::DECIDUOUS));
    }

    /**
     * Select alanı için gruplanmış seçenekler.
     *
     * @return array<string, array<int, string>>
     */
    public static function groupedOptions(): array
    {
        $groups = [
            'Sağ üst (1. bölge)' => range(11, 18),
            'Sol üst (2. bölge)' => range(21, 28),
            'Sol alt (3. bölge)' => range(31, 38),
            'Sağ alt (4. bölge)' => range(41, 48),
            'Süt dişleri' => array_merge(range(51, 55), range(61, 65), range(71, 75), range(81, 85)),
        ];

        return array_map(
            fn (array $teeth) => array_combine($teeth, array_map(fn (int $t) => "Diş {$t}", $teeth)),
            $groups,
        );
    }
}
