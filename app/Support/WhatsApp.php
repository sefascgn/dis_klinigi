<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Patient;

/**
 * Hazır mesajlı WhatsApp bağlantıları (wa.me). Ek servis veya API anahtarı gerektirmez;
 * mesaj, kullanıcının kendi WhatsApp'ında gönderilmeden önce görüntülenir.
 */
class WhatsApp
{
    /**
     * Türkiye numaralarını uluslararası biçime çevirir: 0532..., 532..., +90 532... → 90532...
     */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        $digits = match (true) {
            str_starts_with($digits, '90') && strlen($digits) === 12 => $digits,
            str_starts_with($digits, '0') && strlen($digits) === 11 => '9'.$digits,
            strlen($digits) === 10 => '90'.$digits,
            default => null,
        };

        return $digits;
    }

    public static function link(?string $phone, string $message): ?string
    {
        $number = self::normalize($phone);

        return $number ? 'https://wa.me/'.$number.'?text='.rawurlencode($message) : null;
    }

    public static function appointmentReminder(Appointment $appointment): ?string
    {
        $clinic = ClinicSetting::current();

        $message = sprintf(
            'Sayın %s, %s tarihinde saat %s için %s ile randevunuz bulunmaktadır. Gelemeyecekseniz lütfen bize bildiriniz. %s%s',
            $appointment->patient->full_name,
            $appointment->starts_at->translatedFormat('j F l'),
            $appointment->starts_at->format('H:i'),
            $appointment->doctor->full_name,
            $clinic->name,
            $clinic->phone ? ' · '.$clinic->phone : '',
        );

        return self::link($appointment->patient->phone, $message);
    }

    public static function recallReminder(Patient $patient): ?string
    {
        $clinic = ClinicSetting::current();

        $message = sprintf(
            'Sayın %s, düzenli diş kontrolünüzün zamanı geldi. Size uygun bir gün için randevu oluşturmak isteriz. %s%s',
            $patient->full_name,
            $clinic->name,
            $clinic->phone ? ' · '.$clinic->phone : '',
        );

        return self::link($patient->phone, $message);
    }
}
