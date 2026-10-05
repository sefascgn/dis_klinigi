<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum UserRole: string
{
    use HasOptions;

    case Admin = 'admin';
    case Doctor = 'doctor';
    case Secretary = 'secretary';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Yönetici',
            self::Doctor => 'Doktor',
            self::Secretary => 'Sekreter',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'purple',
            self::Doctor => 'blue',
            self::Secretary => 'green',
        };
    }
}
