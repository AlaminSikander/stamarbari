<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Digits in international format for wa.me links. Local Bangladeshi mobile numbers
     * (01XXXXXXXXX) get the 880 country code; anything else must already include one.
     */
    public static function forWhatsApp(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (preg_match('/^01\d{9}$/', $digits)) {
            $digits = '88'.$digits;
        }

        return strlen($digits) >= 8 && strlen($digits) <= 15 ? $digits : null;
    }

    public static function whatsAppUrl(?string $phone, ?string $text = null): ?string
    {
        $digits = self::forWhatsApp($phone);
        if (! $digits) {
            return null;
        }

        return 'https://wa.me/'.$digits.($text !== null && $text !== '' ? '?text='.rawurlencode($text) : '');
    }

    public static function telUrl(?string $phone): ?string
    {
        $clean = preg_replace('/[^\d+]/', '', (string) $phone);

        return $clean !== '' ? 'tel:'.$clean : null;
    }
}
