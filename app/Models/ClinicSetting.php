<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Klinik bilgileri tek satırlık bir kayıtta tutulur.
 */
class ClinicSetting extends Model
{
    private const CACHE_KEY = 'clinic_settings';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'tax_office',
        'tax_number',
        'opening_time',
        'closing_time',
        'default_appointment_duration',
    ];

    protected function casts(): array
    {
        return [
            'default_appointment_duration' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $setting = static::query()->firstOrCreate([], ['name' => config('app.name')]);

            // Yeni oluşturulduysa veritabanı varsayılanlarını (saatler vb.) modele yükle.
            return $setting->wasRecentlyCreated ? $setting->refresh() : $setting;
        });
    }
}
