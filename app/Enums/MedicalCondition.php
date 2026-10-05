<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Diş tedavisini etkileyebilecek sistemik durumlar (anamnez kontrol listesi).
 */
enum MedicalCondition: string
{
    use HasOptions;

    case Diabetes = 'diabetes';
    case Hypertension = 'hypertension';
    case HeartDisease = 'heart_disease';
    case BloodThinner = 'blood_thinner';
    case BleedingDisorder = 'bleeding_disorder';
    case Hepatitis = 'hepatitis';
    case Pregnancy = 'pregnancy';
    case Asthma = 'asthma';
    case Epilepsy = 'epilepsy';
    case Osteoporosis = 'osteoporosis';
    case Smoker = 'smoker';

    public function label(): string
    {
        return match ($this) {
            self::Diabetes => 'Diyabet',
            self::Hypertension => 'Hipertansiyon',
            self::HeartDisease => 'Kalp hastalığı',
            self::BloodThinner => 'Kan sulandırıcı kullanıyor',
            self::BleedingDisorder => 'Kanama bozukluğu',
            self::Hepatitis => 'Hepatit',
            self::Pregnancy => 'Hamilelik',
            self::Asthma => 'Astım',
            self::Epilepsy => 'Epilepsi',
            self::Osteoporosis => 'Osteoporoz (bifosfonat)',
            self::Smoker => 'Sigara kullanıyor',
        };
    }

    /**
     * Cerrahi işlem / anestezi öncesi mutlaka dikkat gerektirenler kırmızı gösterilir.
     */
    public function isCritical(): bool
    {
        return in_array($this, [
            self::HeartDisease, self::BloodThinner, self::BleedingDisorder,
            self::Pregnancy, self::Epilepsy, self::Osteoporosis,
        ], true);
    }
}
