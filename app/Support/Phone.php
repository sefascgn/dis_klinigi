<?php

namespace App\Support;

/**
 * Ekranda gösterilecek telefon biçimi. Veritabanındaki değer değiştirilmez.
 */
class Phone
{
    /**
     * Türkiye numaraları "0532 123 45 67" biçiminde gösterilir; tanınmayan
     * numaralar (yurt dışı, eksik hane) girildiği gibi bırakılır.
     */
    public static function format(?string $phone): ?string
    {
        $normalized = WhatsApp::normalize($phone);

        if ($normalized === null) {
            return $phone;
        }

        // 90 + 10 hane → 0XXX XXX XX XX
        return preg_replace('/^90(\d{3})(\d{3})(\d{2})(\d{2})$/', '0$1 $2 $3 $4', $normalized);
    }
}
