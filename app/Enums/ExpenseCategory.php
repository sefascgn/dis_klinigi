<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ExpenseCategory: string
{
    use HasOptions;

    case Salary = 'salary';
    case Rent = 'rent';
    case Lab = 'lab';
    case Supplies = 'supplies';
    case Utilities = 'utilities';
    case Tax = 'tax';
    case Equipment = 'equipment';
    case Marketing = 'marketing';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Salary => 'Personel maaşı',
            self::Rent => 'Kira',
            self::Lab => 'Laboratuvar',
            self::Supplies => 'Malzeme / sarf',
            self::Utilities => 'Faturalar (elektrik, su, internet)',
            self::Tax => 'Vergi / SGK',
            self::Equipment => 'Cihaz / bakım',
            self::Marketing => 'Reklam / pazarlama',
            self::Other => 'Diğer',
        };
    }
}
