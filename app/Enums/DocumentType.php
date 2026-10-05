<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentType: string
{
    use HasOptions;

    case Xray = 'xray';
    case Photo = 'photo';
    case Consent = 'consent';
    case Report = 'report';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Xray => 'Röntgen',
            self::Photo => 'Fotoğraf',
            self::Consent => 'Onam formu',
            self::Report => 'Rapor / Tahlil',
            self::Other => 'Diğer',
        };
    }
}
