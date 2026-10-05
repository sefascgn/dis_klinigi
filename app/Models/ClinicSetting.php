<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Klinik bilgileri tek satırlık bir kayıtta tutulur.
 */
class ClinicSetting extends Model
{
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
        'recall_months',
    ];

    protected function casts(): array
    {
        return [
            'default_appointment_duration' => 'integer',
            'recall_months' => 'integer',
        ];
    }

    /**
     * İstek başına bir kez okunur. Kalıcı cache kullanılmaz; böylece şema
     * değişikliklerinden sonra bayat kayıt riski olmaz.
     */
    public static function current(): self
    {
        return once(function () {
            $setting = static::query()->firstOrCreate([], ['name' => config('app.name')]);

            // Yeni oluşturulduysa veritabanı varsayılanlarını (saatler vb.) modele yükle.
            return $setting->wasRecentlyCreated ? $setting->refresh() : $setting;
        });
    }
}
