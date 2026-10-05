<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TreatmentStatus: string
{
    use HasOptions;

    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planlandı',
            self::InProgress => 'Devam ediyor',
            self::Completed => 'Tamamlandı',
            self::Cancelled => 'İptal edildi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::InProgress => 'yellow',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }
}
